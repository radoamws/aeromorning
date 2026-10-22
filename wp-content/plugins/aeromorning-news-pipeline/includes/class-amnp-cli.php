<?php
/**
 * WP-CLI entry points, meant for the system crontab (replacing the
 * extract_news artisan crons):
 *
 *   0,30 * * * *  wp --path=… aeromorning-news process-emails
 *   * /5 * * * *  wp --path=… aeromorning-news publish
 */

if (!defined('ABSPATH')) {
    exit;
}

class AMNP_CLI
{
    /**
     * Read the mailbox and turn each news email into pending FR/EN news.
     *
     * ## OPTIONS
     *
     * [--latest=<n>]
     * : Test mode: process the N most recent emails, read or unread. Combine
     * with AMNP_IMAP_MARK_READ=false to leave the mailbox untouched.
     *
     * @subcommand process-emails
     */
    public function process_emails($args, $assoc): void
    {
        amnp_load_pipeline();
        @set_time_limit(0);

        if (!AMNP_DB::lock('process')) {
            WP_CLI::warning('Another process-emails run is still in progress; skipping.');
            return;
        }

        try {
            $latest = isset($assoc['latest']) ? max(1, (int) $assoc['latest']) : null;
            if ($latest && AMNP_Config::bool('IMAP_MARK_READ')) {
                WP_CLI::warning('--latest with AMNP_IMAP_MARK_READ on: processed emails will be flagged as read.');
            }

            $processor = new AMNP_Processor([WP_CLI::class, 'log']);

            $forced = $processor->runForceQueue();
            if ($forced['created'] || $forced['failed']) {
                WP_CLI::log("Forced publications: {$forced['created']} news created, {$forced['failed']} failed.");
            }

            $counts = $processor->run($latest);
            WP_CLI::success("Processed: {$counts['processed']} | skipped: {$counts['skipped']} | failed: {$counts['failed']}");
        } catch (\Throwable $e) {
            WP_CLI::error($e->getMessage());
        } finally {
            AMNP_DB::unlock('process');
        }
    }

    /**
     * Publish pending news to the FR / EN sites.
     *
     * ## OPTIONS
     *
     * [--status=<status>]
     * : publish or draft. Defaults to AMNP_PUBLISH_STATUS.
     *
     * [--id=<id>]
     * : Publish only this news record.
     */
    public function publish($args, $assoc): void
    {
        amnp_load_pipeline();
        @set_time_limit(0);

        if (!AMNP_DB::lock('publish')) {
            WP_CLI::warning('Another publish run is still in progress; skipping.');
            return;
        }

        try {
            $results = (new AMNP_Publisher([WP_CLI::class, 'log']))->run($assoc['status'] ?? null, isset($assoc['id']) ? [(int) $assoc['id']] : null);
            WP_CLI::success('Published: ' . count($results['success']) . ' | failed: ' . count($results['failed']));
        } catch (\Throwable $e) {
            WP_CLI::error($e->getMessage());
        } finally {
            AMNP_DB::unlock('publish');
        }
    }

    /**
     * Post one published EN news to LinkedIn (honours AMNP_LINKEDIN_MODE).
     *
     * ## OPTIONS
     *
     * <id>
     * : News record id.
     */
    public function linkedin($args, $assoc): void
    {
        amnp_load_pipeline();
        $news = AMNP_DB::getNews((int) $args[0]);
        if (!$news) {
            WP_CLI::error('News not found.');
        }
        if (!$news['wp_link']) {
            WP_CLI::error('This news has not been published to WordPress yet.');
        }

        try {
            $result = (new AMNP_LinkedIn())->postNews($news);
        } catch (\Throwable $e) {
            WP_CLI::error($e->getMessage());
        }

        if (!empty($result['skipped'])) {
            WP_CLI::warning($result['reason']);
            return;
        }
        if (AMNP_LinkedIn::mode() === 'live') {
            AMNP_DB::updateNews((int) $news['id'], ['linkedin_posted' => 1]);
        }
        if (isset($result['text'])) {
            WP_CLI::log("----- post text -----\n{$result['text']}\n---------------------");
            WP_CLI::log('annotations: ' . wp_json_encode($result['input']['metadata']['linkedin']['annotations'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        WP_CLI::success(AMNP_LinkedIn::mode() . ': ' . ($result['post_id'] ?? '') . ', ' . ($result['annotations'] ?? 0) . ' mention(s)');
    }

    /**
     * Import the LinkedIn company URN/name cache of extract_news.
     *
     * ## OPTIONS
     *
     * --file=<path>
     * : Path to extract_news storage/app/linkedin.json.
     *
     * @subcommand linkedin-import-cache
     */
    public function linkedin_import_cache($args, $assoc): void
    {
        amnp_load_pipeline();
        $data = json_decode((string) @file_get_contents($assoc['file']), true);
        if (!is_array($data)) {
            WP_CLI::error('Unreadable JSON file.');
        }
        $counts = AMNP_LinkedIn::importCache($data);
        WP_CLI::success("LinkedIn cache: {$counts['urns']} URNs, {$counts['names']} names.");
    }

    /**
     * Alert by email on high server load or abnormal Cloudflare traffic
     * (meant to run every 5 minutes).
     *
     * @subcommand security-monitor
     */
    public function security_monitor($args, $assoc): void
    {
        amnp_load_pipeline();
        $result = (new AMNP_Security())->run();

        $load = $result['load'] ? vsprintf('%.2f / %.2f / %.2f', $result['load']) : 'n/a';
        $traffic = ($result['summary']['success'] ?? false)
            ? "{$result['summary']['total_requests']} requests, {$result['summary']['mitigated_requests']} mitigated (5 min)"
            : 'Cloudflare: ' . ($result['summary']['message'] ?? 'unavailable');
        WP_CLI::log("load {$load} · {$traffic}");

        if (!$result['alerts']) {
            WP_CLI::success("OK — rien d'anormal.");
        } elseif ($result['suppressed']) {
            WP_CLI::warning('Anomalie détectée, alerte déjà envoyée récemment (cooldown) : ' . implode(' | ', $result['alerts']));
        } else {
            WP_CLI::warning(($result['sent'] ? 'ALERTE envoyée : ' : 'ALERTE non envoyée (pas de destinataire) : ') . implode(' | ', $result['alerts']));
        }
    }

    /**
     * List news records.
     *
     * ## OPTIONS
     *
     * [--status=<status>]
     * : 0 pending, 1 syncing, 2 synced.
     *
     * [--limit=<n>]
     * : Default 20.
     */
    public function list($args, $assoc): void
    {
        global $wpdb;
        $where = isset($assoc['status']) ? $wpdb->prepare(' WHERE status = %d', (int) $assoc['status']) : '';
        $limit = max(1, (int) ($assoc['limit'] ?? 20));
        $rows = $wpdb->get_results('SELECT id, lang, status, wp_post_id, LEFT(title, 70) AS title, categories, tags, image_path, created_at FROM ' . AMNP_DB::news() . $where . " ORDER BY id DESC LIMIT {$limit}", ARRAY_A);
        foreach ($rows as &$row) {
            $row['image_path'] = $row['image_path'] ? basename($row['image_path']) : '';
        }
        WP_CLI\Utils\format_items('table', $rows, ['id', 'lang', 'status', 'wp_post_id', 'title', 'categories', 'tags', 'image_path', 'created_at']);
    }

    /**
     * Check configuration and connectivity (IMAP, OpenAI, author, GD).
     */
    public function check($args, $assoc): void
    {
        amnp_load_pipeline();
        $line = static fn (string $label, bool $ok, string $detail = '') => WP_CLI::log(($ok ? '✓ ' : '✗ ') . str_pad($label, 22) . $detail);

        $line('PHP imap extension', extension_loaded('imap'));
        $line('PHP gd extension', extension_loaded('gd'), function_exists('imagewebp') ? '(webp ok)' : '');
        $line('Author user', (bool) get_user_by('login', AMNP_Config::str('AUTHOR_LOGIN')), AMNP_Config::str('AUTHOR_LOGIN'));
        $line('OpenAI key set', AMNP_Config::str('OPENAI_API_KEY') !== '', AMNP_Config::str('OPENAI_MODEL') . ' / ' . AMNP_Config::str('OPENAI_FALLBACK_MODEL'));
        $line('Publish status', true, AMNP_Config::str('PUBLISH_STATUS'));
        $line('Mark emails read', true, AMNP_Config::bool('IMAP_MARK_READ') ? 'yes' : 'NO (test mode)');
        $line('Cloudflare purge', true, AMNP_Config::bool('CLOUDFLARE_PURGE') ? 'on' : 'off');
        $line('LinkedIn mode', true, AMNP_LinkedIn::mode() . (AMNP_Config::str('BUFFER_API_KEY') !== '' ? ' (Buffer key set)' : ' (no Buffer key)'));
        $line('Notify email', true, AMNP_Config::str('NOTIFY_EMAIL') ?: '(disabled)');
        $line('Image queue dir', is_writable(AMNP_Image::queueDir()), AMNP_Image::queueDir());

        try {
            $ids = (new AMNP_Mailbox())->getLatestEmailIds(1);
            $line('IMAP connection', true, 'latest id ' . ($ids[0] ?? '-'));
        } catch (\Throwable $e) {
            $line('IMAP connection', false, $e->getMessage());
        }

        $ping = wp_remote_get('https://api.openai.com/v1/models', ['timeout' => 15, 'headers' => ['Authorization' => 'Bearer ' . AMNP_Config::str('OPENAI_API_KEY')]]);
        $code = is_wp_error($ping) ? $ping->get_error_message() : (string) wp_remote_retrieve_response_code($ping);
        $line('OpenAI API reachable', $code === '200', $code);
    }

    /**
     * Drop the raw email copies kept on old records (keeps the table small).
     *
     * ## OPTIONS
     *
     * [--days=<n>]
     * : Keep raw data younger than N days. Default 30.
     *
     * @subcommand purge-raw
     */
    public function purge_raw($args, $assoc): void
    {
        global $wpdb;
        $days = max(1, (int) ($assoc['days'] ?? 30));
        $cutoff = gmdate('Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS);
        $news = $wpdb->query($wpdb->prepare('UPDATE ' . AMNP_DB::news() . ' SET content_brut = NULL WHERE status = %d AND content_brut IS NOT NULL AND created_at < %s', AMNP_DB::STATUS_SYNCED, $cutoff));
        $ignored = $wpdb->query($wpdb->prepare('UPDATE ' . AMNP_DB::ignored() . ' SET raw_email_json = NULL WHERE raw_email_json IS NOT NULL AND created_at < %s', $cutoff));
        $logs = $wpdb->query($wpdb->prepare('DELETE FROM ' . AMNP_DB::logs() . ' WHERE started_at < %s', gmdate('Y-m-d H:i:s', time() - 90 * DAY_IN_SECONDS)));
        WP_CLI::success("Raw data cleared: {$news} news, {$ignored} ignored emails; {$logs} logs older than 90 days deleted.");
    }
}
