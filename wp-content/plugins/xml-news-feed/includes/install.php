<?php
defined( 'ABSPATH' ) || exit;

function xnf_activate( $network_wide = false ) {
    // Les répertoires sont partagés (dossier plugin commun à tous les sites)
    xnf_create_dirs();

    if ( is_multisite() && $network_wide ) {
        // Activation réseau : créer tables + options sur chaque site
        $sites = get_sites( [ 'fields' => 'ids', 'number' => 0 ] );
        foreach ( $sites as $blog_id ) {
            switch_to_blog( $blog_id );
            xnf_create_tables();
            xnf_set_default_options();
            restore_current_blog();
        }
    } else {
        // Activation sur un seul site (ou site simple)
        xnf_create_tables();
        xnf_set_default_options();
    }
}

function xnf_deactivate() {
    // Optionnel : ne supprime pas les tables pour préserver les données
}

/**
 * Multisite : créer tables + options quand un nouveau site est ajouté
 * et que le plugin est activé en réseau.
 */
add_action( 'wp_insert_site', 'xnf_on_new_site' );
function xnf_on_new_site( $new_site ) {
    if ( is_plugin_active_for_network( 'xml-news-feed/xml-news-feed.php' ) ) {
        switch_to_blog( $new_site->id );
        xnf_create_tables();
        xnf_set_default_options();
        restore_current_blog();
    }
}

function xnf_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    // base_prefix = préfixe du site principal (ex: wp_), partagé entre tous les sites du réseau
    $files_table = $wpdb->base_prefix . 't_xml_files';
    $data_table  = $wpdb->base_prefix . 't_xml_data';

    $sql_files = "CREATE TABLE IF NOT EXISTS `$files_table` (
        id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        filename    VARCHAR(255) NOT NULL,
        filepath    VARCHAR(500) NOT NULL,
        file_hash   VARCHAR(64)  NOT NULL,
        status      TINYINT NOT NULL DEFAULT 0 COMMENT '0=non traité, 1=en cours, 2=traité',
        created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_file_hash (file_hash)
    ) $charset;";

    $sql_data = "CREATE TABLE IF NOT EXISTS `$data_table` (
        id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        file_id      INT UNSIGNED NOT NULL,
        title        TEXT,
        subtitle     TEXT,
        date_string  VARCHAR(255),
        date_publish DATE,
        detail_link  TEXT,
        img          VARCHAR(500),
        status       TINYINT NOT NULL DEFAULT 1 COMMENT '0=non publié, 1=publié',
        created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_date_publish (date_publish),
        KEY idx_status (status)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql_files );
    dbDelta( $sql_data );
}

function xnf_create_dirs() {
    $dirs = [ XNF_XML_DIR, XNF_XML_BACKUP, XNF_IMG_DIR ];
    foreach ( $dirs as $dir ) {
        if ( ! is_dir( $dir ) ) {
            wp_mkdir_p( $dir );
        }
        $htaccess = $dir . '.htaccess';
        if ( ! file_exists( $htaccess ) ) {
            // Autoriser accès aux images, bloquer les XML/PHP
            $rules = ( $dir === XNF_IMG_DIR )
                ? "Options -Indexes\n"
                : "Options -Indexes\nDeny from all\n";
            file_put_contents( $htaccess, $rules );
        }
        $index = $dir . 'index.php';
        if ( ! file_exists( $index ) ) {
            file_put_contents( $index, '<?php // Silence is golden' );
        }
    }
}

function xnf_set_default_options() {
    $defaults = [
        'xnf_limit'        => 10,
        'xnf_show_title'   => 1,
        'xnf_show_subtitle'=> 1,
        'xnf_show_date'    => 1,
        'xnf_show_img'     => 1,
        'xnf_show_link'    => 1,
    ];
    foreach ( $defaults as $key => $val ) {
        if ( false === get_option( $key ) ) {
            add_option( $key, $val );
        }
    }
}
