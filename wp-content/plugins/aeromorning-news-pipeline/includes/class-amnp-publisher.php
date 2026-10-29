<?php
/**
 * Pending news → WordPress posts. Port of extract_news news:publish
 * (PublishNewsCommand + WordPressPostingService::publishToWordPress), done
 * in-process instead of three REST round-trips per article:
 *   1. featured image imported into the right site's media library,
 *   2. post created with categories, tags and thumbnail in one insert,
 *   3. Yoast meta + score + indexable through the existing mu-plugin
 *      function (aeromorning_update_yoast_meta), called directly,
 *   4. FR/EN hreflang partner meta once both sides exist.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AMNP_Publisher
{
    /** @var callable|null */
    private $out;

    public function __construct(?callable $out = null)
    {
        $this->out = $out;
    }

    private function say(string $line): void
    {
        if ($this->out) {
            ($this->out)($line);
        }
    }

    /**
     * @param string|null $status 'publish' or 'draft' (defaults to AMNP_PUBLISH_STATUS)
     * @param int[]|null  $onlyIds publish only these news records
     * @param string      $source  'cli' (cron) or 'admin'
     */
    public function run(?string $status = null, ?array $onlyIds = null, string $source = 'cli'): array
    {
        global $wpdb;

        $status = $status ?: AMNP_Config::str('PUBLISH_STATUS');
        $status = in_array($status, ['publish', 'draft'], true) ? $status : 'draft';

        $author = get_user_by('login', AMNP_Config::str('AUTHOR_LOGIN'));
        if (!$author) {
            throw new \RuntimeException('Author user "' . AMNP_Config::str('AUTHOR_LOGIN') . '" not found (AMNP_AUTHOR_LOGIN).');
        }

        $runId = AMNP_Logger::startRun('publish_pending', $source, ['status' => $status, 'ids' => $onlyIds]);
        $table = AMNP_DB::news();
        $previousUserId = get_current_user_id();

        try {
            // A crashed run leaves items in "syncing": put them back in the queue.
            $reset = $wpdb->query($wpdb->prepare("UPDATE {$table} SET status = %d WHERE status = %d", AMNP_DB::STATUS_PENDING, AMNP_DB::STATUS_SYNCING));
            if ($reset) {
                $this->say("Reset {$reset} stuck syncing item(s) back to pending.");
            }

            if ($onlyIds !== null) {
                $onlyIds = array_values(array_filter(array_map('intval', $onlyIds)));
                $pending = $onlyIds
                    ? $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE status = %d AND id IN (" . implode(',', array_fill(0, count($onlyIds), '%d')) . ') ORDER BY id', AMNP_DB::STATUS_PENDING, ...$onlyIds), ARRAY_A)
                    : [];
            } else {
                $pending = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE status = %d ORDER BY id", AMNP_DB::STATUS_PENDING), ARRAY_A);
            }

            if (!$pending) {
                AMNP_Logger::finishRun($runId, AMNP_Logger::STATUS_SUCCESS, ['published' => 0, 'failed' => 0], 'No pending news to publish');
                return ['success' => [], 'failed' => []];
            }

            // Same capabilities and kses filtering as the REST calls made as this user.
            wp_set_current_user($author->ID);
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $results = ['success' => [], 'failed' => []];
            $publishedUrls = [];
            $touchedImages = [];

            foreach ($pending as $news) {
                $id = (int) $news['id'];
                $this->say("Processing news #{$id} [{$news['lang']}]: {$news['title']}");
                AMNP_DB::updateNews($id, ['status' => AMNP_DB::STATUS_SYNCING]);

                try {
                    $result = $this->publishOne($news, $status, (int) $author->ID);
                } catch (\Throwable $e) {
                    $result = ['success' => false, 'error' => $e->getMessage()];
                }

                if ($result['success']) {
                    AMNP_DB::updateNews($id, ['status' => AMNP_DB::STATUS_SYNCED]);
                    $this->linkHreflangWithSiblingIfReady($id);
                    $this->maybePostToLinkedIn($id, $status);

                    $results['success'][] = ['id' => $id, 'lang' => $news['lang'], 'title' => $news['title'], 'wp_post_id' => $result['wp_post_id']];
                    $publishedUrls[] = $result['wp_link'];
                    $this->say("  ✓ " . ($status === 'publish' ? 'Publié' : 'Brouillon créé') . " — WP post ID: {$result['wp_post_id']} — {$result['wp_link']}");
                } else {
                    AMNP_DB::updateNews($id, ['status' => AMNP_DB::STATUS_PENDING]);
                    $results['failed'][] = ['id' => $id, 'lang' => $news['lang'], 'title' => $news['title'], 'error' => $result['error'] ?? 'Unknown error'];
                    AMNP_Logger::error("Publishing news #{$id} failed: " . ($result['error'] ?? 'Unknown error'));
                    $this->say("  ✗ Échec: " . ($result['error'] ?? 'Unknown error'));
                }

                if (!empty($news['image_path'])) {
                    $touchedImages[$news['image_path']] = true;
                }
            }

            $this->cleanupQueuedImages(array_keys($touchedImages));

            // Drafts are not public: nothing to purge.
            if ($status === 'publish' && $publishedUrls) {
                $purge = (new AMNP_Cloudflare())->purgeAfterPublish($publishedUrls);
                $this->say('Cloudflare: ' . wp_json_encode($purge));
            }

            $remaining = $wpdb->get_results($wpdb->prepare("SELECT id, lang, title FROM {$table} WHERE status = %d", AMNP_DB::STATUS_PENDING), ARRAY_A);
            AMNP_Notifier::publishingSummary($results, $remaining ?: []);

            AMNP_Logger::finishRun(
                $runId,
                $results['failed'] ? AMNP_Logger::STATUS_PARTIAL : AMNP_Logger::STATUS_SUCCESS,
                ['published' => count($results['success']), 'failed' => count($results['failed']), 'failed_items' => $results['failed']],
                'Publishing completed.'
            );

            return $results;
        } catch (\Throwable $e) {
            AMNP_Logger::failRun($runId, $e, ['note' => 'Fatal error in publish']);
            throw $e;
        } finally {
            // Admin-triggered runs must hand the session back to the logged-in user.
            wp_set_current_user($previousUserId);
        }
    }

    private function publishOne(array $news, string $status, int $authorId): array
    {
        // Already done when the news is stored; repeated for rows queued before
        // AMNP_Content existed (idempotent).
        $news['content'] = AMNP_Content::cleanHtml((string) $news['content']);
        foreach (['title', 'metadescription', 'focuskeyphrase'] as $field) {
            $news[$field] = AMNP_Content::cleanText((string) $news[$field]);
        }

        switch_to_blog(AMNP_Config::blogForLang($news['lang']));

        try {
            // 1. Featured image (default AeroMorning visual when the email had none).
            $attachmentId = $this->importImage($news);

            // 2. Post with taxonomies and thumbnail in a single insert, so publish
            //    hooks (LiteSpeed purge, sitemaps...) see the complete post.
            $postarr = [
                'post_type'     => 'post',
                'post_status'   => $status,
                'post_author'   => $authorId,
                'post_title'    => (string) $news['title'],
                'post_content'  => (string) $news['content'],
                'post_excerpt'  => (string) $news['metadescription'],
                'post_category' => $this->idList($news['categories']),
                'tax_input'     => ['post_tag' => $this->idList($news['tags'])],
            ];
            if ($attachmentId) {
                $postarr['meta_input'] = ['_thumbnail_id' => $attachmentId];
            }

            $postId = wp_insert_post(wp_slash($postarr), true);
            if (is_wp_error($postId)) {
                if ($attachmentId && $attachmentId !== (int) get_option('amnp_default_image_id')) {
                    wp_delete_attachment($attachmentId, true);
                }
                return ['success' => false, 'error' => 'Post creation failed: ' . $postId->get_error_message()];
            }

            // Attach article images to their post; the shared default visual stays unattached.
            if ($attachmentId && $attachmentId !== (int) get_option('amnp_default_image_id')) {
                wp_update_post(['ID' => $attachmentId, 'post_parent' => $postId]);
            }

            $wpLink = (string) get_permalink($postId);
            AMNP_DB::updateNews((int) $news['id'], ['wp_post_id' => $postId, 'wp_link' => $wpLink]);

            // 3. Yoast meta, computed score and indexable rebuild.
            $yoast = $this->updateYoastMeta($postId, $news);
            if (!$yoast['success']) {
                AMNP_Logger::warning("Yoast meta update failed for post {$postId}", $yoast);
            }

            return ['success' => true, 'wp_post_id' => $postId, 'wp_link' => $wpLink];
        } finally {
            restore_current_blog();
        }
    }

    /** Import the queued image into the current site's media library. */
    private function importImage(array $news): ?int
    {
        $source = (string) ($news['image_path'] ?? '');
        $isDefault = $source === '' || !is_file($source);

        // The default visual is imported once per site and reused, instead of
        // adding a duplicate to the media library for every image-less email.
        if ($isDefault) {
            $existing = (int) get_option('amnp_default_image_id');
            if ($existing && get_post_type($existing) === 'attachment') {
                return $existing;
            }
            $source = AMNP_DIR . '/assets/visuel-news-aeromorning.jpg';
        }

        // media_handle_sideload() moves the file: give it a copy, the queued
        // original is shared by the FR and EN posts.
        $tmp = wp_tempnam(basename($source));
        if (!@copy($source, $tmp)) {
            AMNP_Logger::warning("Could not copy image for import: {$source}");
            return null;
        }

        $attachmentId = media_handle_sideload(['name' => basename($source), 'tmp_name' => $tmp], 0);
        if (is_wp_error($attachmentId)) {
            @unlink($tmp);
            AMNP_Logger::warning('Image import failed: ' . $attachmentId->get_error_message(), ['source' => basename($source)]);
            return null;
        }

        if ($isDefault) {
            update_option('amnp_default_image_id', (int) $attachmentId, false);
        }

        return (int) $attachmentId;
    }

    private function updateYoastMeta(int $postId, array $news): array
    {
        $payload = [
            'metadesc'     => (string) $news['metadescription'],
            'focuskw'      => (string) $news['focuskeyphrase'],
            'post_title'   => (string) $news['title'],
            'post_excerpt' => (string) $news['metadescription'],
            'post_content' => (string) $news['content'],
            'reindex'      => true,
            'touch_post'   => true,
        ];

        if (!function_exists('aeromorning_update_yoast_meta')) {
            update_post_meta($postId, '_yoast_wpseo_metadesc', sanitize_text_field($payload['metadesc']));
            update_post_meta($postId, '_yoast_wpseo_focuskw', sanitize_text_field($payload['focuskw']));
            return ['success' => true, 'note' => 'mu-plugin aeromorning-api.php not loaded: meta only'];
        }

        $request = new WP_REST_Request('POST', "/aeromorning/v1/yoast/{$postId}");
        $request->set_param('id', $postId);
        $request->set_header('content-type', 'application/json');
        $request->set_body(wp_json_encode($payload));

        $response = aeromorning_update_yoast_meta($request);
        $data = $response->get_data();

        return ['success' => $response->get_status() < 300, 'data' => $data];
    }

    /**
     * EN articles whose source email mentions LinkedIn get a LinkedIn post.
     * A LinkedIn failure never blocks the WordPress publication. Drafts are
     * only ever posted in dry-run mode (nothing is sent).
     */
    private function maybePostToLinkedIn(int $newsId, string $status): void
    {
        $news = AMNP_DB::getNews($newsId);
        if (!$news || $news['lang'] !== 'EN' || !$news['linkedin'] || $news['linkedin_posted']) {
            return;
        }
        $mode = AMNP_LinkedIn::mode();
        if ($mode === 'off' || ($status !== 'publish' && $mode !== 'dry-run')) {
            return;
        }

        try {
            $result = (new AMNP_LinkedIn())->postNews($news);
            if ($mode === 'live' && $result['success']) {
                AMNP_DB::updateNews($newsId, ['linkedin_posted' => 1]);
            }
            $this->say('  LinkedIn (' . $mode . '): ' . ($result['success'] ? 'ok, ' . ($result['annotations'] ?? 0) . ' mention(s)' : ($result['reason'] ?? 'skipped')));
        } catch (\Throwable $e) {
            AMNP_Logger::warning("News #{$newsId} LinkedIn post failed: " . $e->getMessage());
            $this->say('  LinkedIn: échec — ' . $e->getMessage());
        }
    }

    /** Point each translation at the other once both sides are synced. */
    private function linkHreflangWithSiblingIfReady(int $newsId): void
    {
        global $wpdb;
        $table = AMNP_DB::news();
        $news = AMNP_DB::getNews($newsId);
        if (!$news || $news['hreflang_linked'] || !$news['wp_post_id'] || !$news['wp_link'] || $news['email_message_id'] === '') {
            return;
        }

        $sibling = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE email_message_id = %s AND lang <> %s AND status = %d AND wp_post_id IS NOT NULL AND wp_link IS NOT NULL LIMIT 1",
            $news['email_message_id'], $news['lang'], AMNP_DB::STATUS_SYNCED
        ), ARRAY_A);
        if (!$sibling) {
            return;
        }

        $metaKey = defined('AEROMORNING_HREFLANG_META_KEY') ? AEROMORNING_HREFLANG_META_KEY : '_aeromorning_hreflang_partner_url';

        foreach ([[$news, $sibling], [$sibling, $news]] as [$self, $partner]) {
            switch_to_blog(AMNP_Config::blogForLang($self['lang']));
            update_post_meta((int) $self['wp_post_id'], $metaKey, esc_url_raw($partner['wp_link']));
            restore_current_blog();
        }

        AMNP_DB::updateNews((int) $news['id'], ['hreflang_linked' => 1]);
        AMNP_DB::updateNews((int) $sibling['id'], ['hreflang_linked' => 1]);
        AMNP_Logger::info("Hreflang linked news #{$news['id']} [{$news['lang']}] <-> #{$sibling['id']} [{$sibling['lang']}]");
    }

    /** Delete queued images once every news using them is published. */
    private function cleanupQueuedImages(array $paths): void
    {
        global $wpdb;
        $table = AMNP_DB::news();
        foreach ($paths as $path) {
            $stillNeeded = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE image_path = %s AND status <> %d", $path, AMNP_DB::STATUS_SYNCED));
            if ($stillNeeded === 0 && is_file($path) && str_starts_with(wp_normalize_path($path), wp_normalize_path(AMNP_Image::queueDir()))) {
                @unlink($path);
            }
        }
    }

    private function idList(?string $csv): array
    {
        return array_values(array_filter(array_map('intval', explode(',', (string) $csv))));
    }
}
