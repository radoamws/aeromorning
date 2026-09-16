<?php
/**
 * Plugin Name: XML News Feed
 * Plugin URI:  #
 * Description: Importe des fichiers XML PR Newswire depuis /xml, stocke les données et les affiche en frontend via shortcode.
 * Version:     1.0.0
 * Author:      Votre Nom
 * License:     GPL2
 */

defined( 'ABSPATH' ) || exit;

define( 'XNF_VERSION',    '1.0.0' );
define( 'XNF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'XNF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'XNF_XML_DIR',    XNF_PLUGIN_DIR . 'xml/' );
define( 'XNF_XML_BACKUP', XNF_PLUGIN_DIR . 'xml_backup/' );
define( 'XNF_IMG_DIR',    XNF_PLUGIN_DIR . 'images/' );
define( 'XNF_IMG_URL',    XNF_PLUGIN_URL . 'images/' );
define( 'XNF_CRON_TOKEN', hash( 'sha256', AUTH_KEY . 'xnf_cron_v1' ) );

require_once XNF_PLUGIN_DIR . 'includes/install.php';
require_once XNF_PLUGIN_DIR . 'includes/scanner.php';
require_once XNF_PLUGIN_DIR . 'includes/admin.php';
require_once XNF_PLUGIN_DIR . 'includes/frontend.php';
require_once XNF_PLUGIN_DIR . 'includes/cron-endpoints.php';

register_activation_hook( __FILE__, 'xnf_activate' );
register_deactivation_hook( __FILE__, 'xnf_deactivate' );
