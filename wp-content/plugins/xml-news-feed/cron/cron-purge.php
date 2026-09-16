#!/usr/bin/env php
<?php
/**
 * Script CRON autonome : Purge des anciens fichiers XML et données
 *
 * Usage :
 *   php -q /chemin/vers/cron-purge.php --token=VOTRE_TOKEN
 *   wget -q "https://votresite.com/?xnf_action=purge&token=VOTRE_TOKEN" -O -
 *
 * Conserve uniquement les fichiers et données des 7 derniers jours.
 */

$token = '';
foreach ( $argv ?? [] as $arg ) {
    if ( strpos( $arg, '--token=' ) === 0 ) {
        $token = substr( $arg, 8 );
    }
}

$_GET['xnf_action'] = 'purge';
$_GET['token']      = $token;

define( 'STDIN_CRON', true );
$wp_root = dirname( __FILE__, 5 ); // remonte de cron/ → xml-news-feed/ → plugins/ → wp-content/ → WP root
require_once $wp_root . '/wp-load.php';
