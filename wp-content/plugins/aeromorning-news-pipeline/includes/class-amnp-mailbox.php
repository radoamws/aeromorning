<?php
/**
 * IMAP access to news@aeromorning.com. Port of extract_news EmailService
 * (same php-imap library, same connection fallbacks, same encoding repair
 * and image-candidate filtering).
 */

if (!defined('ABSPATH')) {
    exit;
}

use PhpImap\Exceptions\ConnectionException;
use PhpImap\IncomingMail;
use PhpImap\Mailbox;

final class AMNP_Mailbox
{
    private ?Mailbox $mailbox = null;
    private ?string $lastMailboxInitError = null;
    private string $attachmentsDir;

    public function __construct()
    {
        $this->attachmentsDir = trailingslashit(get_temp_dir()) . 'amnp-attachments';
    }

    private function initializeMailbox(): void
    {
        try {
            $this->lastMailboxInitError = null;

            if (!extension_loaded('imap')) {
                throw new \RuntimeException('PHP IMAP extension is not enabled.');
            }
            if (!$this->hasValidConfig()) {
                throw new \RuntimeException('IMAP configuration is incomplete. Check AMNP_IMAP_HOST, AMNP_IMAP_USERNAME and AMNP_IMAP_PASSWORD.');
            }

            wp_mkdir_p($this->attachmentsDir);

            $connectionErrors = [];
            foreach ($this->buildMailboxPathsToTry() as $imapPath) {
                try {
                    $mailbox = new Mailbox(
                        $imapPath,
                        AMNP_Config::str('IMAP_USERNAME'),
                        AMNP_Config::str('IMAP_PASSWORD'),
                        $this->attachmentsDir,
                        'UTF-8'
                    );

                    $timeoutSec = AMNP_Config::int('IMAP_TIMEOUT_SEC');
                    if ($timeoutSec > 0) {
                        $mailbox->setTimeouts($timeoutSec);
                    }
                    $mailbox->setConnectionRetry(AMNP_Config::int('IMAP_CONNECTION_RETRY'));
                    $mailbox->setConnectionRetryDelay(0);

                    // Open the stream now so TLS/SSL failures are caught here.
                    $mailbox->getImapStream(true);

                    $this->mailbox = $mailbox;
                    AMNP_Logger::info('IMAP connection established.', ['imap_path' => $this->redactMailboxPath($imapPath)]);
                    return;
                } catch (\Throwable $e) {
                    $msg = $this->normalizeImapExceptionMessage($e);
                    $connectionErrors[] = $msg;
                    AMNP_Logger::warning('IMAP connection attempt failed.', ['imap_path' => $this->redactMailboxPath($imapPath), 'error' => $msg]);
                }
            }

            $summary = $connectionErrors ? implode(' | ', array_unique($connectionErrors)) : 'Unknown IMAP error';
            throw new \RuntimeException('IMAP connection failed. ' . $summary);
        } catch (\Throwable $e) {
            $this->lastMailboxInitError = $this->normalizeImapExceptionMessage($e);
            AMNP_Logger::error('IMAP connection error: ' . $this->lastMailboxInitError);
            $this->mailbox = null;
        }
    }

    private function requireMailbox(): Mailbox
    {
        if (!extension_loaded('imap')) {
            throw new \RuntimeException('PHP IMAP extension is not enabled.');
        }
        if (!$this->mailbox) {
            $this->initializeMailbox();
        }
        if (!$this->mailbox) {
            $suffix = $this->lastMailboxInitError ? (' ' . $this->lastMailboxInitError) : '';
            throw new \RuntimeException('IMAP mailbox is unavailable.' . $suffix);
        }
        return $this->mailbox;
    }

    private function hasValidConfig(): bool
    {
        $host = AMNP_Config::str('IMAP_HOST');
        $username = AMNP_Config::str('IMAP_USERNAME');
        $password = AMNP_Config::str('IMAP_PASSWORD');
        return $host !== '' && $username !== '' && $password !== '';
    }

    private function buildMailboxPathsToTry(): array
    {
        $host = AMNP_Config::str('IMAP_HOST');
        $port = AMNP_Config::str('IMAP_PORT');
        $encryption = strtolower(AMNP_Config::str('IMAP_ENCRYPTION'));
        $folder = AMNP_Config::str('IMAP_FOLDER');
        $validateCert = AMNP_Config::bool('IMAP_VALIDATE_CERT');

        $paths = [];
        $paths[] = $this->buildMailboxPathFor($host, $port, $encryption, $validateCert, $folder);

        // Common fix on hosts with bad/self-signed certs.
        if ($validateCert) {
            $paths[] = $this->buildMailboxPathFor($host, $port, $encryption, false, $folder);
        }

        // Server expects STARTTLS on 143 instead of implicit SSL on 993.
        if ($encryption === 'ssl' && $port === '993') {
            $paths[] = $this->buildMailboxPathFor($host, '143', 'tls', $validateCert, $folder);
            if ($validateCert) {
                $paths[] = $this->buildMailboxPathFor($host, '143', 'tls', false, $folder);
            }
        }

        // Configured TLS on 143 but server is implicit SSL on 993.
        if ($encryption === 'tls' && $port === '143') {
            $paths[] = $this->buildMailboxPathFor($host, '993', 'ssl', $validateCert, $folder);
            if ($validateCert) {
                $paths[] = $this->buildMailboxPathFor($host, '993', 'ssl', false, $folder);
            }
        }

        if (AMNP_Config::bool('IMAP_ALLOW_NOTLS_FALLBACK')) {
            $paths[] = $this->buildMailboxPathFor($host, $port, 'notls', $validateCert, $folder);
            $paths[] = $this->buildMailboxPathFor($host, '143', 'notls', $validateCert, $folder);
        }

        return array_values(array_unique($paths));
    }

    private function buildMailboxPathFor(string $host, string $port, string $encryption, bool $validateCert, string $folder): string
    {
        $flags = ['/imap'];
        if ($encryption === 'ssl') {
            $flags[] = '/ssl';
        } elseif ($encryption === 'tls') {
            $flags[] = '/tls';
        } elseif ($encryption === 'notls' || $encryption === 'none') {
            $flags[] = '/notls';
        }
        if (!$validateCert) {
            $flags[] = '/novalidate-cert';
        }
        $folder = $folder !== '' ? $folder : 'INBOX';
        return '{' . $host . ':' . $port . implode('', $flags) . '}' . $folder;
    }

    private function redactMailboxPath(string $imapPath): string
    {
        if (preg_match('/^(\{[^}]+\})/i', $imapPath, $m) === 1) {
            return $m[1] . '<folder>';
        }
        return '<imap_path>';
    }

    private function normalizeImapExceptionMessage(\Throwable $e): string
    {
        if ($e instanceof ConnectionException) {
            $first = $e->getErrors('first');
            return is_string($first) ? $first : (string) $e->getMessage();
        }

        $msgTrim = trim((string) $e->getMessage());
        if ($msgTrim !== '' && str_starts_with($msgTrim, '[')) {
            $decoded = json_decode($msgTrim, true);
            if (is_array($decoded) && isset($decoded[0]) && is_string($decoded[0])) {
                return $decoded[0];
            }
        }

        return $msgTrim !== '' ? $msgTrim : get_class($e);
    }

    /** Email IDs matching the configured criteria (UNSEEN in production). */
    public function getUnreadEmailIds(): array
    {
        try {
            $criteria = strtoupper(AMNP_Config::str('IMAP_SEARCH_CRITERIA'));
            $ids = $this->requireMailbox()->searchMailbox($criteria);
            if (!$ids) {
                AMNP_Logger::info("No emails found for IMAP criteria: {$criteria}");
                return [];
            }
            return $ids;
        } catch (\Throwable $e) {
            $msg = $this->normalizeImapExceptionMessage($e);
            AMNP_Logger::error('Error fetching unread emails: ' . $msg);
            throw new \RuntimeException($msg, 0, $e);
        }
    }

    /** The N most recent emails regardless of their seen flag (test mode). */
    public function getLatestEmailIds(int $count): array
    {
        $ids = $this->requireMailbox()->searchMailbox('ALL');
        if (!$ids) {
            return [];
        }
        sort($ids, SORT_NUMERIC);
        return array_slice($ids, -max(1, $count));
    }

    /** Fetch a mail without marking it as seen. */
    public function getMailById(int $mailId): IncomingMail
    {
        try {
            return $this->requireMailbox()->getMail($mailId, false);
        } catch (\Throwable $e) {
            $msg = $this->normalizeImapExceptionMessage($e);
            AMNP_Logger::warning("Error fetching email ID {$mailId}: " . $msg);
            throw new \RuntimeException($msg, 0, $e);
        }
    }

    public function extractEmailContent(IncomingMail $mail): array
    {
        $content = [
            'subject'     => $this->repairEncodingArtifacts((string) ($mail->subject ?? '')),
            'html_body'   => $this->repairEncodingArtifacts((string) ($mail->textHtml ?? '')),
            'text_body'   => $this->repairEncodingArtifacts((string) ($mail->textPlain ?? '')),
            'attachments' => [],
            'message_id'  => $mail->messageId ?? '',
            'from'        => $mail->fromAddress ?? '',
        ];

        foreach ($mail->getAttachments() as $attachment) {
            // filePath is a php-imap magic property (__get, no __isset): read it with ??.
            $path = $attachment->filePath ?? '';
            $filename = $attachment->name ?? '';
            if ($filename === '' && $path) {
                $filename = basename((string) $path);
            }
            $content['attachments'][] = [
                'filename' => $filename,
                'mime'     => $attachment->mimeType ?? ($attachment->mime ?? ''),
                'path'     => $path,
            ];
        }

        return $content;
    }

    private function repairEncodingArtifacts(string $value): string
    {
        if ($value === '') {
            return '';
        }

        // Ensure valid UTF-8 before running Unicode regexes.
        if (function_exists('mb_check_encoding') && !mb_check_encoding($value, 'UTF-8')) {
            $converted = @mb_convert_encoding($value, 'UTF-8', 'UTF-8,Windows-1252,ISO-8859-1');
            if (is_string($converted) && $converted !== '' && mb_check_encoding($converted, 'UTF-8')) {
                $value = $converted;
            }
        }

        // Some sources include ASCII control separators (e.g. \x13) between fields.
        $value = str_replace("\x13", ' - ', $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $value) ?? $value;

        // A: 3-byte UTF-8 sequences left as 6 hex chars ("de28099impulsions").
        static $utf8Hex6Map = [
            'e28093' => "\u{2013}", 'e28094' => "\u{2014}", 'e28095' => "\u{2015}",
            'e28098' => "\u{2018}", 'e28099' => "\u{2019}", 'e2809a' => "\u{201A}",
            'e2809c' => "\u{201C}", 'e2809d' => "\u{201D}", 'e280a6' => "\u{2026}",
            'e280b2' => "\u{2032}",
        ];
        foreach ($utf8Hex6Map as $hex => $char) {
            $value = str_ireplace($hex, $char, $value);
        }

        // B: same sequences with spaces between bytes.
        static $utf8Hex6SpacedMap = [
            'e2 80 93' => "\u{2013}", 'E2 80 93' => "\u{2013}",
            'e2 80 94' => "\u{2014}", 'E2 80 94' => "\u{2014}",
            'e2 80 98' => "\u{2018}", 'E2 80 98' => "\u{2018}",
            'e2 80 99' => "\u{2019}", 'E2 80 99' => "\u{2019}",
            'e2 80 9c' => "\u{201C}", 'E2 80 9C' => "\u{201C}",
            'e2 80 9d' => "\u{201D}", 'E2 80 9D' => "\u{201D}",
            'e2 80 a6' => "\u{2026}", 'E2 80 A6' => "\u{2026}",
        ];
        $value = str_replace(array_keys($utf8Hex6SpacedMap), array_values($utf8Hex6SpacedMap), $value);

        // C: 2-byte UTF-8 sequences (4 hex chars) for French accents.
        static $utf8Hex4Map = [
            'c3a0' => 'à', 'c3a7' => 'ç', 'c3a8' => 'è', 'c3a9' => 'é',
            'c3aa' => 'ê', 'c3ab' => 'ë', 'c3ae' => 'î', 'c3af' => 'ï',
            'c3b4' => 'ô', 'c3b9' => 'ù', 'c3ba' => 'ú', 'c3bb' => 'û',
            'c3bc' => 'ü', 'c380' => 'À', 'c387' => 'Ç', 'c388' => 'È',
            'c389' => 'É', 'c38a' => 'Ê', 'c38e' => 'Î', 'c394' => 'Ô',
            'c399' => 'Ù',
        ];
        foreach ($utf8Hex4Map as $hex => $char) {
            $value = str_ireplace($hex, $char, $value);
        }

        // D: quoted-printable bytes stripped of '=' (d=E9 -> de9), only when
        // adjacent to letters and only for pairs containing a digit — all-letter
        // pairs ('ca', 'ea'...) corrupt normal words ("American", "réseau").
        $map = [
            'e0' => 'à', 'e2' => 'â', 'e7' => 'ç', 'e8' => 'è', 'e9' => 'é',
            'f4' => 'ô', 'f9' => 'ù', 'a0' => ' ',
            'c0' => 'À', 'c2' => 'Â', 'c7' => 'Ç', 'c8' => 'È', 'c9' => 'É',
            'd4' => 'Ô', 'd9' => 'Ù',
            'b9' => "\u{2019}",
        ];
        $value = preg_replace_callback(
            '/(?<=\p{L})([0-9a-fA-F]{2})(?=(?:\p{L}|\p{Z}|\p{P}|$))/u',
            static function (array $m) use ($map): string {
                return $map[strtolower($m[1])] ?? $m[1];
            },
            $value
        ) ?? $value;

        $value = preg_replace('/[ \t]{2,}/u', ' ', $value) ?? $value;

        return trim($value);
    }

    public function hasAttachments(IncomingMail $mail): bool
    {
        return !empty($mail->getAttachments());
    }

    public function markAsRead(int $mailId): bool
    {
        if (!AMNP_Config::bool('IMAP_MARK_READ')) {
            return true;
        }
        try {
            $this->requireMailbox()->setFlag([$mailId], '\\Seen');
            return true;
        } catch (\Throwable $e) {
            AMNP_Logger::error("Error marking email {$mailId} as read: " . $e->getMessage());
            return false;
        }
    }

    /** Remove the attachments php-imap saved to disk for the current email. */
    public function cleanupAttachments(): void
    {
        foreach (glob(trailingslashit($this->attachmentsDir) . '*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    public function extractImageCandidatesFromHtml(string $html): array
    {
        $validCandidates = [];

        $extractIntAttr = static function (string $tag, string $attr): int {
            if (preg_match('/\b' . preg_quote($attr, '/') . '\s*=\s*["\']?(\d{1,5})/i', $tag, $m) === 1) {
                return (int) $m[1];
            }
            return 0;
        };
        $extractPxFromStyle = static function (string $tag, string $prop): int {
            if (preg_match('/\bstyle\s*=\s*["\'][^"\']*\b' . preg_quote($prop, '/') . '\s*:\s*(\d{1,5})\s*px/i', $tag, $m) === 1) {
                return (int) $m[1];
            }
            return 0;
        };
        $isSmallIcon = static function (string $imgTag) use ($extractIntAttr, $extractPxFromStyle): bool {
            $w = $extractIntAttr($imgTag, 'width') ?: $extractPxFromStyle($imgTag, 'width');
            $h = $extractIntAttr($imgTag, 'height') ?: $extractPxFromStyle($imgTag, 'height');
            if ($w > 0 && $h > 0) {
                return $w <= 160 && $h <= 160;
            }
            if ($w > 0) {
                return $w <= 80;
            }
            if ($h > 0) {
                return $h <= 80;
            }
            return false;
        };

        if (preg_match_all('/<img\b[^>]*>/i', $html, $matches)) {
            foreach ($matches[0] as $imgTag) {
                if (!preg_match('/\bsrc=["\']([^"\']+)["\']/i', $imgTag, $srcMatch)) {
                    continue;
                }
                if ($isSmallIcon($imgTag)) {
                    continue;
                }

                $candidateUrls = [(string) $srcMatch[1]];
                if (preg_match('/\bsrcset=["\']([^"\']+)["\']/i', $imgTag, $srcsetMatch) === 1) {
                    foreach (array_map('trim', explode(',', trim((string) $srcsetMatch[1]))) as $part) {
                        if ($part === '') {
                            continue;
                        }
                        $urlPart = trim((string) preg_split('/\s+/', $part)[0]);
                        if ($urlPart !== '') {
                            $candidateUrls[] = $urlPart;
                        }
                    }
                }

                foreach ($candidateUrls as $candidateUrl) {
                    if (!$this->isForbiddenInlineImageUrl($candidateUrl, $imgTag)) {
                        $validCandidates[] = $candidateUrl;
                    }
                }
            }
        }

        if (preg_match_all('/<a[^>]+href=["\']([^"\']+\.(?:jpg|jpeg|png|gif|webp))(?:\?[^"\']*)?["\'][^>]*>/i', $html, $matches)) {
            foreach ($matches[1] as $url) {
                if (!$this->isForbiddenInlineImageUrl((string) $url)) {
                    $validCandidates[] = $url;
                }
            }
        }

        return array_values(array_unique($validCandidates));
    }

    private function isForbiddenInlineImageUrl(string $url, string $imgTag = ''): bool
    {
        $normalizedUrl = strtolower(trim($url));
        $normalizedTag = strtolower(trim($imgTag));

        if ($normalizedUrl === '') {
            return true;
        }

        // Known email signature banner assets.
        if (
            ($normalizedTag !== '' && preg_match('/endless\s+possibilities|constellation|www\.amws\.space|amws\.space/i', $normalizedTag) === 1)
            || preg_match('/endless\s*possibilities|constellation/i', $normalizedUrl) === 1
        ) {
            return true;
        }

        // Webmail image proxies: https://ci*.googleusercontent.com/...#https://origin/img.png
        if (str_contains($normalizedUrl, '#')) {
            $fragment = (string) substr($normalizedUrl, (int) strrpos($normalizedUrl, '#') + 1);
            if ($fragment !== '' && preg_match('/^https?:\/\//i', $fragment) === 1) {
                if (str_contains($fragment, 'amws') || preg_match('/endless\s*possibilities|constellation/i', $fragment) === 1) {
                    return true;
                }
            }
        }

        if (str_contains($normalizedUrl, 'amws') || str_contains($normalizedUrl, 'hc-undulydeepcolt-eu.n0c.com')) {
            return true;
        }

        // Relative URLs in forwarded emails are webmail/header/footer assets.
        if ($this->isRelativeImageUrl($normalizedUrl)) {
            return true;
        }

        if (preg_match('/\b(logo|favicon|sprite|icon|icons|social|share|addtoany|addthis|facebook|twitter|x\.com|linkedin|pinterest|instagram|youtube|whatsapp|telegram|reddit|button|badge|avatar|gravatar|signature|tracking|pixel|spacer|1x1|doubleclick|mailchimp|roundcube|webmail|banner|banniere|ads|advert|promo)\b/i', $normalizedUrl) === 1) {
            return true;
        }

        if (preg_match('/\bwp-content\/plugins\b|\bwp-includes\/(images|css)\b/i', $normalizedUrl) === 1) {
            return true;
        }

        return preg_match('/\.(svg)(\?|#|$)/i', $normalizedUrl) === 1;
    }

    private function isRelativeImageUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        return !preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $url)
            && !str_starts_with($url, 'cid:')
            && !str_starts_with($url, 'data:');
    }
}
