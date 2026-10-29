<?php
/**
 * OpenAI calls used by the email pipeline. Port of the reachable part of
 * extract_news OpenAIService: prompts are copied verbatim, the retry /
 * fallback / content_filter / length logic is unchanged. Only the transport
 * changed (WordPress HTTP API instead of openai-php + Guzzle).
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AMNP_OpenAI
{
    private const ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    /** Why the last callOpenAI() returned null: 'content_filter', 'length' or null. */
    private ?string $lastFailureReason = null;

    private const WP_JSON_KEYS = [
        'titleFR',
        'shorttitleFR',
        'titleEN',
        'shorttitleEN',
        'FR',
        'EN',
        'metadescriptionFR',
        'metadescriptionEN',
        'focuskeyphraseFR',
        'focuskeyphraseEN',
    ];

    /**
     * Extract both FR and EN news payloads from a forwarded email.
     * The email content (json-encoded) is appended at the end of the prompt;
     * image tags / base64 blobs are stripped first to avoid token overflows.
     */
    public function extractWordPressNewsJson(array $emailContent, int $maxRetries = 3): ?array
    {
        // 60 000 chars: a long article must reach OpenAI in full (t_news id 1194).
        $emailContentForAi = $this->sanitizeEmailContentForOpenAI($emailContent, 60000);
        $emailJson = wp_json_encode($emailContentForAi, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($emailJson) || trim($emailJson) === '') {
            return null;
        }

        $basePrompt = $this->buildWordPressExtractionPrompt($emailJson);
        $lastRaw = null;
        $lastErrors = [];
        $contentFilterHit = false;
        $lengthHit = false;

        // 24 000: long FR+EN articles exceeded 12 000 and got cut (finish_reason=length).
        $maxTokensForExtraction = 24000;

        for ($attempt = 1; $attempt <= max(1, $maxRetries); $attempt++) {
            $prompt = ($attempt === 1 || $lastRaw === null)
                ? $basePrompt
                : $this->buildWordPressRepairPrompt($emailJson, (string) $lastRaw, $lastErrors);

            $raw = $this->callOpenAI($prompt, $maxTokensForExtraction, 0.0, ['type' => 'json_object'], ['disable_fast_fallback' => true]);
            $lastRaw = $raw;

            if ($raw === null) {
                $reason = $this->lastFailureReason;
                if ($reason === 'length') {
                    $lastErrors = ['length'];
                    $lengthHit = true;
                } elseif ($reason === 'content_filter') {
                    $lastErrors = ['content_filter'];
                    $contentFilterHit = true;
                } else {
                    $lastErrors = ['openai_call_failed'];
                }
                continue;
            }

            $decoded = $this->decodeJsonObject($raw);
            if (!is_array($decoded)) {
                $trimmed = trim((string) $raw);
                if (str_starts_with($trimmed, '{') && !str_ends_with($trimmed, '}')) {
                    // A dangling, unclosed JSON object is a cutoff, never a content_filter.
                    $lastErrors = ['truncated_json'];
                    $lengthHit = true;
                } else {
                    $lastErrors = ['invalid_json'];
                }
                continue;
            }

            $errors = $this->validateWordPressNewsPayload($decoded);
            if (empty($errors)) {
                return $decoded;
            }
            $lastErrors = $errors;
        }

        // Final fallback: stable model, then reduced content if content_filter.
        $primaryModel  = AMNP_Config::str('OPENAI_MODEL');
        $fallbackModel = trim(AMNP_Config::str('OPENAI_FALLBACK_MODEL'));

        if ($fallbackModel !== '' && strtolower($fallbackModel) !== strtolower(trim($primaryModel))) {
            AMNP_Logger::info("OpenAI: bascule sur le modèle de secours pour l'extraction WordPress", [
                'primary'            => $primaryModel,
                'fallback'           => $fallbackModel,
                'errors'             => $lastErrors,
                'content_filter_hit' => $contentFilterHit,
            ]);

            $raw = $this->callOpenAI($basePrompt, $maxTokensForExtraction, 0.0, ['type' => 'json_object'], ['model' => $fallbackModel]);

            if ($raw !== null) {
                $decoded = $this->decodeJsonObject($raw);
                if (is_array($decoded)) {
                    $errors = $this->validateWordPressNewsPayload($decoded);
                    if (empty($errors)) {
                        return $decoded;
                    }
                    $lastErrors = $errors;
                }
            } elseif ($this->lastFailureReason === 'length') {
                $lengthHit = true;
            } elseif ($this->lastFailureReason === 'content_filter') {
                $contentFilterHit = true;
            }

            // Never summarise an article that was merely too long (length cutoff):
            // that violates the verbatim rule. Only a real content_filter gets the
            // reduced content + concise prompt.
            if ($contentFilterHit && !$lengthHit && $raw === null) {
                AMNP_Logger::info('OpenAI: content_filter détecté → re-tentative avec contenu réduit à 4 000 chars + prompt concis', ['fallback' => $fallbackModel]);

                $shortJson = wp_json_encode($this->sanitizeEmailContentForOpenAI($emailContent, 4000), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                if (is_string($shortJson) && trim($shortJson) !== '') {
                    $raw = $this->callOpenAI($this->buildWordPressExtractionPromptConcise($shortJson), 6000, 0.0, ['type' => 'json_object'], ['model' => $fallbackModel]);
                    if ($raw !== null) {
                        $decoded = $this->decodeJsonObject($raw);
                        if (is_array($decoded)) {
                            $errors = $this->validateWordPressNewsPayload($decoded);
                            if (empty($errors)) {
                                return $decoded;
                            }
                            $lastErrors = $errors;
                        }
                    }
                }
            }
        }

        AMNP_Logger::warning('OpenAI extractWordPressNewsJson failed validation', [
            'errors'             => $lastErrors,
            'content_filter_hit' => $contentFilterHit,
            'length_hit'         => $lengthHit,
            'raw_excerpt'        => is_string($lastRaw) ? mb_substr($lastRaw, 0, 1200) : null,
        ]);

        return null;
    }

    private function sanitizeEmailContentForOpenAI(array $emailContent, int $maxBodyChars = 8000): array
    {
        $sanitized = $emailContent;

        // Attachments are handled in PHP; they only add tokens for OpenAI.
        unset($sanitized['attachments']);

        if (isset($sanitized['html_body']) && is_string($sanitized['html_body'])) {
            $html = $this->extractMessageBodyHtmlForOpenAI($sanitized['html_body']);
            $html = $this->stripImagesFromHtmlForOpenAI($html);
            if ($maxBodyChars > 0 && mb_strlen($html) > $maxBodyChars) {
                $html = mb_substr($html, 0, $maxBodyChars) . '…[tronqué]';
            }
            $sanitized['html_body'] = $html;
        }

        foreach (['plain_body', 'text_body', 'body'] as $key) {
            if (isset($sanitized[$key]) && is_string($sanitized[$key]) && $maxBodyChars > 0 && mb_strlen($sanitized[$key]) > $maxBodyChars) {
                $sanitized[$key] = mb_substr($sanitized[$key], 0, $maxBodyChars) . '…[tronqué]';
            }
        }

        return $sanitized;
    }

    private function extractMessageBodyHtmlForOpenAI(string $html): string
    {
        $source = $html;
        if (trim($source) === '' || stripos($source, 'messagebody') === false || !class_exists('DOMDocument')) {
            return $source;
        }

        $previousUseErrors = libxml_use_internal_errors(true);

        try {
            $dom = new \DOMDocument();
            $wrapped = '<!doctype html><html><head><meta charset="utf-8"></head><body>' . $source . '</body></html>';
            if (!@$dom->loadHTML($wrapped, LIBXML_NOWARNING | LIBXML_NOERROR)) {
                return $source;
            }

            $xpath = new \DOMXPath($dom);
            $nodes = $xpath->query('//*[@id="messagebody"]');
            if (!$nodes || $nodes->length < 1) {
                return $source;
            }

            $messageBody = $nodes->item(0);
            if (!$messageBody instanceof \DOMElement) {
                return $source;
            }

            // Remove attachment blocks inside messagebody (best-effort).
            $attachmentNodes = $xpath->query(
                './/*[contains(translate(@class, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "attachment")
                    or contains(translate(@class, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "attachement")
                    or contains(translate(@class, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "attachments")
                    or contains(translate(@id, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "attachment")
                    or contains(translate(@id, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "attachement")
                    or contains(translate(@id, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "attachments")
                ]',
                $messageBody
            );
            if ($attachmentNodes && $attachmentNodes->length > 0) {
                for ($i = $attachmentNodes->length - 1; $i >= 0; $i--) {
                    $n = $attachmentNodes->item($i);
                    if ($n && $n->parentNode) {
                        $n->parentNode->removeChild($n);
                    }
                }
            }

            $out = '';
            foreach ($messageBody->childNodes as $child) {
                $out .= $dom->saveHTML($child);
            }
            $out = trim($out);
            if ($out === '') {
                return $source;
            }

            $out = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $out) ?? $out;
            $out = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $out) ?? $out;
            return preg_replace('/<!--.*?-->/s', ' ', $out) ?? $out;
        } catch (\Throwable $_) {
            return $source;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseErrors);
        }
    }

    private function stripImagesFromHtmlForOpenAI(string $html): string
    {
        $text = $html;
        if (trim($text) === '') {
            return '';
        }

        // 1. Outlook/Yahoo conditional VML blocks (file:/// and blob: paths trigger content_filter).
        $text = preg_replace('/<!--\[if[^\]]*\]>.*?<!\[endif\]-->/is', ' ', $text) ?? $text;
        // 2. Residual VML / Office tags.
        $text = preg_replace('/<v:[a-z]+\b[^>]*>.*?<\/v:[a-z]+>/is', ' ', $text) ?? $text;
        $text = preg_replace('/<v:[a-z]+\b[^>]*\/>/i', ' ', $text) ?? $text;
        $text = preg_replace('/<o:[a-z]+\b[^>]*>.*?<\/o:[a-z]+>/is', ' ', $text) ?? $text;
        // 3. Remaining HTML comments.
        $text = preg_replace('/<!--.*?-->/s', ' ', $text) ?? $text;
        // 4. <picture> and <img>.
        $text = preg_replace('/<picture\b[^>]*>.*?<\/picture>/is', ' ', $text) ?? $text;
        $text = preg_replace('/<img\b[^>]*>/i', ' ', $text) ?? $text;
        // 5. base64 / blob: / file:// URLs.
        $text = preg_replace('/data:image\/[a-z0-9.+-]+;base64,[a-z0-9\/+\r\n=]+/i', '[image-removed]', $text) ?? $text;
        $text = preg_replace('/blob:[^\s"\'<>]+/i', '[blob-url-removed]', $text) ?? $text;
        return preg_replace('/file:\/\/\/[^\s"\'<>]+/i', '[local-path-removed]', $text) ?? $text;
    }

    private function buildWordPressExtractionPrompt(string $emailJson): string
    {
        return "L'idée est de lire, analyser le contenu d'un mail en HTML et retourner les informations dans la description suivante pour être ajouter automatiquement dans wordpress.\n"
            . "Voici les descriptions:\n"
            . " - Voici un extraction de mail transféré en html qui contient des actualités aéronautique et spatial en version française et anglaise. Il peut contenir des html du header, footer, forwarder, ... que tu ne doit pas prendre en compte.\n"
            . " - Analyse bien le contenu du mail selon les textes.\n"
            . " - Prépare un JSON avec les clés \"titleFR\", \"shorttitleFR\", \"titleEN\", \"shorttitleEN\",\"FR\",\"EN\", \"metadescriptionFR\", \"metadescriptionEN\", \"focuskeyphraseFR\" et \"focuskeyphraseEN\".\n"
            . " - Après analyse, Fait une extraction du titre de la section française et met dans le JSON \"titleFR\" en texte brut sans HTML. Faire pareil pour la version EN mais a mettre dans \"titleEN\".\n"
            . " - si le \"titleFR\" dépasse les 62 caractères, reformule la phrase pour que ça soit moins de 62 caractères pour le SEO et met dans le json \"shorttitleFR\". Sinon, met directement le \"titleFR\" dans \"shorttitleFR\". Faire pareil pour la version EN mais a mettre dans la clé \"shorttitleEN\" du JSON.\n"
            . " - Fait un extraction du contenu de la version française tout en gardant les balises html pour la mise en page. (gras, saut de ligne, puce (ul, li, ...), italique, ...). Bien enlever tout ce qui ne concerne pas la news (actualité), mais garde la description de la société (A propos ...). Si la source de l'article n'est pas mentionné dans le contenu, ajoute à la fin ce format avec la source identifié \"Source: <le_nom_de_la_source>\" (la source peu être la société concerné ou aeromorning même si c'est mentionné). Encode-le dans la valeur de la clé FR du JSON. Si le \"titleFR\" dessus a dépassé les 62 caractères, modifie dans ce contenu HTML FR le titre complet \"titleFR\" pour que ça soit dans une balise h2, sinon l'enlever du contenu. Faire pareil pour la version EN mais a mettre dans le clé EN du JSON.\n"
            . " - Génère un metadescription selon le contenu FR qui doit strictement faire entre 107 et 142 caractères et le mettre dans le JSON \"metadescriptionFR\". Faire pareil pour la version EN mais a mettre dans \"metadescriptionEN\" du JSON.\n"
            . " - Génère un FocusKeyPhrase depuis le contenu FR qui ne doit strictement pas avoir une virgule et le mettre dans \"focuskeyphraseFR\". Faire pareil pour la version EN mais a mettre dans \"focuskeyphraseEN\" du JSON.\n"
            . " - Me retourner un JSON valide pour PHP. Écris les caractères accentués et la ponctuation typographique tels quels en UTF-8 (é, è, à, ç, ’, –, « »). N'échappe que ce que JSON impose (guillemets doubles, antislash, retours à la ligne) ; n'utilise jamais de séquences \\uXXXX, et surtout jamais \\u0000.\n"
            . " - HTML des champs FR/EN : uniquement des balises simples (p, h2, h3, strong, em, u, ul, ol, li, br, table/tr/th/td pour un vrai tableau de données, a href), sans attribut class, style, id, lang, width. Pas de div, span ni font. Chaque paragraphe tient sur une seule ligne : supprime les retours à la ligne de mise en forme de l'email à l'intérieur des phrases.\n\n"
            . "RÈGLE ABSOLUE — VERBATIM :\n"
            . "Tu dois extraire le contenu TEL QUEL, mot pour mot, sans JAMAIS :\n"
            . " • couper, raccourcir ou tronquer une section ou un paragraphe ;\n"
            . " • reformuler, paraphraser, résumer ou réécrire un passage ;\n"
            . " • ajouter des informations non présentes dans le mail ;\n"
            . " • supprimer des sections entières même si elles te semblent secondaires.\n"
            . "Reproduis l'intégralité du contenu de chaque version (FR et EN) exactement comme il apparaît dans l'email. La seule modification autorisée est la correction des erreurs d'encodage évidentes (ex : apostrophes cassées, tirets remplacés par des codes hex).\n\n"
            . "IMPORTANT: Tu dois retourner uniquement le JSON brut (pas de Markdown, pas de ```). Le JSON doit être valide et parseable par json_decode en PHP. Les champs FR/EN contiennent du HTML avec de vraies balises (pas de &lt;p&gt;).\n\n"
            . "Voici le contenu du mail:\n\n"
            . $emailJson;
    }

    /** Used only when the standard prompt hit OpenAI's content_filter. */
    private function buildWordPressExtractionPromptConcise(string $emailJson): string
    {
        return "Extract aviation news from this forwarded email for WordPress (bilingual FR/EN).\n\n"
            . "Return ONLY a raw JSON object (no Markdown, no ```) with these exact keys:\n"
            . "titleFR, shorttitleFR, titleEN, shorttitleEN, FR, EN, "
            . "metadescriptionFR, metadescriptionEN, focuskeyphraseFR, focuskeyphraseEN\n\n"
            . "Rules:\n"
            . "- titleFR / titleEN: plain text, no HTML.\n"
            . "- shorttitleFR / shorttitleEN: ≤ 62 chars (rephrase if needed).\n"
            . "- FR / EN: concise HTML article (≤ 350 words each). Keep only essential news facts. "
            . "Use <p>, <b>, <i>, <ul>/<li> as needed. Add '<p><b>Source: X</b></p>' at end if source not mentioned.\n"
            . "- metadescriptionFR / metadescriptionEN: exactly 107–142 chars.\n"
            . "- focuskeyphraseFR / focuskeyphraseEN: no comma.\n"
            . "- The JSON values must be properly escaped for PHP json_decode.\n\n"
            . "Email content:\n\n"
            . $emailJson;
    }

    private function buildWordPressRepairPrompt(string $emailJson, string $previousRaw, array $errors): string
    {
        return "Tu as retourné une réponse invalide/non conforme pour json_decode().\n"
            . "Corrige ta réponse et retourne UNIQUEMENT un JSON valide (pas de Markdown, pas de ```).\n"
            . "Le JSON doit contenir exactement les clés suivantes: " . implode(', ', self::WP_JSON_KEYS) . ".\n"
            . 'Erreurs à corriger: ' . implode(', ', $errors) . ".\n\n"
            . "RÉPONSE PRÉCÉDENTE (à corriger):\n"
            . mb_substr($previousRaw, 0, 8000)
            . "\n\nVoici le contenu du mail:\n\n"
            . $emailJson;
    }

    private function decodeJsonObject(?string $raw): ?array
    {
        if (!is_string($raw)) {
            return null;
        }
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw) ?? $raw;
        $raw = preg_replace('/\s*```\s*$/', '', $raw) ?? $raw;

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Cut-off JSON object: let the repair prompt handle it.
        if (str_starts_with($raw, '{') && !str_ends_with($raw, '}')) {
            return null;
        }

        if (preg_match('/\{.*\}/s', $raw, $matches) === 1) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function validateWordPressNewsPayload(array $payload): array
    {
        $errors = [];
        foreach (self::WP_JSON_KEYS as $key) {
            if (!array_key_exists($key, $payload)) {
                $errors[] = 'missing_' . $key;
            }
        }
        // Empty SEO fields used to write blank Yoast meta.
        foreach (['metadescriptionFR', 'metadescriptionEN', 'focuskeyphraseFR', 'focuskeyphraseEN'] as $key) {
            if (array_key_exists($key, $payload) && (!is_string($payload[$key]) || trim($payload[$key]) === '')) {
                $errors[] = 'empty_' . $key;
            }
        }
        return array_values(array_unique($errors));
    }

    public function classifyCategories(string $newsContent, array $categories, string $lang = 'FR'): ?string
    {
        if (empty($categories)) {
            return '';
        }

        $categoriesList = implode("\n", array_map(static function ($cat) {
            return "- ID: {$cat['wp_id']}, Name: {$cat['categ_name']}";
        }, $categories));

        $prompt = "ROLE :
Tu es un classificateur editorial specialise en aeronautique et transport aerien.

CONTENU DE LA NEWS:
$newsContent

LISTE DES CATEGORIES:
$categoriesList

--------------------------------------------------

DETECTION PRIORITAIRE - HEADER AEROMORNING :
Cherche dans le contenu une ligne qui mentionne la categorie de l'article (qui sera après la categorie News)
ou toute variation (Defence, Spatial, MRO, etc.).
Si tu en trouves une, extrais la categorie secondaire (le mot apres le slash : Industry, Industrie, Defence, MRO, Spatial, etc.)
et cherche-la dans la LISTE DES CATEGORIES. Si elle y est, ajoute son wp_id en second (apres News).

REGLE FIXE :
La categorie \"News\" est TOUJOURS en premiere position. Son wp_id doit etre le premier retourne.

CRITERES DE SELECTION :
Selectionne au MAXIMUM 3 categories.
Une categorie est pertinente UNIQUEMENT si elle decrit directement le secteur ou l'activite principale de la news.

FORMAT DE SORTIE :
Retourne uniquement les wp_id separes par des virgules. Le wp_id de \"News\" en premier.
Aucun texte supplementaire.

EXEMPLE VALIDE :
1,5";

        return $this->sanitizeIdList($this->callOpenAI($prompt, 80), $categories, $newsContent, true, 'categ_name');
    }

    public function classifyTags(string $newsContent, array $tags, string $lang = 'FR'): ?string
    {
        if (empty($tags)) {
            return '';
        }

        // The full FR tag list (~9 500 names) can push the prompt past the
        // model's 128k-token context: OpenAI rejects the call and the article
        // gets no tags at all (17% of FR news in extract_news, Apr–Sep 2026).
        // Only when the full list would overflow, keep the tags actually named
        // in the article — the prompt only allows those anyway.
        $tags = $this->limitTagsToContextBudget($tags, $newsContent);
        if (empty($tags)) {
            return '';
        }

        $tagsList = implode("\n", array_map(static function ($tag) {
            return "- ID: {$tag['wp_id']}, Name: {$tag['tag_name']}";
        }, $tags));

        $prompt = "ROLE :
Tu es un classificateur editorial expert en aeronautique, aviation civile et industrie aérospatiale.

CONTENU DE LA NEWS:
$newsContent

LISTE DES TAGS DISPONIBLES:
$tagsList

========================
ETAPE 1 - DETECTION DU HEADER AEROMORNING (PRIORITE ABSOLUE)
========================
Cherche dans le contenu une ligne qui mentionne la catégorie de l'article
ou toute variation (Defence, Spatial, MRO, Airline, etc.).

Si tu en trouves une :
1. Cherche dans la liste le tag dont le nom de la société ou du secteur concerné
2. Extrais la categorie apres le slash (Industry, Industrie, Defence, MRO, etc.)
   Cherche ce mot dans la liste des tags disponibles -> tag prioritaire 2
3. Cherche ensuite dans le contenu l’entite aeronautique, spatiale ou défense principale (nom de societe, d’avion ou de programme)
   Si elle est dans la liste -> tag prioritaire 3 (uniquement si la pertinence est certaine)
4. Retourne ces 2 ou 3 tags uniquement. Limite stricte : 3 tags maximum.

Si tu n’en trouves pas -> applique l’ETAPE 2.

========================
FILTRE D’EXCLUSION ABSOLU - AUCUNE EXCEPTION POSSIBLE
========================
REJETTE TOUJOURS, meme si le tag est dans une liste explicite du contenu :
- Tout tag qui est une ANNEE ou contient une annee : 2023, 2024, 2025, 2026, 2027, ...
- Tout tag qui est un MOIS : jan, feb, mar, apr, may, jun, jul, aug, sep, oct, nov, dec
- Tout tag de periode : Q1, Q2, Q3, Q4, H1, H2
- Tout acronyme generique non identifie comme entite aviation connue (ex: ACH, ACI, ACS, AERO sans contexte precis)
- Tout tag de temporalite : today, breaking, recent, update, this week

========================
REGLE ANTI-SUBSTITUTION (TRES IMPORTANT)
========================
Si une entite mentionnee dans le contenu (societe, compagnie aerienne, programme)
n’existe PAS dans la liste des tags disponibles, NE LA REMPLACE PAS par une entite similaire.
Exemple : si le contenu parle de \"Loong Air\" et que ce tag n’existe pas dans la liste,
ne pas prendre \"SkyWest Airlines\" ou toute autre compagnie a la place.
Prefere simplement ne pas ajouter ce tag.

========================
ETAPE 2 - ANALYSE NORMALE (si pas de header AeroMorning detecte)
========================
1. Identifie l’entite aeronautique principale (societe, avion, compagnie, programme) mentionnee dans le contenu
   Verifie qu’elle existe EXACTEMENT dans la liste des tags -> priorite 1
2. Identifie une seconde entite pertinente si elle existe exactement dans la liste -> priorite 2
3. N’ajoute un 3eme tag que si sa pertinence est CERTAINE et l’entite existe dans la liste
Limite stricte : 3 tags maximum. Moins de tags vaut mieux que des tags faux.

========================
LIMITE ABSOLUE
========================
- Maximum 3 tags au total
- Si moins de 3 tags sont certains, retourne-en moins
- Ne jamais substituer une entite absente de la liste par une entite similaire
- Ne jamais inclure de dates ou annees

========================
FORMAT DE SORTIE
========================
- Retourne UNIQUEMENT les wp_id separes par des virgules
- Aucun texte, aucun espace
- Si aucun tag pertinent -> retourne vide

EXEMPLE VALIDE :
1,3,7";

        return $this->sanitizeIdList($this->callOpenAI($prompt, 80), $tags, $newsContent, false, 'tag_name');
    }

    public function isAviationRelevant(string $content): bool
    {
        $plainContent = $this->sanitizePlainText($content);
        if ($plainContent === '') {
            return false;
        }

        $excerpt = mb_substr($plainContent, 0, 4000);

        $prompt = "You are filtering incoming emails for an aviation and aerospace news publication workflow.\n"
            . "Answer YES if the email contains any real news, article, press release, announcement, nomination, robotics or editorial content related to ANY of the following topics:\n"
            . "aviation, aerospace, airline, airport, aircraft, flight operations, air transport, cargo, freight, defense (military aviation, naval aviation, defense industry), drone / UAV, eVTOL / urban air mobility, satellite, space, rocket, orbital, helicopter, engine, MRO, certification (EASA, FAA, TCCA, ANAC), safety, regulation, environment / sustainability / emissions / SAF, innovation, technology, digitalization, artificial intelligence in aviation, cybersecurity in aviation, industry news, nominations / appointments / leadership changes in aviation companies, aviation jobs / employment, aviation competitions / awards, industry events / airshows.\n"
            . "Answer NO only if the email is clearly spam, a transactional notification (Airtable, Jira, Slack, billing, SaaS tool alerts), a personal/private exchange with no news content, a purely administrative or legal notice, or marketing completely unrelated to aviation or aerospace.\n"
            . "When in doubt, answer YES.\n"
            . "Return only one word: YES or NO.\n\n"
            . $excerpt;

        $response = mb_strtoupper(trim((string) $this->callOpenAI($prompt, 64)));
        if ($response === 'YES') {
            return true;
        }
        if ($response === 'NO') {
            return false;
        }

        return $this->matchesAviationHeuristic($plainContent);
    }

    public function chooseRelevantImageUrl(string $content, array $imageCandidates): ?string
    {
        $imageCandidates = array_values(array_unique(array_filter(array_map('trim', $imageCandidates), static fn ($url) => $url !== '')));
        if (empty($imageCandidates)) {
            return null;
        }
        if (count($imageCandidates) === 1) {
            return $imageCandidates[0];
        }

        $candidateList = implode("\n", array_map(static fn (string $url, int $index) => ($index + 1) . '. ' . $url, $imageCandidates, array_keys($imageCandidates)));
        $prompt = "You are selecting the featured image for an aviation news article extracted from a forwarded email.\n"
            . "Choose the single image URL that is most likely the main article image.\n"
            . "Reject logos, banners, sponsor images, signatures, social icons, webmail assets, headers, footers, and decorative graphics.\n"
            . "Never choose the AMWS email signature banner (blue 'Endless possibilities', 'Constellation', or anything linked to 'amws.space').\n"
            . "Return only one exact URL from the candidate list below. If none is suitable, return NONE.\n\n"
            . "ARTICLE EXCERPT:\n" . mb_substr($this->prepareStructuredPromptText($content), 0, 2500) . "\n\n"
            . "IMAGE CANDIDATES:\n{$candidateList}";

        $response = trim((string) $this->callOpenAI($prompt, 40));
        if ($response === '' || mb_strtoupper($response) === 'NONE') {
            return null;
        }
        foreach ($imageCandidates as $url) {
            if ($response === $url) {
                return $url;
            }
        }
        return null;
    }

    /** ~3 characters per token for this mixed FR/EN + IDs list; 300k chars ≈ 100k tokens. */
    private const TAG_PROMPT_CHAR_BUDGET = 300000;

    private function limitTagsToContextBudget(array $tags, string $newsContent): array
    {
        $listChars = 0;
        foreach ($tags as $tag) {
            $listChars += 14 + strlen((string) $tag['wp_id']) + strlen((string) $tag['tag_name']);
        }
        if ($listChars + strlen($newsContent) <= self::TAG_PROMPT_CHAR_BUDGET) {
            return $tags;
        }

        $normalize = static function (string $text): string {
            $text = mb_strtolower(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'UTF-8');
            return ' ' . trim(preg_replace('/[^\pL\pN]+/u', ' ', $text) ?? $text) . ' ';
        };

        $article = $normalize($newsContent);
        $kept = array_values(array_filter($tags, static function ($tag) use ($normalize, $article) {
            $name = trim($normalize((string) $tag['tag_name']));
            return $name !== '' && str_contains($article, " {$name} ");
        }));

        AMNP_Logger::info('Tag list too large for the model context: kept the tags named in the article', [
            'all' => count($tags),
            'kept' => count($kept),
        ]);

        return $kept;
    }

    private function sanitizeIdList(?string $value, array $items, string $content, bool $includeNewsDefault, string $nameField): string
    {
        // Tags/categories that are dates or years must NEVER be selected.
        $isDateItem = static function (string $name): bool {
            return (bool) preg_match('/^\s*(20\d{2}|19\d{2}|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|q[1-4]|h[12])\s*$/i', $name);
        };

        $allowedIds = [];
        $idToName = [];
        foreach ($items as $item) {
            $allowedIds[(string) $item['wp_id']] = true;
            $idToName[(string) $item['wp_id']] = mb_strtolower((string) $item[$nameField]);
        }

        $ids = [];
        foreach (preg_split('/[^0-9]+/', (string) $value) ?: [] as $part) {
            if ($part === '' || !isset($allowedIds[$part]) || in_array($part, $ids, true)) {
                continue;
            }
            if ($isDateItem($idToName[$part] ?? '')) {
                continue;
            }
            $ids[] = $part;
        }

        // "News" category is mandatory and always first.
        if ($includeNewsDefault) {
            foreach ($items as $item) {
                if (mb_strtolower((string) $item[$nameField]) === 'news') {
                    $newsId = (string) $item['wp_id'];
                    $ids = array_values(array_filter($ids, static fn ($id) => $id !== $newsId));
                    array_unshift($ids, $newsId);
                    break;
                }
            }
        }

        // Content-based fallback, categories only, to fill remaining slots.
        $limit = 3;
        if ($includeNewsDefault && count($ids) < $limit) {
            $contentText = mb_strtolower($this->sanitizePlainText(strip_tags($content)));
            foreach ($items as $item) {
                if (count($ids) >= $limit) {
                    break;
                }
                $name = mb_strtolower((string) $item[$nameField]);
                if ($name === '' || in_array((string) $item['wp_id'], $ids, true) || $isDateItem($name)) {
                    continue;
                }
                if (str_contains($contentText, $name)) {
                    $ids[] = (string) $item['wp_id'];
                }
            }
        }

        return implode(',', array_slice($ids, 0, $limit));
    }

    private function sanitizePlainText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        return trim(trim($text, " \t\n\r\0\x0B\"'"));
    }

    private function prepareStructuredPromptText(string $content): string
    {
        $text = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\/(p|div|h1|h2|h3|h4|li|ul|ol|blockquote)>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<(li)[^>]*>/i', '- ', $text) ?? $text;
        $text = strip_tags($text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        $filtered = [];
        foreach (preg_split('/\n/', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                if (!empty($filtered) && end($filtered) !== '') {
                    $filtered[] = '';
                }
                continue;
            }
            if ($this->isForbiddenTitleCandidate($line)) {
                continue;
            }
            $filtered[] = $line;
        }

        return trim(implode("\n", $filtered));
    }

    private function isForbiddenTitleCandidate(string $title): bool
    {
        $title = mb_strtolower(trim($title));
        if ($title === '') {
            return true;
        }

        // Category-like strings and menu separators are not real titles.
        if (str_contains($title, ' / ') && preg_match('/\b(news|industry|industrie|services)\b/i', $title) === 1) {
            return true;
        }
        if (substr_count($title, ',') >= 2 && preg_match('/\b(aeronautique|aéronautique|industrie|industry|environnement|environment|helicopteres?|hélicoptères|news|services)\b/i', $title) === 1) {
            return true;
        }

        $forbiddenPatterns = [
            '/^(top|bottom) of form$/i',
            '/^related articles$/i',
            '/^leave a comment$/i',
            '/^additional links$/i',
            '/^topics\s*:/i',
            '/^flash news$/i',
            '/^posted by\s*:/i',
            '/^source\s*:/i',
            '/^about\s*:/i',
            '/^à propos\s*:/i',
            '/^ou\s*f\s*news$/i',
            '/^industry$/i',
            '/^ou\s*industrie$/i',
            '/^industry\s*ou\s*industrie$/i',
            '/^ou\s*f\s*news\s*\/\s*industry\s*ou\s*industrie$/i',
        ];
        foreach ($forbiddenPatterns as $pattern) {
            if (preg_match($pattern, $title) === 1) {
                return true;
            }
        }

        return false;
    }

    private function matchesAviationHeuristic(string $content): bool
    {
        $content = mb_strtolower($content);

        $keywords = [
            'aviation', 'aeronaut', 'aerosp', 'airline', 'airport', 'aircraft', 'flight', 'fleet',
            'boeing', 'airbus', 'embraer', 'atr', 'safran', 'rolls-royce', 'pratt', 'ge aviation',
            'engine', 'faa', 'easa', 'iata', 'icao', 'dgac',
            'nasa', 'spacex', 'satellite', 'rocket', 'launch', 'orbital', 'lunar', 'spatial', 'espace',
            'helicopter', 'helicoptere', 'hélicoptère', 'evtol', 'vtol', 'urban air',
            'air cargo', 'fret aerien', 'fret aérien', 'cargo', 'freight',
            'transport aerien', 'transport aérien', 'compagnie aerienne', 'compagnie aérienne',
            'aeroport', 'aéroport', 'avion', 'vol ',
            'defense', 'défense', 'militaire', 'naval', 'armée de l\'air', 'air force',
            'drone', 'uav', 'uas',
            'technolog', 'innovation', 'innovant', 'numérique', 'digital', 'intelligence artificielle',
            'artificial intelligence', 'cybersecur', 'industrie 4',
            'durabilit', 'sustainability', 'saf ', 'carburant durable', 'émission', 'emission',
            'environnement', 'décarbonation', 'net zero',
            'certif', 'homologat', 'mro', 'maintenance', 'industrie aeronautique', 'industrie aéronautique',
            'nomination', 'nommé', 'nommée', 'directeur général', 'ceo', 'président',
            'emploi', 'recrutement', 'hiring', 'concours', 'award', 'airshow', 'salon du bourget', 'paris air',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($content, $keyword)) {
                return true;
            }
        }
        return false;
    }

    private function callOpenAI(string $prompt, int $maxTokens = 500, float $temperature = 0.3, ?array $responseFormat = null, array $options = []): ?string
    {
        $this->lastFailureReason = null;

        $apiKey = AMNP_Config::str('OPENAI_API_KEY');
        if ($apiKey === '') {
            AMNP_Logger::error('OpenAI API error: AMNP_OPENAI_API_KEY is not set');
            return null;
        }

        $model = AMNP_Config::str('OPENAI_MODEL');
        $fallbackModel = trim(AMNP_Config::str('OPENAI_FALLBACK_MODEL'));

        if (isset($options['model']) && is_string($options['model']) && $options['model'] !== '') {
            $model = $options['model'];
        }

        // gpt-5 is replaced by the faster fallback model for the small calls
        // (relevance, image, categories, tags), never for the main extraction.
        $disableFastFallback = (bool) ($options['disable_fast_fallback'] ?? false);
        if (!$disableFastFallback
            && AMNP_Config::bool('OPENAI_GPT5_FAST_FALLBACK')
            && $fallbackModel !== ''
            && strtolower($fallbackModel) !== strtolower(trim($model))
            && str_starts_with(strtolower(trim($model)), 'gpt-5')
        ) {
            $model = $fallbackModel;
        }

        // Reasoning models can spend their budget internally and return nothing.
        if ($this->shouldUseMaxCompletionTokens($model)) {
            $maxTokens = max($maxTokens, AMNP_Config::int('OPENAI_MIN_COMPLETION_TOKENS'));
        }

        $payload = $this->buildChatPayload($model, $prompt, $maxTokens, $temperature, $responseFormat);

        $attempts = 0;
        while ($attempts < 2) {
            $attempts++;
            try {
                $result = $this->request($payload);
                $contentText = $result['content'];
                $finishReason = $result['finish_reason'];

                if ($contentText !== '') {
                    // content_filter: partial, unusable output — caller retries shorter.
                    if ($finishReason === 'content_filter') {
                        $this->lastFailureReason = 'content_filter';
                        AMNP_Logger::warning('OpenAI content_filter: réponse tronquée par le filtre de sécurité', ['model' => $model, 'output_chars' => mb_strlen($contentText)]);
                        return null;
                    }
                    // length: cut by max tokens. Returning the fragment used to publish
                    // truncated articles (t_news id 1194); the caller must not summarise.
                    if ($finishReason === 'length') {
                        $this->lastFailureReason = 'length';
                        AMNP_Logger::warning('OpenAI length: réponse coupée par max_tokens (pas un content_filter)', ['model' => $model, 'max_tokens' => $maxTokens, 'output_chars' => mb_strlen($contentText)]);
                        return null;
                    }
                    return $contentText;
                }

                // Empty output from a reasoning model: fall back to a chat-stable model.
                if ($fallbackModel !== '' && strtolower($fallbackModel) !== strtolower(trim($model)) && $this->shouldUseMaxCompletionTokens($model)) {
                    $fallback = $this->request($this->buildChatPayload($fallbackModel, $prompt, $maxTokens, $temperature, $responseFormat));
                    return $fallback['content'] !== '' ? $fallback['content'] : null;
                }

                return null;
            } catch (\Throwable $e) {
                $message = $e->getMessage();
                AMNP_Logger::error('OpenAI API error: ' . $message);

                // Some models don't support response_format; retry once without it.
                if ($responseFormat !== null && preg_match('/response_format|unknown parameter|unrecognized request argument|unsupported/i', $message) === 1) {
                    $responseFormat = null;
                    $payload = $this->buildChatPayload($model, $prompt, $maxTokens, $temperature, null);
                    continue;
                }
                // Retry once on transient/timeout failures.
                if ($attempts < 2 && preg_match('/timeout|timed out|cURL error 28/i', $message) === 1) {
                    continue;
                }
                return null;
            }
        }

        return null;
    }

    /**
     * @return array{content: string, finish_reason: ?string}
     * @throws \RuntimeException on transport or API error
     */
    private function request(array $payload): array
    {
        $response = wp_remote_post(self::ENDPOINT, [
            'timeout' => AMNP_Config::int('OPENAI_HTTP_TIMEOUT'),
            'headers' => [
                'Authorization' => 'Bearer ' . AMNP_Config::str('OPENAI_API_KEY'),
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException($response->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = json_decode((string) wp_remote_retrieve_body($response), true);

        if ($code < 200 || $code >= 300) {
            $apiMessage = is_array($body) ? (string) ($body['error']['message'] ?? '') : '';
            throw new \RuntimeException("HTTP {$code}" . ($apiMessage !== '' ? ": {$apiMessage}" : ''));
        }

        $choice = is_array($body) ? ($body['choices'][0] ?? []) : [];
        $content = $choice['message']['content'] ?? '';

        if (is_array($content)) {
            $parts = [];
            foreach ($content as $part) {
                if (is_string($part)) {
                    $parts[] = $part;
                } elseif (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                    $parts[] = $part['text'];
                }
            }
            $content = implode('', $parts);
        }

        return [
            'content'       => trim((string) $content),
            'finish_reason' => isset($choice['finish_reason']) ? (string) $choice['finish_reason'] : null,
        ];
    }

    private function buildChatPayload(string $model, string $prompt, int $maxTokens, float $temperature, ?array $responseFormat = null): array
    {
        $payload = [
            'model'    => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ];

        if (is_array($responseFormat)) {
            $payload['response_format'] = $responseFormat;
        }

        // Some models only support the default temperature.
        if (!$this->shouldOmitTemperature($model)) {
            $payload['temperature'] = $temperature;
        }

        // Newer models reject max_tokens and require max_completion_tokens.
        if ($this->shouldUseMaxCompletionTokens($model)) {
            $payload['max_completion_tokens'] = $maxTokens;
        } else {
            $cap = AMNP_Config::int('OPENAI_MAX_TOKENS');
            $payload['max_tokens'] = $cap > 0 ? min($maxTokens, $cap) : $maxTokens;
        }

        return $payload;
    }

    private function shouldUseMaxCompletionTokens(string $model): bool
    {
        $model = strtolower(trim($model));
        return str_starts_with($model, 'gpt-5') || str_starts_with($model, 'o1') || str_starts_with($model, 'o3') || str_starts_with($model, 'gpt-4.1');
    }

    private function shouldOmitTemperature(string $model): bool
    {
        $model = strtolower(trim($model));
        return str_starts_with($model, 'gpt-5') || str_starts_with($model, 'o1') || str_starts_with($model, 'o3') || str_starts_with($model, 'o4');
    }
}
