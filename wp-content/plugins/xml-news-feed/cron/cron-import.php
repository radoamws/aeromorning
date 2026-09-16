#!/usr/bin/env php
<?php
/**
 * Script CRON autonome : Import des fichiers XML
 *
 * Usage :
 *   php -q /chemin/vers/cron-import.php --token=VOTRE_TOKEN
 *   wget -q "https://votresite.com/?xnf_action=import&token=VOTRE_TOKEN" -O -
 *
 * Le TOKEN est affiché une fois dans le backoffice, ou calculable depuis :
 *   hash('sha256', AUTH_KEY . 'xnf_cron_v1')
 * (AUTH_KEY est défini dans wp-config.php)
 */

// Récupérer le token passé en argument CLI
$token = '';
foreach ( $argv ?? [] as $arg ) {
    if ( strpos( $arg, '--token=' ) === 0 ) {
        $token = substr( $arg, 8 );
    }
}

// Simuler la requête GET pour déclencher le hook WordPress
$_GET['xnf_action'] = 'import';
$_GET['token']      = $token;

// Charger WordPress (adapter le chemin si nécessaire)
define( 'STDIN_CRON', true );
$wp_root = dirname( __FILE__, 5 ); // remonte de cron/ → xml-news-feed/ → plugins/ → wp-content/ → WP root
require_once $wp_root . '/wp-load.php';
