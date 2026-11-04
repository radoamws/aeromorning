<?php
/**
 * Admin > Statistiques: audience of the FR and EN sites for a period,
 * compared with another one, with countries, cities and outbound clicks
 * (advertiser banners). Printable for advertisers, exportable as CSV.
 *
 * One batchRunReports call per GA4 property and filter combination, cached
 * (1 h while the period is recent, 24 h once GA has finalised it).
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AMS_Page
{
    private const SLUG = 'aeromorning-stats';

    /** Our metric keys => GA4 metric names, in request order. */
    private const METRICS = [
        'users'    => 'totalUsers',
        'sessions' => 'sessions',
        'views'    => 'screenPageViews',
        'engaged'  => 'engagedSessions',
    ];
    private const LABELS = [
        'users'    => 'Visiteurs uniques',
        'sessions' => 'Visites',
        'views'    => 'Pages vues',
        'engaged'  => 'Visites engagées',
    ];
    private const VERSIONS = ['fr' => 'FR (aeromorning.com)', 'en' => 'UK (aeromorning.com/en)'];
    private const TOP = 25;

    private static string $hook = '';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_enqueue_scripts', [self::class, 'assets']);
    }

    public static function menu(): void
    {
        self::$hook = (string) add_menu_page(
            'Statistiques d\'audience', 'Statistiques', 'manage_options', self::SLUG,
            [self::class, 'render'], 'dashicons-chart-area', 3
        );
        // Before any output: the CSV export replaces the page.
        add_action('load-' . self::$hook, [self::class, 'maybeExport']);
    }

    public static function assets(string $hook): void
    {
        if ($hook !== self::$hook) {
            return;
        }
        $dir = __DIR__ . '/assets/';
        $url = content_url('mu-plugins/aeromorning-stats/assets/');
        wp_enqueue_style('ams-admin', $url . 'admin.css', [], (string) filemtime($dir . 'admin.css'));
        wp_enqueue_script('ams-admin', $url . 'admin.js', [], (string) filemtime($dir . 'admin.js'), true);
    }

    /* ------------------------------------------------------------------ */
    /* Filters                                                            */
    /* ------------------------------------------------------------------ */

    private static function params(): array
    {
        $get = wp_unslash($_GET);
        $tz = wp_timezone();
        $today = new DateTimeImmutable('today', $tz);

        $version = in_array($get['v'] ?? '', ['both', 'fr', 'en'], true) ? $get['v'] : 'both';
        $mode = ($get['mode'] ?? '') === 'custom' ? 'custom' : 'month';
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) ($get['month'] ?? ''))
            ? $get['month'] : $today->modify('first day of last month')->format('Y-m');

        if ($mode === 'month') {
            $from = new DateTimeImmutable($month . '-01', $tz);
            $to = $from->modify('last day of this month');
        } else {
            $from = self::date((string) ($get['from'] ?? ''), $tz) ?? $today->modify('-29 days');
            $to = self::date((string) ($get['to'] ?? ''), $tz) ?? $today;
        }
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $wholeMonth = $mode === 'month' && $to <= $today;
        $to = min($to, $today);
        $from = max(min($from, $to), $to->modify('-3 years'));

        $compare = in_array($get['cmp'] ?? '', ['year', 'prev', 'none'], true) ? $get['cmp'] : 'year';
        $cFrom = $cTo = null;
        if ($compare === 'year') {
            $cFrom = self::minusYear($from);
            // A whole month is compared with the whole month (28 vs 29 Feb).
            $cTo = $wholeMonth ? $cFrom->modify('last day of this month') : self::minusYear($to);
        } elseif ($compare === 'prev') {
            $days = (int) $from->diff($to)->days;
            $cTo = $from->modify('-1 day');
            $cFrom = $cTo->modify("-{$days} days");
        }

        $list = static function (string $key) use ($get): array {
            $values = explode(',', sanitize_text_field((string) ($get[$key] ?? '')));
            return array_values(array_unique(array_filter(array_map('trim', $values), 'strlen')));
        };

        return [
            'v'       => $version,
            'mode'    => $mode,
            'month'   => $month,
            'from'    => $from,
            'to'      => $to,
            'cmp'     => $compare,
            'cFrom'   => $cFrom,
            'cTo'     => $cTo,
            'sort'    => isset(self::METRICS[$get['sort'] ?? '']) ? $get['sort'] : 'users',
            'country' => sanitize_text_field((string) ($get['country'] ?? '')),
            'exclude' => $list('exclude'),
            'xcity'   => $list('xcity'),
            'adv'     => sanitize_text_field((string) ($get['adv'] ?? '')),
            'today'   => $today,
        ];
    }

    private static function date(string $value, DateTimeZone $tz): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $tz);
        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }

    /** Same day one year earlier, 29 Feb becoming 28 Feb. */
    private static function minusYear(DateTimeImmutable $date): DateTimeImmutable
    {
        $first = $date->modify('first day of this month')->modify('-1 year');
        return $first->setDate((int) $first->format('Y'), (int) $first->format('n'), min((int) $date->format('j'), (int) $first->format('t')));
    }

    /** URL of the page with the current filters, plus overrides. */
    private static function url(array $p, array $override = []): string
    {
        $args = array_merge([
            'page'    => self::SLUG,
            'v'       => $p['v'],
            'mode'    => $p['mode'],
            'month'   => $p['month'],
            'from'    => $p['from']->format('Y-m-d'),
            'to'      => $p['to']->format('Y-m-d'),
            'cmp'     => $p['cmp'],
            'sort'    => $p['sort'],
            'country' => $p['country'],
            'exclude' => implode(',', $p['exclude']),
            'xcity'   => implode(',', $p['xcity']),
            'adv'     => $p['adv'],
        ], $override);
        return add_query_arg(array_map('rawurlencode', array_filter($args, 'strlen')), admin_url('admin.php'));
    }

    /* ------------------------------------------------------------------ */
    /* Data                                                               */
    /* ------------------------------------------------------------------ */

    private static function fetch(array $p): array
    {
        $ranges = [['startDate' => $p['from']->format('Y-m-d'), 'endDate' => $p['to']->format('Y-m-d'), 'name' => 'cur']];
        if ($p['cFrom']) {
            $ranges[] = ['startDate' => $p['cFrom']->format('Y-m-d'), 'endDate' => $p['cTo']->format('Y-m-d'), 'name' => 'cmp'];
        }
        $multi = count($ranges) > 1;
        $daily = $p['from']->diff($p['to'])->days <= 92;
        $keyFormat = $daily ? 'Ymd' : 'Ym';
        $bounds = ['cur' => [$p['from']->format($keyFormat), $p['to']->format($keyFormat)]];
        if ($p['cFrom']) {
            $bounds['cmp'] = [$p['cFrom']->format($keyFormat), $p['cTo']->format($keyFormat)];
        }

        $metrics = array_map(static fn (string $m): array => ['name' => $m], array_values(self::METRICS));
        $byUsers = [['metric' => ['metricName' => 'totalUsers'], 'desc' => true]];
        $geo = self::geoFilter($p);
        $base = ['dateRanges' => $ranges, 'metrics' => $metrics] + ($geo ? ['dimensionFilter' => $geo] : []);

        $clickFilter = [['filter' => ['fieldName' => 'eventName', 'stringFilter' => ['value' => 'click']]]];
        if ($p['adv'] !== '') {
            $clickFilter[] = ['filter' => ['fieldName' => 'linkDomain', 'stringFilter' => ['value' => $p['adv'], 'matchType' => 'CONTAINS', 'caseSensitive' => false]]];
        }
        if ($geo) {
            $clickFilter[] = $geo;
        }

        $requests = [
            $base,
            $base + ['dimensions' => [['name' => 'country']], 'orderBys' => $byUsers, 'limit' => 500],
            $base + ['dimensions' => [['name' => 'city'], ['name' => 'country']], 'orderBys' => $byUsers, 'limit' => 1000],
            $base + ['dimensions' => [['name' => $daily ? 'date' : 'yearMonth']], 'keepEmptyRows' => true, 'limit' => 2000],
            [
                'dateRanges'      => $ranges,
                'dimensions'      => [['name' => 'linkDomain']],
                'metrics'         => [['name' => 'eventCount']],
                'dimensionFilter' => ['andGroup' => ['expressions' => $clickFilter]],
                'orderBys'        => [['metric' => ['metricName' => 'eventCount'], 'desc' => true]],
                'limit'           => 300,
            ],
        ];

        // GA finalises a day's data within ~48 h.
        $ttl = $p['to'] >= $p['today']->modify('-2 days') ? HOUR_IN_SECONDS : DAY_IN_SECONDS;
        $versions = $p['v'] === 'both' ? array_keys(self::VERSIONS) : [$p['v']];

        $empty = array_fill_keys(array_keys(self::METRICS), 0.0);
        $data = ['totals' => [], 'versions' => [], 'countries' => [], 'cities' => [], 'series' => [], 'clicks' => [], 'daily' => $daily];

        foreach ($versions as $version) {
            $reports = AMS_GA::batch(AMS_GA::properties()[$version], $requests, $ttl);

            foreach (self::rows($reports[0] ?? [], $multi) as [, $range, $values]) {
                $data['versions'][$version][$range] = self::metrics($values);
                $data['totals'][$range] = self::sum($data['totals'][$range] ?? $empty, self::metrics($values));
            }
            foreach (self::rows($reports[1] ?? [], $multi) as [$dims, $range, $values]) {
                $row = &$data['countries'][$dims[0]];
                $row['label'] = $dims[0];
                $row[$range] = self::sum($row[$range] ?? $empty, self::metrics($values));
                unset($row);
            }
            foreach (self::rows($reports[2] ?? [], $multi) as [$dims, $range, $values]) {
                $row = &$data['cities'][$dims[0] . '|' . $dims[1]];
                $row['label'] = $dims[0];
                $row['country'] = $dims[1];
                $row[$range] = self::sum($row[$range] ?? $empty, self::metrics($values));
                unset($row);
            }
            foreach (self::rows($reports[3] ?? [], $multi) as [$dims, $range, $values]) {
                // keepEmptyRows returns every date of every range for each
                // range: keep the range's own dates only.
                [$min, $max] = $bounds[$range];
                if ($dims[0] < $min || $dims[0] > $max) {
                    continue;
                }
                $data['series'][$range][$dims[0]] =self::sum($data['series'][$range][$dims[0]] ?? $empty, self::metrics($values));
            }
            foreach (self::rows($reports[4] ?? [], $multi) as [$dims, $range, $values]) {
                $domain = $dims[0] !== '' ? $dims[0] : '(non renseigné)';
                $data['clicks'][$domain][$range] = ($data['clicks'][$domain][$range] ?? 0) + (float) $values[0];
            }
        }
        foreach ($data['series'] as &$points) {
            ksort($points);
        }
        unset($points);

        return $data;
    }

    /** @return array<int, array{0: string[], 1: string, 2: string[]}> */
    private static function rows(array $report, bool $multi): array
    {
        $out = [];
        foreach ($report['rows'] ?? [] as $row) {
            $dims = array_column($row['dimensionValues'] ?? [], 'value');
            // With several date ranges GA appends a "dateRange" dimension.
            $range = $multi ? (string) array_pop($dims) : 'cur';
            $out[] = [$dims, $range, array_column($row['metricValues'] ?? [], 'value')];
        }
        return $out;
    }

    private static function metrics(array $values): array
    {
        return array_combine(array_keys(self::METRICS), array_map('floatval', array_pad(array_slice($values, 0, 4), 4, 0)));
    }

    private static function sum(array $a, array $b): array
    {
        foreach ($b as $k => $v) {
            $a[$k] = ($a[$k] ?? 0) + $v;
        }
        return $a;
    }

    private static function geoFilter(array $p): ?array
    {
        $expressions = [];
        if ($p['country'] !== '') {
            $expressions[] = ['filter' => ['fieldName' => 'country', 'stringFilter' => ['value' => $p['country']]]];
        }
        if ($p['exclude']) {
            $expressions[] = ['notExpression' => ['filter' => ['fieldName' => 'country', 'inListFilter' => ['values' => $p['exclude']]]]];
        }
        if ($p['xcity']) {
            $expressions[] = ['notExpression' => ['filter' => ['fieldName' => 'city', 'inListFilter' => ['values' => $p['xcity']]]]];
        }
        if (!$expressions) {
            return null;
        }
        return count($expressions) === 1 ? $expressions[0] : ['andGroup' => ['expressions' => $expressions]];
    }

    /**
     * Traffic of crawlers and headless browsers that run the GA tag: visits
     * that are (almost) never engaged. Data centres show up as cities
     * (Groningen, The Dalles, Ashburn...), whole countries when the bots are
     * spread over them (Singapore, China in 2025-2026).
     *
     * @return array{countries: array, cities: array}
     */
    private static function suspects(array $data): array
    {
        $out = ['countries' => [], 'cities' => []];
        $total = $data['totals']['cur'] ?? null;
        if (!$total || $total['users'] <= 0 || $total['sessions'] <= 0) {
            return $out;
        }
        $rate = static fn (array $m): float => $m['sessions'] > 0 ? $m['engaged'] / $m['sessions'] : 0.0;

        foreach ($data['countries'] as $row) {
            $cur = $row['cur'] ?? null;
            if (!$cur || $cur['sessions'] < 50) {
                continue;
            }
            $share = $cur['users'] / $total['users'];
            // Fixed thresholds: a rule relative to the overall rate would keep
            // flagging the next country once the first ones are excluded.
            if (($share >= 0.03 && $rate($cur) < 0.05) || ($share >= 0.05 && $rate($cur) < 0.15)) {
                $out['countries'][$row['label']] = ['share' => $share, 'rate' => $rate($cur)];
            }
        }
        foreach ($data['cities'] as $row) {
            $cur = $row['cur'] ?? null;
            if (!$cur || $row['label'] === '(not set)' || isset($out['countries'][$row['country']])
                || $cur['users'] < max(100, $total['users'] * 0.003) || $rate($cur) >= 0.05) {
                continue;
            }
            $out['cities'][$row['label']] = ['share' => $cur['users'] / $total['users'], 'rate' => $rate($cur)];
        }
        return $out;
    }

    /** Rows of a breakdown sorted on the chosen metric. */
    private static function ranked(array $rows, string $metric): array
    {
        $rows = array_filter($rows, static fn (array $r): bool => ($r['cur'][$metric] ?? 0) > 0);
        uasort($rows, static fn (array $a, array $b): int => $b['cur'][$metric] <=> $a['cur'][$metric]);
        return array_values($rows);
    }

    private static function rankedClicks(array $clicks): array
    {
        $clicks = array_filter($clicks, static fn (array $c): bool => ($c['cur'] ?? 0) > 0 || ($c['cmp'] ?? 0) > 0);
        uasort($clicks, static fn (array $a, array $b): int => [$b['cur'] ?? 0, $b['cmp'] ?? 0] <=> [$a['cur'] ?? 0, $a['cmp'] ?? 0]);
        return $clicks;
    }

    /* ------------------------------------------------------------------ */
    /* CSV                                                                */
    /* ------------------------------------------------------------------ */

    public static function maybeExport(): void
    {
        if (($_GET['export'] ?? '') !== 'csv' || !current_user_can('manage_options') || !AMS_GA::isConfigured()) {
            return;
        }
        $p = self::params();
        try {
            $data = self::fetch($p);
        } catch (RuntimeException $e) {
            wp_die(esc_html($e->getMessage()));
        }

        $hasCmp = (bool) $p['cFrom'];
        $keys = array_keys(self::METRICS);
        $num = static fn ($v): string => (string) round((float) $v);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="aeromorning-stats-' . $p['from']->format('Ymd') . '-' . $p['to']->format('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // so that Excel reads UTF-8
        $put = static function (array $line) use ($out): void {
            fputcsv($out, $line, ';');
        };

        $put(['AeroMorning - statistiques d\'audience']);
        $put(['Version', self::versionLabel($p['v'])]);
        $put(['Période', $p['from']->format('d/m/Y') . ' - ' . $p['to']->format('d/m/Y')]);
        if ($hasCmp) {
            $put(['Comparée à', $p['cFrom']->format('d/m/Y') . ' - ' . $p['cTo']->format('d/m/Y')]);
        }
        foreach (self::filterSummary($p) as $label => $value) {
            $put([$label, $value]);
        }

        $put([]);
        $head = ['Version'];
        foreach ($keys as $k) {
            $head[] = self::LABELS[$k];
            if ($hasCmp) {
                $head[] = self::LABELS[$k] . ' (comparaison)';
            }
        }
        $put($head);
        $versions = $data['versions'] + ($p['v'] === 'both' ? ['total' => $data['totals']] : []);
        foreach ($versions as $version => $ranges) {
            $line = [$version === 'total' ? 'Total FR + UK' : self::VERSIONS[$version]];
            foreach ($keys as $k) {
                $line[] = $num($ranges['cur'][$k] ?? 0);
                if ($hasCmp) {
                    $line[] = $num($ranges['cmp'][$k] ?? 0);
                }
            }
            $put($line);
        }

        foreach (['countries' => 'Pays', 'cities' => 'Ville'] as $key => $title) {
            $put([]);
            $head = $key === 'cities' ? [$title, 'Pays'] : [$title];
            foreach ($keys as $k) {
                $head[] = self::LABELS[$k];
            }
            if ($hasCmp) {
                $head[] = self::LABELS[$p['sort']] . ' (comparaison)';
            }
            $put($head);
            foreach (self::ranked($data[$key], $p['sort']) as $row) {
                $line = $key === 'cities' ? [$row['label'], $row['country']] : [$row['label']];
                foreach ($keys as $k) {
                    $line[] = $num($row['cur'][$k]);
                }
                if ($hasCmp) {
                    $line[] = $num($row['cmp'][$p['sort']] ?? 0);
                }
                $put($line);
            }
        }

        $put([]);
        $put($hasCmp ? ['Clics sortants (domaine)', 'Clics', 'Clics (comparaison)'] : ['Clics sortants (domaine)', 'Clics']);
        foreach (self::rankedClicks($data['clicks']) as $domain => $c) {
            $put($hasCmp ? [$domain, $num($c['cur'] ?? 0), $num($c['cmp'] ?? 0)] : [$domain, $num($c['cur'] ?? 0)]);
        }

        fclose($out);
        exit;
    }

    /* ------------------------------------------------------------------ */
    /* Page                                                               */
    /* ------------------------------------------------------------------ */

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $p = self::params();

        echo '<div class="wrap ams">';
        echo '<h1 class="ams-title">Statistiques d\'audience <span>AeroMorning</span></h1>';

        if (!AMS_GA::isConfigured()) {
            echo '<div class="notice notice-error"><p>Google Analytics n\'est pas configuré : définissez <code>AEROMORNING_GA_KEY_FILE</code> dans <code>wp-config.php</code> (chemin de la clé JSON du compte de service, hors du dossier public).</p></div></div>';
            return;
        }

        self::renderFilters($p);

        try {
            $data = self::fetch($p);
        } catch (RuntimeException $e) {
            echo '<div class="notice notice-error"><p>' . esc_html($e->getMessage()) . '</p></div></div>';
            return;
        }

        $hasCmp = (bool) $p['cFrom'];

        echo '<div class="ams-context"><div class="ams-context-text">';
        echo '<strong>' . esc_html(self::versionLabel($p['v'])) . '</strong> · ' . esc_html(self::period($p['from'], $p['to']));
        if ($hasCmp) {
            echo ' <span class="ams-muted">comparé à ' . esc_html(self::period($p['cFrom'], $p['cTo'])) . '</span>';
        }
        foreach (self::filterSummary($p) as $label => $value) {
            echo ' <span class="ams-chip">' . esc_html($label . ' : ' . $value) . '</span>';
        }
        echo '</div><div class="ams-actions ams-noprint">';
        echo '<button type="button" class="button" onclick="window.print()"><span class="dashicons dashicons-printer"></span> Imprimer / PDF</button> ';
        echo '<a class="button" href="' . esc_url(self::url($p, ['export' => 'csv'])) . '"><span class="dashicons dashicons-download"></span> Export CSV</a>';
        echo '</div></div>';

        $suspects = self::suspects($data);
        if ($suspects['countries'] || $suspects['cities']) {
            echo '<div class="notice notice-warning ams-noprint ams-suspects"><p><strong>Trafic probablement automatisé</strong> (robots qui exécutent Google Analytics : beaucoup de visiteurs, presque aucune visite engagée).</p><ul>';
            foreach (['countries' => 'Pays', 'cities' => 'Villes / centres de données'] as $type => $title) {
                if (!$suspects[$type]) {
                    continue;
                }
                $items = [];
                foreach ($suspects[$type] as $name => $s) {
                    $items[] = sprintf('<strong>%s</strong> <span class="ams-muted">(%s des visiteurs, %s engagées)</span>', esc_html($name), self::pct($s['share']), self::pct($s['rate']));
                }
                echo '<li>' . esc_html($title) . ' : ' . implode(', ', $items) . '</li>';
            }
            $excludeAll = self::url($p, [
                'exclude' => implode(',', array_merge($p['exclude'], array_keys($suspects['countries']))),
                'xcity'   => implode(',', array_merge($p['xcity'], array_keys($suspects['cities']))),
            ]);
            echo '</ul><p><a class="button button-primary button-small" href="' . esc_url($excludeAll) . '">Exclure ce trafic</a> <span class="ams-muted">Les filtres restent modifiables ci-dessus.</span></p></div>';
        }

        // Key figures.
        echo '<div class="ams-kpis">';
        foreach (self::LABELS as $k => $label) {
            $cur = $data['totals']['cur'][$k] ?? 0;
            $approx = $p['v'] === 'both' && $k === 'users';
            echo '<div class="ams-kpi' . ($k === $p['sort'] ? ' is-active' : '') . '">';
            echo '<div class="ams-kpi-label">' . esc_html($label) . ($approx ? ' <span class="ams-approx" title="Somme des deux versions : un visiteur venu sur les deux est compté deux fois.">≈</span>' : '') . '</div>';
            echo '<div class="ams-kpi-value">' . self::num($cur) . '</div>';
            if ($hasCmp) {
                $cmp = $data['totals']['cmp'][$k] ?? 0;
                echo '<div class="ams-kpi-cmp">' . self::delta($cur, $cmp) . ' <span class="ams-muted">vs ' . self::num($cmp) . '</span></div>';
            }
            if ($k === 'engaged' && ($data['totals']['cur']['sessions'] ?? 0) > 0) {
                echo '<div class="ams-kpi-note">' . self::pct($cur / $data['totals']['cur']['sessions']) . ' des visites</div>';
            }
            echo '</div>';
        }
        echo '</div>';

        if ($p['v'] === 'both') {
            self::renderVersions($data, $hasCmp);
        }

        self::renderChart($data, $p, $hasCmp);

        echo '<div class="ams-grid">';
        self::renderBreakdown('Pays', $data['countries'], $data, $p, false);
        self::renderBreakdown('Villes', $data['cities'], $data, $p, true);
        echo '</div>';

        self::renderClicks($data, $p, $hasCmp);

        echo '<div class="ams-help"><p><strong>Définitions (Google Analytics 4).</strong> ';
        echo '<em>Visiteurs uniques</em> : utilisateurs distincts sur la période (« Total users »). ';
        echo '<em>Visites</em> : sessions. ';
        echo '<em>Pages vues</em> : pages affichées ; la bannière du haut et la bannière pleine largeur figurant sur toutes les pages, c\'est aussi leur nombre d\'affichages. ';
        echo '<em>Visites engagées</em> : visites de plus de 10 secondes, ou d\'au moins 2 pages, ou avec conversion ; c\'est l\'indicateur le moins sensible aux robots. ';
        echo '<em>Clics sortants</em> : clics vers un autre site, par domaine de destination. ';
        echo 'Les données des 48 dernières heures peuvent encore évoluer.</p></div>';

        echo '</div>';
    }

    private static function renderFilters(array $p): void
    {
        $today = $p['today'];
        $months = [];
        $cursor = $today->modify('first day of this month');
        for ($i = 0; $i < 36; $i++) {
            $months[$cursor->format('Y-m')] = ucfirst(wp_date('F Y', $cursor->getTimestamp()));
            $cursor = $cursor->modify('-1 month');
        }
        $lastYear = (int) $today->format('Y') - 1;
        $presets = [
            'Mois dernier'      => ['mode' => 'month', 'month' => $today->modify('first day of last month')->format('Y-m')],
            'Mois en cours'     => ['mode' => 'month', 'month' => $today->format('Y-m')],
            '30 derniers jours' => ['mode' => 'custom', 'from' => $today->modify('-29 days')->format('Y-m-d'), 'to' => $today->format('Y-m-d')],
            '12 derniers mois'  => ['mode' => 'custom', 'from' => $today->modify('first day of this month')->modify('-12 months')->format('Y-m-d'), 'to' => $today->modify('last day of last month')->format('Y-m-d')],
            'Année en cours'    => ['mode' => 'custom', 'from' => $today->format('Y') . '-01-01', 'to' => $today->format('Y-m-d')],
            'Année dernière'    => ['mode' => 'custom', 'from' => $lastYear . '-01-01', 'to' => $lastYear . '-12-31'],
        ];
        $select = static function (string $name, string $label, array $options, string $current): void {
            echo '<div class="ams-field"><label for="ams-' . esc_attr($name) . '">' . esc_html($label) . '</label><select id="ams-' . esc_attr($name) . '" name="' . esc_attr($name) . '">';
            foreach ($options as $value => $text) {
                echo '<option value="' . esc_attr((string) $value) . '"' . selected($current, (string) $value, false) . '>' . esc_html($text) . '</option>';
            }
            echo '</select></div>';
        };

        echo '<form class="ams-filters ams-noprint" method="get" action="' . esc_url(admin_url('admin.php')) . '">';
        echo '<input type="hidden" name="page" value="' . esc_attr(self::SLUG) . '">';

        echo '<div class="ams-row">';
        $select('v', 'Version du site', ['both' => 'FR + UK'] + self::VERSIONS, $p['v']);
        $select('mode', 'Période', ['month' => 'Un mois', 'custom' => 'Dates libres'], $p['mode']);
        echo '<div class="ams-field" data-mode="month"><label for="ams-month">Mois</label><select id="ams-month" name="month">';
        foreach ($months as $value => $label) {
            echo '<option value="' . esc_attr($value) . '"' . selected($p['month'], $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></div>';
        $max = esc_attr($today->format('Y-m-d'));
        echo '<div class="ams-field" data-mode="custom"><label for="ams-from">Du</label><input type="date" id="ams-from" name="from" value="' . esc_attr($p['from']->format('Y-m-d')) . '" max="' . $max . '"></div>';
        echo '<div class="ams-field" data-mode="custom"><label for="ams-to">Au</label><input type="date" id="ams-to" name="to" value="' . esc_attr($p['to']->format('Y-m-d')) . '" max="' . $max . '"></div>';
        $select('cmp', 'Comparer à', ['year' => 'Même période, année précédente', 'prev' => 'Période précédente', 'none' => 'Aucune comparaison'], $p['cmp']);
        $select('sort', 'Classer par', self::LABELS, $p['sort']);
        echo '</div>';

        echo '<div class="ams-row">';
        echo '<div class="ams-field"><label for="ams-country">Pays uniquement</label><input type="text" id="ams-country" name="country" list="ams-countries" placeholder="Tous les pays" value="' . esc_attr($p['country']) . '"></div>';
        echo '<div class="ams-field ams-field-wide"><label for="ams-exclude">Exclure les pays <small>(séparés par des virgules)</small></label><input type="text" id="ams-exclude" name="exclude" placeholder="ex. Singapore, China" value="' . esc_attr(implode(', ', $p['exclude'])) . '"></div>';
        echo '<div class="ams-field ams-field-wide"><label for="ams-xcity">Exclure les villes</label><input type="text" id="ams-xcity" name="xcity" placeholder="ex. Groningen, The Dalles" value="' . esc_attr(implode(', ', $p['xcity'])) . '"></div>';
        echo '<div class="ams-field"><label for="ams-adv">Annonceur (domaine cliqué)</label><input type="text" id="ams-adv" name="adv" placeholder="ex. liebherr" value="' . esc_attr($p['adv']) . '"></div>';
        echo '<div class="ams-field ams-submit"><button type="submit" class="button button-primary">Afficher</button>';
        echo '<a class="button-link" href="' . esc_url(admin_url('admin.php?page=' . self::SLUG)) . '">Réinitialiser</a></div>';
        echo '</div>';

        echo '<div class="ams-presets"><span class="ams-muted">Raccourcis :</span>';
        foreach ($presets as $label => $override) {
            echo ' <a class="button button-small" href="' . esc_url(self::url($p, $override)) . '">' . esc_html($label) . '</a>';
        }
        echo '</div>';

        // GA4 country names are in English.
        echo '<datalist id="ams-countries">';
        foreach (['France', 'United States', 'United Kingdom', 'Germany', 'Canada', 'Belgium', 'Switzerland', 'Netherlands', 'Spain', 'Italy', 'India', 'Brazil', 'Japan', 'China', 'Singapore', 'Hong Kong', 'United Arab Emirates', 'Morocco', 'Tunisia', 'Algeria'] as $country) {
            echo '<option value="' . esc_attr($country) . '">';
        }
        echo '</datalist>';
        echo '</form>';
    }

    private static function renderVersions(array $data, bool $hasCmp): void
    {
        echo '<div class="ams-card"><h2>Par version du site</h2><table class="widefat striped ams-table"><thead><tr><th>Version</th>';
        foreach (self::LABELS as $label) {
            echo '<th class="num">' . esc_html($label) . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ($data['versions'] + ['total' => $data['totals']] as $version => $ranges) {
            $isTotal = $version === 'total';
            echo '<tr' . ($isTotal ? ' class="ams-total"' : '') . '><td>' . esc_html($isTotal ? 'Total' : self::VERSIONS[$version]) . '</td>';
            foreach (array_keys(self::METRICS) as $k) {
                $cur = $ranges['cur'][$k] ?? 0;
                echo '<td class="num">' . ($isTotal && $k === 'users' ? '≈ ' : '') . self::num($cur);
                if ($hasCmp) {
                    $cmp = $ranges['cmp'][$k] ?? 0;
                    echo '<br><small>' . self::delta($cur, $cmp) . ' <span class="ams-muted">vs ' . self::num($cmp) . '</span></small>';
                }
                echo '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    /** Inline SVG line chart of the chosen metric (no JS library). */
    private static function renderChart(array $data, array $p, bool $hasCmp): void
    {
        $metric = $p['sort'];
        $keys = array_keys($data['series']['cur'] ?? []);
        $pick = static fn (array $points): array => array_values(array_map(static fn (array $m): float => $m[$metric], $points));
        $cur = $pick($data['series']['cur'] ?? []);
        $cmp = $hasCmp ? $pick($data['series']['cmp'] ?? []) : [];
        if (count($cur) < 2) {
            return;
        }

        $w = 1000;
        $h = 240;
        $padL = 60;
        $padB = 28;
        $n = max(count($cur), count($cmp));
        $max = max(1.0, ...$cur, ...($cmp ?: [0.0]));
        $step = 10 ** floor(log10($max));
        $max = ceil($max / $step) * $step;
        $x = static fn (int $i): float => $padL + ($w - $padL - 10) * $i / max(1, $n - 1);
        $y = static fn (float $v): float => 10 + ($h - $padB - 10) * (1 - $v / $max);
        $path = static function (array $values) use ($x, $y): string {
            $d = '';
            foreach ($values as $i => $v) {
                $d .= ($i ? 'L' : 'M') . round($x($i), 1) . ' ' . round($y($v), 1);
            }
            return $d;
        };

        echo '<div class="ams-card"><h2>Évolution : ' . esc_html(mb_strtolower(self::LABELS[$metric])) . ' par ' . ($data['daily'] ? 'jour' : 'mois') . '</h2>';
        echo '<svg class="ams-chart" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="' . esc_attr(self::LABELS[$metric]) . '">';
        for ($i = 0; $i <= 4; $i++) {
            $v = $max * $i / 4;
            $gy = round($y($v), 1);
            echo '<line class="grid" x1="' . $padL . '" x2="' . ($w - 10) . '" y1="' . $gy . '" y2="' . $gy . '"/>';
            echo '<text class="axis" x="' . ($padL - 8) . '" y="' . ($gy + 4) . '" text-anchor="end">' . esc_html(self::num($v)) . '</text>';
        }
        $labels = min(8, count($keys));
        for ($j = 0; $j < $labels; $j++) {
            $i = (int) round((count($keys) - 1) * $j / max(1, $labels - 1));
            $key = (string) $keys[$i];
            // date = YYYYMMDD, yearMonth = YYYYMM
            $text = strlen($key) === 8 ? substr($key, 6, 2) . '/' . substr($key, 4, 2) : substr($key, 4, 2) . '/' . substr($key, 0, 4);
            echo '<text class="axis" x="' . round($x($i), 1) . '" y="' . ($h - 8) . '" text-anchor="middle">' . esc_html($text) . '</text>';
        }
        if ($cmp) {
            echo '<path class="line-cmp" d="' . $path($cmp) . '"/>';
        }
        echo '<path class="line-cur" d="' . $path($cur) . '"/>';
        echo '</svg>';
        echo '<div class="ams-legend"><span class="cur"></span>' . esc_html(self::period($p['from'], $p['to']));
        if ($cmp) {
            echo '<span class="cmp"></span>' . esc_html(self::period($p['cFrom'], $p['cTo']));
        }
        echo '</div></div>';
    }

    private static function renderBreakdown(string $title, array $rows, array $data, array $p, bool $withCountry): void
    {
        $metric = $p['sort'];
        $hasCmp = (bool) $p['cFrom'];
        $ranked = self::ranked($rows, $metric);
        $total = max(1, $data['totals']['cur'][$metric] ?? 0);

        echo '<div class="ams-card"><h2>' . esc_html($title) . ' <span class="ams-muted">classés par ' . esc_html(mb_strtolower(self::LABELS[$metric])) . ' · ' . count($ranked) . '</span></h2>';
        if (!$ranked) {
            echo '<p class="ams-muted">Aucune donnée.</p></div>';
            return;
        }

        $head = '<div class="ams-scroll"><table class="widefat striped ams-table"><thead><tr><th class="rank">#</th><th>' . esc_html($withCountry ? 'Ville' : 'Pays') . '</th>';
        foreach (self::LABELS as $k => $label) {
            $head .= '<th class="num' . ($k === $metric ? ' is-active' : '') . '">' . esc_html($label) . '</th>';
        }
        $head .= ($hasCmp ? '<th class="num">Évol.</th>' : '') . '<th class="num">Part</th></tr></thead><tbody>';

        echo $head;
        foreach ($ranked as $i => $row) {
            if ($i === self::TOP) {
                echo '</tbody></table></div><details class="ams-more"><summary>Afficher les ' . (count($ranked) - self::TOP) . ' autres</summary>' . $head;
            }
            $label = $row['label'] === '(not set)' ? '(non déterminé)' : $row['label'];
            echo '<tr><td class="rank">' . ($i + 1) . '</td><td class="label">' . esc_html($label) . ($withCountry ? ' <span class="ams-muted">' . esc_html($row['country']) . '</span>' : '') . '</td>';
            foreach (array_keys(self::METRICS) as $k) {
                echo '<td class="num' . ($k === $metric ? ' is-active' : '') . '">' . self::num($row['cur'][$k]) . '</td>';
            }
            if ($hasCmp) {
                echo '<td class="num">' . self::delta($row['cur'][$metric], $row['cmp'][$metric] ?? 0) . '</td>';
            }
            echo '<td class="num">' . self::pct($row['cur'][$metric] / $total) . '</td></tr>';
        }
        echo '</tbody></table></div>' . (count($ranked) > self::TOP ? '</details>' : '') . '</div>';
    }

    private static function renderClicks(array $data, array $p, bool $hasCmp): void
    {
        $clicks = self::rankedClicks($data['clicks']);
        $adv = $p['adv'];
        echo '<div class="ams-card"><h2>Clics sortants' . ($adv !== '' ? ' vers « ' . esc_html($adv) . ' »' : ' par domaine') . '</h2>';
        if (!$clicks) {
            echo '<p class="ams-muted">Aucun clic enregistré' . ($adv !== '' ? ' vers un domaine contenant « ' . esc_html($adv) . ' »' : '') . ' sur la période.</p></div>';
            return;
        }
        echo '<table class="widefat striped ams-table"><thead><tr><th>Domaine</th><th class="num">Clics</th>' . ($hasCmp ? '<th class="num">Comparaison</th><th class="num">Évol.</th>' : '') . '</tr></thead><tbody>';
        foreach (array_slice($clicks, 0, $adv !== '' ? 100 : 20, true) as $domain => $c) {
            echo '<tr><td>' . esc_html((string) $domain) . '</td><td class="num">' . self::num($c['cur'] ?? 0) . '</td>';
            if ($hasCmp) {
                echo '<td class="num">' . self::num($c['cmp'] ?? 0) . '</td><td class="num">' . self::delta($c['cur'] ?? 0, $c['cmp'] ?? 0) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
        if ($adv === '') {
            echo '<p class="ams-muted ams-noprint">Saisissez le domaine d\'un annonceur dans « Annonceur » pour isoler les clics vers son site.</p>';
        }
        echo '</div>';
    }

    /* ------------------------------------------------------------------ */
    /* Formatting                                                         */
    /* ------------------------------------------------------------------ */

    private static function versionLabel(string $version): string
    {
        return $version === 'both' ? 'FR + UK' : self::VERSIONS[$version];
    }

    private static function filterSummary(array $p): array
    {
        $out = [];
        if ($p['country'] !== '') {
            $out['Pays'] = $p['country'];
        }
        if ($p['exclude']) {
            $out['Pays exclus'] = implode(', ', $p['exclude']);
        }
        if ($p['xcity']) {
            $out['Villes exclues'] = implode(', ', $p['xcity']);
        }
        if ($p['adv'] !== '') {
            $out['Annonceur'] = $p['adv'];
        }
        return $out;
    }

    private static function period(DateTimeImmutable $from, DateTimeImmutable $to): string
    {
        if ($from->format('j') === '1' && $to->format('Y-m') === $from->format('Y-m') && $to->format('t') === $to->format('j')) {
            return ucfirst(wp_date('F Y', $from->getTimestamp()));
        }
        return 'du ' . wp_date('j M Y', $from->getTimestamp()) . ' au ' . wp_date('j M Y', $to->getTimestamp());
    }

    private static function num(float $value): string
    {
        return number_format_i18n(round($value));
    }

    private static function pct(float $ratio): string
    {
        return number_format_i18n($ratio * 100, $ratio < 0.1 ? 1 : 0) . ' %';
    }

    private static function delta(float $cur, float $cmp): string
    {
        if ($cmp <= 0) {
            return '<span class="ams-delta">' . ($cur > 0 ? 'nouveau' : '–') . '</span>';
        }
        $change = ($cur - $cmp) / $cmp;
        $class = abs($change) < 0.005 ? '' : ($change > 0 ? ' up' : ' down');
        return '<span class="ams-delta' . $class . '">' . ($change > 0 ? '+' : '') . number_format_i18n($change * 100, abs($change) < 0.1 ? 1 : 0) . ' %</span>';
    }
}
