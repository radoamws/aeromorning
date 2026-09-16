<?php
/*
Plugin Name: AMWEB LLM Generator
Description: Génère automatiquement les fichiers llm.txt pour le multisite
Version: 1.0
Network: true
*/

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Génération du contenu llm.txt
|--------------------------------------------------------------------------
*/

function amweb_generate_llm_content($blog_id = null) {

    if ($blog_id) {
        switch_to_blog($blog_id);
    }

    $site_name = get_bloginfo('name');
    $site_url  = home_url();

    $content = "# {$site_name}\n";
    $content .= "Site: {$site_url}\n";
    $content .= "Type: Aviation News & Travel Media\n\n";

    /*
    |--------------------------------------------------------------------------
    | Catégories
    |--------------------------------------------------------------------------
    */

    $content .= "## Priority Content\n";

    $categories = get_categories([
        'hide_empty' => true
    ]);

    foreach ($categories as $cat) {
        $content .= get_category_link($cat->term_id) . "\n";
    }

    /*
    |--------------------------------------------------------------------------
    | Derniers articles
    |--------------------------------------------------------------------------
    */

    $content .= "\n## Latest Articles\n";

    $posts = get_posts([
        'numberposts' => 20,
        'post_status' => 'publish'
    ]);

    foreach ($posts as $post) {
        $content .= get_permalink($post->ID) . "\n";
    }

    /*
    |--------------------------------------------------------------------------
    | Sitemap
    |--------------------------------------------------------------------------
    */

    $content .= "\n## Sitemap\n";
    $content .= home_url('/sitemap_index.xml') . "\n";

    /*
    |--------------------------------------------------------------------------
    | AI Guidance
    |--------------------------------------------------------------------------
    */

    $content .= "\n## AI Crawling Guidance\n";
    $content .= "Allow: /\n";
    $content .= "Disallow: /wp-admin/\n";
    $content .= "Disallow: /cart/\n";
    $content .= "Disallow: /checkout/\n";

    /*
    |--------------------------------------------------------------------------
    | Langue
    |--------------------------------------------------------------------------
    */

    $locale = get_locale();

    $content .= "\n## Language\n";
    $content .= $locale . "\n";

    if ($blog_id) {
        restore_current_blog();
    }

    return $content;
}

/*
|--------------------------------------------------------------------------
| Génération du fichier physique
|--------------------------------------------------------------------------
*/

function amweb_write_llm_file($blog_id = null) {

    if ($blog_id) {
        switch_to_blog($blog_id);
    }

    $content = amweb_generate_llm_content();

    $path = ABSPATH . 'llm.txt';

    file_put_contents($path, $content);

    if ($blog_id) {
        restore_current_blog();
    }
}

/*
|--------------------------------------------------------------------------
| Génération multisite
|--------------------------------------------------------------------------
*/

function amweb_generate_all_llm_files() {

    if (is_multisite()) {

        $sites = get_sites();

        foreach ($sites as $site) {

            switch_to_blog($site->blog_id);

            $content = amweb_generate_llm_content();

            $upload_dir = wp_upload_dir();

            $path = ABSPATH;

            file_put_contents($path . 'llm.txt', $content);

            restore_current_blog();
        }

    } else {

        amweb_write_llm_file();
    }
}

/*
|--------------------------------------------------------------------------
| CRON toutes les heures
|--------------------------------------------------------------------------
*/

register_activation_hook(__FILE__, function () {

    if (!wp_next_scheduled('amweb_generate_llm_cron')) {

        wp_schedule_event(time(), 'hourly', 'amweb_generate_llm_cron');
    }

    amweb_generate_all_llm_files();
});

register_deactivation_hook(__FILE__, function () {

    wp_clear_scheduled_hook('amweb_generate_llm_cron');
});

add_action('amweb_generate_llm_cron', 'amweb_generate_all_llm_files');

/*
|--------------------------------------------------------------------------
| Regénération après publication
|--------------------------------------------------------------------------
*/

add_action('save_post', function () {

    amweb_generate_all_llm_files();

});


/* multisite */
add_action('init', function () {

    add_rewrite_rule(
        '^llm\.txt$',
        'index.php?amweb_llm=1',
        'top'
    );

});

add_filter('query_vars', function($vars) {

    $vars[] = 'amweb_llm';

    return $vars;
});

add_action('template_redirect', function () {

    if (get_query_var('amweb_llm')) {

        header('Content-Type: text/plain; charset=utf-8');

        echo amweb_generate_llm_content();

        exit;
    }

});