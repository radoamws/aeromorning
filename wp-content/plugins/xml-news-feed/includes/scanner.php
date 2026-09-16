<?php
defined( 'ABSPATH' ) || exit;

/**
 * Scanne /xml, insère les nouveaux fichiers dans t_xml_files,
 * puis parse et importe les données dans t_xml_data.
 */
function xnf_scan_and_import() {
    global $wpdb;
    $files_table = $wpdb->base_prefix . 't_xml_files';
    $data_table  = $wpdb->base_prefix . 't_xml_data';

    if ( ! is_dir( XNF_XML_DIR ) ) {
        return [ 'error' => 'Répertoire /xml introuvable.' ];
    }

    $files = glob( XNF_XML_DIR . '*.xml' );
    if ( empty( $files ) ) {
        return [ 'info' => 'Aucun fichier XML trouvé.' ];
    }

    $results = [];
    foreach ( $files as $filepath ) {
        $filename  = basename( $filepath );
        $file_hash = sha1_file( $filepath );

        // Vérifier si déjà connu
        $existing = $wpdb->get_row(
            $wpdb->prepare( "SELECT id, status FROM `$files_table` WHERE file_hash = %s", $file_hash )
        );

        if ( $existing ) {
            if ( (int) $existing->status === 2 ) {
                $results[] = "$filename : déjà traité.";
                continue;
            }
            $file_id = (int) $existing->id;
        } else {
            $wpdb->insert( $files_table, [
                'filename'  => $filename,
                'filepath'  => $filepath,
                'file_hash' => $file_hash,
                'status'    => 0,
            ] );
            $file_id = $wpdb->insert_id;
        }

        // Marquer en cours
        $wpdb->update( $files_table, [ 'status' => 1 ], [ 'id' => $file_id ] );

        // Parser le XML
        $data = xnf_parse_xml( $filepath, $file_id );
        if ( is_wp_error( $data ) ) {
            $results[] = "$filename : erreur de parsing – " . $data->get_error_message();
            $wpdb->update( $files_table, [ 'status' => 0 ], [ 'id' => $file_id ] );
            continue;
        }

        // Insérer les données (éviter les doublons par file_id)
        $existing_data = $wpdb->get_var(
            $wpdb->prepare( "SELECT id FROM `$data_table` WHERE file_id = %d", $file_id )
        );
        if ( ! $existing_data ) {
            $wpdb->insert( $data_table, $data );
        }

        // Marquer traité
        $wpdb->update( $files_table, [ 'status' => 2 ], [ 'id' => $file_id ] );
        $results[] = "$filename : importé avec succès.";
    }

    return $results;
}

/**
 * Parse un fichier XML NewsML et retourne un tableau prêt pour insertion en BDD.
 */
function xnf_parse_xml( $filepath, $file_id ) {
    libxml_use_internal_errors( true );
    $xml = simplexml_load_file( $filepath );
    if ( ! $xml ) {
        $errors = libxml_get_errors();
        libxml_clear_errors();
        return new WP_Error( 'xml_parse', 'Impossible de lire le fichier XML.' );
    }

    // Namespaces potentiels – utiliser une approche robuste
    $ns = $xml->getNamespaces( true );

    // NewsLines
    $headline    = '';
    $subheadline = '';
    $dateline    = '';
    $date_id_raw = '';

    // Chercher dans NewsComponent/NewsLines
    $newslines = $xml->xpath( '//NewsLines' );
    if ( ! empty( $newslines ) ) {
        $nl          = $newslines[0];
        $headline    = (string) $nl->HeadLine;
        $subheadline = (string) $nl->SubHeadLine;
        $dateline    = (string) $nl->DateLine;
    }

    // DateId
    $date_ids = $xml->xpath( '//DateId' );
    if ( ! empty( $date_ids ) ) {
        $date_id_raw = (string) $date_ids[0];
    }
    $date_publish = xnf_parse_date( $date_id_raw );

    // ContentItem HTML
    $content_items = $xml->xpath( '//ContentItem/DataContent' );
    $html_content  = '';
    if ( ! empty( $content_items ) ) {
        // Le contenu est XHTML embarqué – le récupérer comme chaîne brute
        $raw = $xml->xpath( '//ContentItem/DataContent' );
        if ( $raw ) {
            $html_content = (string) $raw[0]->asXML();
            // Extraire le HTML intérieur entre les commentaires
            if ( preg_match( '/<!-- start embedded XHTML document -->(.*?)<!-- end embedded XHTML document -->/si', $html_content, $m ) ) {
                $html_content = $m[1];
            }
        }
    }

    // Fallback : lire la DataContent comme texte brut (CDATA)
    if ( empty( $html_content ) ) {
        $data_contents = $xml->xpath( '//ContentItem/DataContent' );
        if ( ! empty( $data_contents ) ) {
            $html_content = (string) $data_contents[0];
        }
    }

    // Lire le fichier brut pour extraire l'HTML embarqué de manière fiable
    $raw_file = file_get_contents( $filepath );
    $html_content_clean = '';
    if ( preg_match( '/<!-- start embedded XHTML document -->(.*?)<!-- end embedded XHTML document -->/si', $raw_file, $m2 ) ) {
        $html_content_clean = trim( $m2[1] );
    }

    $detail_link = '';
    $img         = '';

    if ( $html_content_clean ) {
        // Extraire detail_link depuis <a id="PRNURL" ...>
        if ( preg_match( '/<a[^>]+id=["\']PRNURL["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html_content_clean, $lm ) ) {
            $detail_link = esc_url_raw( $lm[1] );
        }
        if ( empty( $detail_link ) ) {
            if ( preg_match( '/<a[^>]+href=["\']([^"\']+)["\'][^>]+id=["\']PRNURL["\'][^>]*>/i', $html_content_clean, $lm ) ) {
                $detail_link = esc_url_raw( $lm[1] );
            }
        }

        // Extraire image depuis DivAssetPlaceHolder1
        if ( preg_match( '/<div[^>]+id=["\']DivAssetPlaceHolder1["\'][^>]*>.*?<img[^>]+src=["\']([^"\']+)["\'][^>]*>/si', $html_content_clean, $im ) ) {
            $img_url  = $im[1];
            $img_name = xnf_download_image( $img_url );
            $img      = $img_name ?: '';
        }
    }

    return [
        'file_id'      => $file_id,
        'title'        => wp_strip_all_tags( $headline ),
        'subtitle'     => wp_strip_all_tags( $subheadline ),
        'date_string'  => wp_strip_all_tags( $dateline ),
        'date_publish' => $date_publish,
        'detail_link'  => $detail_link,
        'img'          => $img,
        'status'       => 1,
    ];
}

/**
 * Convertit une DateId (ex: 20210610) en format DATE SQL.
 */
function xnf_parse_date( $raw ) {
    $raw = preg_replace( '/[^0-9]/', '', $raw );
    if ( strlen( $raw ) >= 8 ) {
        return substr( $raw, 0, 4 ) . '-' . substr( $raw, 4, 2 ) . '-' . substr( $raw, 6, 2 );
    }
    return null;
}

/**
 * Télécharge une image depuis une URL et la sauvegarde dans /images/ du plugin.
 * Retourne le nom du fichier local ou false en cas d'échec.
 */
function xnf_download_image( $url ) {
    if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
        return false;
    }

    // Vérifier que l'URL est HTTPS et domaine attendu (sécurité)
    $allowed_hosts = [ 'mma.prnewswire.com', 'c212.net' ];
    $host = parse_url( $url, PHP_URL_HOST );
    // Note: on accepte tout domaine externe mais on sécurise le nom de fichier
    // Pour restreindre, décommenter : if ( ! in_array( $host, $allowed_hosts ) ) return false;

    $ext = pathinfo( parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION );
    $ext = preg_replace( '/[^a-z0-9]/i', '', strtolower( $ext ) );
    if ( ! in_array( $ext, [ 'jpg', 'jpeg', 'png', 'gif', 'webp' ] ) ) {
        $ext = 'jpg';
    }

    $unique_name = wp_unique_filename( XNF_IMG_DIR, sanitize_file_name( basename( parse_url( $url, PHP_URL_PATH ) ) ) );
    // Ajouter un hash pour unicité absolue
    $unique_name = md5( $url ) . '_' . time() . '.' . $ext;
    $dest        = XNF_IMG_DIR . $unique_name;

    // Utiliser wp_remote_get
    $response = wp_remote_get( $url, [ 'timeout' => 15, 'sslverify' => true ] );
    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
        return false;
    }

    $body = wp_remote_retrieve_body( $response );
    if ( empty( $body ) ) {
        return false;
    }

    // Validation MIME basique
    $finfo = new finfo( FILEINFO_MIME_TYPE );
    $mime  = $finfo->buffer( $body );
    if ( ! in_array( $mime, [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ] ) ) {
        return false;
    }

    file_put_contents( $dest, $body );
    return $unique_name;
}
