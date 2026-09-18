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
| Rendu — /llm.txt est toujours généré à la volée (voir template_redirect
| plus bas), jamais écrit comme fichier statique.
|--------------------------------------------------------------------------
|
| Un fichier statique à ABSPATH . 'llm.txt' ne peut pas fonctionner sur ce
| multisite : switch_to_blog() ne change que le contexte de requête WP, pas
| ABSPATH (le même dossier disque pour tous les sites). Le site traité en
| dernier écrasait donc systématiquement le fichier des autres sites, et
| Apache servait ensuite ce fichier statique en priorité sur la route
| dynamique ci-dessous — /llm.txt du site FR affichait le contenu du site EN.
| Générer le contenu à la demande (une poignée de catégories + 20 posts,
| requêtes déjà rapides) évite le problème sans perte de fraîcheur.
|--------------------------------------------------------------------------
*/

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

// Without this, WordPress's canonical redirect appends a trailing slash
// (llm.txt -> llm.txt/), which no longer matches the ^llm\.txt$ rewrite
// rule above and 404s. Same fix commonly used for ads.txt/humans.txt-style
// flat-file rewrite endpoints.
add_filter('redirect_canonical', function ($redirect_url) {

    if (get_query_var('amweb_llm')) {
        return false;
    }

    return $redirect_url;
});

add_action('template_redirect', function () {

    if (get_query_var('amweb_llm')) {

        header('Content-Type: text/plain; charset=utf-8');

        echo amweb_generate_llm_content();

        exit;
    }

});