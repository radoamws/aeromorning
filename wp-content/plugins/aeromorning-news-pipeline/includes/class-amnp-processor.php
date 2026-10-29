<?php
/**
 * Email → news records (FR + EN, status pending). Port of extract_news
 * NewsController::processEmails() / processSingleEmail() and the helpers
 * they use. Categories and tags are read straight from WordPress instead
 * of the synced t_categories_* / t_tags_* copies.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AMNP_Processor
{
    public const RESULT_PROCESSED = 'processed';
    public const RESULT_FAILED    = 'failed';
    public const RESULT_SKIPPED   = 'skipped';

    private AMNP_Mailbox $mailbox;
    private AMNP_OpenAI $openai;
    private AMNP_Image $image;

    /** @var array<string, array> per-language term lists, loaded once per run */
    private array $termCache = [];

    /** @var callable|null progress output (WP-CLI) */
    private $out;

    public function __construct(?callable $out = null)
    {
        $this->mailbox = new AMNP_Mailbox();
        $this->openai  = new AMNP_OpenAI();
        $this->image   = new AMNP_Image();
        $this->out     = $out;
    }

    private function say(string $line): void
    {
        if ($this->out) {
            ($this->out)($line);
        }
    }

    /**
     * @param int|null $latest test mode: process the N most recent emails
     *                         (seen or not) instead of the UNSEEN ones
     */
    public function run(?int $latest = null): array
    {
        $runId = AMNP_Logger::startRun('emails_process', 'cli', [
            'imap_criteria' => $latest ? "LATEST {$latest}" : AMNP_Config::str('IMAP_SEARCH_CRITERIA'),
            'mark_read'     => AMNP_Config::bool('IMAP_MARK_READ'),
        ]);

        try {
            $mailIds = $latest ? $this->mailbox->getLatestEmailIds($latest) : $this->mailbox->getUnreadEmailIds();

            if (empty($mailIds)) {
                AMNP_Logger::finishRun($runId, AMNP_Logger::STATUS_SUCCESS, ['processed' => 0, 'failed' => 0, 'skipped' => 0], 'No emails to process for current IMAP criteria');
                return ['processed' => 0, 'failed' => 0, 'skipped' => 0];
            }

            $counts  = ['processed' => 0, 'failed' => 0, 'skipped' => 0];
            $summary = ['processed' => [], 'failed' => [], 'skipped' => []];

            foreach ($mailIds as $mailId) {
                $this->say("→ Fetching email ID {$mailId}...");
                try {
                    $mail = $this->mailbox->getMailById((int) $mailId);
                    $this->say('   Subject: ' . mb_substr(trim((string) ($mail->subject ?? '')), 0, 120));

                    $result = $this->processSingleEmail($mail, $summary);
                    $counts[$result]++;
                } catch (\Throwable $e) {
                    AMNP_Logger::error("Error processing email {$mailId}: " . $e->getMessage());
                    $counts['failed']++;
                    $summary['failed'][] = ['subject' => 'unknown', 'from' => '', 'error' => $e->getMessage()];
                } finally {
                    $this->mailbox->cleanupAttachments();
                }
                $this->say("   Status: processed={$counts['processed']}, skipped={$counts['skipped']}, failed={$counts['failed']}");
            }

            AMNP_Notifier::processingSummary($summary);

            AMNP_Logger::finishRun(
                $runId,
                $counts['failed'] > 0 ? AMNP_Logger::STATUS_PARTIAL : AMNP_Logger::STATUS_SUCCESS,
                $counts + ['failed_items' => $summary['failed'], 'skipped_items' => $summary['skipped']],
                'Email processing completed'
            );

            return $counts;
        } catch (\Throwable $e) {
            AMNP_Logger::error('Error in email processing: ' . $e->getMessage());
            AMNP_Logger::failRun($runId, $e, ['note' => 'Fatal error in email processing']);
            throw $e;
        }
    }

    /**
     * Ignored emails an editor asked to publish anyway (admin "Forcer la
     * publication"). Queued instead of run in the web request: it re-runs
     * the whole AI extraction (1–3 min). The resulting news go through the
     * normal publish cron.
     */
    public function runForceQueue(): array
    {
        global $wpdb;
        $table = AMNP_DB::ignored();
        $queued = $wpdb->get_results("SELECT * FROM {$table} WHERE force_requested_at IS NOT NULL AND force_published_at IS NULL ORDER BY id", ARRAY_A);
        $done = ['created' => 0, 'failed' => 0];

        foreach ($queued as $ignored) {
            $this->say("→ Forced publication of ignored email #{$ignored['id']}: " . mb_substr((string) $ignored['subject'], 0, 100));
            $emailContent = $ignored['raw_email_json'] ? json_decode((string) $ignored['raw_email_json'], true) : null;
            if (!is_array($emailContent)) {
                // Raw copy purged or never stored: best effort with the excerpt.
                $emailContent = [
                    'subject'    => (string) $ignored['subject'],
                    'from'       => (string) $ignored['sender'],
                    'message_id' => (string) $ignored['message_id'],
                    'html_body'  => '',
                    'text_body'  => (string) $ignored['excerpt'],
                ];
            }
            if (empty($emailContent['message_id'])) {
                $emailContent['message_id'] = 'force:' . $ignored['id'];
            }

            $result = $this->buildNewsFromEmailContent($emailContent);
            if ($result['success']) {
                $wpdb->update($table, ['force_published_at' => AMNP_DB::now()], ['id' => (int) $ignored['id']]);
                $done['created'] += count($result['created_ids']);
                $this->say('   Created news: ' . implode(', ', $result['created_ids']));
            } else {
                // Leave it queued only if it can still succeed later.
                $wpdb->update($table, ['force_requested_at' => null], ['id' => (int) $ignored['id']]);
                $done['failed']++;
                AMNP_Logger::warning("Forced publication of ignored email #{$ignored['id']} failed: {$result['error']}");
                $this->say("   Failed: {$result['error']}");
            }
        }

        return $done;
    }

    private function buildNewsFromEmailContent(array $emailContent): array
    {
        $contentBrut = (string) wp_json_encode($emailContent, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $messageId = (string) ($emailContent['message_id'] ?? '');
        $subject = (string) ($emailContent['subject'] ?? '');

        $existingLangs = AMNP_DB::langsForMessage($messageId);
        if (in_array('FR', $existingLangs, true) && in_array('EN', $existingLangs, true)) {
            return ['success' => false, 'error' => 'News already exist for both languages', 'created_ids' => []];
        }

        $htmlBody = (string) ($emailContent['html_body'] ?? '');
        $normalizedBodyContent = $this->normalizeEmailBody(trim($htmlBody) !== '' ? $htmlBody : (string) ($emailContent['text_body'] ?? ''));
        $languageDetectionText = $this->normalizePlainTextContent(strip_tags($normalizedBodyContent));

        // Image: HTML candidates only (attachments are not kept for ignored emails).
        $imagePath = null;
        $imageCandidates = array_values(array_filter(
            $this->mailbox->extractImageCandidatesFromHtml($normalizedBodyContent),
            fn ($url) => !$this->isForbiddenSignatureImageUrl((string) $url)
        ));
        if ($imageCandidates) {
            $found = $this->openai->chooseRelevantImageUrl($languageDetectionText !== '' ? $languageDetectionText : $normalizedBodyContent, $imageCandidates);
            if ($found && !$this->isForbiddenSignatureImageUrl($found) && $this->image->isValidImageUrl($found)) {
                $imagePath = $this->image->downloadAndOptimizeImage($found, $subject);
            }
        }

        $payload = $this->openai->extractWordPressNewsJson($emailContent);
        if (!is_array($payload)) {
            $this->discardImage($imagePath);
            return ['success' => false, 'error' => 'OpenAI did not return a valid JSON payload', 'created_ids' => []];
        }

        $created = [];
        foreach (['FR', 'EN'] as $lang) {
            if (($payload[$lang] ?? '') !== '' && !in_array($lang, $existingLangs, true)
                && $this->createNewsFromPayload($lang, $payload, $subject, $messageId, $imagePath, $contentBrut)) {
                global $wpdb;
                $created[] = (int) $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . AMNP_DB::news() . ' WHERE email_message_id = %s AND lang = %s ORDER BY id DESC LIMIT 1', $messageId, $lang));
            }
        }

        if (!$created) {
            $this->discardImage($imagePath);
            return ['success' => false, 'error' => 'No news records created from the email content', 'created_ids' => []];
        }
        return ['success' => true, 'created_ids' => $created];
    }

    private function processSingleEmail($mail, array &$summary): string
    {
        $emailContent = ['subject' => 'no-subject', 'from' => '', 'message_id' => ''];

        try {
            $emailContent = $this->mailbox->extractEmailContent($mail);
            $contentBrut = (string) wp_json_encode($emailContent, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $subject = (string) ($emailContent['subject'] ?? '');
            $messageId = (string) ($emailContent['message_id'] ?? '');

            $existingLangs = AMNP_DB::langsForMessage($messageId);

            // Both languages already stored: skip before spending OpenAI calls.
            if ($messageId !== '' && in_array('FR', $existingLangs, true) && in_array('EN', $existingLangs, true)) {
                $summary['skipped'][] = ['subject' => $subject, 'from' => $emailContent['from'] ?? '', 'reason' => 'already_processed'];
                $this->mailbox->markAsRead($mail->id);
                return self::RESULT_SKIPPED;
            }

            $bodyContent = trim((string) $emailContent['html_body']) !== '' ? $emailContent['html_body'] : $emailContent['text_body'];
            $normalizedBodyContent = $this->normalizeEmailBody((string) $bodyContent);
            $languageDetectionText = $this->normalizePlainTextContent(strip_tags($normalizedBodyContent));
            $relevanceText = $languageDetectionText !== '' ? $languageDetectionText : $normalizedBodyContent;

            if (!$this->openai->isAviationRelevant($relevanceText)) {
                if (!$this->storeIgnoredEmail($emailContent, $relevanceText, 'not_relevant')) {
                    $summary['failed'][] = ['subject' => $subject, 'from' => $emailContent['from'] ?? '', 'error' => 'Ignored email could not be persisted to DB; email left unread for retry'];
                    return self::RESULT_FAILED;
                }
                $summary['skipped'][] = ['subject' => $subject, 'from' => $emailContent['from'] ?? '', 'reason' => 'not_relevant'];
                AMNP_Logger::info('Skipping non-aviation email: ' . $subject);
                $this->mailbox->markAsRead($mail->id);
                return self::RESULT_SKIPPED;
            }

            // Featured image: best attachment first, then AI pick among HTML images.
            $imagePath = null;
            $hasAttachments = $this->mailbox->hasAttachments($mail);
            if ($hasAttachments) {
                $inlineCidFilenames = $this->extractInlineCidFilenames((string) ($emailContent['html_body'] ?? ''));
                $bestAttachment = $this->selectBestImageAttachment($mail->getAttachments(), $inlineCidFilenames);
                // php-imap exposes filePath through __get() with no __isset(): empty()/isset()
                // always report it missing, only ?? (which falls back to __get) reads it.
                $attachmentPath = $bestAttachment ? ($bestAttachment->filePath ?? null) : null;
                if ($attachmentPath) {
                    $imagePath = $this->image->processAttachmentImage($attachmentPath, $subject);
                }
            }

            if ($imagePath === null) {
                $imageCandidates = array_values(array_filter(
                    $this->mailbox->extractImageCandidatesFromHtml($normalizedBodyContent),
                    fn ($url) => !$this->isForbiddenSignatureImageUrl((string) $url)
                ));
                if ($imageCandidates) {
                    $foundImageUrl = $this->openai->chooseRelevantImageUrl($relevanceText, $imageCandidates);
                    if ($foundImageUrl && !$this->isForbiddenSignatureImageUrl($foundImageUrl) && $this->image->isValidImageUrl($foundImageUrl)) {
                        $imagePath = $this->image->downloadAndOptimizeImage($foundImageUrl, $subject);
                    }
                }
            }

            AMNP_Logger::info("Email subject: {$subject}", ['has_attachment' => $hasAttachments, 'image' => $imagePath ? basename($imagePath) : null]);

            $newsPayload = $this->openai->extractWordPressNewsJson($emailContent);
            if (!is_array($newsPayload)) {
                $summary['failed'][] = ['subject' => $subject, 'from' => $emailContent['from'] ?? '', 'error' => 'OpenAI did not return a valid WordPress JSON payload (check OpenAI quota/billing and AMNP_OPENAI_API_KEY)'];
                $this->discardImage($imagePath);
                return self::RESULT_FAILED;
            }

            $wantsFr = ($newsPayload['FR'] ?? '') !== '' && !in_array('FR', $existingLangs, true);
            $wantsEn = ($newsPayload['EN'] ?? '') !== '' && !in_array('EN', $existingLangs, true);

            $createdFr = $wantsFr && $this->createNewsFromPayload('FR', $newsPayload, $subject, $messageId, $imagePath, $contentBrut);
            $createdEn = $wantsEn && $this->createNewsFromPayload('EN', $newsPayload, $subject, $messageId, $imagePath, $contentBrut);
            $createdAny = $createdFr || $createdEn;

            if (!$createdAny) {
                $this->discardImage($imagePath);
            }

            if (!$createdAny && !empty($existingLangs)) {
                $summary['skipped'][] = ['subject' => $subject, 'from' => $emailContent['from'] ?? '', 'reason' => 'already_processed'];
                $this->mailbox->markAsRead($mail->id);
                return self::RESULT_SKIPPED;
            }

            if (!$createdAny) {
                $summary['failed'][] = ['subject' => $subject, 'from' => $emailContent['from'] ?? '', 'error' => 'No news generated from WordPress prompt payload'];
                return self::RESULT_FAILED;
            }

            // Only mark read once every intended language was persisted.
            if (($wantsFr && !$createdFr) || ($wantsEn && !$createdEn)) {
                $summary['failed'][] = ['subject' => $subject, 'from' => $emailContent['from'] ?? '', 'error' => 'Partial insert: at least one language failed; email left unread for retry'];
                return self::RESULT_FAILED;
            }

            $this->mailbox->markAsRead($mail->id);
            $summary['processed'][] = ['subject' => $subject, 'from' => $emailContent['from'] ?? ''];
            return self::RESULT_PROCESSED;
        } catch (\Throwable $e) {
            AMNP_Logger::error('Error processing single email: ' . $e->getMessage());
            $summary['failed'][] = ['subject' => $emailContent['subject'] ?? 'no-subject', 'from' => $emailContent['from'] ?? '', 'error' => $e->getMessage()];
            return self::RESULT_FAILED;
        }
    }

    private function createNewsFromPayload(string $lang, array $payload, string $subject, string $messageId, ?string $imagePath, string $contentBrut): bool
    {
        try {
            $title = is_string($payload["shorttitle{$lang}"] ?? null) ? AMNP_Content::cleanText($payload["shorttitle{$lang}"]) : '';
            if ($title === '') {
                $title = is_string($payload["title{$lang}"] ?? null) ? AMNP_Content::cleanText($payload["title{$lang}"]) : '';
            }
            if ($title === '') {
                $title = AMNP_Content::cleanText($subject);
            }

            $content = is_string($payload[$lang] ?? null) ? AMNP_Content::cleanHtml($payload[$lang]) : '';
            if ($content === '') {
                AMNP_Logger::warning("Empty {$lang} content from WordPress prompt for email: {$messageId}");
                return false;
            }

            // Categories and tags are computed from the final HTML content.
            $categories = $this->termsForClassification($lang, 'category');
            $categoriesString = (string) $this->openai->classifyCategories($content, $categories, $lang);

            $tags = $this->termsForClassification($lang, 'post_tag');
            $tagsString = (string) $this->openai->classifyTags($content, $tags, $lang);
            $tagsString = $this->reorderSelectedTags($tagsString, $tags, $categories, $categoriesString, $content, $title);

            $id = AMNP_DB::insertNews([
                'lang'             => $lang,
                'title'            => $title,
                'content'          => $content,
                'content_brut'     => $contentBrut,
                'metadescription'  => is_string($payload["metadescription{$lang}"] ?? null) ? AMNP_Content::cleanText($payload["metadescription{$lang}"]) : '',
                'focuskeyphrase'   => is_string($payload["focuskeyphrase{$lang}"] ?? null) ? AMNP_Content::cleanText($payload["focuskeyphrase{$lang}"]) : '',
                'categories'       => $categoriesString,
                'tags'             => $tagsString,
                'image_path'       => $imagePath,
                'status'           => AMNP_DB::STATUS_PENDING,
                'email_message_id' => $messageId,
                // Source email mentions LinkedIn → candidate for the EN LinkedIn post.
                'linkedin'         => stripos(trim($contentBrut), 'linkedin') !== false ? 1 : 0,
                'linkedin_posted'  => 0,
            ]);

            if (!$id) {
                global $wpdb;
                AMNP_Logger::error("Error inserting {$lang} news: " . $wpdb->last_error);
                return false;
            }

            AMNP_Logger::info("{$lang} news created successfully for email: {$messageId}", ['news_id' => $id]);
            return true;
        } catch (\Throwable $e) {
            AMNP_Logger::error("Error processing {$lang} news from WordPress payload: " . $e->getMessage());
            return false;
        }
    }

    /** All terms of the site matching $lang, in the shape the prompts expect. */
    private function termsForClassification(string $lang, string $taxonomy): array
    {
        $key = "{$lang}:{$taxonomy}";
        if (isset($this->termCache[$key])) {
            return $this->termCache[$key];
        }

        switch_to_blog(AMNP_Config::blogForLang($lang));
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'id=>name', 'orderby' => 'term_id']);
        restore_current_blog();

        $nameField = $taxonomy === 'category' ? 'categ_name' : 'tag_name';
        $list = [];
        if (!is_wp_error($terms)) {
            foreach ($terms as $id => $name) {
                $list[] = ['wp_id' => (int) $id, $nameField => html_entity_decode((string) $name, ENT_QUOTES, 'UTF-8')];
            }
        }

        return $this->termCache[$key] = $list;
    }

    private function storeIgnoredEmail(array $emailContent, string $excerpt, string $reason): bool
    {
        global $wpdb;
        $raw = $emailContent;
        unset($raw['attachments']);

        $ok = $wpdb->insert(AMNP_DB::ignored(), [
            'message_id'     => (string) ($emailContent['message_id'] ?? ''),
            'subject'        => (string) ($emailContent['subject'] ?? ''),
            'sender'         => (string) ($emailContent['from'] ?? ''),
            'reason'         => $reason,
            'excerpt'        => mb_substr(trim($excerpt), 0, 5000),
            'raw_email_json' => wp_json_encode($raw, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: null,
            'processed_at'   => AMNP_DB::now(),
            'created_at'     => AMNP_DB::now(),
        ]);

        if (!$ok) {
            AMNP_Logger::warning('Unable to persist ignored email: ' . $wpdb->last_error);
        }
        return (bool) $ok;
    }

    private function discardImage(?string $imagePath): void
    {
        if ($imagePath && is_file($imagePath)) {
            @unlink($imagePath);
        }
    }

    // ── Text helpers (verbatim ports from NewsController) ──────────────────

    private function normalizeEmailBody(string $bodyContent): string
    {
        $content = trim($bodyContent);
        if ($content === '') {
            return '';
        }

        if (strpos($content, '<') === false) {
            $plain = $this->normalizePlainTextContent($content);
            return $plain === '' ? '' : $this->convertPlainTextToHtmlFragment($plain);
        }

        $clean = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $content) ?? $content;
        $clean = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $clean) ?? $clean;
        $clean = preg_replace('/<head\b[^>]*>.*?<\/head>/is', ' ', $clean) ?? $clean;
        $clean = preg_replace('/<!--.*?-->/s', ' ', $clean) ?? $clean;
        $clean = preg_replace('/<(meta|link|xml|o:p)\b[^>]*>.*?<\/\1>/is', ' ', $clean) ?? $clean;
        $clean = preg_replace('/<(meta|link)\b[^>]*\/?>/is', ' ', $clean) ?? $clean;

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $clean, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = '';
        $bodyNode = $dom->getElementsByTagName('body')->item(0);
        if ($bodyNode) {
            foreach ($bodyNode->childNodes as $child) {
                $body .= $dom->saveHTML($child);
            }
        }
        if ($body === '') {
            $body = $dom->saveHTML() ?: $clean;
        }

        // Keep the original structure; only drop empty paragraphs.
        $body = preg_replace('/<p>\s*<\/p>/i', '', $body) ?? $body;

        return trim($body);
    }

    private function convertPlainTextToHtmlFragment(string $plain): string
    {
        $html = '';
        foreach (preg_split('/\n{2,}/', $plain) ?: [] as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }

            $listType = null;
            $buffer = [];
            $flushParagraph = static function () use (&$html, &$buffer): void {
                if ($buffer) {
                    $html .= '<p>' . implode('<br>', $buffer) . '</p>';
                    $buffer = [];
                }
            };

            foreach (preg_split('/\n+/', $paragraph) ?: [] as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $wanted = null;
                if (preg_match('/^(?:[-*•])\s+(.+)$/u', $line, $matches)) {
                    $wanted = 'ul';
                } elseif (preg_match('/^\d+[.)]\s+(.+)$/u', $line, $matches)) {
                    $wanted = 'ol';
                }

                if ($wanted !== null) {
                    $flushParagraph();
                    if ($listType !== $wanted) {
                        if ($listType !== null) {
                            $html .= "</{$listType}>";
                        }
                        $html .= "<{$wanted}>";
                        $listType = $wanted;
                    }
                    $html .= '<li>' . htmlspecialchars(trim($matches[1]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
                    continue;
                }

                if ($listType !== null) {
                    $html .= "</{$listType}>";
                    $listType = null;
                }
                $buffer[] = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }

            $flushParagraph();
            if ($listType !== null) {
                $html .= "</{$listType}>";
            }
        }
        return $html;
    }

    private function normalizePlainTextContent(string $content): string
    {
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $content = preg_replace('/\x{00a0}/u', ' ', $content) ?? $content;
        $content = preg_replace('/[ \t]+/', ' ', $content) ?? $content;
        $content = preg_replace('/\n{3,}/', "\n\n", $content) ?? $content;

        $filtered = [];
        foreach (preg_split('/\n/', strip_tags($content)) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '' && !$this->isBoilerplateLine($line)) {
                $filtered[] = $line;
            }
        }
        return trim(implode("\n", $filtered));
    }

    private function isBoilerplateLine(string $line): bool
    {
        $patterns = [
            '/^top of form$/i', '/^bottom of form$/i', '/^posted by\s*:/i', '/^related articles$/i',
            '/^be the first to comment/i', '/^leave a comment$/i', '/^additional links$/i', '/^topics\s*:/i',
            '/^rechercher\s*:/i', '/^copyright\s+/i', '/^dernieres news$/i', '/^derni[eè]res news$/iu',
            '/^mots cl[eé]$/iu', '/^[éè]v[eè]nements [àa] venir$/iu', '/^flash news$/i', '/^facebook$/i',
            '/^twitter$/i', '/^linkedin$/i', '/^youtube$/i', '/^niveau de confidentialite/i',
            '/^rado\s+rakotoarivelo/i', '/^a\s*:\s*route royale/i', '/^w\s*:\s*/i', '/^photo jointe/i',
            '/^texte uk et f/i', '/^aeromorning version/i', '/^________________________________/i',
            '/^from\s*:/i', '/^sent\s*:/i', '/^to\s*:/i', '/^subject\s*:/i', '/^cc\s*:/i', '/^de\s*:/i',
            '/^envoye\s*:/i', '/^objet\s*:/i', '/unsubscribe/i', '/view this email/i', '/www\./i', '/@/i',
            '/^tel\s*:/i', '/^phone\s*:/i', '/business wire/i', '/prnewswire/i', '/referent/i',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }
        return false;
    }

    private function reorderSelectedTags(?string $tagIds, array $allTags, array $allCategories, ?string $categoryIds, string $contentHtml, string $title): string
    {
        $selectedIds = array_values(array_filter(explode(',', (string) $tagIds), static fn ($id) => $id !== ''));
        if (!$selectedIds) {
            return '';
        }

        $tagMap = [];
        foreach ($allTags as $tag) {
            $tagMap[(string) $tag['wp_id']] = $tag;
        }

        $selectedCategoryIds = array_values(array_filter(explode(',', (string) $categoryIds), static fn ($id) => $id !== ''));
        $selectedCategoryNames = [];
        foreach ($allCategories as $category) {
            if (in_array((string) $category['wp_id'], $selectedCategoryIds, true)) {
                $selectedCategoryNames[] = $this->normalizeSearchText((string) $category['categ_name']);
            }
        }

        $articleText = $this->normalizeSearchText($title . ' ' . strip_tags($contentHtml));
        $ordered = [];
        $seenTagNames = [];

        foreach ($selectedIds as $index => $tagId) {
            if (!isset($tagMap[$tagId])) {
                continue;
            }
            $normalizedTag = $this->normalizeSearchText((string) $tagMap[$tagId]['tag_name']);
            $canonicalTag = $this->canonicalizeTagName($normalizedTag);
            if ($canonicalTag === '' || isset($seenTagNames[$canonicalTag])) {
                continue;
            }

            // 0 = matches a selected category, 1 = location, 2 = organization, 3 = other.
            $priority = 3;
            foreach ($selectedCategoryNames as $categoryName) {
                if ($categoryName !== '' && ($normalizedTag === $categoryName || str_contains($normalizedTag, $categoryName) || str_contains($categoryName, $normalizedTag))) {
                    $priority = 0;
                    break;
                }
            }
            if ($priority > 0 && $this->looksLikeLocationTag($normalizedTag, $articleText)) {
                $priority = 1;
            }
            if ($priority > 1 && $this->looksLikeOrganizationTag($normalizedTag, $articleText)) {
                $priority = 2;
            }

            $ordered[] = ['id' => $tagId, 'priority' => $priority, 'position' => $index];
            $seenTagNames[$canonicalTag] = true;
        }

        usort($ordered, static fn (array $l, array $r): int => [$l['priority'], $l['position']] <=> [$r['priority'], $r['position']]);

        return implode(',', array_map(static fn (array $item) => $item['id'], $ordered));
    }

    private function canonicalizeTagName(string $tagName): string
    {
        $tagName = trim($tagName);
        if ($tagName === '') {
            return '';
        }
        $tagName = preg_replace('/\b(news|actualites|actualite)\b/iu', '', $tagName) ?? $tagName;
        $tagName = preg_replace('/\s+/', ' ', trim($tagName)) ?? trim($tagName);
        $tagName = preg_replace('/s\b/u', '', $tagName) ?? $tagName;
        return trim($tagName);
    }

    private function normalizeSearchText(string $text): string
    {
        $text = mb_strtolower(trim(strip_tags($text)), 'UTF-8');
        $text = preg_replace('/[^\pL\pN]+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    private function looksLikeLocationTag(string $tagName, string $articleText): bool
    {
        if ($tagName === '' || !str_contains(' ' . $articleText . ' ', ' ' . $tagName . ' ')) {
            return false;
        }
        if (preg_match('/\b(airport|aeroport|aéroport|city|ville|country|pays|region|région|hub)\b/u', $tagName) === 1) {
            return true;
        }
        return preg_match('/\b(in|at|from|to|near|via|en|a|au|aux|dans|depuis|vers)\s+' . preg_quote($tagName, '/') . '\b/u', $articleText) === 1;
    }

    private function looksLikeOrganizationTag(string $tagName, string $articleText): bool
    {
        if ($tagName === '' || !str_contains(' ' . $articleText . ' ', ' ' . $tagName . ' ')) {
            return false;
        }
        return preg_match('/\b(airlines?|airways?|airbus|boeing|embraer|atr|faa|easa|iata|nasa|group|groupe|company|compagnie|agency|agence|corporation|corp|inc|ltd|sa|sas|plc)\b/u', $tagName) === 1;
    }

    private function isForbiddenSignatureImageUrl(string $url): bool
    {
        $url = strtolower($url);
        return str_contains($url, 'cid:') || str_contains($url, 'amws') || str_contains($url, 'amltd');
    }

    private function extractInlineCidFilenames(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }
        preg_match_all('/\bcid:([^"\'\s>]+)/i', $html, $matches);
        $filenames = [];
        foreach ($matches[1] ?? [] as $value) {
            $value = trim(explode('@', (string) $value, 2)[0]);
            if ($value !== '') {
                $filenames[strtolower($value)] = true;
            }
        }
        return array_keys($filenames);
    }

    private function selectBestImageAttachment(array $attachments, array $inlineCidFilenames = []): ?object
    {
        $bestAttachment = null;
        $bestScore = -1;
        $inlineCidLookup = array_fill_keys(array_map('strtolower', $inlineCidFilenames), true);

        foreach ($attachments as $attachment) {
            $mimeType = strtolower((string) ($attachment->mimeType ?? $attachment->mime ?? ''));
            $filePath = $attachment->filePath ?? null;
            if (!$filePath || !str_contains($mimeType, 'image')) {
                continue;
            }

            $name = strtolower((string) ($attachment->name ?? basename((string) $filePath)));

            // Signature/inline images are referenced as cid:... in the HTML.
            if ($name !== '' && isset($inlineCidLookup[$name])) {
                continue;
            }
            if (str_contains($name, 'amws') || str_contains($name, 'amltd')) {
                continue;
            }

            $score = (int) ($attachment->sizeInBytes ?? @filesize($filePath) ?: 0);
            if (preg_match('/logo|icon|signature|linkedin|facebook|twitter|instagram|header|footer/', $name)) {
                $score -= 500000;
            }
            if (preg_match('/amws|endless|possibilities|constellation/', $name)) {
                $score -= 500000;
            }
            if (preg_match('/blob|photo|image|cover|hero|artemis|volaris|viva/i', $name)) {
                $score += 200000;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestAttachment = $attachment;
            }
        }

        return $bestAttachment;
    }
}
