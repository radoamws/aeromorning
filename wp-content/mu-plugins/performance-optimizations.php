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
