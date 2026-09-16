<?php
/**
 * Fires only on "Delete" from the Plugins screen (not on deactivate).
 * Removes the plugin's own settings row on every site; deliberately leaves
 * the per-post meta (_am_news_exclude, _am_news_genres, ...) in place —
 * with ~15k articles a bulk meta delete is expensive and that data is
 * harmless if the plugin is reinstalled later.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

function am_news_seo_uninstall_site() {
	delete_option( 'am_news_seo_options' );
	delete_option( 'am_news_seo_flush_needed' );
	delete_transient( 'am_news_sitemap_xml' );
}

if ( is_multisite() ) {
	$site_ids = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		am_news_seo_uninstall_site();
		restore_current_blog();
	}
} else {
	am_news_seo_uninstall_site();
}
