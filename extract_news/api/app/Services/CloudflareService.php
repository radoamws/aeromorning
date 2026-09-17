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
