<?php
/**
 * Crawl and enumeration hardening that can live in code (deployed by CI),
 * as opposed to the file-level rules that have to sit in .htaccess.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * robots.txt rules, added through Yoast's own API so they land inside its
 * generated block. Yoast strips WordPress's default "Disallow: /wp-admin/"
 * and its own search/wp-json options are disabled on multisite, so without
 * this the file was a bare "Disallow:" (crawl everything).
 *
 * Only polite crawlers obey robots.txt -- abusive bots are handled by the
 * Cloudflare firewall rules. The calendar and a_href= entries are the two
 * crawler traps behind the October 2026 load incidents.
 */
add_action('Yoast\WP\SEO\register_robots_rules', function ($robots) {
    foreach (['/wp-admin/', '/en/wp-admin/'] as $admin) {
        $robots->add_disallow('*', $admin);
        $robots->add_allow('*', $admin . 'admin-ajax.php');
    }
    foreach ([
        '/wp-json/', '/en/wp-json/', '/*?rest_route=',
        '/*?s=', '/*&s=', '/*/search/',
        '/*?replytocom=', '/*/trackback/',
        '/*tribe-bar-date=', '/*eventDisplay=past', '/*ical=', '/*outlook-ical=',
        '/*a_href=',
    ] as $path) {
        $robots->add_disallow('*', $path);
    }
    foreach (['AhrefsBot', 'SemrushBot', 'MJ12bot', 'DotBot', 'PetalBot', 'Bytespider', 'DataForSeoBot', 'BLEXBot'] as $bot) {
        $robots->add_disallow($bot, '/');
    }
});

// Yoast's helper has no Crawl-delay directive. Googlebot ignores it; Bing
// and Yandex honour it (5s still allows ~17k pages/day).
add_filter('robots_txt', function ($output) {
    return preg_replace('/^(User-agent: \*)$/m', "$1\nCrawl-delay: 5", $output, 1);
}, 100000);

/**
 * Username enumeration: /?author=N redirects to /author/<login>/, and the
 * public REST users endpoint lists every author. Both only for visitors --
 * logged-in editors (and the block editor) keep normal access; the
 * extract_news pipeline doesn't use either.
 */
add_action('template_redirect', function () {
    if (isset($_GET['author']) && !is_user_logged_in()) {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
    }
}, 1);

add_filter('rest_endpoints', function ($endpoints) {
    if (is_user_logged_in()) {
        return $endpoints;
    }
    foreach (array_keys($endpoints) as $route) {
        if (strpos($route, '/wp/v2/users') === 0) {
            unset($endpoints[$route]);
        }
    }
    return $endpoints;
});
