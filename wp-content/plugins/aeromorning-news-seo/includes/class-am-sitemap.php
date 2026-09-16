<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates /sitemap-news.xml per Google News sitemap spec:
 * https://www.google.com/schemas/sitemap-news/0.9
 *
 * Only articles published within the configured freshness window (default
 * 48h) are listed, per Google's own recommendation — a news sitemap is not
 * meant to be a full archive, that's what the regular XML sitemap is for.
 */
class AM_Sitemap {

	const TRANSIENT_KEY = 'am_news_sitemap_xml';
	const QUERY_VAR      = 'am_news_sitemap';
	const MAX_URLS       = 1000; // Hard limit imposed by the Google News sitemap spec.

	/** @var AM_Settings */
	private $settings;

	public function __construct( AM_Settings $settings ) {
		$this->settings = $settings;
	}

	public function init() {
		add_action( 'init', array( $this, 'add_rewrite_rule' ) );
		add_filter( 'query_vars', array( $this, 'add_query_var' ) );
		add_action( 'template_redirect', array( $this, 'maybe_render' ) );
		add_filter( 'robots_txt', array( $this, 'add_to_robots' ), 10, 2 );

		// WordPress's own canonical-redirect logic doesn't recognise this
		// custom query var as a "file" endpoint and normally 301s it to a
		// trailing-slash URL (…/sitemap-news.xml/). Google (and any
		// other XML consumer) should never have to follow a redirect to
		// read the sitemap, so bypass canonical redirection entirely for
		// this one request.
		add_filter( 'redirect_canonical', array( $this, 'bypass_canonical_redirect' ) );

		// Bust the cached XML whenever a relevant post changes state.
		add_action( 'save_post', array( $this, 'invalidate_cache' ) );
		add_action( 'transition_post_status', array( $this, 'invalidate_cache_on_transition' ), 10, 3 );
		add_action( 'delete_post', array( $this, 'invalidate_cache' ) );

		// If Yoast SEO's own sitemap index is present, list our news
		// sitemap in it too so it's discoverable without a manual submit.
		add_filter( 'wpseo_sitemap_index', array( $this, 'add_to_yoast_sitemap_index' ) );
	}

	public function bypass_canonical_redirect( $redirect_url ) {
		if ( get_query_var( self::QUERY_VAR ) ) {
			return false;
		}
		return $redirect_url;
	}

	public function add_rewrite_rule() {
		if ( ! AM_News_SEO::get_option( 'enable_sitemap' ) ) {
			return;
		}
		// Deliberately NOT "news-sitemap.xml": Yoast SEO registers its own
		// catch-all rule `^([^/]+?)-sitemap([0-9]+)?\.xml$` (top priority)
		// for its post-type/taxonomy sitemaps. "news-sitemap.xml" matches
		// that pattern too (captures "news" as the sitemap type), so Yoast
		// claims the request first, finds no "news" provider, and 404s —
		// regardless of our own rule also being registered correctly. Any
		// filename ending in "-sitemap.xml" collides the same way, so this
		// URL is shaped to end in "-news.xml" instead, which Yoast's regex
		// cannot match.
		add_rewrite_rule( '^sitemap-news\.xml$', 'index.php?' . self::QUERY_VAR . '=1', 'top' );
	}

	public function add_query_var( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	public function invalidate_cache() {
		delete_transient( self::TRANSIENT_KEY );
	}

	public function invalidate_cache_on_transition( $new_status, $old_status, $post ) {
		if ( 'publish' === $new_status || 'publish' === $old_status ) {
			$this->invalidate_cache();
		}
	}

	public function add_to_robots( $output, $public ) {
		if ( '1' == $public && AM_News_SEO::get_option( 'enable_sitemap' ) ) {
			$output .= "\nSitemap: " . home_url( '/sitemap-news.xml' ) . "\n";
		}
		return $output;
	}

	public function add_to_yoast_sitemap_index( $index ) {
		if ( ! AM_News_SEO::get_option( 'enable_sitemap' ) ) {
			return $index;
		}
		$url    = home_url( '/sitemap-news.xml' );
		$index .= '<sitemap><loc>' . esc_url( $url ) . '</loc><lastmod>' . esc_html( date( 'c' ) ) . '</lastmod></sitemap>' . "\n";
		return $index;
	}

	public function maybe_render() {
		if ( ! AM_News_SEO::get_option( 'enable_sitemap' ) || ! get_query_var( self::QUERY_VAR ) ) {
			return;
		}

		$cached = get_transient( self::TRANSIENT_KEY );
		if ( false === $cached ) {
			$cached = $this->build_xml();
			$minutes = (int) AM_News_SEO::get_option( 'cache_minutes', 15 );
			set_transient( self::TRANSIENT_KEY, $cached, max( 1, $minutes ) * MINUTE_IN_SECONDS );
		}

		header( 'Content-Type: application/xml; charset=UTF-8' );
		echo $cached; // phpcs:ignore WordPress.Security.EscapeOutput -- already-escaped XML built in build_xml().
		exit;
	}

	private function build_xml() {
		$options    = AM_News_SEO::get_options();
		$post_types = ! empty( $options['post_types'] ) ? $options['post_types'] : array( 'post' );
		$hours      = max( 1, (int) $options['max_age_hours'] );

		$args = array(
			'post_type'              => $post_types,
			'post_status'            => 'publish',
			'posts_per_page'         => self::MAX_URLS,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'date_query'             => array(
				array(
					'after'     => gmdate( 'Y-m-d H:i:s', time() - $hours * HOUR_IN_SECONDS ),
					'column'    => 'post_date_gmt',
					'inclusive' => true,
				),
			),
			'meta_query'             => array(
				array(
					'key'     => AM_NEWS_SEO_META_PREFIX . 'exclude',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		if ( ! empty( $options['exclude_cats'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'category',
					'field'    => 'term_id',
					'terms'    => $options['exclude_cats'],
					'operator' => 'NOT IN',
				),
			);
		}

		$query = new WP_Query( $args );

		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
		$xml .= 'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9" ';
		$xml .= 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

		foreach ( $query->posts as $post ) {
			// Second pass, explicit: meta_query NOT EXISTS misses posts
			// where the meta value was saved as an empty string.
			$excluded = get_post_meta( $post->ID, AM_NEWS_SEO_META_PREFIX . 'exclude', true );
			if ( '1' === $excluded ) {
				continue;
			}
			$xml .= $this->url_entry( $post, $options );
		}

		$xml .= '</urlset>';

		wp_reset_postdata();

		return $xml;
	}

	/**
	 * Makes a string safe to place inside a CDATA section (or anywhere
	 * else in the document): strips XML 1.0 control characters that are
	 * illegal even inside CDATA (e.g. a stray NUL byte that ended up in a
	 * post title from a bad import/copy-paste — CDATA only protects
	 * against markup delimiters, not against invalid characters), and
	 * escapes the literal "]]>" sequence that would otherwise terminate
	 * the section early.
	 */
	private function cdata_safe( $string ) {
		$string = (string) $string;
		// Strip C0 control chars except tab/LF/CR, plus DEL — these bytes
		// never collide with UTF-8 continuation/lead bytes (all >= 0x80),
		// so this is safe to run without a unicode regex mode.
		$string = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string );
		return str_replace( ']]>', ']] >', $string );
	}

	private function url_entry( $post, $options ) {
		$loc  = esc_url( $this->cdata_safe( get_permalink( $post ) ) );
		$date = get_post_time( 'c', false, $post );
		$title = $this->cdata_safe( get_the_title( $post ) );

		$keywords = get_post_meta( $post->ID, AM_NEWS_SEO_META_PREFIX . 'keywords', true );
		if ( '' === $keywords ) {
			$cats = get_the_category( $post->ID );
			if ( ! empty( $cats ) ) {
				$keywords = implode( ', ', wp_list_pluck( $cats, 'name' ) );
			}
		}
		$keywords = $this->cdata_safe( $keywords );

		$genres  = (array) get_post_meta( $post->ID, AM_NEWS_SEO_META_PREFIX . 'genres', true );
		$tickers = $this->cdata_safe( get_post_meta( $post->ID, AM_NEWS_SEO_META_PREFIX . 'stock_tickers', true ) );
		$publication_name = $this->cdata_safe( $options['publication_name'] );

		$entry  = "\t<url>\n";
		$entry .= "\t\t<loc>{$loc}</loc>\n";
		$entry .= "\t\t<news:news>\n";
		$entry .= "\t\t\t<news:publication>\n";
		$entry .= "\t\t\t\t<news:name><![CDATA[" . $publication_name . "]]></news:name>\n";
		$entry .= "\t\t\t\t<news:language>" . esc_html( $options['language'] ) . "</news:language>\n";
		$entry .= "\t\t\t</news:publication>\n";
		$entry .= "\t\t\t<news:publication_date>" . esc_html( $date ) . "</news:publication_date>\n";
		$entry .= "\t\t\t<news:title><![CDATA[" . $title . "]]></news:title>\n";

		if ( ! empty( $genres ) ) {
			$entry .= "\t\t\t<news:genres><![CDATA[" . $this->cdata_safe( implode( ', ', $genres ) ) . "]]></news:genres>\n";
		}
		if ( '' !== $keywords ) {
			$entry .= "\t\t\t<news:keywords><![CDATA[" . $keywords . "]]></news:keywords>\n";
		}
		if ( '' !== $tickers ) {
			$entry .= "\t\t\t<news:stock_tickers><![CDATA[" . $tickers . "]]></news:stock_tickers>\n";
		}

		$entry .= "\t\t</news:news>\n";

		if ( ! empty( $options['show_images'] ) && has_post_thumbnail( $post ) ) {
			$image_url = get_the_post_thumbnail_url( $post, 'full' );
			if ( $image_url ) {
				$entry .= "\t\t<image:image>\n";
				$entry .= "\t\t\t<image:loc>" . esc_url( $image_url ) . "</image:loc>\n";
				$entry .= "\t\t</image:image>\n";
			}
		}

		$entry .= "\t</url>\n";

		return $entry;
	}
}
