<?php
/**
 * Plugin Name: AeroMorning MailPoet multisite fix
 * Description: Schedules MailPoet post notifications for posts published from another site (news pipeline). See DEPLOYMENT-NOTES.md (2026-11-26).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The news pipeline runs on the FR site and publishes EN posts after
 * switch_to_blog(). MailPoet fixes its table prefix when it boots, so its
 * "post published" hook then scheduled the FR newsletter, never the EN one
 * (EN newsletter stopped on 2026-10-22, the day the pipeline went live).
 * The publisher queues those post IDs in the target site's options; the first
 * request served by that site hands them to MailPoet in the right context.
 */
add_action('wp_loaded', function () {
    $postIds = get_option('amnp_mailpoet_pending');
    if (empty($postIds)
        || !class_exists('\MailPoet\Config\Env')
        || !class_exists('\MailPoet\DI\ContainerWrapper')
        || !class_exists('\MailPoet\Newsletter\Scheduler\PostNotificationScheduler')) {
        return;
    }

    global $wpdb;
    // Only when MailPoet booted for this very site.
    if (\MailPoet\Config\Env::$dbPrefix !== $wpdb->prefix . 'mailpoet_') {
        return;
    }

    delete_option('amnp_mailpoet_pending');
    try {
        $scheduler = \MailPoet\DI\ContainerWrapper::getInstance()
            ->get(\MailPoet\Newsletter\Scheduler\PostNotificationScheduler::class);
        foreach ((array) $postIds as $postId) {
            if (get_post_status((int) $postId) === 'publish') {
                $scheduler->schedulePostNotification((int) $postId);
            }
        }
    } catch (\Throwable $e) {
        error_log('[aeromorning] MailPoet post notification scheduling failed: ' . $e->getMessage());
    }
});
