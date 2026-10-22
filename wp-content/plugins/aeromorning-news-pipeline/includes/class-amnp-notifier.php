<?php
/**
 * Summary emails, same plain-text layout as extract_news. Sent through
 * wp_mail (i.e. the site's WP Mail SMTP configuration).
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AMNP_Notifier
{
    public static function processingSummary(array $summary): void
    {
        $processed = count($summary['processed']);
        $failed    = count($summary['failed']);
        $skipped   = count($summary['skipped']);

        $date = wp_date('d/m/Y H:i');
        $body  = "Résumé traitement emails news — {$date}\n" . str_repeat('=', 60) . "\n\n";
        $body .= "Traités : {$processed}\nIgnorés : {$skipped}\nÉchecs  : {$failed}\n\n";

        $body .= self::section('MAILS TRAITÉS', $summary['processed'], static fn ($i) => '');
        $body .= self::section('MAILS IGNORÉS (non pertinents / déjà traités)', $summary['skipped'], static fn ($i) => ($i['reason'] ?? '') !== '' ? " | raison: {$i['reason']}" : '');
        $body .= self::section('MAILS EN ÉCHEC', $summary['failed'], static fn ($i) => ($i['error'] ?? '') !== '' ? " | erreur: {$i['error']}" : '');

        self::send("Résumé traitement emails news — {$date}", $body);
    }

    public static function publishingSummary(array $results, array $remainingPending): void
    {
        $success = count($results['success']);
        $failed  = count($results['failed']);

        $date = wp_date('d/m/Y H:i');
        $body  = "Résumé de publication WordPress — {$date}\n" . str_repeat('=', 60) . "\n\n";
        $body .= 'Total traité dans ce batch : ' . ($success + $failed) . "\n";
        $body .= "  ✓ Publiées avec succès (status 2) : {$success}\n";
        $body .= "  ✗ En échec (restent en attente status 0) : {$failed}\n";
        if ($remainingPending) {
            $body .= '  ⚠ Non traitées         (status 0) : ' . count($remainingPending) . "  ← traitement possiblement interrompu\n";
        }
        $body .= "\n";

        if ($results['success']) {
            $body .= str_repeat('-', 60) . "\nNEWS PUBLIÉES (status 2)\n" . str_repeat('-', 60) . "\n";
            foreach ($results['success'] as $item) {
                $body .= "  [#{$item['id']}] [{$item['lang']}] {$item['title']}\n        → WP post ID : {$item['wp_post_id']}\n";
            }
            $body .= "\n";
        }
        if ($results['failed']) {
            $body .= str_repeat('-', 60) . "\nNEWS EN ÉCHEC (status 0 — à retenter)\n" . str_repeat('-', 60) . "\n";
            foreach ($results['failed'] as $item) {
                $body .= "  [#{$item['id']}] [{$item['lang']}] {$item['title']}\n        → Erreur : {$item['error']}\n";
            }
            $body .= "\n";
        }
        if ($remainingPending) {
            $body .= str_repeat('-', 60) . "\nNEWS NON TRAITÉES (status 0) — " . count($remainingPending) . " restante(s)\n" . str_repeat('-', 60) . "\n";
            foreach ($remainingPending as $item) {
                $body .= "  [#{$item['id']}] [{$item['lang']}] {$item['title']}\n";
            }
            $body .= "\n";
        }

        self::send("Résumé publication WordPress — {$date}", $body);
    }

    private static function section(string $title, array $items, callable $suffix): string
    {
        if (!$items) {
            return '';
        }
        $out = str_repeat('-', 60) . "\n{$title}\n" . str_repeat('-', 60) . "\n";
        foreach ($items as $item) {
            $out .= "- {$item['subject']}" . (($item['from'] ?? '') !== '' ? " | {$item['from']}" : '') . $suffix($item) . "\n";
        }
        return $out . "\n";
    }

    private static function send(string $subject, string $body): void
    {
        $to = AMNP_Config::str('NOTIFY_EMAIL');
        if ($to === '') {
            AMNP_Logger::info("Summary email skipped (AMNP_NOTIFY_EMAIL empty): {$subject}");
            return;
        }
        if (!wp_mail($to, $subject, $body)) {
            AMNP_Logger::error("Failed to send summary email: {$subject}");
        }
    }
}
