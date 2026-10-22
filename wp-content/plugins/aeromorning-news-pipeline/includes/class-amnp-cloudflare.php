<?php
/**
 * Targeted Cloudflare purge after publishing (homepages, feeds, new article
 * URLs) — never purge_everything. Port of extract_news CloudflareService.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AMNP_Cloudflare
{
    public function purgeAfterPublish(array $articleUrls): array
    {
        if (!AMNP_Config::bool('CLOUDFLARE_PURGE')) {
            return ['skipped' => true, 'reason' => 'AMNP_CLOUDFLARE_PURGE is off'];
        }

        $urls = [
            get_home_url(AMNP_Config::int('FR_BLOG_ID'), '/'),
            get_home_url(AMNP_Config::int('EN_BLOG_ID'), '/'),
            get_home_url(AMNP_Config::int('FR_BLOG_ID'), '/feed/'),
            get_home_url(AMNP_Config::int('EN_BLOG_ID'), '/feed/'),
        ];
        $urls = array_values(array_unique(array_filter(array_merge($urls, $articleUrls))));

        return $this->purgeUrls($urls);
    }

    /**
     * Last N minutes: total requests, requests blocked/challenged by Cloudflare,
     * top countries and paths (GraphQL Analytics). Used by the security monitor.
     */
    public function getThreatSummary(int $minutes = 5): array
    {
        $zoneId = AMNP_Config::str('CLOUDFLARE_ZONE_ID');
        $token  = AMNP_Config::str('CLOUDFLARE_API_TOKEN');
        if ($zoneId === '' || $token === '') {
            return ['success' => false, 'message' => 'Missing Cloudflare credentials'];
        }

        $now = gmdate('Y-m-d\TH:i:s\Z');
        $since = gmdate('Y-m-d\TH:i:s\Z', time() - $minutes * 60);
        $filter = "{datetime_geq: \"{$since}\", datetime_leq: \"{$now}\"}";
        $query = "query { viewer { zones(filter: {zoneTag: \"{$zoneId}\"}) {
            totals: httpRequestsAdaptiveGroups(limit: 1, filter: {$filter}) { count }
            byAction: firewallEventsAdaptiveGroups(limit: 10, filter: {$filter}, orderBy: [count_DESC]) { count dimensions { action } }
            topCountry: httpRequestsAdaptiveGroups(limit: 5, filter: {$filter}, orderBy: [count_DESC]) { count dimensions { clientCountryName } }
            topPath: httpRequestsAdaptiveGroups(limit: 5, filter: {$filter}, orderBy: [count_DESC]) { count dimensions { clientRequestPath } }
        } } }";

        $response = wp_remote_post('https://api.cloudflare.com/client/v4/graphql', [
            'timeout' => 15,
            'headers' => ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'],
            'body'    => wp_json_encode(['query' => $query]),
        ]);
        if (is_wp_error($response)) {
            return ['success' => false, 'message' => $response->get_error_message()];
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        $zone = $body['data']['viewer']['zones'][0] ?? null;
        if ((int) wp_remote_retrieve_response_code($response) !== 200 || $zone === null) {
            return ['success' => false, 'message' => 'GraphQL query failed: ' . wp_json_encode($body['errors'] ?? $body)];
        }

        $mitigated = 0;
        foreach ($zone['byAction'] ?? [] as $row) {
            if (($row['dimensions']['action'] ?? '') !== 'allow') {
                $mitigated += (int) $row['count'];
            }
        }

        return [
            'success'            => true,
            'total_requests'     => (int) ($zone['totals'][0]['count'] ?? 0),
            'mitigated_requests' => $mitigated,
            'top_countries'      => $zone['topCountry'] ?? [],
            'top_paths'          => $zone['topPath'] ?? [],
        ];
    }

    public function purgeUrls(array $urls): array
    {
        $zoneId = AMNP_Config::str('CLOUDFLARE_ZONE_ID');
        $token  = AMNP_Config::str('CLOUDFLARE_API_TOKEN');
        if ($zoneId === '' || $token === '') {
            return ['skipped' => true, 'reason' => 'Missing Cloudflare credentials'];
        }

        $results = [];
        // Cloudflare accepts at most 30 URLs per purge call.
        foreach (array_chunk($urls, 30) as $chunk) {
            $response = wp_remote_post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache", [
                'timeout' => 15,
                'headers' => ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'],
                'body'    => wp_json_encode(['files' => $chunk]),
            ]);

            $ok = !is_wp_error($response) && (int) wp_remote_retrieve_response_code($response) === 200;
            if (!$ok) {
                AMNP_Logger::warning('Cloudflare purge failed', [
                    'error' => is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response),
                ]);
            }
            $results[] = $ok;
        }

        return ['success' => !in_array(false, $results, true), 'urls' => count($urls)];
    }
}
