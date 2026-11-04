<?php
/**
 * Audience statistics back-office (Admin > Statistiques), read from the two
 * GA4 properties (FR site, EN site) through the Google Analytics Data API.
 * Replaces the old /stat/ click counter, whose tables were partly filled with
 * generated rows (insertion_stat.php) and which stopped recording in 02/2025.
 *
 * Admin-only: nothing is loaded on front-end requests. GA is queried only
 * when an administrator opens the page, and every answer is cached.
 *
 * Configuration (wp-config.php):
 *   define('AEROMORNING_GA_KEY_FILE', '/home/aeromorning/ga-service-account.json');
 * The JSON key of a service account that has the "Viewer" role on both
 * properties. Keep it outside the web root.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (is_admin()) {
    require_once __DIR__ . '/aeromorning-stats/class-ams-ga.php';
    require_once __DIR__ . '/aeromorning-stats/class-ams-page.php';
    AMS_Page::init();
}
