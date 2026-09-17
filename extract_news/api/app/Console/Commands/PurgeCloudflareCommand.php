<?php

namespace App\Console\Commands;

use App\Services\CloudflareService;
use Illuminate\Console\Command;

class PurgeCloudflareCommand extends Command
{
    protected $signature = 'cache:purge-cloudflare
                            {--verify : Vérifier la validité du token sans purger}';

    protected $description = 'Purge le cache Cloudflare (homepages + feeds)';

    public function handle(): int
    {
        /** @var CloudflareService $cf */
        $cf = app(CloudflareService::class);

        if ($this->option('verify')) {
            $result = $cf->verifyToken();
            if ($result['success'] ?? false) {
                $this->info('✓ Token valide — zone : ' . ($result['zone_name'] ?? '?'));
                return self::SUCCESS;
            }
            $this->error('✗ Token invalide : ' . ($result['message'] ?? 'erreur inconnue'));
            return self::FAILURE;
        }

        $result = $cf->purgeHomepage();

        if ($result['skipped'] ?? false) {
            $this->warn('⚠ Purge ignorée : ' . ($result['reason'] ?? ''));
            return self::SUCCESS;
        }

        if ($result['success'] ?? false) {
            $urls = $result['urls'] ?? [];
            $this->info('✓ Cache Cloudflare purgé (' . count($urls) . ' URLs)');
            foreach ($urls as $url) {
                $this->line("  · {$url}");
            }
            return self::SUCCESS;
        }

        $this->error('✗ Purge échouée');
        foreach ($result['errors'] ?? [] as $err) {
            $msg = is_array($err) ? ($err['message'] ?? json_encode($err)) : (string) $err;
            $this->error('  ' . $msg);
        }
        return self::FAILURE;
    }
}
