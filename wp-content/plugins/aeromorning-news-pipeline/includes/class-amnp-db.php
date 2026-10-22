<?php
/**
 * Network-wide tables (base prefix), shared by the FR and EN sites — same
 * three tables the Laravel app had (t_news, t_ignored_emails, t_process_logs).
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AMNP_DB
{
    public const STATUS_PENDING = 0;
    public const STATUS_SYNCING = 1;
    public const STATUS_SYNCED  = 2;

    public static function news(): string
    {
        global $wpdb;
        return $wpdb->base_prefix . 'amnp_news';
    }

    public static function ignored(): string
    {
        global $wpdb;
        return $wpdb->base_prefix . 'amnp_ignored_emails';
    }

    public static function logs(): string
    {
        global $wpdb;
        return $wpdb->base_prefix . 'amnp_process_logs';
    }

    public static function install(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $news    = self::news();
        $ignored = self::ignored();
        $logs    = self::logs();

        dbDelta("CREATE TABLE {$news} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  lang char(2) NOT NULL,
  title text NOT NULL,
  content longtext NOT NULL,
  content_brut longtext NULL,
  metadescription text NULL,
  focuskeyphrase text NULL,
  categories text NULL,
  tags text NULL,
  image_path varchar(255) NULL,
  wp_post_id bigint(20) unsigned NULL,
  wp_link varchar(500) NULL,
  hreflang_linked tinyint(1) NOT NULL DEFAULT 0,
  status tinyint(1) NOT NULL DEFAULT 0,
  email_message_id varchar(191) NOT NULL DEFAULT '',
  linkedin tinyint(1) NOT NULL DEFAULT 0,
  linkedin_posted tinyint(1) NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY email_lang (email_message_id,lang),
  KEY status (status),
  KEY wp_post_id (wp_post_id)
) {$charset};");

        dbDelta("CREATE TABLE {$ignored} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  message_id varchar(191) NULL,
  subject text NULL,
  sender varchar(255) NULL,
  reason varchar(50) NOT NULL,
  excerpt text NULL,
  raw_email_json longtext NULL,
  processed_at datetime NULL,
  force_requested_at datetime NULL,
  force_published_at datetime NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY message_id (message_id),
  KEY processed_at (processed_at)
) {$charset};");

        dbDelta("CREATE TABLE {$logs} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  process_type varchar(50) NOT NULL,
  status varchar(20) NOT NULL,
  source varchar(20) NULL,
  message text NULL,
  details longtext NULL,
  started_at datetime NOT NULL,
  finished_at datetime NULL,
  PRIMARY KEY  (id),
  KEY process_type (process_type),
  KEY started_at (started_at)
) {$charset};");

        update_site_option('amnp_db_version', AMNP_DB_VERSION);
    }

    public static function now(): string
    {
        return current_time('mysql', true);
    }

    public static function insertNews(array $data): ?int
    {
        global $wpdb;
        $now = self::now();
        $data += ['created_at' => $now, 'updated_at' => $now];
        $ok = $wpdb->insert(self::news(), $data);
        return $ok ? (int) $wpdb->insert_id : null;
    }

    public static function updateNews(int $id, array $data): void
    {
        global $wpdb;
        $data['updated_at'] = self::now();
        $wpdb->update(self::news(), $data, ['id' => $id]);
    }

    public static function getNews(int $id): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::news() . ' WHERE id = %d', $id), ARRAY_A);
        return $row ?: null;
    }

    /** @return string[] languages already stored for this email */
    public static function langsForMessage(string $messageId): array
    {
        global $wpdb;
        if ($messageId === '') {
            return [];
        }
        return $wpdb->get_col($wpdb->prepare('SELECT lang FROM ' . self::news() . ' WHERE email_message_id = %s', $messageId));
    }

    /**
     * MySQL advisory lock: released automatically if the PHP process dies,
     * so a crashed run can never block the next ones.
     */
    public static function lock(string $name): bool
    {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 0)', 'amnp_' . $name)) === 1;
    }

    public static function unlock(string $name): void
    {
        global $wpdb;
        $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', 'amnp_' . $name));
    }
}
