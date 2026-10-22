<?php
/**
 * All settings come from constants in wp-config.php (secrets never live in
 * the code or the database). Defaults mirror the extract_news .env values.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AMNP_Config
{
    private const DEFAULTS = [
        // OpenAI
        'OPENAI_API_KEY'                => '',
        'OPENAI_MODEL'                  => 'gpt-5-mini',
        'OPENAI_FALLBACK_MODEL'         => 'gpt-4o-mini',
        'OPENAI_GPT5_FAST_FALLBACK'     => true,
        'OPENAI_HTTP_TIMEOUT'           => 120,
        'OPENAI_MAX_TOKENS'             => 4096,
        'OPENAI_MIN_COMPLETION_TOKENS'  => 256,
        // IMAP (news@aeromorning.com)
        'IMAP_HOST'                     => '',
        'IMAP_PORT'                     => '993',
        'IMAP_USERNAME'                 => '',
        'IMAP_PASSWORD'                 => '',
        'IMAP_ENCRYPTION'               => 'ssl',
        'IMAP_VALIDATE_CERT'            => true,
        'IMAP_FOLDER'                   => 'INBOX',
        'IMAP_SEARCH_CRITERIA'          => 'UNSEEN',
        'IMAP_TIMEOUT_SEC'              => 15,
        'IMAP_CONNECTION_RETRY'         => 1,
        'IMAP_ALLOW_NOTLS_FALLBACK'     => false,
        // false = never flag emails as read (local tests against the prod mailbox)
        'IMAP_MARK_READ'                => true,
        // Featured image
        'IMAGE_WIDTH'                   => 700,
        'IMAGE_HEIGHT'                  => 400,
        'IMAGE_MAX_SIZE'                => 1000000,
        'IMAGE_BACKGROUND_COLOR'        => '#005A8C',
        'IMAGE_QUALITY'                 => 85,
        // Publishing
        'PUBLISH_STATUS'                => 'publish',
        'AUTHOR_LOGIN'                  => 'dev',
        'FR_BLOG_ID'                    => 1,
        'EN_BLOG_ID'                    => 2,
        // Notifications ('' disables the summary emails)
        'NOTIFY_EMAIL'                  => '',
        // LinkedIn (EN only, via Buffer): 'off' | 'dry-run' | 'live'
        'LINKEDIN_MODE'                 => 'off',
        'BUFFER_API_KEY'                => '',
        'BUFFER_LINKEDIN_CHANNEL_ID'    => '',
        // Cloudflare purge after publishing
        'CLOUDFLARE_PURGE'              => true,
        'CLOUDFLARE_ZONE_ID'            => '',
        'CLOUDFLARE_API_TOKEN'          => '',
    ];

    public static function get(string $key)
    {
        $const = 'AMNP_' . $key;
        if (defined($const)) {
            return constant($const);
        }
        return self::DEFAULTS[$key] ?? null;
    }

    public static function bool(string $key): bool
    {
        $value = self::get($key);
        if (is_bool($value)) {
            return $value;
        }
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key): int
    {
        return (int) self::get($key);
    }

    public static function str(string $key): string
    {
        return (string) self::get($key);
    }

    public static function blogForLang(string $lang): int
    {
        return $lang === 'EN' ? self::int('EN_BLOG_ID') : self::int('FR_BLOG_ID');
    }
}
