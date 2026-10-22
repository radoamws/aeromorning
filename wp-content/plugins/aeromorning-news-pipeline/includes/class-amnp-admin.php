<?php
/**
 * Back-office replacing the extract_news Nuxt dashboard (main site, admins
 * only): news list with filters, SEO preview, publish now, status changes,
 * LinkedIn post, ignored emails with "force publish", processing runs.
 *
 * Long AI work never runs in a web request: "force publish" only queues the
 * email for the next process-emails cron run. Publishing (≈1 s per article)
 * is done immediately.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AMNP_Admin
{
    private const SLUG = 'amnp-news';
    private const PER_PAGE = 20;
    private const MAX_BULK_PUBLISH = 20;
    private const STATUSES = [0 => 'En attente', 1 => 'En cours', 2 => 'Publiée'];

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'menu']);
        foreach (['publish', 'bulk', 'linkedin', 'force'] as $action) {
            add_action("admin_post_amnp_{$action}", [self::class, "handle_{$action}"]);
        }
    }

    public static function menu(): void
    {
        if (get_current_blog_id() !== AMNP_Config::int('FR_BLOG_ID')) {
            return;
        }
        add_menu_page('AeroMorning News', 'AeroMorning News', 'manage_options', self::SLUG, [self::class, 'render'], 'dashicons-email-alt', 26);
    }

    // ── Rendering ───────────────────────────────────────────────────────────

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        amnp_load_pipeline();

        $tab = sanitize_key($_GET['tab'] ?? 'news');
        $tabs = ['news' => 'News', 'ignored' => 'E-mails ignorés', 'logs' => 'Traitements'];

        echo '<div class="wrap"><h1>AeroMorning News</h1>';
        self::renderNotice();
        self::renderModeBanner();

        echo '<nav class="nav-tab-wrapper">';
        foreach ($tabs as $key => $label) {
            printf('<a href="%s" class="nav-tab%s">%s</a>', esc_url(self::url(['tab' => $key])), $tab === $key ? ' nav-tab-active' : '', esc_html($label));
        }
        echo '</nav>';

        if ($tab === 'ignored') {
            self::renderIgnored();
        } elseif ($tab === 'logs') {
            self::renderLogs();
        } elseif (!empty($_GET['view'])) {
            self::renderPreview((int) $_GET['view']);
        } else {
            self::renderNewsList();
        }

        echo '</div>';
        self::styles();
    }

    private static function renderModeBanner(): void
    {
        $flags = [];
        if (AMNP_Config::str('PUBLISH_STATUS') !== 'publish') {
            $flags[] = 'publication en <strong>brouillon</strong>';
        }
        if (!AMNP_Config::bool('IMAP_MARK_READ')) {
            $flags[] = 'e-mails <strong>non marqués comme lus</strong>';
        }
        $flags[] = 'LinkedIn : <strong>' . esc_html(AMNP_LinkedIn::mode()) . '</strong>';
        echo '<p class="description">Configuration : ' . implode(' · ', $flags) . '. Le traitement des e-mails tourne uniquement par le cron système (toutes les 30 min), la publication toutes les 5 min.</p>';
    }

    private static function renderNewsList(): void
    {
        global $wpdb;
        $table = AMNP_DB::news();

        $q = sanitize_text_field(wp_unslash($_GET['q'] ?? ''));
        $status = isset($_GET['status']) && $_GET['status'] !== '' ? (int) $_GET['status'] : null;
        $lang = in_array($_GET['lang'] ?? '', ['FR', 'EN'], true) ? $_GET['lang'] : '';
        $orderby = in_array($_GET['orderby'] ?? '', ['id', 'created_at', 'title'], true) ? $_GET['orderby'] : 'id';
        $order = strtolower($_GET['order'] ?? '') === 'asc' ? 'ASC' : 'DESC';
        $page = max(1, (int) ($_GET['paged'] ?? 1));

        $where = ['1=1'];
        $args = [];
        if ($q !== '') {
            $where[] = '(title LIKE %s OR email_message_id LIKE %s)';
            $args[] = '%' . $wpdb->esc_like($q) . '%';
            $args[] = '%' . $wpdb->esc_like($q) . '%';
        }
        if ($status !== null) {
            $where[] = 'status = %d';
            $args[] = $status;
        }
        if ($lang !== '') {
            $where[] = 'lang = %s';
            $args[] = $lang;
        }
        $whereSql = implode(' AND ', $where);
        $prepare = static fn (string $sql) => $args ? $wpdb->prepare($sql, ...$args) : $sql;

        $total = (int) $wpdb->get_var($prepare("SELECT COUNT(*) FROM {$table} WHERE {$whereSql}"));
        $offset = ($page - 1) * self::PER_PAGE;
        $rows = $wpdb->get_results($prepare("SELECT id, lang, status, title, metadescription, focuskeyphrase, categories, tags, image_path, wp_post_id, wp_link, linkedin, linkedin_posted, created_at FROM {$table} WHERE {$whereSql} ORDER BY {$orderby} {$order} LIMIT " . self::PER_PAGE . " OFFSET {$offset}"), ARRAY_A);

        // Stats (same figures as the Nuxt dashboard).
        $stats = $wpdb->get_results("SELECT lang, status, COUNT(*) n FROM {$table} GROUP BY lang, status", ARRAY_A);
        $count = static function (?string $l, ?int $s) use ($stats): int {
            $n = 0;
            foreach ($stats as $row) {
                if (($l === null || $row['lang'] === $l) && ($s === null || (int) $row['status'] === $s)) {
                    $n += (int) $row['n'];
                }
            }
            return $n;
        };
        printf(
            '<p class="amnp-stats">Total <strong>%d</strong> · En attente <strong>%d</strong> (FR %d / EN %d) · En cours <strong>%d</strong> · Publiées <strong>%d</strong> (FR %d / EN %d)</p>',
            $count(null, null), $count(null, 0), $count('FR', 0), $count('EN', 0), $count(null, 1), $count(null, 2), $count('FR', 2), $count('EN', 2)
        );

        // Filters.
        echo '<form method="get" class="amnp-filters">';
        printf('<input type="hidden" name="page" value="%s">', esc_attr(self::SLUG));
        printf('<input type="search" name="q" value="%s" placeholder="Titre ou message-id">', esc_attr($q));
        echo '<select name="status"><option value="">Tous statuts</option>';
        foreach (self::STATUSES as $value => $label) {
            printf('<option value="%d"%s>%s</option>', $value, selected($status, $value, false), esc_html($label));
        }
        echo '</select><select name="lang"><option value="">FR + EN</option>';
        foreach (['FR', 'EN'] as $l) {
            printf('<option%s>%s</option>', selected($lang, $l, false), $l);
        }
        echo '</select><select name="orderby">';
        foreach (['id' => 'N°', 'created_at' => 'Date', 'title' => 'Titre'] as $value => $label) {
            printf('<option value="%s"%s>Tri : %s</option>', $value, selected($orderby, $value, false), $label);
        }
        echo '</select><select name="order">';
        printf('<option value="desc"%s>↓</option><option value="asc"%s>↑</option>', selected($order, 'DESC', false), selected($order, 'ASC', false));
        echo '</select> <button class="button">Filtrer</button> ';
        printf('<a class="button-link" href="%s">Réinitialiser</a></form>', esc_url(self::url()));

        // List + bulk actions.
        printf('<form method="post" action="%s">', esc_url(admin_url('admin-post.php')));
        echo '<input type="hidden" name="action" value="amnp_bulk">';
        wp_nonce_field('amnp_bulk');
        echo '<div class="tablenav top"><select name="bulk_action">'
            . '<option value="">Actions groupées</option>'
            . '<option value="publish">Publier maintenant (en attente, max ' . self::MAX_BULK_PUBLISH . ')</option>'
            . '<option value="status_0">Passer en « En attente »</option>'
            . '<option value="status_2">Passer en « Publiée »</option>'
            . '</select> <button class="button" onclick="return amnpConfirmBulk(this.form)">Appliquer</button></div>';

        echo '<table class="widefat striped"><thead><tr><td class="check-column"><input type="checkbox" onclick="document.querySelectorAll(\'.amnp-cb\').forEach(c=>c.checked=this.checked)"></td><th>N°</th><th>Langue</th><th>Statut</th><th>Titre</th><th>SEO</th><th>Image</th><th>Article</th><th>LinkedIn</th><th>Créée (UTC)</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $actions = [sprintf('<a href="%s">Aperçu</a>', esc_url(self::url(['view' => $id])))];
            if ((int) $row['status'] === AMNP_DB::STATUS_PENDING) {
                $actions[] = sprintf('<a href="%s">Publier maintenant</a>', esc_url(self::actionUrl('publish', $id)));
            }
            if ($row['lang'] === 'EN' && $row['wp_link'] && (int) $row['status'] === AMNP_DB::STATUS_SYNCED) {
                $actions[] = sprintf('<a href="%s" onclick="return confirm(\'Publier ce post sur LinkedIn ?\')">Poster sur LinkedIn</a>', esc_url(self::actionUrl('linkedin', $id)));
            }

            $article = '—';
            if ($row['wp_post_id']) {
                $blogId = AMNP_Config::blogForLang($row['lang']);
                $article = sprintf(
                    '<a href="%s" target="_blank" rel="noopener">voir</a> · <a href="%s">modifier</a>',
                    esc_url($row['wp_link']),
                    esc_url(get_admin_url($blogId, 'post.php?post=' . (int) $row['wp_post_id'] . '&action=edit'))
                );
            }

            printf(
                '<tr><th class="check-column"><input type="checkbox" class="amnp-cb" name="ids[]" value="%d"></th><td>%d</td><td>%s</td><td><span class="amnp-status amnp-s%d">%s</span></td><td><strong>%s</strong><div class="row-actions">%s</div></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                $id, $id, esc_html($row['lang']), (int) $row['status'], esc_html(self::STATUSES[(int) $row['status']] ?? $row['status']),
                esc_html($row['title']), implode(' | ', $actions),
                self::seoBadges($row), $row['image_path'] ? '✓' : '<span title="Visuel par défaut">défaut</span>',
                $article,
                $row['lang'] === 'EN' ? ($row['linkedin_posted'] ? 'postée' : ($row['linkedin'] ? 'prévue' : '—')) : '',
                esc_html($row['created_at'])
            );
        }
        if (!$rows) {
            echo '<tr><td colspan="10">Aucune news.</td></tr>';
        }
        echo '</tbody></table></form>';

        $pages = (int) ceil($total / self::PER_PAGE);
        if ($pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">' . paginate_links([
                'base'    => add_query_arg('paged', '%#%'),
                'format'  => '',
                'current' => $page,
                'total'   => $pages,
            ]) . '</div></div>';
        }

        echo "<script>function amnpConfirmBulk(f){var a=f.bulk_action.value;if(!a){return false;}if(a==='status_0'){return confirm('Repasser une news déjà publiée « En attente » la fera republier (nouvel article en double). Continuer ?');}return true;}</script>";
    }

    /** Same SEO limits as the Nuxt preview: title ≤ 62, meta 107–142, keyphrase 2–5 words. */
    private static function seoChecks(array $row): array
    {
        $titleLen = mb_strlen((string) $row['title']);
        $metaLen = mb_strlen((string) $row['metadescription']);
        $words = count(preg_split('/\s+/u', trim((string) $row['focuskeyphrase']), -1, PREG_SPLIT_NO_EMPTY));
        return [
            'Titre'       => [$titleLen . ' car.', $titleLen > 0 && $titleLen <= 62],
            'Meta'        => [$metaLen . ' car.', $metaLen >= 107 && $metaLen <= 142],
            'Expression'  => [$words . ' mot(s)', $words >= 2 && $words <= 5 && !str_contains((string) $row['focuskeyphrase'], ',')],
        ];
    }

    private static function seoBadges(array $row): string
    {
        $out = [];
        foreach (self::seoChecks($row) as $label => [$value, $ok]) {
            $out[] = sprintf('<span class="amnp-badge %s" title="%s : %s">%s</span>', $ok ? 'ok' : 'ko', esc_attr($label), esc_attr($value), esc_html(mb_substr($label, 0, 1)));
        }
        return implode('', $out);
    }

    private static function renderPreview(int $id): void
    {
        $row = AMNP_DB::getNews($id);
        if (!$row) {
            echo '<p>News introuvable.</p>';
            return;
        }

        printf('<p><a href="%s">← Retour à la liste</a></p>', esc_url(self::url()));
        printf('<h2>[%s] %s</h2>', esc_html($row['lang']), esc_html($row['title']));

        echo '<table class="widefat amnp-seo"><tbody>';
        foreach (self::seoChecks($row) as $label => [$value, $ok]) {
            printf('<tr><th>%s</th><td><span class="amnp-badge %s">%s</span></td></tr>', esc_html($label), $ok ? 'ok' : 'ko', esc_html($value));
        }
        printf('<tr><th>Meta description</th><td>%s</td></tr>', esc_html($row['metadescription']));
        printf('<tr><th>Expression clé</th><td>%s</td></tr>', esc_html($row['focuskeyphrase']));

        switch_to_blog(AMNP_Config::blogForLang($row['lang']));
        $termNames = static function (string $csv, string $taxonomy): string {
            $names = [];
            foreach (array_filter(array_map('intval', explode(',', $csv))) as $termId) {
                $term = get_term($termId, $taxonomy);
                $names[] = $term && !is_wp_error($term) ? $term->name : "#{$termId}";
            }
            return $names ? implode(', ', $names) : '—';
        };
        printf('<tr><th>Catégories</th><td>%s</td></tr>', esc_html($termNames((string) $row['categories'], 'category')));
        printf('<tr><th>Tags</th><td>%s</td></tr>', esc_html($termNames((string) $row['tags'], 'post_tag')));
        restore_current_blog();

        printf('<tr><th>Statut</th><td>%s%s</td></tr>', esc_html(self::STATUSES[(int) $row['status']] ?? ''), $row['wp_link'] ? ' — <a href="' . esc_url($row['wp_link']) . '" target="_blank" rel="noopener">voir l\'article</a>' : '');
        printf('<tr><th>E-mail source</th><td><code>%s</code></td></tr>', esc_html($row['email_message_id']));
        echo '</tbody></table>';

        $imageUrl = self::queuedImageUrl((string) $row['image_path']);
        echo '<h3>Image à la une</h3>';
        if ($imageUrl) {
            printf('<img src="%s" alt="" class="amnp-image">', esc_url($imageUrl));
        } elseif ($row['wp_post_id']) {
            switch_to_blog(AMNP_Config::blogForLang($row['lang']));
            echo get_the_post_thumbnail((int) $row['wp_post_id'], 'medium') ?: '<p>—</p>';
            restore_current_blog();
        } else {
            echo '<p>Aucune image extraite : le visuel par défaut sera utilisé.</p>';
        }

        echo '<h3>Contenu</h3><div class="amnp-content">' . wp_kses_post((string) $row['content']) . '</div>';
    }

    private static function renderIgnored(): void
    {
        global $wpdb;
        $table = AMNP_DB::ignored();
        $q = sanitize_text_field(wp_unslash($_GET['q'] ?? ''));
        $state = sanitize_key($_GET['state'] ?? '');
        $page = max(1, (int) ($_GET['paged'] ?? 1));

        $where = ['1=1'];
        $args = [];
        if ($q !== '') {
            $where[] = '(subject LIKE %s OR sender LIKE %s)';
            $args[] = '%' . $wpdb->esc_like($q) . '%';
            $args[] = '%' . $wpdb->esc_like($q) . '%';
        }
        if ($state === 'queued') {
            $where[] = 'force_requested_at IS NOT NULL AND force_published_at IS NULL';
        } elseif ($state === 'forced') {
            $where[] = 'force_published_at IS NOT NULL';
        } elseif ($state === 'ignored') {
            $where[] = 'force_requested_at IS NULL AND force_published_at IS NULL';
        }
        $whereSql = implode(' AND ', $where);
        $prepare = static fn (string $sql) => $args ? $wpdb->prepare($sql, ...$args) : $sql;

        $total = (int) $wpdb->get_var($prepare("SELECT COUNT(*) FROM {$table} WHERE {$whereSql}"));
        $offset = ($page - 1) * self::PER_PAGE;
        $rows = $wpdb->get_results($prepare("SELECT id, subject, sender, reason, excerpt, processed_at, force_requested_at, force_published_at FROM {$table} WHERE {$whereSql} ORDER BY id DESC LIMIT " . self::PER_PAGE . " OFFSET {$offset}"), ARRAY_A);

        echo '<form method="get" class="amnp-filters">';
        printf('<input type="hidden" name="page" value="%s"><input type="hidden" name="tab" value="ignored">', esc_attr(self::SLUG));
        printf('<input type="search" name="q" value="%s" placeholder="Objet ou expéditeur">', esc_attr($q));
        echo '<select name="state">';
        foreach (['' => 'Tous', 'ignored' => 'Ignorés', 'queued' => 'En file (forcés)', 'forced' => 'Publiés de force'] as $value => $label) {
            printf('<option value="%s"%s>%s</option>', esc_attr($value), selected($state, $value, false), esc_html($label));
        }
        echo '</select> <button class="button">Filtrer</button></form>';

        echo '<table class="widefat striped"><thead><tr><th>N°</th><th>Objet</th><th>Expéditeur</th><th>Raison</th><th>Extrait</th><th>Reçu (UTC)</th><th>Action</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            if ($row['force_published_at']) {
                $action = 'Publié de force le ' . esc_html($row['force_published_at']);
            } elseif ($row['force_requested_at']) {
                $action = 'En file — traité au prochain passage du cron';
            } else {
                $action = sprintf(
                    '<form method="post" action="%s"><input type="hidden" name="action" value="amnp_force"><input type="hidden" name="id" value="%d">%s<button class="button button-small" onclick="return confirm(\'Relancer l\\\'extraction IA et publier cet e-mail ?\')">Forcer la publication</button></form>',
                    esc_url(admin_url('admin-post.php')), (int) $row['id'], wp_nonce_field('amnp_force_' . (int) $row['id'], '_wpnonce', true, false)
                );
            }
            printf(
                '<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td><td><details><summary>%s…</summary>%s</details></td><td>%s</td><td>%s</td></tr>',
                (int) $row['id'], esc_html($row['subject']), esc_html($row['sender']), esc_html($row['reason']),
                esc_html(mb_substr((string) $row['excerpt'], 0, 80)), nl2br(esc_html(mb_substr((string) $row['excerpt'], 0, 2000))),
                esc_html($row['processed_at']), $action
            );
        }
        if (!$rows) {
            echo '<tr><td colspan="7">Aucun e-mail ignoré.</td></tr>';
        }
        echo '</tbody></table>';

        $pages = (int) ceil($total / self::PER_PAGE);
        if ($pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">' . paginate_links(['base' => add_query_arg('paged', '%#%'), 'format' => '', 'current' => $page, 'total' => $pages]) . '</div></div>';
        }
    }

    private static function renderLogs(): void
    {
        global $wpdb;
        $runs = $wpdb->get_results('SELECT * FROM ' . AMNP_DB::logs() . ' ORDER BY id DESC LIMIT 50', ARRAY_A);

        echo '<table class="widefat striped"><thead><tr><th>Tâche</th><th>Source</th><th>Statut</th><th>Message</th><th>Début (UTC)</th><th>Durée</th><th>Détails</th></tr></thead><tbody>';
        foreach ($runs as $run) {
            $duration = $run['finished_at'] ? (strtotime($run['finished_at']) - strtotime($run['started_at'])) . ' s' : '—';
            $details = $run['details'] ? json_decode((string) $run['details'], true) : null;
            printf(
                '<tr><td>%s</td><td>%s</td><td><span class="amnp-run amnp-run-%s">%s</span></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                esc_html($run['process_type']), esc_html((string) $run['source']), esc_attr($run['status']), esc_html($run['status']),
                esc_html((string) $run['message']), esc_html($run['started_at']), esc_html($duration),
                $details ? '<details><summary>voir</summary><pre>' . esc_html(wp_json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</pre></details>' : ''
            );
        }
        if (!$runs) {
            echo '<tr><td colspan="7">Aucun traitement enregistré.</td></tr>';
        }
        echo '</tbody></table>';
    }

    // ── Actions (admin-post.php) ────────────────────────────────────────────

    public static function handle_publish(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        self::guard('amnp_publish_' . $id);
        self::publish([$id]);
    }

    public static function handle_bulk(): void
    {
        self::guard('amnp_bulk');
        $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? []))));
        $action = sanitize_key($_POST['bulk_action'] ?? '');

        if (!$ids) {
            self::redirect('Aucune news sélectionnée.', 'warning');
        }
        if ($action === 'publish') {
            self::publish(array_slice($ids, 0, self::MAX_BULK_PUBLISH));
        }
        if (in_array($action, ['status_0', 'status_2'], true)) {
            $status = $action === 'status_0' ? AMNP_DB::STATUS_PENDING : AMNP_DB::STATUS_SYNCED;
            foreach ($ids as $id) {
                AMNP_DB::updateNews($id, ['status' => $status]);
            }
            self::redirect(count($ids) . ' news passée(s) en « ' . self::STATUSES[$status] . ' ».');
        }
        self::redirect('Action inconnue.', 'warning');
    }

    public static function handle_linkedin(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        self::guard('amnp_linkedin_' . $id);
        amnp_load_pipeline();

        $news = AMNP_DB::getNews($id);
        if (!$news || !$news['wp_link']) {
            self::redirect('News introuvable ou pas encore publiée.', 'error');
        }
        try {
            $result = (new AMNP_LinkedIn())->postNews($news);
        } catch (\Throwable $e) {
            self::redirect('LinkedIn : ' . $e->getMessage(), 'error');
        }
        if (!empty($result['skipped'])) {
            self::redirect('LinkedIn : ' . $result['reason'], 'warning');
        }
        if (AMNP_LinkedIn::mode() === 'live') {
            AMNP_DB::updateNews($id, ['linkedin_posted' => 1]);
        }
        self::redirect('LinkedIn (' . AMNP_LinkedIn::mode() . ') : ' . ($result['annotations'] ?? 0) . ' mention(s).');
    }

    public static function handle_force(): void
    {
        global $wpdb;
        $id = (int) ($_POST['id'] ?? 0);
        self::guard('amnp_force_' . $id);
        $wpdb->update(AMNP_DB::ignored(), ['force_requested_at' => AMNP_DB::now()], ['id' => $id, 'force_published_at' => null]);
        self::redirect('E-mail mis en file : il sera extrait au prochain passage du cron (≤ 30 min), puis publié.', 'success', ['tab' => 'ignored']);
    }

    private static function publish(array $ids): void
    {
        amnp_load_pipeline();
        if (!AMNP_DB::lock('publish')) {
            self::redirect('Une publication est déjà en cours (cron). Réessayez dans une minute.', 'warning');
        }
        try {
            $results = (new AMNP_Publisher())->run(null, $ids, 'admin');
        } catch (\Throwable $e) {
            self::redirect('Publication impossible : ' . $e->getMessage(), 'error');
        } finally {
            AMNP_DB::unlock('publish');
        }

        $ok = count($results['success']);
        $ko = count($results['failed']);
        if ($ok === 0 && $ko === 0) {
            self::redirect('Rien à publier : seules les news « En attente » sont publiées.', 'warning');
        }
        $message = "{$ok} news publiée(s)";
        if ($ko) {
            $message .= ", {$ko} en échec : " . implode(' ; ', array_column($results['failed'], 'error'));
        }
        self::redirect($message . '.', $ko ? 'warning' : 'success');
    }

    private static function guard(string $nonceAction): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé.', 403);
        }
        check_admin_referer($nonceAction);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private static function url(array $args = []): string
    {
        return add_query_arg(array_merge(['page' => self::SLUG], $args), admin_url('admin.php'));
    }

    private static function actionUrl(string $action, int $id): string
    {
        return wp_nonce_url(add_query_arg(['action' => "amnp_{$action}", 'id' => $id], admin_url('admin-post.php')), "amnp_{$action}_{$id}");
    }

    /** @return never */
    private static function redirect(string $message, string $type = 'success', array $args = []): void
    {
        set_transient('amnp_notice_' . get_current_user_id(), ['message' => $message, 'type' => $type], 60);
        wp_safe_redirect(self::url($args));
        exit;
    }

    private static function renderNotice(): void
    {
        $key = 'amnp_notice_' . get_current_user_id();
        $notice = get_transient($key);
        if (is_array($notice)) {
            delete_transient($key);
            printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr($notice['type']), esc_html($notice['message']));
        }
    }

    private static function queuedImageUrl(string $path): ?string
    {
        if ($path === '' || !is_file($path)) {
            return null;
        }
        switch_to_blog(AMNP_Config::int('FR_BLOG_ID'));
        $uploads = wp_upload_dir(null, false);
        restore_current_blog();
        return trailingslashit($uploads['baseurl']) . 'amnp-queue/' . rawurlencode(basename($path));
    }

    private static function styles(): void
    {
        echo '<style>
            .amnp-filters{margin:12px 0;display:flex;gap:6px;flex-wrap:wrap;align-items:center}
            .amnp-stats{font-size:14px}
            .amnp-status{padding:2px 6px;border-radius:3px;font-size:12px}
            .amnp-s0{background:#fcf0c3}.amnp-s1{background:#d6e9fb}.amnp-s2{background:#d7f0db}
            .amnp-badge{display:inline-block;min-width:18px;text-align:center;margin-right:3px;padding:1px 5px;border-radius:3px;font-size:11px;font-weight:600}
            .amnp-badge.ok{background:#d7f0db;color:#1e5b2a}.amnp-badge.ko{background:#f8d7da;color:#842029}
            .amnp-seo th{width:160px}
            .amnp-image{max-width:700px;width:100%;height:auto;border:1px solid #ddd}
            .amnp-content{max-width:820px;background:#fff;border:1px solid #ddd;padding:16px 20px}
            .amnp-run-failed{color:#b32d2e;font-weight:600}.amnp-run-partial{color:#996800;font-weight:600}
            details pre{max-width:640px;white-space:pre-wrap}
        </style>';
    }
}
