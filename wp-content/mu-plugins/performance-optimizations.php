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
