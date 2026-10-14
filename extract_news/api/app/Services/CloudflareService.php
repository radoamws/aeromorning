<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareService
{
    private string $zoneId;
    private string $apiToken;
    private bool $enabled;

    public function __construct()
    {
        $this->zoneId   = (string) env('CLOUDFLARE_ZONE_ID', '');
        $this->apiToken = (string) env('CLOUDFLARE_API_TOKEN', '');
        $this->enabled  = filter_var(env('CLOUDFLARE_PURGE_ENABLED', true), FILTER_VALIDATE_BOOL);
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Purge the FR and EN homepages + RSS feeds from Cloudflare cache.
     * Called after every successful WordPress publish batch.
     * Never purges the entire zone (no purge_everything).
     */
    public function purgeHomepage(): array
    {
        $frBase = rtrim((string) env('WORDPRESS_FR_URL', 'https://aeromorning.com'), '/');
        $enBase = rtrim((string) env('WORDPRESS_EN_URL', 'https://aeromorning.com/en'), '/');

        $urls = [
            $frBase . '/',         // FR homepage
            $enBase . '/',         // EN homepage
            $frBase . '/feed/',    // FR RSS feed (listed in readers/aggregators)
            $enBase . '/feed/',    // EN RSS feed
        ];

        return $this->callPurgeApi($urls, 'homepage+feeds');
    }

    /**
     * Purge one or more specific article URLs.
     * Pass the direct WordPress permalink(s) for both FR and EN editions.
     *
     * @param string[] $articleUrls  e.g. ['https://aeromorning.com/my-article/', 'https://aeromorning.com/en/my-article/']
     */
    public function purgeArticles(array $articleUrls): array
    {
        $urls = array_values(array_filter(array_unique($articleUrls)));

        if (empty($urls)) {
            return ['skipped' => true, 'reason' => 'No article URLs provided'];
        }

        return $this->callPurgeApi($urls, 'articles');
    }

    /**
     * Purge an arbitrary list of URLs.
     * Useful for admin/cron callers that build their own list.
     */
    public function purgeUrls(array $urls): array
    {
        $urls = array_values(array_filter(array_unique($urls)));

        if (empty($urls)) {
            return ['skipped' => true, 'reason' => 'No URLs provided'];
        }

        return $this->callPurgeApi($urls, 'custom');
    }

    /**
     * Security snapshot over the last N minutes: total requests, how many
     * were blocked/challenged by Cloudflare (WAF, bot fight, custom rules,
     * rate limiting), and the top offending country + path. Used by
     * security:monitor to decide whether to alert.
     */
    public function getThreatSummary(int $minutes = 5): array
    {
        if ($this->zoneId === '' || $this->apiToken === '') {
            return ['success' => false, 'message' => 'Missing Cloudflare credentials'];
        }

        $now = gmdate('Y-m-d\TH:i:s\Z');
        $since = gmdate('Y-m-d\TH:i:s\Z', time() - ($minutes * 60));

        $query = <<<GQL
        query {
          viewer {
            zones(filter: {zoneTag: "{$this->zoneId}"}) {
              totals: httpRequestsAdaptiveGroups(limit: 1, filter: {datetime_geq: "{$since}", datetime_leq: "{$now}"}) {
                count
              }
              byAction: firewallEventsAdaptiveGroups(limit: 10, filter: {datetime_geq: "{$since}", datetime_leq: "{$now}"}, orderBy: [count_DESC]) {
                count
                dimensions { action }
              }
              topCountry: httpRequestsAdaptiveGroups(limit: 5, filter: {datetime_geq: "{$since}", datetime_leq: "{$now}"}, orderBy: [count_DESC]) {
                count
                dimensions { clientCountryName }
              }
              topPath: httpRequestsAdaptiveGroups(limit: 5, filter: {datetime_geq: "{$since}", datetime_leq: "{$now}"}, orderBy: [count_DESC]) {
                count
                dimensions { clientRequestPath }
              }
            }
          }
        }
        GQL;

        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(15)
                ->post('https://api.cloudflare.com/client/v4/graphql', ['query' => $query]);

            $body = $response->json();
            $zone = $body['data']['viewer']['zones'][0] ?? null;

            if (!$response->ok() || $zone === null) {
                return ['success' => false, 'message' => 'GraphQL query failed: ' . json_encode($body['errors'] ?? $body)];
            }

            $totalRequests = $zone['totals'][0]['count'] ?? 0;
            $mitigated = 0;
            foreach ($zone['byAction'] ?? [] as $row) {
                if (($row['dimensions']['action'] ?? '') !== 'allow') {
                    $mitigated += $row['count'];
                }
            }

            return [
                'success' => true,
                'minutes' => $minutes,
                'total_requests' => $totalRequests,
                'mitigated_requests' => $mitigated,
                'by_action' => $zone['byAction'] ?? [],
                'top_countries' => $zone['topCountry'] ?? [],
                'top_paths' => $zone['topPath'] ?? [],
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Diagnostic: verify that the Cloudflare credentials are valid.
     * Returns ['success' => bool, 'status' => int, 'message' => string].
     */
    public function verifyToken(): array
    {
        if ($this->zoneId === '' || $this->apiToken === '') {
            return ['success' => false, 'message' => 'Missing CLOUDFLARE_ZONE_ID or CLOUDFLARE_API_TOKEN in .env'];
        }

        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->get("https://api.cloudflare.com/client/v4/zones/{$this->zoneId}");

            $body = $response->json();
            $ok = $response->ok() && ($body['success'] ?? false);

            return [
                'success'    => $ok,
                'http_status' => $response->status(),
                'zone_name'  => $body['result']['name'] ?? null,
                'message'    => $ok
                    ? 'Token valid — zone: ' . ($body['result']['name'] ?? '?')
                    : 'Token invalid or zone not found: ' . json_encode($body['errors'] ?? []),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // Internal
    // -------------------------------------------------------------------------

    /**
     * Send a purge_cache request for the given URLs.
     * Cloudflare allows max 30 URLs per request; we batch automatically.
     */
    private function callPurgeApi(array $urls, string $context = ''): array
    {
        if (!$this->enabled) {
            return ['skipped' => true, 'reason' => 'CLOUDFLARE_PURGE_ENABLED is false'];
        }

        if ($this->zoneId === '' || $this->apiToken === '') {
            Log::warning('Cloudflare purge skipped: missing credentials', ['context' => $context]);
            return ['skipped' => true, 'reason' => 'Missing Cloudflare credentials in .env'];
        }

        $apiUrl  = "https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/purge_cache";
        $batches = array_chunk($urls, 30); // CF limit: 30 URLs per request
        $allErrors = [];

        foreach ($batches as $batch) {
            try {
                $response = Http::withToken($this->apiToken)
                    ->timeout(15)
                    ->post($apiUrl, ['files' => $batch]);

                $body = $response->json();

                if ($response->ok() && ($body['success'] ?? false)) {
                    Log::info("Cloudflare cache purged [{$context}]", ['urls' => $batch]);
                } else {
                    $errors = $body['errors'] ?? [];
                    Log::error("Cloudflare purge failed [{$context}]", [
                        'http_status' => $response->status(),
                        'errors'      => $errors,
                        'urls'        => $batch,
                    ]);
                    $allErrors = array_merge($allErrors, $errors);
                }
            } catch (\Throwable $e) {
                Log::error("Cloudflare purge exception [{$context}]: " . $e->getMessage(), ['urls' => $batch]);
                $allErrors[] = ['message' => $e->getMessage()];
            }
        }

        if (empty($allErrors)) {
            return ['success' => true, 'urls' => $urls];
        }

        return ['success' => false, 'errors' => $allErrors, 'urls' => $urls];
    }
}
