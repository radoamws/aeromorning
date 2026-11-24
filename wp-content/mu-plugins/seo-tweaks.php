<?php
/**
 * Plugin Name: AeroMorning SEO tweaks
 * Description: Publisher schema, thin tag archives, removed spam posts. See DEPLOYMENT-NOTES.md (2026-11-24).
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Tag archives with fewer posts than this are noindex and left out of the sitemap. */
const AEROMORNING_TAG_MIN_POSTS = 5;

/**
 * Spam posts injected on the FR site (blog 1) in Dec 2023 through the "dev"
 * account, backdated to 2023 at 00:00:00 (dating / adult webcam content).
 * Served as 410 Gone so Google drops them, until they are deleted in the admin.
 */
const AEROMORNING_SPAM_POST_IDS = [
    25862, 25888, 25960, 26039, 26067, 26071, 26081, 26133, 26161, 26165, 26230,
    26233, 26239, 26243, 26259, 26263, 26271, 26273, 26275, 26277, 26283, 26305,
    26346, 26367, 26369, 26371, 26375, 26379, 26391, 26417, 26433, 26445, 26447,
];

function aeromorning_is_spam_post($post): bool
{
    $post = get_post($post);
    return $post instanceof WP_Post
        && get_current_blog_id() === 1
        && in_array((int) $post->ID, AEROMORNING_SPAM_POST_IDS, true)
        // Same signature as found in the DB: guards against a reused ID.
        && substr($post->post_date, 11) === '00:00:00';
}

add_action('template_redirect', function () {
    if (is_singular('post') && aeromorning_is_spam_post(get_queried_object_id())) {
        global $wp_query;
        $wp_query->set_404();
        status_header(410);
        nocache_headers();
        header('X-Robots-Tag: noindex');
    }
}, 1);

// Keep them out of listings, feeds, search and the sitemap.
add_action('pre_get_posts', function (WP_Query $query) {
    if (!is_admin() && get_current_blog_id() === 1 && !$query->is_singular()) {
        $query->set('post__not_in', array_merge((array) $query->get('post__not_in'), AEROMORNING_SPAM_POST_IDS));
    }
});
add_filter('wpseo_exclude_from_sitemap_by_post_ids', function ($ids) {
    return get_current_blog_id() === 1 ? array_merge((array) $ids, AEROMORNING_SPAM_POST_IDS) : $ids;
});

/**
 * FR Organization had no logo, so Yoast dropped the Organization node and
 * articles had no "publisher" (EN has one). Logo = site icon, 512x512.
 */
add_filter('option_wpseo_titles', function ($titles) {
    if (is_array($titles) && get_current_blog_id() === 1 && empty($titles['company_logo_id'])) {
        $logoId = (int) get_option('site_icon');
        if ($logoId && ($url = wp_get_attachment_url($logoId))) {
            $titles['company_logo_id'] = $logoId;
            $titles['company_logo'] = $url;
        }
    }
    return $titles;
});

/**
 * Articles are signed by shared accounts ("Aeromorning", "aeromorningnews"...):
 * a fake Person adds nothing. Credit the AeroMorning Organization instead;
 * articles by real named authors keep their Person.
 */
function aeromorning_is_generic_author(int $userId): bool
{
    $user = get_userdata($userId);
    return !$user || preg_match('/aero\s*morning|^dev$|^admin/i', $user->display_name . ' ' . $user->user_login) === 1;
}

add_filter('wpseo_schema_article', function ($data, $context) {
    if (isset($context->post->post_author) && aeromorning_is_generic_author((int) $context->post->post_author)) {
        // Same @id as Yoast's Organization node (Schema_IDs::ORGANIZATION_HASH).
        $data['author'] = ['@id' => $context->site_url . '#organization'];
    }
    return $data;
}, 10, 2);

add_filter('wpseo_schema_needs_author', function ($needed) {
    $post = get_post();
    return ($needed && is_singular() && $post && aeromorning_is_generic_author((int) $post->post_author)) ? false : $needed;
});

/**
 * ~9,500 FR tags, 3 out of 4 used by a single article: thousands of
 * near-empty pages indexed. Keep only the tags with real content indexable.
 */
add_filter('wpseo_robots_array', function ($robots) {
    if (is_tag()) {
        $term = get_queried_object();
        if ($term instanceof WP_Term && (int) $term->count < AEROMORNING_TAG_MIN_POSTS) {
            $robots['index'] = 'noindex';
            $robots['follow'] = 'follow';
        }
    }
    return $robots;
});

add_filter('wpseo_sitemap_entry', function ($url, $type, $object) {
    if ($type === 'term' && $object instanceof WP_Term && $object->taxonomy === 'post_tag' && (int) $object->count < AEROMORNING_TAG_MIN_POSTS) {
        return false;
    }
    return $url;
}, 10, 3);
