<?php

namespace App\Console\Commands;

use App\Services\CloudflareService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SecurityMonitorCommand extends Command
{
    protected $signature = 'security:monitor';

    protected $description = 'Surveille la charge serveur et le trafic Cloudflare, alerte par email en cas de pic anormal';

    /**
     * Thresholds calibrated against the 2026-10-14 incident: normal load
     * average ~2 on this 4-CPU box, ~20 during the crash; normal traffic
     * ~18-20k req/h, ~107k req/h during the crawler-trap spike.
     */
    private const LOAD_THRESHOLD = 8.0;
    private const REQUESTS_PER_MINUTE_THRESHOLD = 1000; // ~60k/h sustained
    private const MITIGATED_RATIO_THRESHOLD = 0.5; // >50% of traffic being blocked/challenged
    private const ALERT_COOLDOWN_MINUTES = 60;
    private const CHECK_WINDOW_MINUTES = 5;

    public function handle(): int
    {
        $alerts = [];

        $load = $this->getLoadAverage();
        if ($load !== null && $load[0] > self::LOAD_THRESHOLD) {
            $alerts[] = sprintf(
                "Charge serveur élevée : load average %.2f (1min) / %.2f (5min) / %.2f (15min) — seuil d'alerte : %.1f",
                $load[0], $load[1], $load[2], self::LOAD_THRESHOLD
            );
        }

        /** @var CloudflareService $cf */
        $cf = app(CloudflareService::class);
        $summary = $cf->getThreatSummary(self::CHECK_WINDOW_MINUTES);

        if ($summary['success'] ?? false) {
            $total = $summary['total_requests'];
            $mitigated = $summary['mitigated_requests'];
            $perMinute = $total / max(1, self::CHECK_WINDOW_MINUTES);
            $ratio = $total > 0 ? $mitigated / $total : 0;

            if ($perMinute > self::REQUESTS_PER_MINUTE_THRESHOLD) {
                $alerts[] = sprintf(
                    "Volume de trafic anormal : %d requêtes en %d min (~%d/min) — seuil : %d/min",
                    $total, self::CHECK_WINDOW_MINUTES, $perMinute, self::REQUESTS_PER_MINUTE_THRESHOLD
                );
            }

            if ($total > 100 && $ratio > self::MITIGATED_RATIO_THRESHOLD) {
                $alerts[] = sprintf(
                    "Part anormale de trafic bloqué/challengé par Cloudflare : %d/%d requêtes (%.0f%%) — seuil : %.0f%%",
                    $mitigated, $total, $ratio * 100, self::MITIGATED_RATIO_THRESHOLD * 100
                );
            }
        } else {
            Log::warning('security:monitor: Cloudflare threat summary unavailable', $summary);
        }

        if (empty($alerts)) {
            $this->info('OK — rien d\'anormal.');
            return self::SUCCESS;
        }

        if ($this->recentlyAlerted()) {
            $this->warn('Anomalie détectée mais email déjà envoyé récemment (cooldown) : ' . implode(' | ', $alerts));
            Log::info('security:monitor: anomaly detected, alert suppressed by cooldown', ['alerts' => $alerts]);
            return self::SUCCESS;
        }

        $this->sendAlert($alerts, $summary ?? []);
        $this->markAlerted();
        $this->error('ALERTE envoyée : ' . implode(' | ', $alerts));

        return self::SUCCESS;
    }

    /** @return array{0: float, 1: float, 2: float}|null */
    private function getLoadAverage(): ?array
    {
        if (!function_exists('sys_getloadavg')) {
            return null;
        }
        $load = sys_getloadavg();
        return $load === false ? null : $load;
    }

    private function recentlyAlerted(): bool
    {
        return Cache::has('security_monitor_last_alert');
    }

    private function markAlerted(): void
    {
        Cache::put('security_monitor_last_alert', now()->toIso8601String(), now()->addMinutes(self::ALERT_COOLDOWN_MINUTES));
    }

    private function sendAlert(array $alerts, array $summary): void
    {
        $to = (string) env('NOTIFY_EMAIL');
        if ($to === '') {
            Log::warning('security:monitor: NOTIFY_EMAIL not set, cannot send alert');
            return;
        }

        $lines = [];
        $lines[] = "Alerte sécurité/charge — aeromorning.com (" . now()->format('Y-m-d H:i:s') . " UTC)";
        $lines[] = '';
        foreach ($alerts as $a) {
            $lines[] = "- {$a}";
        }
        $lines[] = '';

        if (!empty($summary['top_countries'])) {
            $lines[] = 'Top pays (' . self::CHECK_WINDOW_MINUTES . ' min) :';
            foreach ($summary['top_countries'] as $row) {
                $lines[] = sprintf('  %s : %d', $row['dimensions']['clientCountryName'] ?? '?', $row['count']);
            }
            $lines[] = '';
        }

        if (!empty($summary['top_paths'])) {
            $lines[] = 'Top chemins (' . self::CHECK_WINDOW_MINUTES . ' min) :';
            foreach ($summary['top_paths'] as $row) {
                $lines[] = sprintf('  %s : %d', $row['dimensions']['clientRequestPath'] ?? '?', $row['count']);
            }
            $lines[] = '';
        }

        $lines[] = 'Vérifier : Cloudflare → Security → Events, et le load average serveur (uptime).';
        $lines[] = "Prochaine alerte possible dans " . self::ALERT_COOLDOWN_MINUTES . " min si l'anomalie persiste.";

        $body = implode("\n", $lines);
        $subject = '[aeromorning.com] Alerte sécurité / charge serveur';

        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
            return;
        } catch (\Throwable $e) {
            Log::error('security:monitor: SMTP send failed, falling back to mail(): ' . $e->getMessage());
        }

        // SMTP (Gmail, see .env) has been failing auth since at least 2026-08-26
        // (pre-existing, unrelated to this command — see storage/logs/laravel.log).
        // Fall back to the server's local MTA so the alert isn't silently lost.
        $sent = @mail($to, $subject, $body, 'From: ' . (string) env('MAIL_FROM_ADDRESS', 'news@aeromorning.com'));
        if (!$sent) {
            Log::error('security:monitor: fallback mail() also failed — alert NOT delivered');
        }
    }
}
