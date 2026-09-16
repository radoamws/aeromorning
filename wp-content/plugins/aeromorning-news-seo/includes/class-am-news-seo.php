<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core loader / singleton. Wires up settings, metabox, sitemap and schema
 * modules and exposes the per-site option helpers everything else uses.
 */
final class AM_News_SEO {

	/** @var AM_News_SEO|null */
	private static $instance = null;

	/** @var AM_Settings */
	public $settings;

	/** @var AM_Metabox */
	public $metabox;

	/** @var AM_Sitemap */
	public $sitemap;

	/** @var AM_Schema */
	public $schema;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		load_plugin_textdomain( 'aeromorning-news-seo', false, dirname( plugin_basename( AM_NEWS_SEO_FILE ) ) . '/languages' );

		$this->settings = new AM_Settings();
		$this->metabox  = new AM_Metabox( $this->settings );
		$this->sitemap  = new AM_Sitemap( $this->settings );
		$this->schema   = new AM_Schema( $this->settings );

		$this->settings->init();
		$this->metabox->init();
		$this->sitemap->init();
		$this->schema->init();
	}

	/**
	 * Convenience accessor used across the plugin: returns this SITE's
	 * options (never the network's), merged with defaults so every key is
	 * always present even before the settings page has been saved once.
	 *
	 * @return array
	 */
	public static function get_options() {
		$saved = get_option( AM_NEWS_SEO_OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::default_options() );
	}

	public static function get_option( $key, $default = null ) {
		$options = self::get_options();
		return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
	}

	/**
	 * Defaults are deliberately derived from the current site's own data
	 * (site name, locale) so FR and EN sub-sites get sane, distinct values
	 * out of the box without any manual setup.
	 *
	 * @return array
	 */
	public static function default_options() {
		return array(
			'enable_sitemap'   => true,
			'enable_schema'    => true,
			'publication_name' => get_bloginfo( 'name' ),
			'language'         => self::guess_language_code(),
			'post_types'       => array( 'post' ),
			'exclude_cats'     => array(),
			'max_age_hours'    => 48,
			'show_images'      => true,
			'cache_minutes'    => 15,
			'publisher_logo'   => '',
		);
	}

	/**
	 * Maps the site's WP locale to the 2-3 letter language code Google
	 * News expects in <news:language> (ISO 639, with the couple of
	 * exceptions Google documents, e.g. zh-cn/zh-tw).
	 *
	 * @return string
	 */
	public static function guess_language_code() {
		$locale = get_locale();
		$map    = array(
			'zh_CN' => 'zh-cn',
			'zh_TW' => 'zh-tw',
		);
		if ( isset( $map[ $locale ] ) ) {
			return $map[ $locale ];
		}
		$parts = explode( '_', $locale );
		return strtolower( $parts[0] );
	}
}
