<?php
/**
 * Process runs (one row per cron run, like t_process_logs) + PHP error log
 * for the detailed trace.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AMNP_Logger
{
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_FAILED  = 'failed';

    public static function startRun(string $type, string $source = 'cli', array $details = []): int
    {
        global $wpdb;
        $wpdb->insert(AMNP_DB::logs(), [
            'process_type' => $type,
            'status'       => self::STATUS_RUNNING,
            'source'       => $source,
            'details'      => $details ? wp_json_encode($details) : null,
            'started_at'   => AMNP_DB::now(),
        ]);
        return (int) $wpdb->insert_id;
    }

    public static function finishRun(int $runId, string $status, array $details = [], ?string $message = null): void
    {
        global $wpdb;
        if ($runId <= 0) {
            return;
        }
        $wpdb->update(AMNP_DB::logs(), [
            'status'      => $status,
            'message'     => $message,
            'details'     => wp_json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'finished_at' => AMNP_DB::now(),
        ], ['id' => $runId]);
    }

    public static function failRun(int $runId, \Throwable $e, array $details = []): void
    {
        $details['exception'] = ['class' => get_class($e), 'message' => $e->getMessage()];
        self::finishRun($runId, self::STATUS_FAILED, $details, $e->getMessage());
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $line = "[aeromorning-news] {$level}: {$message}";
        if ($context) {
            $line .= ' ' . wp_json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        error_log($line);
        if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
            WP_CLI::debug($line, 'aeromorning-news');
        }
    }
}
