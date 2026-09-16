<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activation/deactivation across a multisite network, and auto-setup for
 * any site added to the network later while the plugin is network-active.
 *
 * Every site keeps its own AM_NEWS_SEO_OPTION row (default WordPress
 * behaviour — no cross-site option sharing), so activating network-wide
 * simply means "make sure every site's rewrite rules are flushed and its
 * defaults are seeded", not "share one config across FR and EN".
 */
class AM_Multisite {

	public static function activate( $network_wide ) {
		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id );
				self::activate_single_site();
				restore_current_blog();
			}
			add_action( 'wp_initialize_site', array( __CLASS__, 'activate_new_site' ), 10, 1 );
		} else {
			self::activate_single_site();
		}
	}

	public static function activate_new_site( $new_site ) {
		switch_to_blog( (int) $new_site->blog_id );
		self::activate_single_site();
		restore_current_blog();
	}

	private static function activate_single_site() {
		$existing = get_option( AM_NEWS_SEO_OPTION );
		if ( false === $existing ) {
			add_option( AM_NEWS_SEO_OPTION, AM_News_SEO::default_options() );
		}
		// Rewrite rules can only be (re)flushed once the rule itself has
		// been registered, so just mark the flag; class-am-settings.php's
		// admin_notices hook performs the actual flush on next admin load,
		// and we also flush directly here for the CLI/wp-admin activation
		// request itself.
		update_option( 'am_news_seo_flush_needed', 1 );
	}

	public static function deactivate( $network_wide ) {
		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id );
				flush_rewrite_rules( false );
				restore_current_blog();
			}
		} else {
			flush_rewrite_rules( false );
		}
	}
}
