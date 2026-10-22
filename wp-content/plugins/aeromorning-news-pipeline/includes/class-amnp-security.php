<?php
/**
 * Load + attack-traffic alert. Port of extract_news security:monitor, now
 * running on the WordPress server itself (so the load average is the one
 * that matters again). Same thresholds, one alert per hour at most.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AMNP_Security
{
    private const LOAD_THRESHOLD = 8.0;
    private const REQUESTS_PER_MINUTE_THRESHOLD = 1000; // ~60k/h sustained
    private const MITIGATED_RATIO_THRESHOLD = 0.5;      // >50% blocked/challenged
    private const ALERT_COOLDOWN_MINUTES = 60;
    private const CHECK_WINDOW_MINUTES = 5;
    private const COOLDOWN_KEY = 'amnp_security_last_alert';

    /** @return array{alerts: string[], sent: bool, suppressed: bool, load: ?array, summary: array} */
    public function run(): array
    {
        $alerts = [];

        $load = function_exists('sys_getloadavg') ? (sys_getloadavg() ?: null) : null;
        if ($load !== null && $load[0] > self::LOAD_THRESHOLD) {
            $alerts[] = sprintf("Charge serveur élevée : load average %.2f (1min) / %.2f (5min) / %.2f (15min) — seuil d'alerte : %.1f", $load[0], $load[1], $load[2], self::LOAD_THRESHOLD);
        }

        $summary = (new AMNP_Cloudflare())->getThreatSummary(self::CHECK_WINDOW_MINUTES);
        if ($summary['success'] ?? false) {
            $total = $summary['total_requests'];
            $mitigated = $summary['mitigated_requests'];
            $perMinute = $total / self::CHECK_WINDOW_MINUTES;
            $ratio = $total > 0 ? $mitigated / $total : 0;

            if ($perMinute > self::REQUESTS_PER_MINUTE_THRESHOLD) {
                $alerts[] = sprintf('Volume de trafic anormal : %d requêtes en %d min (~%d/min) — seuil : %d/min', $total, self::CHECK_WINDOW_MINUTES, $perMinute, self::REQUESTS_PER_MINUTE_THRESHOLD);
            }
            if ($total > 100 && $ratio > self::MITIGATED_RATIO_THRESHOLD) {
                $alerts[] = sprintf('Part anormale de trafic bloqué/challengé par Cloudflare : %d/%d requêtes (%.0f%%) — seuil : %.0f%%', $mitigated, $total, $ratio * 100, self::MITIGATED_RATIO_THRESHOLD * 100);
            }
        } else {
            AMNP_Logger::warning('security-monitor: Cloudflare threat summary unavailable', $summary);
        }

        $result = ['alerts' => $alerts, 'sent' => false, 'suppressed' => false, 'load' => $load, 'summary' => $summary];
        if (!$alerts) {
            return $result;
        }
        if (get_site_transient(self::COOLDOWN_KEY)) {
            AMNP_Logger::info('security-monitor: anomaly detected, alert suppressed by cooldown', ['alerts' => $alerts]);
            $result['suppressed'] = true;
            return $result;
        }

        $result['sent'] = $this->sendAlert($alerts, $summary);
        set_site_transient(self::COOLDOWN_KEY, gmdate('c'), self::ALERT_COOLDOWN_MINUTES * MINUTE_IN_SECONDS);
        return $result;
    }

    private function sendAlert(array $alerts, array $summary): bool
    {
        $to = AMNP_Config::str('NOTIFY_EMAIL');
        if ($to === '') {
            AMNP_Logger::warning('security-monitor: AMNP_NOTIFY_EMAIL not set, cannot send alert', ['alerts' => $alerts]);
            return false;
        }

        $lines = ['Alerte sécurité/charge — aeromorning.com (' . gmdate('Y-m-d H:i:s') . ' UTC)', ''];
        foreach ($alerts as $alert) {
            $lines[] = "- {$alert}";
        }
        $lines[] = '';
        foreach (['top_countries' => ['Top pays', 'clientCountryName'], 'top_paths' => ['Top chemins', 'clientRequestPath']] as $key => [$label, $dimension]) {
            if (!empty($summary[$key])) {
                $lines[] = "{$label} (" . self::CHECK_WINDOW_MINUTES . ' min) :';
                foreach ($summary[$key] as $row) {
                    $lines[] = sprintf('  %s : %d', $row['dimensions'][$dimension] ?? '?', $row['count']);
                }
                $lines[] = '';
            }
        }
        $lines[] = 'Vérifier : Cloudflare → Security → Events, et le load average serveur (uptime).';
        $lines[] = 'Prochaine alerte possible dans ' . self::ALERT_COOLDOWN_MINUTES . " min si l'anomalie persiste.";

        $subject = '[aeromorning.com] Alerte sécurité / charge serveur';
        $body = implode("\n", $lines);

        if (wp_mail($to, $subject, $body)) {
            return true;
        }
        // SMTP plugin down: last resort through PHP mail(), as before.
        AMNP_Logger::error('security-monitor: wp_mail failed, falling back to mail()');
        return @mail($to, $subject, $body, 'From: news@aeromorning.com');
    }
}
