<?php

namespace App\Http\Controllers;

use App\Services\CloudflareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CloudflareCacheController extends Controller
{
    /**
     * Purge homepage + feeds cache — called from the dashboard (Sanctum-protected).
     */
    public function purgeHomepage(): JsonResponse
    {
        return $this->doPurge();
    }

    /**
     * Purge homepage + feeds cache — called from a cron job via secret token (no Sanctum).
     *
     * Usage:
     *   POST https://api.aeromorning.com/api/cloudflare/purge-homepage-cron?secret=<CLOUDFLARE_PURGE_CRON_SECRET>
     *
     * Recommended cron (every 5 minutes, server crontab):
     *   *\/5 * * * * curl -s -X POST "https://api.aeromorning.com/api/cloudflare/purge-homepage-cron?secret=8a6e7c1d2f4b5c6a7e8d9f0a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0" > /dev/null 2>&1
     */
    public function purgeHomepageCron(Request $request): JsonResponse
    {
        $secret = (string) env('CLOUDFLARE_PURGE_CRON_SECRET', '');

        if ($secret === '' || $request->query('secret') !== $secret) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        return $this->doPurge();
    }

    /**
     * Diagnostic: verify that the Cloudflare API token is still valid.
     * Sanctum-protected — only for admin use from the dashboard.
     */
    public function verifyToken(): JsonResponse
    {
        $result = app(CloudflareService::class)->verifyToken();

        $httpStatus = ($result['success'] ?? false) ? 200 : 502;

        return response()->json($result, $httpStatus);
    }

    private function doPurge(): JsonResponse
    {
        $result = app(CloudflareService::class)->purgeHomepage();

        if ($result['skipped'] ?? false) {
            return response()->json([
                'success' => true,
                'skipped' => true,
                'message' => 'Purge skipped: ' . ($result['reason'] ?? ''),
            ]);
        }

        if ($result['success'] ?? false) {
            return response()->json([
                'success' => true,
                'message' => 'Cloudflare cache purged (homepage + feeds).',
                'urls'    => $result['urls'] ?? [],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Cloudflare purge failed.',
            'errors'  => $result['errors'] ?? ($result['error'] ?? 'Unknown error'),
        ], 500);
    }
}
