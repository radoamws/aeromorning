<?php
defined( 'ABSPATH' ) || exit;

/**
 * Points d'entrée sécurisés pour les tâches CRON.
 *
 * Usage via WP-CLI ou HTTP avec token :
 *   php -q /chemin/vers/cron-import.php --token=VOTRE_TOKEN
 *   wget -q "https://votresite.com/wp-content/plugins/xml-news-feed/cron/import.php?token=VOTRE_TOKEN"
 *
 * Token = hash( 'sha256', AUTH_KEY . 'xnf_cron_v1' ) défini dans xml-news-feed.php
 */

add_action( 'init', 'xnf_handle_cron_requests' );

function xnf_handle_cron_requests() {
    // Vérifier le paramètre d'action
    $action = $_GET['xnf_action'] ?? '';
    if ( ! in_array( $action, [ 'import', 'purge' ], true ) ) {
        return;
    }

    // Valider le token
    $token = sanitize_text_field( $_GET['token'] ?? '' );
    if ( ! hash_equals( XNF_CRON_TOKEN, $token ) ) {
        status_header( 403 );
        die( 'Accès refusé : token invalide.' );
    }

    if ( $action === 'import' ) {
        xnf_cron_import();
    } elseif ( $action === 'purge' ) {
        xnf_cron_purge();
    }
    die();
}

function xnf_cron_import() {
    $results = xnf_scan_and_import();
    header( 'Content-Type: text/plain; charset=utf-8' );
    echo "=== Import XML News Feed – " . date( 'Y-m-d H:i:s' ) . " ===\n";
    if ( is_array( $results ) ) {
        foreach ( $results as $r ) {
            echo $r . "\n";
        }
    } elseif ( isset( $results['error'] ) ) {
        echo 'ERREUR : ' . $results['error'] . "\n";
    } elseif ( isset( $results['info'] ) ) {
        echo 'INFO : ' . $results['info'] . "\n";
    }
    echo "=== Fin ===\n";
}

function xnf_cron_purge() {
    global $wpdb;
    header( 'Content-Type: text/plain; charset=utf-8' );
    echo "=== Purge XML News Feed – " . date( 'Y-m-d H:i:s' ) . " ===\n";

    $files_table = $wpdb->base_prefix . 't_xml_files';
    $data_table  = $wpdb->base_prefix . 't_xml_data';

    $cutoff = date( 'Y-m-d', strtotime( '-7 days' ) );

    // Supprimer les données de plus d'une semaine
    $old_data = $wpdb->get_results( $wpdb->prepare(
        "SELECT id FROM `$data_table` WHERE date_publish < %s",
        $cutoff
    ) );
    if ( $old_data ) {
        $ids = implode( ',', array_map( 'absint', wp_list_pluck( $old_data, 'id' ) ) );
        $deleted_data = $wpdb->query( "DELETE FROM `$data_table` WHERE id IN ($ids)" );
        echo "Données supprimées : $deleted_data ligne(s).\n";
    } else {
        echo "Aucune donnée à supprimer.\n";
    }

    // Fichiers XML traités de plus d'une semaine → déplacer dans xml_backup/
    $old_files = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, filename, filepath FROM `$files_table` WHERE status = 2 AND updated_at < %s",
        date( 'Y-m-d H:i:s', strtotime( '-7 days' ) )
    ) );

    $moved = 0;
    $deleted_rows = 0;
    foreach ( (array) $old_files as $f ) {
        $src  = XNF_XML_DIR . basename( $f->filename );
        $dest = XNF_XML_BACKUP . basename( $f->filename );
        if ( file_exists( $src ) ) {
            rename( $src, $dest );
            $moved++;
        }
        $wpdb->delete( $files_table, [ 'id' => (int) $f->id ] );
        $deleted_rows++;
    }
    echo "Fichiers XML déplacés vers xml_backup/ : $moved.\n";
    echo "Entrées de fichiers supprimées de la BDD : $deleted_rows.\n";

    // Supprimer les images orphelines (sans correspondance dans t_xml_data)
    $used_imgs = $wpdb->get_col( "SELECT img FROM `$data_table` WHERE img != ''" );
    $all_imgs  = glob( XNF_IMG_DIR . '*' );
    $removed_imgs = 0;
    foreach ( (array) $all_imgs as $img_path ) {
        $img_name = basename( $img_path );
        if ( in_array( $img_name, [ 'index.php', '.htaccess' ], true ) ) continue;
        if ( ! in_array( $img_name, $used_imgs ) ) {
            unlink( $img_path );
            $removed_imgs++;
        }
    }
    echo "Images orphelines supprimées : $removed_imgs.\n";

    echo "=== Fin ===\n";
}
