<?php
/**
 * LinkedIn post for the EN version of each news, through Buffer. Port of
 * extract_news LinkedInService (Buffer path — the Make and direct-API paths
 * were already disabled there): OpenAI company detection, slug check, URN
 * resolution (static map → cache → page scrape), clickable @mentions with
 * UTF-16 positions, hashtags.
 *
 * AMNP_LINKEDIN_MODE: 'off' (default), 'dry-run' (build + log, never send),
 * 'live'. Drafts are never posted.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AMNP_LinkedIn
{
    /** Official LinkedIn names (must match the page name exactly or the mention is rejected). */
    private const KNOWN_NAMES = [
        'flyarcher' => 'Archer', 'jobyaviation' => 'Joby Aviation', 'wisk-aero' => 'Wisk',
        'volocopter' => 'Volocopter', 'insitu' => 'INsitu', 'skygrid' => 'SkyGrid',
        'boeing' => 'Boeing', 'airbusgroup' => 'Airbus', 'safran' => 'Safran', 'thales' => 'Thales',
        'rolls-royce' => 'Rolls-Royce', 'honeywell' => 'Honeywell', 'collins-aerospace' => 'Collins Aerospace',
        'prattwhitney' => 'Pratt & Whitney', 'dassault-aviation' => 'Dassault Aviation',
        'mtu-aero-engines' => 'MTU Aero Engines', 'textron' => 'Textron', 'bell' => 'Bell',
        'leonardo' => 'Leonardo', 'mbda' => 'MBDA', 'rheinmetall' => 'Rheinmetall', 'hensoldt' => 'HENSOLDT',
        'lockheed-martin' => 'Lockheed Martin', 'northrop-grumman-corporation' => 'Northrop Grumman',
        'rtx' => 'RTX', 'bae-systems' => 'BAE Systems', 'general-dynamics' => 'General Dynamics',
        'l3harris-technologies' => 'L3Harris Technologies',
        'korean-air' => 'Korean Air', 'air-france' => 'Air France', 'lufthansa-group' => 'Lufthansa Group',
        'united-airlines' => 'United Airlines', 'delta-air-lines' => 'Delta Air Lines',
        'american-airlines' => 'American Airlines', 'emirates' => 'Emirates', 'cathay-pacific' => 'Cathay Pacific',
        'spacex' => 'SpaceX', 'arianegroup' => 'ArianeGroup',
        'solairus-aviation' => 'Solairus Aviation', 'aerfin-limited' => 'AerFin',
        'orix-aviation-systems-ltd' => 'ORIX Aviation', 'easyjet' => 'easyJet',
        'easa' => 'EASA', 'nato' => 'NATO',
    ];

    /** Verified organization URNs (permanent). */
    private const KNOWN_URNS = [
        'flyarcher' => 'urn:li:organization:42881474', 'jobyaviation' => 'urn:li:organization:6407329',
        'wisk-aero' => 'urn:li:organization:31425081', 'volocopter' => 'urn:li:organization:2914824',
        'insitu' => 'urn:li:organization:1151664', 'skygrid' => 'urn:li:organization:2333563',
        'boeing' => 'urn:li:organization:1384', 'airbusgroup' => 'urn:li:organization:2734',
        'airbus' => 'urn:li:organization:3211', 'safran' => 'urn:li:organization:521777',
        'thales' => 'urn:li:organization:1951', 'rolls-royce' => 'urn:li:organization:3871',
        'honeywell' => 'urn:li:organization:1344', 'collins-aerospace' => 'urn:li:organization:11695727',
        'prattwhitney' => 'urn:li:organization:71747137', 'dassault-aviation' => 'urn:li:organization:8693',
        'mtu-aero-engines' => 'urn:li:organization:14146', 'textron' => 'urn:li:organization:3335',
        'bell' => 'urn:li:organization:1531', 'leonardo' => 'urn:li:organization:36193',
        'mbda' => 'urn:li:organization:164883', 'rheinmetall' => 'urn:li:organization:1568176',
        'hensoldt' => 'urn:li:organization:17896121',
        'lockheed-martin' => 'urn:li:organization:1319', 'northrop-grumman-corporation' => 'urn:li:organization:1412',
        'rtx' => 'urn:li:organization:40653509', 'bae-systems' => 'urn:li:organization:1882',
        'general-dynamics' => 'urn:li:organization:1904', 'l3harris-technologies' => 'urn:li:organization:40745219',
        'korean-air' => 'urn:li:organization:20798', 'air-france' => 'urn:li:organization:4242',
        'lufthansa-group' => 'urn:li:organization:11295773', 'lufthansa' => 'urn:li:organization:4231',
        'united-airlines' => 'urn:li:organization:2380', 'delta-air-lines' => 'urn:li:organization:2272',
        'american-airlines' => 'urn:li:organization:2640', 'emirates' => 'urn:li:organization:5042',
        'cathay-pacific' => 'urn:li:organization:7097', 'easyjet' => 'urn:li:organization:8932',
        'solairus-aviation' => 'urn:li:organization:435016', 'aerfin-limited' => 'urn:li:organization:1293994',
        'orix-aviation-systems-ltd' => 'urn:li:organization:5314080',
        'spacex' => 'urn:li:organization:30846', 'arianegroup' => 'urn:li:organization:10236541',
        'easa' => 'urn:li:organization:213394', 'nato' => 'urn:li:organization:5636',
    ];

    /** Network option holding scraped URNs ('' = confirmed 404) and official names. */
    private const CACHE_OPTION = 'amnp_linkedin_cache';

    public static function mode(): string
    {
        $mode = strtolower(AMNP_Config::str('LINKEDIN_MODE'));
        return in_array($mode, ['off', 'dry-run', 'live'], true) ? $mode : 'off';
    }

    /**
     * @param array $news news row (EN, published, with wp_link)
     * @return array{success: bool, skipped?: bool, reason?: string, post_id?: string, text?: string, annotations?: int}
     */
    public function postNews(array $news): array
    {
        if ($news['lang'] !== 'EN') {
            return ['success' => false, 'skipped' => true, 'reason' => 'Only EN articles are posted to LinkedIn'];
        }
        $mode = self::mode();
        if ($mode === 'off') {
            return ['success' => false, 'skipped' => true, 'reason' => 'AMNP_LINKEDIN_MODE is off'];
        }
        if ($mode === 'live' && (AMNP_Config::str('BUFFER_API_KEY') === '' || AMNP_Config::str('BUFFER_LINKEDIN_CHANNEL_ID') === '')) {
            throw new \RuntimeException('Buffer non configuré : renseignez AMNP_BUFFER_API_KEY et AMNP_BUFFER_LINKEDIN_CHANNEL_ID.');
        }

        $articleUrl = (string) $news['wp_link'];
        $companies = $this->detectCompanies($news);
        $postText = $this->buildPostText($news, $companies);
        $annotations = $this->buildAnnotations($postText, $companies);

        $linkedinMeta = ['linkAttachment' => ['url' => $articleUrl]];
        if ($annotations) {
            $linkedinMeta['annotations'] = $annotations;
        }

        $input = [
            'channelId'      => AMNP_Config::str('BUFFER_LINKEDIN_CHANNEL_ID'),
            'text'           => $postText,
            'schedulingType' => 'automatic',
            'mode'           => 'shareNow',
            'metadata'       => ['linkedin' => $linkedinMeta],
        ];

        if ($mode === 'dry-run') {
            AMNP_Logger::info("LinkedIn DRY-RUN for news #{$news['id']} (not sent)", ['input' => $input]);
            return ['success' => true, 'post_id' => 'dry-run', 'text' => $postText, 'annotations' => count($annotations), 'input' => $input];
        }

        $mutation = <<<'GQL'
mutation CreatePost($input: CreatePostInput!) {
    createPost(input: $input) {
        ... on PostActionSuccess {
            post { id text status }
        }
        ... on MutationError      { message }
        ... on InvalidInputError  { message }
        ... on UnauthorizedError  { message }
        ... on LimitReachedError  { message }
        ... on UnexpectedError    { message }
    }
}
GQL;

        $response = wp_remote_post('https://api.buffer.com', [
            'timeout' => 30,
            'headers' => ['Authorization' => 'Bearer ' . AMNP_Config::str('BUFFER_API_KEY'), 'Content-Type' => 'application/json'],
            'body'    => wp_json_encode(['query' => $mutation, 'variables' => ['input' => $input]]),
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException('Buffer API error: ' . $response->get_error_message());
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $raw = (string) wp_remote_retrieve_body($response);
        $body = json_decode($raw, true);

        AMNP_Logger::info('LinkedIn via Buffer', ['news_id' => $news['id'], 'http_status' => $code, 'body' => mb_substr($raw, 0, 1000), 'annotations' => count($annotations)]);

        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException("Buffer API error (HTTP {$code}): {$raw}");
        }
        if (!empty($body['errors'])) {
            throw new \RuntimeException('Buffer GraphQL error: ' . implode(', ', array_column($body['errors'], 'message')));
        }
        $result = $body['data']['createPost'] ?? [];
        if (!empty($result['message']) && empty($result['post'])) {
            throw new \RuntimeException('Buffer createPost error: ' . $result['message']);
        }

        return ['success' => true, 'post_id' => (string) ($result['post']['id'] ?? 'unknown'), 'annotations' => count($annotations)];
    }

    /** Import the company URN/name cache from extract_news storage/app/linkedin.json. */
    public static function importCache(array $legacy): array
    {
        $cache = self::readCache();
        $cache['company_urns'] = array_merge($cache['company_urns'] ?? [], (array) ($legacy['company_urns'] ?? []));
        $cache['company_names'] = array_merge($cache['company_names'] ?? [], (array) ($legacy['company_names'] ?? []));
        update_site_option(self::CACHE_OPTION, $cache);
        return ['urns' => count($cache['company_urns']), 'names' => count($cache['company_names'])];
    }

    private static function readCache(): array
    {
        $cache = get_site_option(self::CACHE_OPTION, []);
        return is_array($cache) ? $cache : [];
    }

    private static function writeCache(array $merge): void
    {
        update_site_option(self::CACHE_OPTION, array_merge(self::readCache(), $merge));
    }

    // ── Post text ───────────────────────────────────────────────────────────

    /** ✈️ Title / excerpt / @Companies / #hashtags — the URL travels as linkAttachment. */
    private function buildPostText(array $news, array $companies): string
    {
        $text = '✈️ ' . $news['title'] . "\n\n" . $this->buildExcerpt($news, 280);

        if ($companies) {
            $parts = [];
            foreach ($companies as $c) {
                $parts[] = '@' . $this->officialName($c['slug'], $c['name']);
            }
            $text .= "\n\n" . implode(' ', $parts);
        }

        return $text . "\n\n" . $this->buildHashtags($news);
    }

    private function officialName(string $slug, string $fallbackName): string
    {
        if (isset(self::KNOWN_NAMES[$slug])) {
            return self::KNOWN_NAMES[$slug];
        }
        $names = self::readCache()['company_names'] ?? [];
        return !empty($names[$slug]) ? $names[$slug] : $fallbackName;
    }

    /**
     * Buffer annotations: start = UTF-16 offset of the name's first char (after "@"),
     * length = UTF-16 length of the name alone. LinkedIn checks text[start:length] == localizedName.
     */
    private function buildAnnotations(string $text, array $companies): array
    {
        $annotations = [];
        $searchFrom = 0;

        foreach ($companies as $company) {
            $slug = trim((string) ($company['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }
            $officialName = $this->officialName($slug, (string) $company['name']);
            if ($officialName === '') {
                continue;
            }

            $urn = $this->resolveCompanyUrn($slug);
            if (!$urn || !preg_match('/^urn:li:organization:(\d+)$/', $urn, $m)) {
                AMNP_Logger::warning("LinkedIn annotations: pas d'URN valide pour '{$officialName}' (slug: '{$slug}') → texte brut");
                continue;
            }

            $mention = '@' . $officialName;
            $unicodePos = mb_strpos($text, $mention, $searchFrom);
            if ($unicodePos === false) {
                continue;
            }

            $start = $this->utf16PositionOf($text, $unicodePos + 1);
            $length = $this->utf16Len($officialName);
            if ($this->utf16Substring($text, $start, $length) !== $officialName) {
                AMNP_Logger::error('LinkedIn annotation: validation locale échouée', ['slug' => $slug, 'expected' => $officialName]);
                continue;
            }

            $annotations[] = [
                'id'            => $m[1],
                'entity'        => $urn,
                'length'        => $length,
                'link'          => !empty($company['url']) ? $company['url'] : "https://www.linkedin.com/company/{$slug}/",
                'localizedName' => $officialName,
                'start'         => $start,
                'vanityName'    => $slug,
            ];

            $searchFrom = $unicodePos + mb_strlen($mention);
        }

        return $annotations;
    }

    private function utf16Len(string $str): int
    {
        return (int) (strlen((string) mb_convert_encoding($str, 'UTF-16LE', 'UTF-8')) / 2);
    }

    private function utf16PositionOf(string $text, int $unicodeCharIndex): int
    {
        $units = 0;
        for ($i = 0; $i < $unicodeCharIndex; $i++) {
            $units += mb_ord(mb_substr($text, $i, 1)) >= 0x10000 ? 2 : 1;
        }
        return $units;
    }

    private function utf16Substring(string $text, int $start, int $length): string
    {
        $result = '';
        $units = 0;
        $count = mb_strlen($text);
        for ($i = 0; $i < $count; $i++) {
            $char = mb_substr($text, $i, 1);
            $end = $units + (mb_ord($char) >= 0x10000 ? 2 : 1);
            if ($units >= $start && $end <= $start + $length) {
                $result .= $char;
            }
            $units = $end;
            if ($units >= $start + $length) {
                break;
            }
        }
        return $result;
    }

    private function buildExcerpt(array $news, int $maxLen): string
    {
        $raw = (string) ($news['metadescription'] ?: ($news['content'] ?: ''));
        $raw = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
        if (mb_strlen($raw) <= $maxLen) {
            return $raw;
        }
        $cut = mb_substr($raw, 0, $maxLen);
        $lastSpace = mb_strrpos($cut, ' ');
        return ($lastSpace !== false ? mb_substr($cut, 0, $lastSpace) : $cut) . '…';
    }

    private function buildHashtags(array $news): string
    {
        $tags = [];
        if (!empty($news['focuskeyphrase'])) {
            $clean = preg_replace('/[^a-zA-Z0-9\s\-]/', '', (string) $news['focuskeyphrase']);
            foreach (preg_split('/[\s\-]+/', (string) $clean) ?: [] as $word) {
                $word = trim($word);
                if (mb_strlen($word) >= 3) {
                    $tags[] = '#' . ucfirst(mb_strtolower($word));
                }
            }
        }
        $tags[] = '#Aviation';
        $tags[] = '#Aeromorning';
        $tags[] = '#Aerospace';
        return implode(' ', array_slice(array_unique($tags), 0, 10));
    }

    // ── Company detection ──────────────────────────────────────────────────

    private function detectCompanies(array $news): array
    {
        $cacheKey = 'amnp_li_companies_' . $news['id'];
        $cached = get_site_transient($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $companies = $this->detectCompaniesWithOpenAI($news);
        } catch (\Throwable $e) {
            AMNP_Logger::warning('LinkedIn: détection des entreprises échouée: ' . $e->getMessage());
            return [];
        }

        // Never cache an empty result: retry next time instead.
        if ($companies) {
            set_site_transient($cacheKey, $companies, 30 * DAY_IN_SECONDS);
        }
        return $companies;
    }

    private function detectCompaniesWithOpenAI(array $news): array
    {
        $apiKey = AMNP_Config::str('OPENAI_API_KEY');
        if ($apiKey === '') {
            return [];
        }

        $title = (string) $news['title'];
        $meta = (string) $news['metadescription'];
        $content = mb_substr(strip_tags(html_entity_decode((string) $news['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8')), 0, 3000);

        $prompt = <<<PROMPT
You are an expert in the aviation and aerospace industry.

STEP 1 — Extract every company and organization name mentioned in the article below.

STEP 2 — From that list, keep ONLY those related to aviation and aerospace:
  • Airlines, airports, air transport, cargo, freight
  • Aircraft manufacturers, OEMs, engines, components
  • Defense (military aviation, naval aviation, defense industry)
  • MRO (maintenance, repair, overhaul)
  • eVTOL, UAM, AAM, drones, UAV
  • Space, satellite, launch, rockets, orbital, helicopter
  • Certification agencies (FAA, EASA, TCCA, ANAC, DGAC, CAA…)
  • Safety, regulation, sustainability, emissions, SAF
  • Aviation technology, digitalization, AI in aviation, cybersecurity
  • Aviation leasing, financing
  • Industry events, airshows, aviation awards, competitions
  • Leadership changes / nominations in aviation companies

STEP 3 — Return up to 6 of the most important and relevant ones for a LinkedIn @mention.

PRIORITY RULES:
1. Prefer the ACQUIRER over the acquired subsidiary
2. Prefer publicly traded / large established companies
3. Prefer OPERATOR/AIRLINE over aircraft manufacturer when both mentioned
4. Include regulatory bodies (FAA, EASA…) ONLY if they are the central topic
5. Omit pure subsidiaries with no independent LinkedIn following
6. Do NOT include AeroMorning (our own publication)

VERIFIED LINKEDIN SLUGS — use EXACT slug if the company appears in this list:

eVTOL / AAM / Drones:
  Archer / Archer Aviation → flyarcher | Joby / Joby Aviation → jobyaviation
  Wisk / Wisk Aero → wisk-aero | Volocopter → volocopter
  SkyGrid → skygrid | Insitu → insitu

Airlines & air transport:
  Air France → air-france | Lufthansa Group → lufthansa-group | Emirates → emirates
  United Airlines → united-airlines | Delta Air Lines → delta-air-lines
  American Airlines → american-airlines | Korean Air → korean-air
  Cathay Pacific → cathay-pacific | easyJet → easyjet

Aviation services / MRO / leasing:
  Solairus Aviation → solairus-aviation
  AerFin / AerFin Limited → aerfin-limited
  ORIX Aviation / ORIX Aviation Systems → orix-aviation-systems-ltd

Manufacturers & OEMs:
  Airbus → airbusgroup | Boeing → boeing | Safran → safran | Thales → thales
  Dassault Aviation → dassault-aviation | Rolls-Royce → rolls-royce
  Pratt & Whitney → prattwhitney | Honeywell → honeywell
  Collins Aerospace → collins-aerospace | Leonardo → leonardo | MBDA → mbda
  Rheinmetall → rheinmetall | Textron → textron | Bell → bell
  MTU Aero Engines → mtu-aero-engines

Defense:
  Lockheed Martin → lockheed-martin | Northrop Grumman → northrop-grumman-corporation
  RTX / Raytheon → rtx | BAE Systems → bae-systems
  General Dynamics → general-dynamics | L3Harris → l3harris-technologies

Space & agencies / regulators:
  SpaceX → spacex | ArianeGroup → arianegroup
  NATO → nato | EASA → easa

FOR COMPANIES NOT IN THE VERIFIED LIST:
  LinkedIn slugs often include legal suffixes not in the public name. Provide 2–3 slug candidates:
  Examples:
    "Air Lease Corporation" → "air-lease-corporation" (full legal name, no suffix needed)
    "Avolon" → "avolon-aerospace-limited" (legal suffix added)
    "Stellex Aerostructures" → "stellex-aerostructures" or "stellex-aerostructures-llc"
  Always include: plain slug + versions with -limited, -ltd, -group, -inc, -corporation

Return ONLY a valid JSON object (no markdown, no explanation), format:
{"companies": [
  {"name": "Official LinkedIn display name", "slug": "best-slug-guess", "slug_alternatives": ["alt1", "alt2"]},
  {"name": "Airbus", "slug": "airbusgroup", "slug_alternatives": []}
]}
slug_alternatives = [] for companies already in the verified list above.

Article title: {$title}
Summary: {$meta}
Content: {$content}
PROMPT;

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'timeout' => 25,
            'headers' => ['Authorization' => 'Bearer ' . $apiKey, 'Content-Type' => 'application/json'],
            'body'    => wp_json_encode([
                'model'           => AMNP_Config::str('OPENAI_FALLBACK_MODEL'),
                'messages'        => [['role' => 'user', 'content' => $prompt]],
                'temperature'     => 0.1,
                'max_tokens'      => 600,
                'response_format' => ['type' => 'json_object'],
            ]),
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException('OpenAI API error: ' . $response->get_error_message());
        }
        if ((int) wp_remote_retrieve_response_code($response) !== 200) {
            throw new \RuntimeException('OpenAI API error (' . wp_remote_retrieve_response_code($response) . '): ' . wp_remote_retrieve_body($response));
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        $parsed = json_decode((string) ($body['choices'][0]['message']['content'] ?? '{}'), true);
        $companies = array_filter((array) ($parsed['companies'] ?? []), static fn ($c) => !empty($c['name']) && !empty($c['slug']));
        $companies = array_slice(array_values($companies), 0, 8);

        $verified = [];
        foreach ($companies as $company) {
            $primary = trim((string) $company['slug']);
            $validSlug = $this->discoverValidSlug($primary, array_map('trim', (array) ($company['slug_alternatives'] ?? [])));
            if ($validSlug !== null) {
                $verified[] = ['name' => (string) $company['name'], 'slug' => $validSlug, 'url' => "https://www.linkedin.com/company/{$validSlug}/"];
            } else {
                AMNP_Logger::info("LinkedIn: aucun slug valide trouvé pour '{$company['name']}'");
            }
        }

        // Resolve URNs now: Buffer refuses re-posting the same article, so the
        // mentions must work on the first attempt.
        foreach ($verified as $company) {
            $this->resolveCompanyUrn($company['slug']);
        }

        AMNP_Logger::info('LinkedIn: ' . count($verified) . " entreprise(s) détectée(s) pour news #{$news['id']}", ['companies' => array_column($verified, 'name')]);
        return $verified;
    }

    private function discoverValidSlug(string $primary, array $alternatives = []): ?string
    {
        $candidates = array_values(array_unique(array_filter(array_merge([$primary], $alternatives))));
        foreach (['-limited', '-ltd', '-group', '-inc', '-corporation', '-llc', '-systems', '-systems-ltd', '-aviation', '-aerospace', '-services', '-solutions'] as $suffix) {
            $candidates[] = $primary . $suffix;
        }

        $needsDelay = false;
        foreach ($candidates as $slug) {
            if (isset(self::KNOWN_URNS[$slug])) {
                return $slug;
            }
            if ($needsDelay) {
                sleep(1); // avoid LinkedIn 429 between checks
            }
            if ($this->verifyLinkedInSlug($slug)) {
                return $slug;
            }
            $needsDelay = true;
        }
        return null;
    }

    /** 404 = no such page; anything else (200, 429, 999) = exists. */
    private function verifyLinkedInSlug(string $slug): bool
    {
        if (isset(self::KNOWN_URNS[$slug])) {
            return true;
        }
        $urns = self::readCache()['company_urns'] ?? [];
        if (array_key_exists($slug, $urns) && $urns[$slug] === '') {
            return false;
        }

        $result = $this->fetch("https://www.linkedin.com/company/{$slug}/", 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
        // Network failure: trust OpenAI, as before.
        return $result === null || $result['status'] !== 404;
    }

    private function resolveCompanyUrn(string $slug): ?string
    {
        if (isset(self::KNOWN_URNS[$slug])) {
            return self::KNOWN_URNS[$slug];
        }
        $urns = self::readCache()['company_urns'] ?? [];
        if (array_key_exists($slug, $urns)) {
            return $urns[$slug] !== '' ? (string) $urns[$slug] : null;
        }
        return $this->scrapeLinkedInUrn($slug);
    }

    /** LinkedIn serves its SEO HTML to Googlebot / LinkedInBot (browsers get 999). */
    private function scrapeLinkedInUrn(string $slug): ?string
    {
        $userAgents = [
            'Googlebot'   => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'LinkedInBot' => 'LinkedInBot/1.0 (compatible; Mozilla/5.0; Apache-HttpClient +http://www.linkedin.com)',
        ];

        foreach ($userAgents as $uaName => $ua) {
            $result = $this->fetch("https://www.linkedin.com/company/{$slug}/", $ua);
            if ($result === null) {
                continue;
            }
            ['status' => $status, 'body' => $body] = $result;

            if ($status === 404) {
                $cache = self::readCache();
                $cache['company_urns'][$slug] = '';
                self::writeCache(['company_urns' => $cache['company_urns']]);
                return null;
            }

            $orgId = null;
            if (preg_match('/"urn:li:(?:fs_)?organization:(\d+)"/', $body, $m)) {
                $orgId = $m[1];
            } elseif (preg_match('/"entityUrn"\s*:\s*"urn:li:company:(\d+)"/', $body, $m)) {
                $orgId = $m[1];
            } elseif (preg_match('/data-company-id="(\d+)"/', $body, $m)) {
                $orgId = $m[1];
            } elseif (preg_match('/"companyId"\s*:\s*(\d+)/', $body, $m)) {
                $orgId = $m[1];
            }

            if ($orgId) {
                $urn = "urn:li:organization:{$orgId}";
                $cache = self::readCache();
                $cache['company_urns'][$slug] = $urn;
                $update = ['company_urns' => $cache['company_urns']];

                if (preg_match('/<title[^>]*>([^<]+?)\s*\|\s*LinkedIn<\/title>/i', $body, $nm)) {
                    $name = html_entity_decode(trim($nm[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $name = trim((string) preg_replace('/\s*-\s*(?:Employees|Jobs|Products|Company|Overview|About)\b.*/i', '', $name));
                    if ($name !== '') {
                        $cache['company_names'][$slug] = $name;
                        $update['company_names'] = $cache['company_names'];
                    }
                }
                self::writeCache($update);
                AMNP_Logger::info("LinkedIn: '{$slug}' → URN {$urn} [{$uaName}]");
                return $urn;
            }
        }

        // Temporary failure (429/999): not cached, retried next time.
        AMNP_Logger::warning("LinkedIn: '{$slug}' → aucun UA n'a permis de résoudre l'URN");
        return null;
    }

    /** @return array{status: int, body: string}|null */
    private function fetch(string $url, string $userAgent): ?array
    {
        $response = wp_remote_get($url, [
            'timeout'     => 12,
            'redirection' => 5,
            'user-agent'  => $userAgent,
            'headers'     => ['Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'Accept-Language' => 'en-US,en;q=0.9'],
        ]);
        if (is_wp_error($response)) {
            AMNP_Logger::warning("LinkedIn fetch failed for {$url}: " . $response->get_error_message());
            return null;
        }
        return ['status' => (int) wp_remote_retrieve_response_code($response), 'body' => (string) wp_remote_retrieve_body($response)];
    }
}
