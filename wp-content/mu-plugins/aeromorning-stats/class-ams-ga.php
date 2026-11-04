<?php
/**
 * Minimal Google Analytics Data API (v1beta) client: service-account JWT
 * signed with openssl, batchRunReports over wp_remote_post. No Google SDK.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AMS_GA
{
    private const SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';
    private const API = 'https://analyticsdata.googleapis.com/v1beta/';

    /** GA4 property per site version. */
    public static function properties(): array
    {
        return ['fr' => '405884692', 'en' => '472994712'];
    }

    public static function keyFile(): string
    {
        return defined('AEROMORNING_GA_KEY_FILE') ? (string) AEROMORNING_GA_KEY_FILE : '';
    }

    public static function isConfigured(): bool
    {
        $file = self::keyFile();
        return $file !== '' && is_readable($file);
    }

    /**
     * Up to 5 report requests in one HTTP call, cached as a site transient
     * (the same answer serves both sites of the network).
     *
     * @throws RuntimeException with Google's message on failure
     */
    public static function batch(string $property, array $requests, int $ttl): array
    {
        $cacheKey = 'ams_' . md5($property . wp_json_encode($requests));
        $cached = get_site_transient($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $response = wp_remote_post(self::API . "properties/{$property}:batchRunReports", [
            'timeout' => 30,
            'headers' => ['Authorization' => 'Bearer ' . self::token(), 'Content-Type' => 'application/json'],
            'body'    => wp_json_encode(['requests' => $requests]),
        ]);
        $reports = self::decode($response)['reports'] ?? [];

        set_site_transient($cacheKey, $reports, $ttl);
        return $reports;
    }

    /** OAuth access token for the service account, cached ~55 minutes. */
    private static function token(): string
    {
        $token = get_site_transient('ams_ga_token');
        if (is_string($token) && $token !== '') {
            return $token;
        }
        if (!self::isConfigured()) {
            throw new RuntimeException('Clé du compte de service introuvable (AEROMORNING_GA_KEY_FILE).');
        }

        $key = json_decode((string) file_get_contents(self::keyFile()), true);
        if (empty($key['client_email']) || empty($key['private_key'])) {
            throw new RuntimeException('Clé du compte de service invalide.');
        }
        $tokenUri = $key['token_uri'] ?? 'https://oauth2.googleapis.com/token';

        $b64 = static fn (string $s): string => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        $now = time();
        $unsigned = $b64('{"alg":"RS256","typ":"JWT"}') . '.' . $b64((string) wp_json_encode([
            'iss' => $key['client_email'], 'scope' => self::SCOPE, 'aud' => $tokenUri,
            'iat' => $now, 'exp' => $now + 3600,
        ]));
        if (!openssl_sign($unsigned, $signature, $key['private_key'], 'sha256WithRSAEncryption')) {
            throw new RuntimeException('Signature de la clé impossible (openssl).');
        }

        $data = self::decode(wp_remote_post($tokenUri, [
            'timeout' => 15,
            'body'    => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $unsigned . '.' . $b64($signature),
            ],
        ]));
        if (empty($data['access_token'])) {
            throw new RuntimeException('Google n\'a pas renvoyé de jeton d\'accès.');
        }

        set_site_transient('ams_ga_token', $data['access_token'], max(60, (int) ($data['expires_in'] ?? 3600) - 300));
        return $data['access_token'];
    }

    /** @param array|WP_Error $response */
    private static function decode($response): array
    {
        if (is_wp_error($response)) {
            throw new RuntimeException('Google Analytics injoignable : ' . $response->get_error_message());
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data)) {
            throw new RuntimeException('Réponse illisible de Google (HTTP ' . wp_remote_retrieve_response_code($response) . ').');
        }
        if (isset($data['error'])) {
            $error = $data['error'];
            $message = is_array($error) ? ($error['message'] ?? wp_json_encode($error)) : (string) ($data['error_description'] ?? $error);
            throw new RuntimeException('Google Analytics : ' . $message);
        }
        return $data;
    }
}
