<?php
/**
 * Plugin Name:       AeroMorning News SEO
 * Plugin URI:        https://aeromorning.com
 * Description:       Google News XML sitemap + NewsArticle structured data for a WordPress multisite news network (FR/EN). Reimplements the two core Yoast News SEO features (news sitemap, NewsArticle schema) as a free, network-aware plugin. Works standalone or alongside Yoast SEO (Free).
 * Version:           1.0.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            AeroMorning
 * Author URI:        https://aeromorning.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       aeromorning-news-seo
 * Domain Path:       /languages
 * Network:           true
 *
 * This plugin is intentionally network-activatable but every setting it
 * exposes is stored PER SITE (standard WordPress options behaviour), so the
 * French site (aeromorning.com/) and the English site (aeromorning.com/en/)
 * each get their own publication name, language code, sitemap, and schema
 * output — exactly as Google News requires (one sitemap/site, correct
 * <news:language> per edition).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'AM_NEWS_SEO_VERSION', '1.0.0' );
define( 'AM_NEWS_SEO_FILE', __FILE__ );
define( 'AM_NEWS_SEO_PATH', plugin_dir_path( __FILE__ ) );
define( 'AM_NEWS_SEO_URL', plugin_dir_url( __FILE__ ) );
define( 'AM_NEWS_SEO_OPTION', 'am_news_seo_options' );
define( 'AM_NEWS_SEO_META_PREFIX', '_am_news_' );

require_once AM_NEWS_SEO_PATH . 'includes/class-am-news-seo.php';
require_once AM_NEWS_SEO_PATH . 'includes/class-am-settings.php';
require_once AM_NEWS_SEO_PATH . 'includes/class-am-metabox.php';
require_once AM_NEWS_SEO_PATH . 'includes/class-am-sitemap.php';
require_once AM_NEWS_SEO_PATH . 'includes/class-am-schema.php';
require_once AM_NEWS_SEO_PATH . 'includes/class-am-multisite.php';

/**
 * Boots the plugin. Kept as a single entry point so every hook the plugin
 * registers is easy to find from this file.
 */
function am_news_seo() {
	return AM_News_SEO::instance();
}
add_action( 'plugins_loaded', 'am_news_seo' );

register_activation_hook( __FILE__, array( 'AM_Multisite', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'AM_Multisite', 'deactivate' ) );
