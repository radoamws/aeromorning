<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NewsArticle structured data.
 *
 * If Yoast SEO is active, we don't emit a second competing JSON-LD graph —
 * we hook into Yoast's own Article schema generator and switch its @type
 * from Article to NewsArticle (exactly what the paid Yoast News SEO add-on
 * does). If Yoast is not active, we output a minimal standalone NewsArticle
 * JSON-LD block ourselves.
 */
class AM_Schema {

	/** @var AM_Settings */
	private $settings;

	public function __construct( AM_Settings $settings ) {
		$this->settings = $settings;
	}

	public function init() {
		if ( ! AM_News_SEO::get_option( 'enable_schema' ) ) {
			return;
		}

		if ( defined( 'WPSEO_VERSION' ) ) {
			// Yoast SEO (Free or Premium) is active: piggyback on its graph.
			add_filter( 'wpseo_schema_article_type', array( $this, 'filter_yoast_type' ) );
			add_filter( 'wpseo_schema_article', array( $this, 'filter_yoast_article' ), 20 );
		} else {
			// No Yoast: output our own minimal NewsArticle JSON-LD.
			add_action( 'wp_head', array( $this, 'render_standalone_jsonld' ) );
		}
	}

	private function is_eligible_singular() {
		if ( ! is_singular() ) {
			return false;
		}
		$post_types = AM_News_SEO::get_option( 'post_types', array( 'post' ) );
		if ( ! is_singular( $post_types ) ) {
			return false;
		}
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return false;
		}
		if ( '1' === get_post_meta( $post_id, AM_NEWS_SEO_META_PREFIX . 'exclude', true ) ) {
			return false;
		}
		$excluded_cats = AM_News_SEO::get_option( 'exclude_cats', array() );
		if ( ! empty( $excluded_cats ) && is_singular( 'post' ) ) {
			$post_cats = wp_get_post_categories( $post_id );
			if ( array_intersect( $post_cats, $excluded_cats ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * `wpseo_schema_article_type` doesn't reliably receive the post/context
	 * as an argument in every Yoast version, so eligibility is decided from
	 * the current queried object instead (reliable: this filter only ever
	 * runs while Yoast is rendering the schema for the page being viewed).
	 */
	public function filter_yoast_type( $type ) {
		return $this->is_eligible_singular() ? 'NewsArticle' : $type;
	}

	public function filter_yoast_article( $data ) {
		if ( $this->is_eligible_singular() && is_array( $data ) ) {
			$data['@type'] = 'NewsArticle';
		}
		return $data;
	}

	public function render_standalone_jsonld() {
		if ( ! $this->is_eligible_singular() ) {
			return;
		}

		$post = get_post();

		$images = array();
		if ( has_post_thumbnail( $post ) ) {
			$thumb_id = get_post_thumbnail_id( $post );
			foreach ( array( 'full' ) as $size ) {
				$src = wp_get_attachment_image_src( $thumb_id, $size );
				if ( $src ) {
					$images[] = $src[0];
				}
			}
		}

		$logo_url = AM_News_SEO::get_option( 'publisher_logo' );
		if ( ! $logo_url ) {
			$logo_url = get_site_icon_url( 600 );
		}

		$author_name = get_the_author_meta( 'display_name', $post->post_author );

		$headline = get_the_title( $post );
		if ( function_exists( 'mb_strlen' ) && mb_strlen( $headline ) > 110 ) {
			$headline = mb_substr( $headline, 0, 109 ) . '…';
		}

		$data = array(
			'@context'         => 'https://schema.org',
			'@type'            => 'NewsArticle',
			'mainEntityOfPage' => array(
				'@type' => 'WebPage',
				'@id'   => get_permalink( $post ),
			),
			'headline'         => $headline,
			'datePublished'    => get_the_date( 'c', $post ),
			'dateModified'     => get_the_modified_date( 'c', $post ),
			'author'           => array(
				array(
					'@type' => 'Person',
					'name'  => $author_name ? $author_name : get_bloginfo( 'name' ),
				),
			),
			'publisher'        => array(
				'@type' => 'Organization',
				'name'  => AM_News_SEO::get_option( 'publication_name' ),
			),
		);

		if ( ! empty( $images ) ) {
			$data['image'] = $images;
		}

		if ( $logo_url ) {
			$data['publisher']['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $logo_url,
			);
		}

		/**
		 * Let a theme or another plugin tweak the standalone NewsArticle
		 * payload (e.g. add articleSection, keywords) before it's printed.
		 */
		$data = apply_filters( 'am_news_seo_standalone_schema', $data, $post );

		echo "\n<script type=\"application/ld+json\">" . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- JSON-LD, not HTML.
	}
}
