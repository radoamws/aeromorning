# Suivi environnement local vs production — aeromorning.com

Ce document recense tout ce qui diffère entre cet environnement local (XAMPP) et la production, pour éviter qu'un écart local ne parte par erreur en prod, et pour garder trace de ce qui, au contraire, doit y être répliqué (code ou SQL).

**À tenir à jour à chaque changement.** Trois sections à alimenter :
1. **Config locale à NE JAMAIS déployer** — fichiers/valeurs propres à cet environnement (identifiants, chemins, flags de debug...).
2. **Corrections de code à déployer en prod** — vrais bugs corrigés ici, valables aussi en prod.
3. **Scripts SQL à exécuter en prod** — uniquement si une correction touche des données et doit être répliquée côté prod (la plupart des changements SQL faits ici sont des migrations d'URL *locales uniquement* et ne doivent PAS être rejouées en prod — voir section 1).

---

## 1. Config locale — à NE PAS déployer en prod

| Élément | Local | Prod | Fichier |
|---|---|---|---|
| Base de données | `aeromorning` / `root` / sans mot de passe / `localhost` | `aeromorning_2026` / user dédié / mot de passe / `localhost` | `wp-config.php` (non versionné, `.gitignore`) |
| `DOMAIN_CURRENT_SITE` / `PATH_CURRENT_SITE` | `localhost` / `/aeromorning/` | `aeromorning.com` / `/` | `wp-config.php` |
| `WP_DEBUG` / `WP_DEBUG_LOG` | `true` / `true` | `false` / `false` | `wp-config.php` |
| `FORCE_SSL_ADMIN` | `false` (admin en http) | `true` | `wp-config.php` |
| `WP_HTTP_BLOCK_EXTERNAL` | `true`, aucun host autorisé | absent (pas de restriction) | `wp-config.php` |
| `RewriteBase` | `/aeromorning/` | `/` | `.htaccess` (non versionné, `.gitignore`) |
| Plugin LiteSpeed Cache | désactivé (réseau) | actif | état stocké en base (table `wp_sitemeta`/`wp_options`), local uniquement |
| Vhost Apache `aeromorning.com` dans `httpd-vhosts.conf` | présent mais inutilisé (config serveur, hors dépôt) | n/a | `C:\xampp8\apache\conf\extra\httpd-vhosts.conf` |
| `wp_is_application_passwords_available` forcé à `true` | oui (nécessaire car pas de https local) | n'existe pas (prod a du vrai https, le filtre n'est pas nécessaire) | `wp-content/mu-plugins/local-dev-overrides.php` (non versionné, `.gitignore` — nouveau mu-plugin séparé du principal pour ne pas polluer le fichier commité) |

**Pourquoi séparé :** `wp-config.php` et `.htaccess` sont dans `.gitignore` — chaque environnement a sa propre copie, donc rien de tout ça ne peut fuiter vers un commit. Le tableau ci-dessus sert de rappel humain, pas de garde-fou technique.

## 2. Corrections de code à déployer en prod

Ces changements sont des vrais bugs (indépendants de l'environnement), commités dans le dépôt git local. **À reporter manuellement sur le serveur de prod** (ce dépôt git n'est pas encore relié à la prod).

- **2026-09-17** — `wp-content/themes/mh_newsdesk/header.php` et `footer.php` : les liens hreflang, le lien/l'image des drapeaux FR/EN, et le lien "Mentions légales" étaient codés en dur vers `https://aeromorning.com` / `/en` au lieu d'utiliser `get_home_url()`. Corrigé pour utiliser `get_home_url(1, ...)` / `get_home_url(2, ...)`. Sans impact visuel en prod (même URL produite), mais rend le thème correct sur n'importe quel environnement. Commits `4b845b9c`, `5eeeee77`.
- **2026-09-17** — Liaison hreflang FR/EN par article (voir détail dans l'historique ci-dessous). Commit `173d01bb` (validé et commité par l'utilisateur directement). Concerne :
  - `wp-content/mu-plugins/aeromorning-api.php` (endpoint REST + sortie hreflang)
  - `wp-content/themes/mh_newsdesk/header.php` (retrait des 3 lignes hreflang codées en dur, remplacées par le hook du mu-plugin)
  - `extract_news/api/database/migrations/2026_09_17_000001_add_wp_link_and_hreflang_to_t_news_table.php` (migration à exécuter en prod : `php artisan migrate --force`, déjà commitée avec le reste d'`extract_news/`, commit `2b5d879a`)
  - `extract_news/api/app/Models/News.php`, `app/Services/WordPressPostingService.php`, `app/Console/Commands/PublishNewsCommand.php`, `app/Console/Commands/LinkHreflangCommand.php` (nouvelle commande)

## 3. Scripts SQL à exécuter en prod

La migration d'URL `aeromorning.com` → `localhost/aeromorning` faite en base locale via WP-CLI `search-replace` le 2026-09-17 est **strictement locale** — ne jamais la rejouer sur la base de prod.

- **2026-09-17** — `extract_news/add_wp_link_and_hreflang_columns.sql` : équivalent SQL brut de la migration Laravel `2026_09_17_000001_add_wp_link_and_hreflang_to_t_news_table` (ajoute `wp_link` et `hreflang_linked` sur `t_news`, dans la base **extract_news**, pas la base WordPress). Idempotent (`ADD COLUMN IF NOT EXISTS`), même convention que `extract_news/ignored_emails_add_columns.sql` déjà en place. À lancer sur la BDD prod **avant** de déployer le code, ou à la place lancer `php artisan migrate --force` directement sur le serveur si c'est plus simple pour toi — dans ce cas le script `.sql` n'est qu'une référence, ne pas le lancer en plus (double ALTER TABLE sans erreur grâce à `IF NOT EXISTS`, mais la table `migrations` ne serait pas à jour si tu passes par le SQL seul — voir le commentaire dans le fichier).

**Convention retenue pour la suite** (demande explicite) : chaque fois qu'une migration Laravel ajoute/modifie des colonnes dans `t_news` (ou une autre table `extract_news`), je générerai systématiquement le script `.sql` correspondant à côté, sur le même modèle.

---

## Historique des sessions

### 2026-09-16 — Mise en route initiale
- Site cloné dans `C:\xampp8\htdocs\aeromorning`, base `aeromorning` déjà importée en local.
- `wp-config.php` pointé vers la base locale (`root`, sans mot de passe).
- Ajout de `WP_HTTP_BLOCK_EXTERNAL` (bloque tous les appels HTTP sortants) pour éviter des blocages de 240s dus à des plugins qui tentaient des appels externes.
- Premier commit git (`48c0cb1d`) avec `.gitignore` (secrets, uploads, caches générés, `/stat/`).

### 2026-09-17 — URLs, stabilité EN, admin, .htaccess
- Migration des URLs stockées en base (`https://aeromorning.com` et variantes → `http://localhost/aeromorning`) via WP-CLI `search-replace`, ~424k remplacements, sauvegarde SQL prise avant (dans le scratchpad de session, non conservée).
- `wp_site`/`wp_blogs` repointés vers `localhost` / `/aeromorning/` (FR) / `/aeromorning/en/` (EN).
- Fix des liens codés en dur dans le thème (`header.php`, `footer.php`) — voir section 2.
- Désactivation du plugin LiteSpeed Cache (réseau) : sa minification/combinaison CSS/JS synchrone causait des blocages de plusieurs dizaines de secondes, surtout sur EN.
- `FORCE_SSL_ADMIN` passé à `false` : l'admin restait forcé en https en local.
- `.htaccess` : `RewriteBase /` → `RewriteBase /aeromorning/` (la prod est à la racine du domaine, le local est dans un sous-dossier ; sans ce correctif, `/aeromorning/wp-admin` perdait son préfixe et redirigeait vers `/wp-admin/` à la racine).
- Création de ce document.

### 2026-09-17 (suite) — Liaison hreflang FR/EN par article

**Contexte.** L'app `/extract_news/` (Laravel `api/` + Nuxt `gestion-news/`, déployée en prod sur `news.aeromorning.com`) lit les mails reçus sur `news@aeromorning.com`, en extrait un article, et publie deux posts WordPress indépendants (un sur le site FR, un sur le site EN) via l'API REST WordPress. Le hreflang était codé en dur dans le thème et pointait toujours vers les deux pages d'accueil, jamais vers l'article réellement traduit (repéré via l'exemple MBDA : `.../mbda-va-equiper-.../` ↔ `.../mbda-to-equip-.../`).

**Approche retenue (parmi les 3 proposées par l'utilisateur) : `extract_news` pousse le lien vers un endpoint REST custom côté WordPress.** Écartées : (a) transformer `/extract_news/` en plugin WordPress — trop de refonte pour un gain nul (l'appli est un backend Laravel séparé, pas du code WP) ; (b) le plugin réseau ThreeWP Broadcast (déjà installé) — conçu pour dupliquer un post existant vers un autre site depuis l'admin WP, pas pour lier deux posts créés indépendamment via API, aurait demandé de détourner son fonctionnement.

**Ce qui a été fait :**
- Côté WordPress (`wp-content/mu-plugins/aeromorning-api.php`, qui expose déjà un endpoint custom pour Yoast) : nouvel endpoint `POST /wp-json/aeromorning/v1/hreflang/{id}` (auth `current_user_can('edit_posts')`, identique au reste de l'API custom) qui stocke l'URL du partenaire dans un unique postmeta `_aeromorning_hreflang_partner_url`. La langue/permalien du post lui-même sont toujours déduits à la volée (`get_current_blog_id()` / `get_permalink()`), rien d'autre à synchroniser.
- Sortie hreflang centralisée dans un hook `wp_head` du même mu-plugin : sur un article ayant un partenaire connu → hreflang vers les 2 vrais articles ; sinon (accueil, archives, article pas encore traduit) → fallback vers les 2 pages d'accueil (comportement identique à avant). Les 3 lignes hreflang codées en dur ont été retirées de `header.php` (déplacées ici, source unique).
- Côté `extract_news/api` (Laravel) :
  - Nouvelles colonnes `t_news.wp_link` (permalien, déjà renvoyé par l'API WP à la création mais jamais stocké) et `t_news.hreflang_linked` (évite de repousser le lien à chaque run).
  - `WordPressPostingService::linkHreflangWithSiblingIfReady(News $news)` : après publication réussie, cherche la traduction sœur (même `email_message_id`, `lang` opposée, déjà `SYNCED`) ; si elle existe, pousse le lien **dans les deux sens** (un appel REST vers le site FR, un vers le site EN) et marque les deux lignes `hreflang_linked`. Ne fait rien si la sœur n'est pas encore publiée — c'est elle qui déclenchera la liaison à son tour.
  - Appelée automatiquement dans `PublishNewsCommand` (`news:publish`) après chaque publication réussie — **aucun changement de comportement pour l'utilisateur, ça se fait tout seul**.
  - Nouvelle commande `php artisan news:link-hreflang [--dry-run] [--limit=200]` pour rattraper les paires **déjà publiées avant cette fonctionnalité** (résout d'abord leur `wp_link` manquant via un GET REST, puis pousse le lien pour les paires complètes non encore liées). **1051 items déjà synced sans `wp_link`** trouvés lors du test en dry-run — c'est le volume que cette commande devra traiter une fois lancée pour de vrai.

**Optimisation (demande explicite) :** coût par page vue = un `get_post_meta()` (déjà mis en cache par la boucle principale WP, donc gratuit dans le cas courant) + un `get_permalink()`, uniquement sur les articles seuls (`is_singular('post')`) — zéro requête supplémentaire sur les autres types de page. Le coût réseau (appels REST vers WordPress) n'existe que côté Laravel, une fois par paire d'articles, jamais dans le chemin d'une page vue.

**⚠️ Point de sécurité important, indépendant de cette fonctionnalité, découvert en testant :** `extract_news/api/.env` (local) a encore `WORDPRESS_FR_URL=https://aeromorning.com` et `WORDPRESS_EN_URL=https://aeromorning.com/en` — **il pointe vers la PROD, pas vers le local**. Toute commande Laravel exécutée localement (`news:publish`, `news:link-hreflang` sans `--dry-run`, etc.) agit donc réellement sur le site de prod. Rien n'a été changé ici volontairement (fichier `.env`, décision à te laisser) mais **à corriger avant de tester la pipeline extract_news en local** — piste : repointer `WORDPRESS_FR_URL`/`WORDPRESS_EN_URL` vers `http://localhost/aeromorning` et `http://localhost/aeromorning/en` ; le mot de passe d'application existant (`WORDPRESS_AUTH_FR`/`WORDPRESS_AUTH_EN`) devrait continuer à fonctionner tel quel puisque la base locale est une copie de la prod (le hash de l'Application Password a été importé avec).

**Découverte technique associée :** WordPress core exige HTTPS pour les Application Passwords (`wp_is_application_passwords_available()` teste `is_ssl()`). Comme l'admin local tourne en http (`FORCE_SSL_ADMIN=false`, voir plus haut), toute authentification par Application Password échouait en 401 — pas seulement pour ce nouvel endpoint, pour **toute** l'API REST utilisée par `extract_news` (création de post, upload média, Yoast...). Forcé à `true` via `wp-content/mu-plugins/local-dev-overrides.php` (nouveau mu-plugin séparé, gitignoré — inutile de le mettre dans `aeromorning-api.php` qui, lui, part en prod).

**Statut au 2026-09-17 :** `extract_news/` a été ajouté à git (commit `2b5d879a`) à la demande de l'utilisateur, puis les fichiers WordPress du hreflang (`aeromorning-api.php`, `header.php`, `.gitignore`) ont été validés et commités directement par l'utilisateur (commit `173d01bb`). **Tout est maintenant commité et cohérent sur `main`** — plus rien en attente pour cette fonctionnalité.

### 2026-09-17 (suite) — Ajout d'`extract_news/` à git

- Commit `2b5d879a` : 146 fichiers (`extract_news/api/` + `extract_news/gestion-news/` + fichiers racine comme les scripts `.sql`).
- **Exclus délibérément** (ajoutés à `extract_news/.gitignore`) : `api_custom_backup/` et `api_prev_incomplete/` — deux anciennes versions abandonnées de l'API, chacune avec son propre `vendor/` complet (~9000 fichiers), jamais référencées dans `DEPLOYMENT.md`, remplacées par `api/`. Aucune valeur de déploiement ; à supprimer complètement si confirmé inutile un jour.
- **Secrets déjà bien gérés** avant même cet ajout : `extract_news/.gitignore`, `api/.gitignore`, `api_custom_backup/.gitignore`, `api_prev_incomplete/.gitignore` excluaient déjà tous les `.env` (et `gestion-news/.env`), tout `vendor/`/`node_modules/`, les dossiers `storage/` Laravel (chacun avec son propre `.gitignore` imbriqué standard). Rien à ajouter de ce côté.
- `extract_news/aeromorning-api.php` (copie de référence, tenue à jour à côté de l'appli) resynchronisée avec `wp-content/mu-plugins/aeromorning-api.php` — elle avait divergé (lui manquait l'ajout hreflang) avant ce commit.

**Fichiers à uploader en prod pour cette session** (tout est maintenant commité — voir aussi le CI/CD ci-dessous qui automatisera cet upload) :
- `wp-content/mu-plugins/aeromorning-api.php` (ou `extract_news/aeromorning-api.php`, identiques)
- `wp-content/themes/mh_newsdesk/header.php`
- `extract_news/api/app/Models/News.php`
- `extract_news/api/app/Services/WordPressPostingService.php`
- `extract_news/api/app/Console/Commands/PublishNewsCommand.php`
- `extract_news/api/app/Console/Commands/LinkHreflangCommand.php` (nouveau fichier)
- `extract_news/api/database/migrations/2026_09_17_000001_add_wp_link_and_hreflang_to_t_news_table.php`
- **+ lancer `extract_news/add_wp_link_and_hreflang_columns.sql` (ou `php artisan migrate --force`) sur la BDD prod `extract_news` avant de mettre en ligne le code Laravel ci-dessus** (sinon `WordPressPostingService` plantera en écrivant sur des colonnes qui n'existent pas encore).
