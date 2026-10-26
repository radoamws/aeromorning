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

// Cairo itself is self-hosted below (aeromorning_font_faces), no enqueue needed.

// No size reservation is needed for the Smart Slider banners: Smart Slider's
// own CSS already reserves the exact height without JS, through an inline
// <svg class="n2-ss-preserve-size" viewBox="0 0 1920 438"> (the slider's real
// ratio) displayed at width:100%. The ".n2-ss-align{aspect-ratio:5/1}" rule
// that used to live here (2026-10-05) reserved *less* than that for the
// 1920x438 Thales strip, so the banner overflowed its box by ~36px and
// covered the category line of the first article (removed 2026-10-26).

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
 * Self-hosted web fonts — the main cause of the desktop CLS (0.27-0.35) and
 * of the late LCP flagged by Search Console in 2026-10. Measured with
 * Lighthouse on prod before this change, three font sources were fighting:
 *  - the theme's Google Fonts (Oswald + PT Serif) and the MailPoet Cairo
 *    font, which LiteSpeed's "Load Google Fonts Asynchronously" option
 *    turns into a webfontloader *script* — so they started downloading
 *    only after JS ran (~3s desktop, ~8-12s simulated mobile) and every
 *    heading/paragraph re-rendered (and shifted) when they arrived;
 *  - an extra Oswald 300 @font-face pointing to /wp-content/fonts/oswald.ttf
 *    in the Customizer "Additional CSS" of both sites;
 *  - a preload of that TTF built with content_url(), which on the EN site
 *    resolves under /en/wp-content/ — a different URL than the CSS one, so
 *    the font (and FontAwesome, same issue) was downloaded twice.
 * Now: the latin subsets (woff2, from Google Fonts) live in
 * wp-content/fonts/, are declared here with a single main-site URL shared
 * by both sites, and the two fonts used above the fold are preloaded.
 * Fallback faces with metric overrides (computed from the fonts' real
 * metrics with @capsizecss/metrics) keep text the same size while the web
 * font loads, so the swap barely moves anything.
 */
function aeromorning_fonts_url($file) {
    return network_site_url('/wp-content/fonts/' . $file);
}

add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('mh-google-fonts');
}, 20);

// The old @font-face lives in the DB (custom_css posts of both sites);
// filtering it out keeps the DB untouched and the fix in version control.
add_filter('wp_get_custom_css', function ($css) {
    return preg_replace('/@font-face\s*\{[^}]*oswald\.ttf[^}]*\}/i', '', $css);
});

add_action('wp_head', function () {
    echo '<link rel="preload" as="font" type="font/woff2" href="' . esc_url(aeromorning_fonts_url('oswald-latin.woff2')) . '" crossorigin>' . "\n";
    echo '<link rel="preload" as="font" type="font/woff2" href="' . esc_url(aeromorning_fonts_url('pt-serif-400-latin.woff2')) . '" crossorigin>' . "\n";
    // Same root-relative URL font-awesome.min.css uses, on both sites.
    echo '<link rel="preload" as="font" type="font/woff2" href="' . esc_url(network_site_url('/wp-content/themes/mh_newsdesk/fonts/fontawesome-webfont.woff2?v=4.4.0')) . '" crossorigin>' . "\n";

    $faces = [
        ['Oswald', 'normal', '300 700', 'oswald-latin.woff2'],
        ['PT Serif', 'normal', '400', 'pt-serif-400-latin.woff2'],
        ['PT Serif', 'italic', '400', 'pt-serif-400i-latin.woff2'],
        ['PT Serif', 'normal', '700', 'pt-serif-700-latin.woff2'],
        ['PT Serif', 'italic', '700', 'pt-serif-700i-latin.woff2'],
        ['Cairo', 'normal', '400 700', 'cairo-latin.woff2'],
    ];
    $css = '';
    foreach ($faces as [$family, $style, $weight, $file]) {
        $css .= "@font-face{font-family:'$family';font-style:$style;font-weight:$weight;font-display:swap;src:url(" . esc_url(aeromorning_fonts_url($file)) . ") format('woff2')}";
    }
    $css .= "@font-face{font-family:'Oswald Fallback';src:local('Arial');size-adjust:81.43%;ascent-override:146.51%;descent-override:35.49%;line-gap-override:0%}"
        . "@font-face{font-family:'PT Serif Fallback';src:local('Georgia');size-adjust:100.49%;ascent-override:103.39%;descent-override:28.46%;line-gap-override:0%}"
        . "@font-face{font-family:'PT Serif Fallback Times';src:local('Times New Roman');size-adjust:110.28%;ascent-override:94.22%;descent-override:25.93%;line-gap-override:0%}";
    echo '<style id="aeromorning-fonts">' . $css . '</style>' . "\n";
}, 1);

// Insert the fallback faces into the theme's own stacks (style.css lines 43,
// 87 and 591). Printed after the theme CSS and the Additional CSS (priority
// 101) so these same-specificity rules win.
add_action('wp_head', function () {
    echo '<style id="aeromorning-font-stacks">'
        . 'body{font-family:"PT Serif","PT Serif Fallback","PT Serif Fallback Times",Georgia,Times,"Times New Roman",serif}'
        . '.header-nav,.header-sub,h1,h2,h3,h4,h5,h6,.breadcrumb,.footer-bottom,.widget-list li,.cat-item,.page_item,.menu-item,.button,.commentlist .vcard,.pagination,.mh-share-buttons,'
        . '#block-14 .wp-widget-group__inner-blocks .wp-block-rss li.wp-block-rss__item .wp-block-rss__item-title a'
        . '{font-family:"Oswald","Oswald Fallback",Helvetica,Arial,sans-serif}'
        . '</style>' . "\n";
}, 102);

/**
 * LCP on desktop is the first slide of the Smart Slider banner strip
 * (Thales 1920x438, data-first="1" — the first slide is fixed, not random).
 * Smart Slider prints every slide image with loading="lazy" and only flips
 * the visible one to eager from its JS, so the browser couldn't start the
 * LCP image until scripts ran (Lighthouse: ~1.6-2s "load delay"). Make the
 * first slide's image of each slider eager + high priority in the HTML so
 * the preload scanner fetches it immediately.
 */
add_action('template_redirect', function () {
    if (is_admin() || is_feed() || is_robots() || wp_doing_ajax()) {
        return;
    }
    ob_start(function ($html) {
        // Slide images are printed in "background" blocks ahead of the
        // slides themselves; data-public-id="1" is the data-first slide's.
        $marker = 'class="n2-ss-slide-background" data-public-id="1"';
        $offset = 0;
        while (($start = strpos($html, $marker, $offset)) !== false) {
            $offset = $start + strlen($marker);
            $next = strpos($html, 'class="n2-ss-slide-background"', $offset);
            if (!preg_match('/<img\b[^>]*\sloading="lazy"[^>]*>/', $html, $m, PREG_OFFSET_CAPTURE, $start)
                || ($next !== false && $m[0][1] > $next)) {
                continue;
            }
            $img = str_replace(' loading="lazy"', ' loading="eager" fetchpriority="high"', $m[0][0]);
            $html = substr_replace($html, $img, $m[0][1], strlen($m[0][0]));
        }
        return $html;
    });
}, 0);

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
