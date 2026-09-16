<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Per-post controls, mirroring the fields Yoast News SEO adds to the post
 * editor: exclude-from-Google-News, genres, keywords, stock tickers.
 */
class AM_Metabox {

	const NONCE_ACTION = 'am_news_metabox_save';
	const NONCE_NAME   = 'am_news_metabox_nonce';

	/** Valid Google News <news:genres> values. */
	const GENRES = array( 'PressRelease', 'Satire', 'Blog', 'OpEd', 'Opinion', 'UserGenerated' );

	/** @var AM_Settings */
	private $settings;

	public function __construct( AM_Settings $settings ) {
		$this->settings = $settings;
	}

	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'add_box' ) );
		add_action( 'save_post', array( $this, 'save' ) );
	}

	private function get_enabled_post_types() {
		return AM_News_SEO::get_option( 'post_types', array( 'post' ) );
	}

	public function add_box() {
		foreach ( $this->get_enabled_post_types() as $post_type ) {
			add_meta_box(
				'am_news_seo_box',
				__( 'Google News SEO', 'aeromorning-news-seo' ),
				array( $this, 'render' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	public function render( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$exclude  = get_post_meta( $post->ID, AM_NEWS_SEO_META_PREFIX . 'exclude', true );
		$genres   = (array) get_post_meta( $post->ID, AM_NEWS_SEO_META_PREFIX . 'genres', true );
		$keywords = get_post_meta( $post->ID, AM_NEWS_SEO_META_PREFIX . 'keywords', true );
		$tickers  = get_post_meta( $post->ID, AM_NEWS_SEO_META_PREFIX . 'stock_tickers', true );
		?>
		<p>
			<label>
				<input type="checkbox" name="am_news_exclude" value="1" <?php checked( $exclude, '1' ); ?> />
				<?php esc_html_e( 'Exclude from Google News sitemap & NewsArticle schema', 'aeromorning-news-seo' ); ?>
			</label>
		</p>
		<p><strong><?php esc_html_e( 'Genres', 'aeromorning-news-seo' ); ?></strong><br />
			<?php foreach ( self::GENRES as $genre ) : ?>
				<label style="display:inline-block;width:48%;">
					<input type="checkbox" name="am_news_genres[]" value="<?php echo esc_attr( $genre ); ?>" <?php checked( in_array( $genre, $genres, true ) ); ?> />
					<?php echo esc_html( $genre ); ?>
				</label>
			<?php endforeach; ?>
			<br /><span class="description"><?php esc_html_e( 'Leave all unchecked for a standard news article.', 'aeromorning-news-seo' ); ?></span>
		</p>
		<p>
			<label for="am_news_keywords"><strong><?php esc_html_e( 'Keywords', 'aeromorning-news-seo' ); ?></strong></label>
			<input type="text" id="am_news_keywords" name="am_news_keywords" class="widefat" value="<?php echo esc_attr( $keywords ); ?>" placeholder="<?php esc_attr_e( 'comma, separated, terms', 'aeromorning-news-seo' ); ?>" />
			<span class="description"><?php esc_html_e( 'Optional. Falls back to this post\'s categories if left empty.', 'aeromorning-news-seo' ); ?></span>
		</p>
		<p>
			<label for="am_news_stock_tickers"><strong><?php esc_html_e( 'Stock tickers', 'aeromorning-news-seo' ); ?></strong></label>
			<input type="text" id="am_news_stock_tickers" name="am_news_stock_tickers" class="widefat" value="<?php echo esc_attr( $tickers ); ?>" placeholder="NASDAQ:BA, EPA:AIR" />
			<span class="description"><?php esc_html_e( 'Optional. Format: EXCHANGE:SYMBOL, comma separated.', 'aeromorning-news-seo' ); ?></span>
		</p>
		<?php
	}

	public function save( $post_id ) {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( wp_unslash( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! in_array( get_post_type( $post_id ), $this->get_enabled_post_types(), true ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, AM_NEWS_SEO_META_PREFIX . 'exclude', ! empty( $_POST['am_news_exclude'] ) ? '1' : '' );

		$genres = array();
		if ( ! empty( $_POST['am_news_genres'] ) && is_array( $_POST['am_news_genres'] ) ) {
			foreach ( wp_unslash( $_POST['am_news_genres'] ) as $genre ) {
				if ( in_array( $genre, self::GENRES, true ) ) {
					$genres[] = $genre;
				}
			}
		}
		update_post_meta( $post_id, AM_NEWS_SEO_META_PREFIX . 'genres', $genres );

		$keywords = isset( $_POST['am_news_keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['am_news_keywords'] ) ) : '';
		update_post_meta( $post_id, AM_NEWS_SEO_META_PREFIX . 'keywords', $keywords );

		$tickers = isset( $_POST['am_news_stock_tickers'] ) ? sanitize_text_field( wp_unslash( $_POST['am_news_stock_tickers'] ) ) : '';
		update_post_meta( $post_id, AM_NEWS_SEO_META_PREFIX . 'stock_tickers', $tickers );

		// The sitemap XML is cached; a saved post can change which articles
		// belong in the freshness window, so drop the cache immediately.
		delete_transient( AM_Sitemap::TRANSIENT_KEY );
	}
}
