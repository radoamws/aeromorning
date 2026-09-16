<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'xnf_register_menu' );
add_action( 'admin_enqueue_scripts', 'xnf_admin_enqueue' );
add_action( 'wp_ajax_xnf_toggle_status', 'xnf_ajax_toggle_status' );
add_action( 'wp_ajax_xnf_bulk_status', 'xnf_ajax_bulk_status' );
add_action( 'wp_ajax_xnf_get_rows', 'xnf_ajax_get_rows' );

function xnf_register_menu() {
    add_menu_page(
        'XML News Feed',
        'XML News Feed',
        'manage_options',
        'xnf-settings',
        'xnf_page_settings',
        'dashicons-rss',
        30
    );
    add_submenu_page(
        'xnf-settings',
        'Paramètres',
        'Paramètres',
        'manage_options',
        'xnf-settings',
        'xnf_page_settings'
    );
    add_submenu_page(
        'xnf-settings',
        'Liste des flux',
        'Liste des flux',
        'manage_options',
        'xnf-list',
        'xnf_page_list'
    );
}

function xnf_admin_enqueue( $hook ) {
    if ( ! in_array( $hook, [ 'toplevel_page_xnf-settings', 'xml-news-feed_page_xnf-list' ] ) ) {
        return;
    }
    wp_enqueue_style( 'xnf-admin', XNF_PLUGIN_URL . 'assets/admin.css', [], XNF_VERSION );
    wp_enqueue_script( 'xnf-admin', XNF_PLUGIN_URL . 'assets/admin.js', [ 'jquery' ], XNF_VERSION, true );
    wp_localize_script( 'xnf-admin', 'XNF', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'xnf_admin' ),
    ] );
}

// ── PAGE PARAMÈTRES ──────────────────────────────────────────────────────────

function xnf_page_settings() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_POST['xnf_save_settings'] ) && check_admin_referer( 'xnf_settings_nonce' ) ) {
        $limit = absint( $_POST['xnf_limit'] ?? 10 );
        update_option( 'xnf_limit',         $limit );
        update_option( 'xnf_show_title',    isset( $_POST['xnf_show_title'] )    ? 1 : 0 );
        update_option( 'xnf_show_subtitle', isset( $_POST['xnf_show_subtitle'] ) ? 1 : 0 );
        update_option( 'xnf_show_date',     isset( $_POST['xnf_show_date'] )     ? 1 : 0 );
        update_option( 'xnf_show_img',      isset( $_POST['xnf_show_img'] )      ? 1 : 0 );
        update_option( 'xnf_show_link',     isset( $_POST['xnf_show_link'] )     ? 1 : 0 );
        echo '<div class="notice notice-success"><p>Paramètres sauvegardés.</p></div>';
    }

    $limit    = (int) get_option( 'xnf_limit', 10 );
    $s_title  = (int) get_option( 'xnf_show_title', 1 );
    $s_sub    = (int) get_option( 'xnf_show_subtitle', 1 );
    $s_date   = (int) get_option( 'xnf_show_date', 1 );
    $s_img    = (int) get_option( 'xnf_show_img', 1 );
    $s_link   = (int) get_option( 'xnf_show_link', 1 );
    $shortcode = '[xml_news_feed]';
    $cron_token = XNF_CRON_TOKEN;
    $site_url   = get_site_url();
    ?>
    <div class="wrap xnf-wrap">
        <h1>XML News Feed – Paramètres</h1>

        <div class="xnf-shortcode-box">
            <label>Shortcode à copier dans vos pages/articles :</label>
            <input type="text" id="xnf-shortcode-input" value="<?php echo esc_attr( $shortcode ); ?>" readonly />
            <button type="button" class="button" onclick="xnfCopyShortcode()">Copier</button>
        </div>

        <div style="background:#fff;border:1px solid #ccd0d4;border-radius:4px;padding:14px 16px;margin-bottom:24px;">
            <h3 style="margin-top:0">Commandes CRON</h3>
            <p><strong>Token sécurisé :</strong> <code><?php echo esc_html( $cron_token ); ?></code></p>
            <p><strong>Import (lecture &amp; insertion) :</strong></p>
            <p><code>php -q <?php echo esc_html( WP_CONTENT_DIR . '/plugins/xml-news-feed/cron/cron-import.php' ); ?> --token=<?php echo esc_html( $cron_token ); ?></code></p>
            <p>ou via wget :<br><code>wget -q "<?php echo esc_url( $site_url . '/?xnf_action=import&token=' . $cron_token ); ?>" -O -</code></p>
            <p><strong>Purge (archivage &gt; 7 jours) :</strong></p>
            <p><code>php -q <?php echo esc_html( WP_CONTENT_DIR . '/plugins/xml-news-feed/cron/cron-purge.php' ); ?> --token=<?php echo esc_html( $cron_token ); ?></code></p>
            <p>ou via wget :<br><code>wget -q "<?php echo esc_url( $site_url . '/?xnf_action=purge&token=' . $cron_token ); ?>" -O -</code></p>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field( 'xnf_settings_nonce' ); ?>
            <table class="form-table">
                <tr>
                    <th>Nombre de flux affichés</th>
                    <td><input type="number" name="xnf_limit" value="<?php echo esc_attr( $limit ); ?>" min="1" max="500" /></td>
                </tr>
                <tr>
                    <th>Colonnes affichées en frontend</th>
                    <td>
                        <label><input type="checkbox" name="xnf_show_title"    <?php checked( $s_title ); ?> /> Titre</label><br>
                        <label><input type="checkbox" name="xnf_show_subtitle" <?php checked( $s_sub ); ?> /> Sous-titre</label><br>
                        <label><input type="checkbox" name="xnf_show_date"     <?php checked( $s_date ); ?> /> Date</label><br>
                        <label><input type="checkbox" name="xnf_show_img"      <?php checked( $s_img ); ?> /> Image</label><br>
                        <label><input type="checkbox" name="xnf_show_link"     <?php checked( $s_link ); ?> /> Lien détail</label>
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Sauvegarder', 'primary', 'xnf_save_settings' ); ?>
        </form>
    </div>
    <script>
    function xnfCopyShortcode() {
        var el = document.getElementById('xnf-shortcode-input');
        el.select();
        document.execCommand('copy');
        alert('Shortcode copié !');
    }
    </script>
    <?php
}

// ── PAGE LISTE DES FLUX ──────────────────────────────────────────────────────

function xnf_page_list() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap xnf-wrap">
        <h1>XML News Feed – Liste des flux</h1>

        <div class="xnf-toolbar">
            <input type="text" id="xnf-search" placeholder="Rechercher…" />
            <select id="xnf-filter-status">
                <option value="">Tous les statuts</option>
                <option value="1">Publié</option>
                <option value="0">Non publié</option>
            </select>
            <select id="xnf-bulk-action">
                <option value="">Action groupée</option>
                <option value="1">Publier la sélection</option>
                <option value="0">Dépublier la sélection</option>
            </select>
            <button type="button" id="xnf-bulk-apply" class="button">Appliquer</button>
        </div>

        <table id="xnf-table" class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><input type="checkbox" id="xnf-select-all" /></th>
                    <th>ID</th>
                    <th>Titre</th>
                    <th>Date publi</th>
                    <th>Date string</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody id="xnf-tbody">
                <tr><td colspan="6">Chargement…</td></tr>
            </tbody>
        </table>

        <div id="xnf-pagination"></div>
    </div>
    <?php
}

// ── AJAX : liste paginée ─────────────────────────────────────────────────────

function xnf_ajax_get_rows() {
    check_ajax_referer( 'xnf_admin', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Accès refusé.', 403 );
    }

    global $wpdb;
    $table = $wpdb->base_prefix . 't_xml_data';

    $page    = max( 1, absint( $_POST['page'] ?? 1 ) );
    $per_page = 20;
    $search  = sanitize_text_field( $_POST['search'] ?? '' );
    $status  = $_POST['status'] !== '' ? absint( $_POST['status'] ) : null;

    $where   = '1=1';
    $params  = [];
    if ( $search !== '' ) {
        $where   .= ' AND (title LIKE %s OR date_string LIKE %s)';
        $params[] = '%' . $wpdb->esc_like( $search ) . '%';
        $params[] = '%' . $wpdb->esc_like( $search ) . '%';
    }
    if ( $status !== null ) {
        $where   .= ' AND status = %d';
        $params[] = $status;
    }

    $base_sql = "FROM `$table` WHERE $where";
    $total    = (int) $wpdb->get_var( $params ? $wpdb->prepare( "SELECT COUNT(*) $base_sql", ...$params ) : "SELECT COUNT(*) $base_sql" );
    $offset   = ( $page - 1 ) * $per_page;

    $rows_sql = "SELECT id, title, date_publish, date_string, status $base_sql ORDER BY date_publish DESC LIMIT %d OFFSET %d";
    $params[] = $per_page;
    $params[] = $offset;
    $rows     = $wpdb->get_results( $wpdb->prepare( $rows_sql, ...$params ) );

    wp_send_json_success( [
        'rows'       => $rows,
        'total'      => $total,
        'per_page'   => $per_page,
        'page'       => $page,
        'last_page'  => (int) ceil( $total / $per_page ),
    ] );
}

// ── AJAX : toggle statut individuel ─────────────────────────────────────────

function xnf_ajax_toggle_status() {
    check_ajax_referer( 'xnf_admin', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Accès refusé.', 403 );
    }

    global $wpdb;
    $table  = $wpdb->base_prefix . 't_xml_data';
    $id     = absint( $_POST['id'] ?? 0 );
    $status = absint( $_POST['status'] ?? 0 );

    if ( ! $id || ! in_array( $status, [ 0, 1 ] ) ) {
        wp_send_json_error( 'Paramètres invalides.' );
    }

    $wpdb->update( $table, [ 'status' => $status ], [ 'id' => $id ] );
    wp_send_json_success();
}

// ── AJAX : action groupée ────────────────────────────────────────────────────

function xnf_ajax_bulk_status() {
    check_ajax_referer( 'xnf_admin', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Accès refusé.', 403 );
    }

    global $wpdb;
    $table  = $wpdb->base_prefix . 't_xml_data';
    $ids    = array_map( 'absint', (array) ( $_POST['ids'] ?? [] ) );
    $status = absint( $_POST['status'] ?? 0 );

    if ( empty( $ids ) || ! in_array( $status, [ 0, 1 ] ) ) {
        wp_send_json_error( 'Paramètres invalides.' );
    }

    $ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
    $wpdb->query( $wpdb->prepare(
        "UPDATE `$table` SET status = %d WHERE id IN ($ids_placeholder)",
        array_merge( [ $status ], $ids )
    ) );

    wp_send_json_success();
}
