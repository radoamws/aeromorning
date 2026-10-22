<?php
/**
 * Plugin Name: AeroMorning News Pipeline
 * Description: Turns the news emails received on news@aeromorning.com into FR/EN articles (AI extraction, image, categories/tags, Yoast, hreflang). Native replacement for the extract_news Laravel app. Runs from WP-CLI via the system cron — never from WP-Cron or web requests.
 * Version: 0.1.0
 * Requires PHP: 8.1
 * Network: true
 * Text Domain: aeromorning-news-pipeline
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AMNP_VERSION', '0.1.0');
define('AMNP_DB_VERSION', '2');
define('AMNP_DIR', __DIR__);
define('AMNP_FILE', __FILE__);

require_once AMNP_DIR . '/includes/class-amnp-config.php';
require_once AMNP_DIR . '/includes/class-amnp-db.php';
require_once AMNP_DIR . '/includes/class-amnp-logger.php';

register_activation_hook(__FILE__, ['AMNP_DB', 'install']);

// Tables are network-wide (base prefix): make sure they exist even if the
// plugin was deployed by rsync rather than activated through the admin.
// Checked only in admin / WP-CLI — front-end requests never touch the plugin.
add_action('plugins_loaded', function () {
    if ((is_admin() || (defined('WP_CLI') && WP_CLI)) && get_site_option('amnp_db_version') !== AMNP_DB_VERSION) {
        AMNP_DB::install();
    }
});

/**
 * Heavy classes (IMAP, OpenAI, image, processor, publisher) are only loaded
 * by the CLI commands and the admin page — never on front-end requests.
 */
function amnp_load_pipeline(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    require_once AMNP_DIR . '/vendor/autoload.php';
    foreach (['mailbox', 'openai', 'image', 'cloudflare', 'notifier', 'linkedin', 'security', 'processor', 'publisher'] as $part) {
        require_once AMNP_DIR . "/includes/class-amnp-{$part}.php";
    }
}

if (defined('WP_CLI') && WP_CLI) {
    require_once AMNP_DIR . '/includes/class-amnp-cli.php';
    WP_CLI::add_command('aeromorning-news', 'AMNP_CLI');
}

if (is_admin()) {
    require_once AMNP_DIR . '/includes/class-amnp-admin.php';
    AMNP_Admin::init();
}
