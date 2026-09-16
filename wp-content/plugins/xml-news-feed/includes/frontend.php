<?php
defined( 'ABSPATH' ) || exit;

add_shortcode( 'xml_news_feed', 'xnf_shortcode' );
add_action( 'wp_enqueue_scripts', 'xnf_frontend_enqueue' );

function xnf_frontend_enqueue() {
    wp_enqueue_style( 'xnf-frontend', XNF_PLUGIN_URL . 'assets/frontend.css', [], XNF_VERSION );
}

function xnf_shortcode( $atts ) {
    global $wpdb;
    $table = $wpdb->base_prefix . 't_xml_data';

    $limit = (int) get_option( 'xnf_limit', 10 );
    $atts  = shortcode_atts( [ 'limit' => $limit ], $atts, 'xml_news_feed' );
    $limit = absint( $atts['limit'] );

    $show_title    = (int) get_option( 'xnf_show_title', 1 );
    $show_subtitle = (int) get_option( 'xnf_show_subtitle', 1 );
    $show_date     = (int) get_option( 'xnf_show_date', 1 );
    $show_img      = (int) get_option( 'xnf_show_img', 1 );
    $show_link     = (int) get_option( 'xnf_show_link', 1 );

    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM `$table` WHERE status = 1 ORDER BY date_publish DESC LIMIT %d",
        $limit
    ) );

    if ( empty( $rows ) ) {
        return '<p class="xnf-empty">Aucun flux disponible.</p>';
    }

    ob_start();
    ?>
    <div class="xnf-feed">
        <?php foreach ( $rows as $row ) : ?>
        <article class="xnf-item">
            <?php if ( $show_img ) : ?>
            <div class="xnf-img-col">
                <?php if ( ! empty( $row->img ) ) : ?>
                <img src="<?php echo esc_url( XNF_IMG_URL . $row->img ); ?>"
                     alt="<?php echo esc_attr( $row->title ); ?>"
                     class="xnf-thumb" />
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="xnf-content-col">
                <?php if ( $show_title && ! empty( $row->title ) ) : ?>
                <h2 class="xnf-title"><?php echo esc_html( $row->title ); ?></h2>
                <?php endif; ?>
                <?php if ( $show_date && ! empty( $row->date_string ) ) : ?>
                <p class="xnf-date"><?php echo esc_html( $row->date_string ); ?></p>
                <?php endif; ?>
                <?php if ( $show_subtitle && ! empty( $row->subtitle ) ) : ?>
                <p class="xnf-subtitle"><?php echo esc_html( $row->subtitle ); ?></p>
                <?php endif; ?>
                <?php if ( $show_link && ! empty( $row->detail_link ) ) : ?>
                <a href="<?php echo esc_url( $row->detail_link ); ?>" target="_blank" rel="noopener noreferrer" class="xnf-link">Lire la suite →</a>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
