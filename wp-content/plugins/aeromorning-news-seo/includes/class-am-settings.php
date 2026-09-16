<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Per-site settings screen (Settings API, single option row).
 * Runs on every site of the network independently — there is no shared
 * network option, on purpose: FR and EN need different publication names,
 * languages and (potentially) post types/categories.
 */
class AM_Settings {

	const OPTION_GROUP = 'am_news_seo_group';
	const PAGE_SLUG    = 'am-news-seo';

	public function init() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'maybe_handle_manual_flush' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_notices' ) );
	}

	/**
	 * "Flush rewrite rules now" button handler — a manual escape hatch in
	 * case the automatic post-save flush didn't take (stale object cache,
	 * a redirect the browser didn't complete, etc.). Always does a HARD
	 * flush, same as visiting Settings > Permalinks > Save.
	 */
	public function maybe_handle_manual_flush() {
		if ( ! isset( $_GET['am_news_flush'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'am_news_flush' );
		flush_rewrite_rules( true );
		delete_transient( AM_Sitemap::TRANSIENT_KEY );
		delete_option( 'am_news_seo_flush_needed' );
		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE_SLUG, 'am_news_flushed' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Is our rewrite rule actually present in the site's current,
	 * persisted rewrite_rules option, AND does it actually match the live
	 * sitemap URL, AND does it win over any earlier, broader rule (e.g. a
	 * catch-all sitemap pattern from another SEO plugin)? Checking only
	 * "does a rule mentioning our query var exist" is not enough: a stale
	 * rule from a previous version of this plugin (different filename,
	 * same query var) would still match that check while being useless —
	 * so this walks the rules IN ORDER and confirms ours is the first one
	 * to match "sitemap-news.xml", exactly like WP::parse_request() would.
	 *
	 * @return bool
	 */
	public function is_rule_registered() {
		$rules = get_option( 'rewrite_rules' );
		if ( ! is_array( $rules ) ) {
			return false;
		}
		foreach ( $rules as $pattern => $rewrite ) {
			if ( ! preg_match( '#^' . str_replace( '#', '\#', $pattern ) . '#', 'sitemap-news.xml' ) ) {
				continue;
			}
			// First pattern (in registration order) that matches the URL —
			// this is the one WordPress will actually route to.
			return false !== strpos( $rewrite, AM_Sitemap::QUERY_VAR );
		}
		return false;
	}

	public function add_menu() {
		add_menu_page(
			__( 'AeroMorning News SEO', 'aeromorning-news-seo' ),
			__( 'News SEO', 'aeromorning-news-seo' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			'dashicons-megaphone',
			99
		);
	}

	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			AM_NEWS_SEO_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => AM_News_SEO::default_options(),
			)
		);
	}

	/**
	 * Public post types eligible to appear in the news sitemap / carry
	 * NewsArticle schema. Attachments and anything not publicly queryable
	 * are excluded — a news sitemap only ever lists articles.
	 *
	 * @return array<string,string> slug => label
	 */
	public function get_eligible_post_types() {
		$types  = get_post_types( array( 'public' => true ), 'objects' );
		$out    = array();
		unset( $types['attachment'] );
		foreach ( $types as $slug => $obj ) {
			$out[ $slug ] = $obj->labels->name;
		}
		return $out;
	}

	public function sanitize( $input ) {
		$defaults = AM_News_SEO::default_options();
		$clean    = array();

		$clean['enable_sitemap'] = ! empty( $input['enable_sitemap'] );
		$clean['enable_schema']  = ! empty( $input['enable_schema'] );
		$clean['show_images']    = ! empty( $input['show_images'] );

		$clean['publication_name'] = isset( $input['publication_name'] ) && '' !== trim( $input['publication_name'] )
			? sanitize_text_field( $input['publication_name'] )
			: $defaults['publication_name'];

		$clean['language'] = isset( $input['language'] )
			? sanitize_text_field( strtolower( $input['language'] ) )
			: $defaults['language'];

		$eligible          = array_keys( $this->get_eligible_post_types() );
		$clean['post_types'] = array();
		if ( ! empty( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
			foreach ( $input['post_types'] as $pt ) {
				if ( in_array( $pt, $eligible, true ) ) {
					$clean['post_types'][] = sanitize_key( $pt );
				}
			}
		}
		if ( empty( $clean['post_types'] ) ) {
			$clean['post_types'] = array( 'post' );
		}

		$clean['exclude_cats'] = array();
		if ( ! empty( $input['exclude_cats'] ) && is_array( $input['exclude_cats'] ) ) {
			$clean['exclude_cats'] = array_map( 'absint', $input['exclude_cats'] );
		}

		$hours                    = isset( $input['max_age_hours'] ) ? absint( $input['max_age_hours'] ) : $defaults['max_age_hours'];
		$clean['max_age_hours']   = max( 1, min( 72, $hours ) ); // Google News wants ~2 days; hard-cap at 72h.

		$cache                    = isset( $input['cache_minutes'] ) ? absint( $input['cache_minutes'] ) : $defaults['cache_minutes'];
		$clean['cache_minutes']   = max( 1, min( 60, $cache ) );

		$clean['publisher_logo'] = isset( $input['publisher_logo'] ) ? esc_url_raw( trim( $input['publisher_logo'] ) ) : '';

		// Rewrite rules and cached XML both depend on these settings.
		delete_transient( AM_Sitemap::TRANSIENT_KEY );
		update_option( 'am_news_seo_flush_needed', 1 );

		return $clean;
	}

	public function maybe_show_notices() {
		if ( get_option( 'am_news_seo_flush_needed' ) ) {
			flush_rewrite_rules( false );
			delete_option( 'am_news_seo_flush_needed' );
		}
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$options    = AM_News_SEO::get_options();
		$post_types = $this->get_eligible_post_types();
		$categories = get_categories( array( 'hide_empty' => false ) );
		$sitemap_url = home_url( '/sitemap-news.xml' );
		$yoast_active = defined( 'WPSEO_VERSION' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'AeroMorning News SEO', 'aeromorning-news-seo' ); ?></h1>
			<p>
				<?php
				printf(
					/* translators: %s: site name */
					esc_html__( 'Settings below apply only to this site: %s. Configure the other language edition separately from its own wp-admin.', 'aeromorning-news-seo' ),
					'<strong>' . esc_html( get_bloginfo( 'name' ) ) . '</strong>'
				);
				?>
			</p>

			<?php if ( $yoast_active ) : ?>
				<div class="notice notice-info inline">
					<p><?php esc_html_e( 'Yoast SEO detected: this plugin will not output a second JSON-LD block. Instead it switches Yoast\'s existing Article schema to NewsArticle for the post types you enable below.', 'aeromorning-news-seo' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( isset( $_GET['am_news_flushed'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Rewrite rules flushed. Re-check the sitemap URL now.', 'aeromorning-news-seo' ); ?></p></div>
			<?php endif; ?>

			<?php if ( $options['enable_sitemap'] ) : ?>
				<?php if ( $this->is_rule_registered() ) : ?>
					<div class="notice notice-success inline">
						<p>✅ <?php esc_html_e( 'The sitemap-news.xml rewrite rule is currently registered on this site.', 'aeromorning-news-seo' ); ?></p>
					</div>
				<?php else : ?>
					<div class="notice notice-error inline">
						<p>
							⚠️ <?php esc_html_e( 'The sitemap-news.xml rewrite rule is NOT currently registered — visiting the sitemap URL will 404 until this is fixed.', 'aeromorning-news-seo' ); ?>
							<a class="button button-secondary" style="margin-left:8px;" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => self::PAGE_SLUG, 'am_news_flush' => 1 ), admin_url( 'admin.php' ) ), 'am_news_flush' ) ); ?>">
								<?php esc_html_e( 'Flush rewrite rules now', 'aeromorning-news-seo' ); ?>
							</a>
						</p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( self::OPTION_GROUP ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Google News sitemap', 'aeromorning-news-seo' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( AM_NEWS_SEO_OPTION ); ?>[enable_sitemap]" value="1" <?php checked( $options['enable_sitemap'] ); ?> />
								<?php esc_html_e( 'Generate a sitemap-news.xml with articles published in the last N hours (below).', 'aeromorning-news-seo' ); ?>
							</label>
							<?php if ( $options['enable_sitemap'] ) : ?>
								<p class="description">
									<?php esc_html_e( 'Live URL:', 'aeromorning-news-seo' ); ?>
									<a href="<?php echo esc_url( $sitemap_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $sitemap_url ); ?></a>
								</p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'NewsArticle structured data', 'aeromorning-news-seo' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( AM_NEWS_SEO_OPTION ); ?>[enable_schema]" value="1" <?php checked( $options['enable_schema'] ); ?> />
								<?php esc_html_e( 'Mark up eligible posts as NewsArticle instead of the generic Article type.', 'aeromorning-news-seo' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="am_publication_name"><?php esc_html_e( 'Publication name', 'aeromorning-news-seo' ); ?></label></th>
						<td>
							<input type="text" id="am_publication_name" class="regular-text" name="<?php echo esc_attr( AM_NEWS_SEO_OPTION ); ?>[publication_name]" value="<?php echo esc_attr( $options['publication_name'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Must exactly match the name registered in Google Publisher Center / News Producer Center.', 'aeromorning-news-seo' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="am_language"><?php esc_html_e( 'Publication language', 'aeromorning-news-seo' ); ?></label></th>
						<td>
							<input type="text" id="am_language" class="small-text" name="<?php echo esc_attr( AM_NEWS_SEO_OPTION ); ?>[language]" value="<?php echo esc_attr( $options['language'] ); ?>" maxlength="5" />
							<p class="description"><?php esc_html_e( 'ISO 639 code, e.g. "fr" or "en". Auto-detected from this site\'s locale.', 'aeromorning-news-seo' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Post types included', 'aeromorning-news-seo' ); ?></th>
						<td>
							<?php foreach ( $post_types as $slug => $label ) : ?>
								<label style="display:block;margin-bottom:4px;">
									<input type="checkbox" name="<?php echo esc_attr( AM_NEWS_SEO_OPTION ); ?>[post_types][]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $options['post_types'], true ) ); ?> />
									<?php echo esc_html( $label ); ?> <code><?php echo esc_html( $slug ); ?></code>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
					<?php if ( ! empty( $categories ) ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Exclude categories', 'aeromorning-news-seo' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( AM_NEWS_SEO_OPTION ); ?>[exclude_cats][]" multiple size="6" style="min-width:280px;">
								<?php foreach ( $categories as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( in_array( $cat->term_id, $options['exclude_cats'], true ) ); ?>>
										<?php echo esc_html( $cat->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Posts in these categories (e.g. sponsored content, press releases you don\'t want surfaced as news) are left out of the sitemap and keep the generic Article type.', 'aeromorning-news-seo' ); ?></p>
						</td>
					</tr>
					<?php endif; ?>
					<tr>
						<th scope="row"><label for="am_max_age"><?php esc_html_e( 'Sitemap freshness window', 'aeromorning-news-seo' ); ?></label></th>
						<td>
							<input type="number" id="am_max_age" min="1" max="72" name="<?php echo esc_attr( AM_NEWS_SEO_OPTION ); ?>[max_age_hours]" value="<?php echo esc_attr( $options['max_age_hours'] ); ?>" class="small-text" /> <?php esc_html_e( 'hours', 'aeromorning-news-seo' ); ?>
							<p class="description"><?php esc_html_e( 'Google only reads articles published within the last 48 hours from a news sitemap. Keep this at 48 unless you have a reason to change it.', 'aeromorning-news-seo' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Images in sitemap', 'aeromorning-news-seo' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( AM_NEWS_SEO_OPTION ); ?>[show_images]" value="1" <?php checked( $options['show_images'] ); ?> />
								<?php esc_html_e( 'Include the featured image via <image:image> (helps eligibility for image-rich results).', 'aeromorning-news-seo' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="am_publisher_logo"><?php esc_html_e( 'Publisher logo URL', 'aeromorning-news-seo' ); ?></label></th>
						<td>
							<input type="url" id="am_publisher_logo" class="regular-text" name="<?php echo esc_attr( AM_NEWS_SEO_OPTION ); ?>[publisher_logo]" value="<?php echo esc_attr( $options['publisher_logo'] ); ?>" placeholder="<?php echo esc_attr( get_site_icon_url( 600 ) ); ?>" />
							<p class="description"><?php esc_html_e( 'Used only in the fallback NewsArticle schema (when Yoast SEO is not active). Recommended at least 600x60px. Leave empty to use the site icon.', 'aeromorning-news-seo' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="am_cache_minutes"><?php esc_html_e( 'Sitemap cache', 'aeromorning-news-seo' ); ?></label></th>
						<td>
							<input type="number" id="am_cache_minutes" min="1" max="60" name="<?php echo esc_attr( AM_NEWS_SEO_OPTION ); ?>[cache_minutes]" value="<?php echo esc_attr( $options['cache_minutes'] ); ?>" class="small-text" /> <?php esc_html_e( 'minutes', 'aeromorning-news-seo' ); ?>
							<p class="description"><?php esc_html_e( 'The sitemap is also regenerated immediately whenever a post is published, edited or trashed.', 'aeromorning-news-seo' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<hr />
			<h2><?php esc_html_e( 'What this does not do', 'aeromorning-news-seo' ); ?></h2>
			<p><?php esc_html_e( 'Neither the sitemap nor the NewsArticle markup guarantees inclusion in Google News, Top Stories or Discover — Google decides that separately based on content policies and, for Google News, Publisher Center enrollment. This plugin only makes sure the technical signals are correct so you are not excluded on a technicality.', 'aeromorning-news-seo' ); ?></p>
		</div>
		<?php
	}
}
