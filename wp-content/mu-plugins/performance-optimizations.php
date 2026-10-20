<?php
/**
 * Performance fixes that aren't configurable from a plugin's own settings
 * screen. Kept separate from aeromorning-api.php (REST/SEO surface) and
 * local-dev-overrides.php (local-only) to keep each mu-plugin's purpose
 * single and obvious.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MailPoet's front-end form renderer unconditionally enqueues its entire
 * ~85-family custom font library (3 Google Fonts stylesheets, render-
 * blocking) on every page that has a MailPoet form/widget, regardless of
 * which font (if any) the form actually uses. Our forms only ever use
 * "Cairo" (confirmed via wp_mailpoet_forms.body) — so we drop the bulk
 * load and enqueue just that one family instead.
 *
 * MailPoet\Form\Util\CustomFonts::enqueueStyle() registers styles named
 * "mailpoet_custom_fonts_0" / "_1" / "_2" (one per 25-font chunk), called
 * from the form renderer — which runs during widget/content output, i.e.
 * *after* wp_enqueue_scripts has already fired. Dequeuing there is too
 * early (nothing to dequeue yet), so this suppresses the actual <link>
 * output instead, via style_loader_tag, which fires at print time
 * regardless of when the style was enqueued.
 */
add_filter('style_loader_tag', function ($tag, $handle) {
    if (in_array($handle, ['mailpoet_custom_fonts_0', 'mailpoet_custom_fonts_1', 'mailpoet_custom_fonts_2'], true)) {
        return '';
    }
    return $tag;
}, 10, 2);

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'aeromorning-mailpoet-font-cairo',
        'https://fonts.googleapis.com/css?family=Cairo:400,400i,700,700i&display=swap',
        [],
        null
    );
});

/**
 * The Smart Slider 3 banner-ad strip (used site-wide, different numeric
 * instance ID per page/site but always wrapped in .n2-ss-align) has no
 * width/height reserved in the server-rendered HTML — its box only gets
 * its real size once the slider's JS runs, which is the direct cause of
 * the CLS flagged by Search Console (0.35-0.48, "Poor") on both the
 * homepage and article pages. The banner creatives are wide strips
 * (~600x100 to ~1920x438) with no single fixed size, so this reserves an
 * approximate ratio rather than exact pixels — enough to remove the vast
 * majority of the shift. Visually verify after deploy and adjust the
 * ratio if a given placement looks off.
 */
add_action('wp_head', function () {
    echo '<style id="aeromorning-cls-fix">.n2-ss-align{aspect-ratio:5/1;min-height:60px;}</style>' . "\n";
}, 1);

/**
 * The "name-directory" plugin (shortcode.php: name_directory_add_frontend_assets)
 * unconditionally enqueues Google reCAPTCHA (~247KB unused JS per PageSpeed)
 * on every single front-end page whenever reCAPTCHA is enabled in its
 * settings — it never checks whether the current page actually has its
 * form. Only [namedirectory] (not the read-only _random/_single variants)
 * renders the form + recaptcha widget, and only 5 pages site-wide use it
 * (confirmed via wp_posts/wp_2_posts). Drop the script everywhere else.
 */
add_action('wp_enqueue_scripts', function () {
    if (!wp_script_is('name-directory-recaptcha', 'enqueued')) {
        return;
    }

    $needs_recaptcha = false;
    if (is_singular()) {
        $post = get_queried_object();
        if ($post instanceof WP_Post && has_shortcode($post->post_content, 'namedirectory')) {
            $needs_recaptcha = true;
        }
    }

    if (!$needs_recaptcha) {
        wp_dequeue_script('name-directory-recaptcha');
        wp_deregister_script('name-directory-recaptcha');
    }
}, 20);

/**
 * oswald.ttf (theme heading font) and fontawesome-webfont.woff2 are both
 * referenced only via @font-face inside CSS that LiteSpeed loads async —
 * so the browser doesn't discover either font file until that CSS has
 * already downloaded and parsed, making them the longest links in the
 * critical path (PageSpeed: ~1.8-2s each) despite being small files that
 * serve fast on their own. Preloading lets the browser fetch them in
 * parallel with everything else, right from the start of the page.
 */
add_action('wp_head', function () {
    $theme_uri = get_template_directory_uri();
    echo '<link rel="preload" as="font" type="font/ttf" href="' . esc_url(content_url('fonts/oswald.ttf')) . '" crossorigin>' . "\n";
    echo '<link rel="preload" as="font" type="font/woff2" href="' . esc_url($theme_uri . '/fonts/fontawesome-webfont.woff2?v=4.4.0') . '" crossorigin>' . "\n";
}, 1);

/**
 * The Events Calendar's date-based pagination (/events/month/YYYY-MM/,
 * /events/.../list/?tribe-bar-date=YYYY-MM-DD, etc. — the real WP query
 * var underneath both pretty-URL and query-string forms is `eventDate`,
 * confirmed in the plugin's own Rewrite.php) has no cap on how far a
 * requester can navigate. A crawler endlessly clicking "next month" (or
 * directly requesting far-future URLs) generates effectively unlimited
 * distinct, never-cached pages — a second crawler trap found 2026-10-14
 * (first one was the broken AERO 2025 link; this one needed no broken
 * content, just the calendar's own unlimited navigation), responsible
 * for the "143 simultaneous Singapore users" GA anomaly that same day
 * (referer analysis: /agenda/ and tag/singapore-airshow pages with
 * tribe-bar-date values in Feb/March 2027).
 *
 * Short-circuits to a plain 404 — before Tribe's own (expensive) event
 * query runs — for any request whose date is more than ~3 months in the
 * past or ~15 months in the future. The 15-month ceiling was checked
 * against real data, not guessed: the furthest genuinely published event
 * in the DB is 2027-07-15 (~9 months out as of 2026-10), so this leaves
 * real content a comfortable margin while still rejecting requests for
 * dates far beyond anything the site will plausibly ever publish.
 *
 * IMPORTANT CALIBRATION NOTE: unlike the AERO 2025 link (a confirmed,
 * provable trap — one malformed URL that grew a new /page/N/ segment on
 * every bot visit), the evidence that calendar-date browsing was THE
 * cause of the 2026-10-14 Singapore traffic anomaly is weak. The
 * Feb/March 2027 dates seen in that incident's referers are only ~5
 * months out — well within the range of genuine events this industry
 * announces that far ahead (real events on this site go out to 9
 * months) — so this is precautionary hardening against truly extreme
 * abuse (years-out requests), not a confirmed fix for that specific
 * incident. If Singapore/anomalous traffic recurs after this deploys,
 * don't assume the calendar was the cause — re-investigate from
 * scratch (referer + IP/UA breakdown via Cloudflare GraphQL, same
 * method as before).
 */
add_action('template_redirect', function () {
    $raw_date = get_query_var('eventDate');
    if ($raw_date === '' || $raw_date === false) {
        $raw_date = $_GET['tribe-bar-date'] ?? ($_GET['eventDate'] ?? '');
    }
    if (!is_string($raw_date) || $raw_date === '') {
        return;
    }

    $requested = DateTime::createFromFormat('Y-m-d', substr($raw_date, 0, 10));
    if (!$requested) {
        $requested = DateTime::createFromFormat('Y-m', substr($raw_date, 0, 7));
    }
    if (!$requested) {
        return; // Unrecognized format — don't block something we can't parse.
    }

    $earliest = (new DateTime('now'))->modify('-3 months');
    $latest = (new DateTime('now'))->modify('+15 months');

    if ($requested < $earliest || $requested > $latest) {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
    }
}, 5);

/**
 * EWWW Image Optimizer's own lazy-load feature was found enabled on the
 * EN site (blog_id 2) but not FR (blog_id 1) -- an inconsistent, almost
 * certainly accidental per-site setting (confirmed 2026-10-19: EN's
 * homepage served only 2/17 images through EWWW's Picture-WebP
 * rewriting vs FR's 19/21). When this option is on, EWWW defers the
 * real <img src> behind a base64 placeholder + data-src and only swaps
 * in the full-size JPG via its own JS once the image scrolls into
 * view -- its Picture-WebP rewriter doesn't process that deferred
 * markup, so those images silently never get a WebP version, on top of
 * the extra JS/placeholder overhead lazy-load adds on a site that's
 * already behind a CDN (Cloudflare) and a page cache (LiteSpeed).
 * FR works correctly without it, so this forces the same off
 * everywhere rather than just flipping the EN value once in the DB --
 * a DB toggle could silently drift again later, this can't.
 */
add_filter('pre_option_ewww_image_optimizer_lazy_load', '__return_empty_string');

/**
 * WebP delivery is handled by Cloudflare Polish (Pro plan, "lossy" + WebP
 * on): it converts every JPG/PNG to WebP at the edge for browsers that
 * accept it, on both sites, regardless of the URL in the HTML (verified
 * 2026-10-20: EN .jpg thumbnails served as image/webp, cf-polished: ok,
 * 15-26% smaller). EWWW's own WebP conversion duplicated that work on the
 * origin -- extra CPU on every upload (one cwebp run per thumbnail size),
 * ~25k extra files on disk, <picture> markup in every page -- and had
 * drifted between FR and EN. Turned off everywhere; EWWW keeps its normal
 * compression on upload.
 */
// EWWW is network-activated with per-site override allowed, so it reads site
// options -- but falls back to the network ones (lazy_load is '1' there) if
// that override is ever switched off. Pin both levels.
foreach (['ewww_image_optimizer_webp', 'ewww_image_optimizer_picture_webp', 'ewww_image_optimizer_webp_for_cdn', 'ewww_image_optimizer_lazy_load'] as $ewww_webp_option) {
    add_filter('pre_option_' . $ewww_webp_option, '__return_empty_string');
    add_filter('pre_site_option_' . $ewww_webp_option, '__return_empty_string');
}
