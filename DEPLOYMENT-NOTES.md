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

### 2026-09-17 (suite) — CI/CD GitHub Actions vers PlanetHoster

**Objectif :** automatiser ce qui, jusqu'ici, se faisait manuellement (upload FTP/SSH). Une fois actif, la section "Fichiers à uploader en prod" ci-dessus devient obsolète pour tout ce qui touche ces 3 répertoires — un `git push` sur `main` suffit.

**Topologie serveur** (confirmée par l'utilisateur, PlanetHoster World, cPanel, PHP 8.4) — 3 racines sœurs sous `/home/aeromorning/` :
| Repo (source) | Serveur (cible) | Domaine |
|---|---|---|
| racine du repo, sauf `extract_news/` | `/home/aeromorning/public_html` | `aeromorning.com` |
| `extract_news/api/` | `/home/aeromorning/api` | `api.aeromorning.com` |
| `extract_news/gestion-news/` (build statique) | `/home/aeromorning/news` | `news.aeromorning.com` |

**Fichier :** `.github/workflows/deploy.yml`. 3 jobs indépendants (parallèles), déclenchés sur push vers `main` + déclenchement manuel (`workflow_dispatch`) :
1. **deploy-wordpress** : `rsync -az --delete` de la racine du repo vers `public_html`, avec une longue liste d'`--exclude` (essentielle : c'est elle qui empêche `--delete` d'effacer côté serveur tout ce qui n'est pas dans git mais doit y rester — `wp-config.php`, `.htaccess`, `wp-content/uploads/`, les caches LiteSpeed/EWWW générés en prod, etc.). Sans ces excludes, `--delete` détruirait le site en un push.
2. **deploy-laravel-api** : `rsync --delete` de `extract_news/api/` vers `/home/aeromorning/api` (exclut `.env`, `vendor/`, `storage/`, le symlink `public/storage`), puis `composer install --no-dev` + `php artisan migrate --force` par SSH. **Décision retenue** : les migrations passent par `php artisan migrate --force` (pas par les scripts `.sql` bruts) parce que ça tient à jour la table `migrations` de Laravel automatiquement — les `.sql` (`add_wp_link_and_hreflang_columns.sql`, `ignored_emails_add_columns.sql`) restent des références/fallback manuels, pas le mécanisme de prod.
3. **deploy-nuxt** : build (`npm ci && npm run generate`, avec `NUXT_PUBLIC_API_BASE_URL=https://api.aeromorning.com/api` injecté au build comme documenté dans `extract_news/DEPLOYMENT.md`) fait sur le runner GitHub (Node pas nécessaire côté serveur, confirmé : `nuxt.config.ts` a `ssr: false`), puis `rsync --delete` du dossier `.output/public/` vers `/home/aeromorning/news` — celui-ci est un site 100% statique généré, donc `--delete` sans exclude particulier est sûr (rien côté serveur à préserver là-dedans).

**Secrets GitHub requis** (`Settings → Secrets and variables → Actions`, jamais donnés à l'agent) : `SSH_HOST`, `SSH_PORT`, `SSH_USER`, `SSH_PRIVATE_KEY` (clé dédiée au CI/CD, différente de la clé perso de l'utilisateur — la clé publique correspondante doit être dans `~/.ssh/authorized_keys` sur le serveur, déjà fait par l'utilisateur).

**Cron jobs** (`news:process-emails`, `news:publish`, ...) : déjà configurés côté cPanel, **volontairement non gérés par ce pipeline** — n'y touche pas.

**⚠️ Pas encore poussé sur `main` volontairement.** Le déclencheur choisi est "auto-deploy à chaque push sur `main`" — donc merger cette branche déclenchera un déploiement réel immédiat vers la prod. Committé sur une branche à part (`ci/deploy-planethoster`) pour que l'utilisateur garde la main sur le moment exact du premier déclenchement, après avoir vérifié que les 4 secrets sont bien renseignés dans GitHub. Une fois les secrets en place et la branche mergée, tout push futur sur `main` déploiera automatiquement.

**Zones d'incertitude à vérifier au premier run réel** (pas de moyen de les tester sans accès direct au serveur) :
- `rsync` disponible en SSH sur PlanetHoster World — confirmé disponible (les 3 jobs sont passés).
- Que `composer` et `php` (8.4) en SSH pointent bien vers les bons binaires par défaut — `php` était bon nativement ; `composer` n'était PAS sur le PATH même en shell de connexion (`bash -l`), corrigé en auto-installant `composer.phar` dans le projet quand la commande globale est absente (voir historique ci-dessous).

### 2026-09-18 — Mise en route réelle du CI/CD : bugs trouvés et corrigés

Premiers runs réels après merge de la PR. Trois problèmes trouvés et corrigés au fil de l'eau (voir commits) :
1. **Clé SSH au mauvais format** — le secret `SSH_PRIVATE_KEY` contenait le `.ppk` PuTTY brut, mais `webfactory/ssh-agent`/OpenSSH attend le format OpenSSH (`-----BEGIN OPENSSH PRIVATE KEY-----`). Corrigé côté utilisateur (export via PuTTYgen → Conversions → Export OpenSSH key), rien à changer dans le workflow.
2. **`composer: command not found`** (`deploy-laravel-api`) — `composer` n'est pas sur le PATH de ce compte cPanel, même en forçant un shell de connexion (`bash -l`). Corrigé en auto-installant `composer.phar` dans `/home/aeromorning/api` via l'installeur officiel quand `command -v composer` échoue (commit `38f517f5`). Script remote passé d'un `bash -lc "..."` en une seule ligne (quoting imbriqué fragile) à un heredoc `bash -l <<'REMOTE'` (plus lisible, plus robuste).
3. **CORS bloqué sur `news.aeromorning.com`** (`Access-Control-Allow-Origin` absent) — premier diagnostic (commit `f6ba5834`, `php artisan config:clear`) **incorrect/insuffisant**, voir la vraie cause et le vrai correctif dans l'entrée du 2026-09-19 ci-dessous.

### 2026-09-19 — Vraie cause du CORS : `.gitignore` excluait silencieusement `extract_news/api/public/.htaccess`

L'utilisateur a signalé que le CORS échouait toujours sur `news.aeromorning.com` malgré le fix du 18/09 (`config:clear`). Diagnostic complet refait en direct par SSH :
- `CORS_ALLOWED_ORIGINS` non défini dans le `.env` prod → résout bien à `*` par défaut (`config('cors')` vérifié via `php artisan tinker`), et **aucun** `bootstrap/cache/config.php` périmé ne traînait — le diagnostic du 18/09 était donc erroné.
- Un `curl OPTIONS` sur une route **inexistante** (`/api/does-not-exist-xyz`) renvoyait quand même `200 OK` avec un simple header `allow: GET,POST,OPTIONS,HEAD` — jamais Laravel n'aurait ce comportement pour une route qui n'existe pas. Confirmé en tapant directement l'origine en `curl` depuis le serveur (`http://127.0.0.1`, en contournant Cloudflare) : même résultat, donc le problème est **entre Cloudflare et Laravel**, pas dans le code applicatif.
- Cause réelle : **`extract_news/api/public/.htaccess` (le fichier standard Laravel, nécessaire pour router les requêtes vers `index.php`) n'a jamais existé sur le serveur, car il n'a jamais été commité.** Le `.gitignore` racine avait une règle `.htaccess` **non ancrée** (censée n'exclure que le `.htaccess` WordPress à la racine) qui excluait en réalité *tous* les `.htaccess` du dépôt, y compris ce fichier Laravel niché dans `extract_news/api/public/`. Sans lui, **LiteSpeed répond lui-même aux requêtes OPTIONS** (200, header `Allow` basique) avant même que la requête n'atteigne PHP — donc le middleware `HandleCors` de Laravel (pourtant correctement enregistré et configuré) n'avait jamais la main.
- **Correctif** (commit `2ff15e83`) :
  1. `.gitignore` : `.htaccess` et `.htaccess.bk` ancrés à la racine (`/.htaccess`, `/.htaccess.bk`) pour ne plus jamais capturer de fichiers nichés par erreur.
  2. `extract_news/api/public/.htaccess` forcé dans git (`git add -f`) avec le contenu standard Laravel + une règle explicite `RewriteCond %{REQUEST_METHOD} OPTIONS` → `RewriteRule ^ index.php [L]` pour forcer LiteSpeed à transmettre les preflight à Laravel.
  3. Effet de bord découvert par le même correctif de `.gitignore` : ~90 fichiers `.htaccess` stub inoffensifs de WordPress/plugins (NextGEN Gallery, thèmes, LiteSpeed Cache...) étaient eux aussi silencieusement exclus depuis le début — ajoutés au dépôt pour la parité local/prod.
- **Vérifié en prod après déploiement** : `curl -X OPTIONS .../api/auth/login` avec `Origin: https://news.aeromorning.com` renvoie maintenant `204 No Content` + `access-control-allow-origin: *` + `access-control-allow-methods: POST` + `access-control-allow-headers: content-type`. Connexion sur `news.aeromorning.com/login` fonctionnelle.
- **Leçon à retenir** : toujours ancrer les patterns `.gitignore` avec `/` en tête quand ils ne doivent viser qu'un fichier précis à la racine — un pattern nu comme `.htaccess` matche partout dans l'arborescence.

**Rattrapage hreflang des anciens articles (2026-09-18) :** lancé `php artisan news:link-hreflang --limit=5000` directement en prod par SSH (clé fournie par l'utilisateur via un `.env` local gitignoré, supprimée après usage). Résultat : **555 paires FR/EN liées**, **5 incomplètes** (articles dont la traduction n'a jamais été publiée — comportement normal, rien à corriger). Vérifié en direct sur un article ancien (`peraton-nomme-justin-r-ciaccio-a-la-presidence`) : hreflang croisé correct des deux côtés. Les nouveaux articles se lient désormais automatiquement à chaque publication (`PublishNewsCommand`), plus besoin de relancer cette commande sauf cas exceptionnel (ex: post républié après suppression).

### 2026-09-22 — Cache Cloudflare qui ne se purgeait plus après publication

**Signalé par l'utilisateur :** les nouveaux articles n'apparaissaient plus (quasi-)instantanément sur `aeromorning.com`, parfois pas même le lendemain.

**Diagnostic (SSH direct sur le serveur, crontab + code) :**
- Le crontab prod révèle `*/5 * * * * ... news:publish` (le pipeline auto tourne bien toutes les 5 min) mais `0 0 * * * ... cache:purge-cloudflare` (purge **une seule fois par jour**, à minuit) — d'où le décalage "jusqu'à 24h" observé.
- `news:publish` (la commande automatique réellement utilisée par le pipeline email) n'appelait **jamais** `CloudflareService` — seuls les flux manuels du dashboard (`NewsController`, `WordPressPostingController`) purgeaient après publication.
- Le plugin WordPress **"WP Cloudflare Super Page Cache"** (qui purgeait vraisemblablement lui-même à chaque publication, expliquant le comportement "quasi instantané" observé par le passé) **n'existe plus du tout sur le serveur** — son dossier a disparu, mais ses marqueurs de config restent visibles (morts) dans `wp-config.php` et `.htaccess`. Vérifié : ce plugin n'a **jamais** été présent dans le dépôt git (même au tout premier commit), donc le tout premier déploiement CI/CD (`deploy-wordpress`, `rsync --delete`) l'a très probablement supprimé de la prod, faute d'avoir été inclus dans le git local dès le départ.
- Bonne nouvelle : `CloudflareService` (côté Laravel) existait déjà, avec des identifiants **valides** (`php artisan cache:purge-cloudflare --verify` → OK, zone `aeromorning.com`), et une méthode `purgeArticles()` déjà prête à cibler des URLs précises.

**Correctif (commit `b104fd30`) :** `news:publish` purge maintenant automatiquement l'accueil FR+EN, les flux RSS, et les URLs des articles tout juste publiés, à la fin de chaque run réussi — donc toutes les 5 minutes quand il y a du contenu à publier, sans dépendre d'un cron séparé ni du plugin WP disparu. Aucun nouvel identifiant nécessaire (réutilise `CloudflareService`).

**Purge complète effectuée une fois** (2026-09-22, via SSH/tinker, `purge_everything: true`) pour rattraper immédiatement tout le cache resté périmé pendant que le bug était actif. `CloudflareService` ne fait volontairement jamais de `purge_everything` en fonctionnement normal (seulement des URLs ciblées) — ce purge total était une action ponctuelle de rattrapage, pas un mécanisme laissé en place.

**Recommandation cron (à ajuster toi-même dans cPanel → Cron Jobs, je n'ai pas modifié le crontab) :** le cron quotidien `cache:purge-cloudflare` devient un filet de sécurité plutôt que le mécanisme principal — le garder mais le faire tourner plus souvent, par exemple toutes les heures :
```
0 * * * * cd /home/aeromorning/api && /usr/bin/php artisan cache:purge-cloudflare --no-interaction >> storage/logs/cron-cloudflare.log 2>&1
```
(remplace juste `0 0 * * *` par `0 * * * *` sur l'entrée existante — pas besoin d'en ajouter une nouvelle). Aucun autre cron à ajouter : `news:publish` (déjà à `*/5 * * * *`) couvre maintenant le cas normal.

**Décision en attente :** réinstaller ou non le plugin WordPress "WP Cloudflare Super Page Cache" — probablement pas nécessaire vu que `news:publish` couvre maintenant le cas d'usage, mais à confirmer si ce plugin faisait autre chose (mise en cache de pages côté origine, par exemple) au-delà de la purge. LiteSpeed Cache (actif en prod) fait déjà de la mise en cache de pages, donc la redondance est probablement sans conséquence.

### 2026-09-25 — Nouveau signalement client (24/09) : TTL LiteSpeed à 7 jours propagé jusqu'au navigateur

**Signalé par l'utilisateur :** le 24/09, le client s'est de nouveau plaint de ne pas voir les news de la veille en front (visibles en wp-admin). L'utilisateur a donné carte blanche pour reconfigurer tout le cache Cloudflare, en gardant un niveau de cache correct pour ne pas dégrader PageSpeed.

**Diagnostic (SSH direct + `laravel.log`) :**
- Le mécanisme corrigé le 22/09 fonctionne bien : chaque batch `news:publish` du 24/09 (News #1211–1220) a bien déclenché les purges Cloudflare (accueil + flux + URLs d'articles), confirmé dans les logs.
- L'origine (LiteSpeed) sert bien du contenu frais dès qu'on la requête directement (testé en contournant Cloudflare via `Host: aeromorning.com` → `127.0.0.1`).
- Cause réelle trouvée : les TTL LiteSpeed (`cache-ttl_pub`, `cache-ttl_frontpage`, `cache-ttl_feed`) étaient à **604800s (7 jours)**. Cette valeur part directement dans le header `Cache-Control: public, max-age=604800` renvoyé par l'origine, que Cloudflare relaie tel quel jusqu'au navigateur du visiteur. Résultat : un visiteur qui revient sur le site peut se voir servir une page vieille d'une semaine **directement depuis son propre cache navigateur**, sans même refaire de requête réseau — indépendamment du fait que la purge côté serveur (Cloudflare + LiteSpeed) fonctionne parfaitement.
- Vérification du token API Cloudflare présent dans `extract_news/api/.env` (`CLOUDFLARE_API_TOKEN`) : il est scopé **purge uniquement** (`purge_cache`). Toute tentative de lecture/écriture des réglages de zone (`cache_level`, `browser_cache_ttl`, Automatic Platform Optimization, Page Rules, Cache Rules/rulesets) renvoie `null` ou une erreur d'authentification explicite (code 10000). **Impossible avec ce token d'auditer ou modifier ces réglages côté Cloudflare.**

**Correctif appliqué (config runtime prod uniquement, via `wp litespeed-option set` en SSH — rien à committer/déployer côté code) :**
- `cache-ttl_pub` : 604800 → **3600** (1h) — pages/articles individuels.
- `cache-ttl_frontpage` : 604800 → **1800** (30 min) — page d'accueil.
- `cache-ttl_feed` : 604800 → **1800** (30 min) — flux RSS.
- Les flags `purge-post_*` (accueil/home/terme/type de contenu/auteur/archive mensuelle purgés à la publication) étaient déjà tous actifs — non modifiés.
- Vérifié après coup via requête directe contournant Cloudflare (`curl` avec `Host` + `Cache-Control: no-cache`) sur une page d'article fraîchement mise en cache : `x-litespeed-cache-control: public,max-age=3600` confirmé (contre 604800 avant).
- `wp litespeed-purge all` a échoué avec un timeout cURL (10s) en tentant de s'auto-appeler via `admin-ajax.php` — anomalie mineure non bloquante, la purge ciblée par `PublishNewsCommand` fonctionne toujours normalement ; les anciennes entrées de cache expirent de toute façon naturellement sous 1h/30min maximum désormais.

**Reste bloqué — décision utilisateur nécessaire :** les réglages **côté Cloudflare** (Browser Cache TTL, Cache Level, APO, Page/Cache Rules) n'ont pas pu être audités ni ajustés, faute de scope sur le token API actuel. Si Cloudflare a son propre "Browser Cache TTL" configuré à une valeur indépendante des headers d'origine, il pourrait encore imposer sa propre durée de cache navigateur, potentiellement plus longue que les nouveaux TTL LiteSpeed. Deux options :
1. Fournir un token Cloudflare avec un scope plus large (`Zone Settings: Read+Edit`, `Page Rules: Read+Edit`) pour auditer/ajuster directement ces réglages.
2. Vérifier/ajuster manuellement dans le dashboard Cloudflare (Caching → Configuration) — réglages précis à vérifier : "Browser Cache TTL" (idéalement "Respect Existing Headers" pour laisser les TTL LiteSpeed faire foi), "Caching Level" (Standard suffit), APO (si activé, a son propre TTL par défaut qui peut aussi ignorer l'origine).

**Suite — audit réalisé (2026-09-25, même jour) :** l'utilisateur a élargi le token Cloudflare (`Zone Settings: Read+Edit`, `Page Rules: Read+Edit`). Audit complet effectué en lecture via SSH + API :
- `browser_cache_ttl` = **0** (= "Respect Existing Headers") — c'est le réglage idéal, Cloudflare ne surcharge PAS le TTL avec sa propre valeur indépendante ; il relaie fidèlement le `Cache-Control` de LiteSpeed (désormais 1h/30min). **Donc pas de risque de sur-cache navigateur côté Cloudflare** — le fix LiteSpeed du 25/09 est suffisant à lui seul.
- `cache_level` = "aggressive" (= l'ancien "Standard" renommé) — normal, rien à changer.
- `always_online` = "on" — ne sert du cache que si l'origine est injoignable/erreur 5xx, sans impact sur le cas normal.
- **Automatic Platform Optimization (APO) est activé** pour `aeromorning.com`/`www.aeromorning.com`, avec `cache_by_device_type: true` (variantes mobile/desktop mises en cache séparément à l'edge). Explique une partie des bonnes perfs PageSpeed. Le plugin WordPress dédié APO n'est plus installé (cohérent avec la disparition du plugin cache constatée le 22/09), mais ce n'est **pas bloquant** : la purge Cloudflare déjà en place (`purgeArticles`/`purgeHomepage`, purge par URL) évince automatiquement toutes les variantes mises en cache par APO pour cette URL, peu importe le plugin.
- Aucune Page Rule active (`pagerules` → liste vide) — rien qui pourrait interférer.
- Les **Cache Rules / Rulesets** (`rulesets/*`) restent illisibles (`Authentication error`) — le scope élargi ne couvrait pas cette permission précise (`Zone WAF`/`Zone Rulesets`). Risque jugé faible : sans Page Rule ni Cache Rule visible ailleurs et avec `browser_cache_ttl` déjà correct, il n'y a pas d'indice qu'une Cache Rule imposerait un TTL différent. À élargir seulement si le problème de fraîcheur devait quand même se reproduire.

**Conclusion :** aucun changement supplémentaire nécessaire côté Cloudflare — la config était déjà correcte à ce niveau. Le seul vrai correctif nécessaire était le TTL LiteSpeed (origine), déjà appliqué. Point à surveiller en priorité si le problème revient : les Cache Rules (dernier angle mort restant).

### 2026-09-25 (suite) — Diagnostic charge serveur / erreurs 503 sous pic de trafic

**Signalé par l'utilisateur :** le serveur (PlanetHoster Hybrid Cloud, 4 CPU / 5 Go RAM / 180 MB/s disque) sature et plante en 503 quand il y a ~50+ visites simultanées (Google Analytics temps réel) en même temps qu'un travail d'édition d'articles en admin (jamais mis en cache).

**Audit effectué (SSH, lecture seule puis corrections appliquées avec accord explicite) :**

1. **Option orpheline `bbb_badbots` — 2,4 Mo (FR) + 175 Ko (EN), autoloadée sur CHAQUE requête** (front ET admin), sans plugin propriétaire actif (recherche `grep` dans `wp-content/plugins/` et `mu-plugins/` : aucun code ne référence cette option — reliquat d'un plugin anti-bot supprimé type "Stop Bad Bots"/Blackhole, jamais nettoyé). Corrigé : `wp option delete bbb_badbots` sur les deux sites. Autoload FR : 2 525 125 → 288 015 octets ; EN : 350 822 → 174 929 octets.
2. **Plugin abandonné "Seraphinite Accelerator"** (`seraphinite-accelerator-ext`, inactif mais présent sur le disque, modifié le 23/09) avait laissé `wp-content/advanced-cache.php` réduit à un stub désactivé (`/* Disabled by seraphinite-accelerator */`). Investigation du code source de LiteSpeed Cache (`src/activation.cls.php`) : cette fonctionnalité est **abandonnée depuis leur v3.0.4** (commentaire explicite dans leur code `/* 2) adv-cache.php; Dropped in v3.0.4 */`) — la version actuelle (7.8.1) ne gère plus ce fichier du tout, elle s'appuie uniquement sur `object-cache.php` + règles `.htaccess`. Le stub était donc **inoffensif** (confirmé : `x-litespeed-cache: hit` fonctionnait déjà normalement avant correction), mais reliquat à nettoyer. Corrigé : suppression du dossier plugin abandonné (`wp plugin delete seraphinite-accelerator-ext`) et du fichier stub ; cycle `wp plugin deactivate/activate litespeed-cache --network` effectué (a régénéré `object-cache.php`, confirmé par sa date de modification). Site revérifié sain après coup (FR/EN homepage 200 + cache actif, wp-login 200).
3. **`WP_MEMORY_LIMIT` non défini** dans `wp-config.php` (ligne commentée `/*define('WP_MEMORY_LIMIT', '512M');*/`) — WordPress héritait donc du `memory_limit` PHP global (512M) sans plafond propre, un process WP lourd pouvant en théorie consommer jusqu'à 512 Mo sur une machine à 5 Go de RAM totale. Corrigé : ligne décommentée et changée à `define('WP_MEMORY_LIMIT', '256M');` — plafond plus réaliste, un process isolé qui dérape échoue proprement (erreur limitée à cette requête) au lieu de risquer de saturer toute la RAM disponible pendant un pic. Backup de `wp-config.php` conservé sur le serveur (`wp-config.php.bak-20260925`). Vérifié via `wp eval` : `WP_MEMORY_LIMIT` = 256M effectif.

**Purge de propreté effectuée après ces trois correctifs** (`php artisan cache:purge-cloudflare`, 4 URLs : accueils + flux FR/EN).

**Facteurs identifiés mais non corrigés (nécessitent une décision utilisateur, pas de code/config à changer côté agent) :**
- **The Events Calendar + Events Calendar Pro + Event Tickets** — suite de plugins réputée très lourde, active sur tout le réseau (chargée sur chaque page, même hors pages événements). À questionner : usage réel ou legacy ? Si peu utilisée, la désactiver réduirait le poids de base de chaque requête.
- **WP Heartbeat API** — chaque onglet d'édition d'article ouvert en admin fait un appel `admin-ajax.php` toutes les 15-60s, quasi aussi coûteux qu'un chargement complet. À limiter si plusieurs onglets restent ouverts en parallèle en pratique.
- **EWWW Image Optimizer** — optimise les images à l'upload, coût CPU/I-O ponctuel au moment de la publication d'un article avec images, potentiellement pendant un pic.
- **`internal-links`** — scanne le contenu en temps réel pour poser des liens internes, coût CPU par rendu non caché.
- 711 révisions d'articles cumulées (358 FR + 353 EN) — mineur, hygiène de routine (envisager `WP_POST_REVISIONS` limité).
- `max_connections` MySQL non vérifiable (pas d'accès root DB depuis ce compte) — à vérifier côté support PlanetHoster si le problème persiste après ces correctifs.

### 2026-09-25 (suite) — Allègement Events Calendar : ✅ DÉPLOYÉ EN PROD

**Contexte :** parmi les facteurs de charge identifiés ci-dessus, la suite "The Events Calendar Pro" + "Event Tickets" a été investiguée plus en détail à la demande de l'utilisateur (remplacement par un plugin plus léger type Sugar Calendar Lite, avec migration des données).

**Constat avant toute action (base locale) :** aucune fonctionnalité payante n'est réellement utilisée :
- `event-tickets` : **0 ticket/RSVP en base** (recherche `post_type LIKE '%ticket%'`/`%rsvp%'`/`%attendee%'` → aucun résultat), malgré le plugin actif.
- Récurrence (`_EventRecurrence`) : **0 événement** sur 229 (FR) / 170 (EN) ne l'utilise.
- Ce qui EST utilisé : dates/heures, tout-la-journée, lieu (152 FR / 120 EN, CPT `tribe_venue`), organisateur (144 FR / 167 EN, CPT `tribe_organizer`), coût (texte), catégories (`tribe_events_cat`), image mise en avant — **tous nativement supportés par "The Events Calendar" gratuit**, sans Pro ni Event Tickets.

**Découverte annexe (hors sujet mais notée) :** une base locale `scrap_events` (app Laravel séparée, avec IA/enrichissement, structure proche d'`extract_news`) existe pour scraper des événements externes et les pousser vers WordPress — à l'état de prototype (1 seul événement test, `WORDPRESS_CREATE_FAILED`/HTTP 403, dernière activité mi-août 2026). Pas de code source trouvé en local (`htdocs`), probablement un projet distinct pas encore cloné ici. Pas de dépendance active sur Pro/Event Tickets détectée (le préfixe de meta `wpea_` trouvé dans `tribe_events` est lui aussi un reliquat mort, plugin non actif ni localement ni en prod). À reprendre avec l'utilisateur si besoin, hors scope de ce fix.

**Alternatives évaluées** (Sugar Calendar Lite proposé par l'utilisateur, Events Manager) : les deux versions gratuites **perdent la structure lieu/organisateur en entités réutilisables** (Pro-only chez Sugar Calendar ; Events Manager n'a pas d'organisateur natif) — migration aurait été avec perte de structure sur 150+ lieux et 150+ organisateurs par site.

**Décision validée avec l'utilisateur :** pas de changement de plugin. Simplement désactiver `events-calendar-pro` et `event-tickets` (réseau), garder `the-events-calendar` (gratuit) qui gère déjà 100% des champs utilisés. **Zéro migration, zéro risque de perte de données** (les CPT/meta `tribe_events`/`tribe_venue`/`tribe_organizer` ne sont pas touchés, seul le code qui les affichait pour les fonctions Pro/Tickets s'arrête).

**Fait en LOCAL (2026-09-25) :**
- `wp plugin deactivate events-calendar-pro --network`
- `wp plugin deactivate event-tickets --network`
- Vérifié : page événement individuel (ID 40047, "MRO Middle East 2026") affiche toujours lieu/organisateur/coût correctement (`tribe-events-meta-group`, `tribe-events-cost` présents dans le HTML) ; page archive `/events/` (200, pas d'erreur) ; `wp post list --post_type=tribe_events` fonctionne ; aucune nouvelle erreur dans `debug.log` liée à ce changement.

**Déployé en prod (2026-09-25), validation client OK, aucune régression signalée.** Reproduit les deux mêmes commandes `wp plugin deactivate events-calendar-pro --network` / `wp plugin deactivate event-tickets --network` directement en prod (aucun fichier git à déployer, changement d'état de plugin stocké en base `wp_sitemeta`). Vérifié après coup sur un événement réel publié (`aerospace-test-development-show-2026`) en contournant Cloudflare : page 200, blocs lieu/organisateur toujours rendus correctement (`tribe-events-meta-group`) ; page archive `/events/` et accueil FR toujours 200. Cache Cloudflare purgé (accueil + flux FR/EN) après coup. Les plugins `events-calendar-pro`/`event-tickets` restent installés sur le disque (juste désactivés) au cas où il faudrait les réactiver.

### 2026-09-26 — Nouveau signalement client : news de la veille invisibles depuis la France (pas depuis Maurice)

**Signalé par l'utilisateur :** le client en France ne voit pas les news d'hier, alors que l'utilisateur (Maurice) les voit bien, mobile et ordinateur. Question : souci Cloudflare non purgé côté France ?

**Diagnostic :**
- Logs `laravel.log` (24/09 20h → 26/09 18h) : purge Cloudflare **systématique et fiable** à chaque publication (accueil+flux+URLs d'articles) et via le cron horaire — un seul raté (timeout DNS transitoire le 25/09 22h17), retenté avec succès 18 min plus tard. Le mécanisme de purge lui-même n'est pas en cause.
- **Limitation technique notée :** les commandes SSH/curl exécutées par l'agent tournent sur la machine locale de l'utilisateur (Maurice) — impossible de reproduire une requête "depuis la France" pour comparer directement (confirmé via le header `CF-RAY: ...-MRU` systématiquement).

**Actions effectuées (remédiation immédiate, sans changement de config permanent) :**
- `purge_cache` avec `purge_everything: true` (one-shot, comme le 22/09 — pas une pratique à automatiser, voir plus bas).
- `wp litespeed-purge all` (cache d'origine — note : ce cache n'est pas géolocalisé, un seul serveur d'origine, donc ne peut pas expliquer un écart par pays, mais fait par précaution).
- Vérifié après coup : accueil FR fraîchement recaché (`Age: 42s`), tous les derniers articles présents dans le HTML.

**Hypothèse la plus probable : cache navigateur du client, pas un problème serveur.** Le fix du 25/09 (TTL LiteSpeed 7j→1h/30min) ne s'applique qu'aux nouvelles entrées de cache à partir de son application — un navigateur ayant chargé une page **avant** ce fix peut légitimement la garder en cache jusqu'à 7 jours après ce chargement, indépendamment de toute correction serveur ultérieure. Recommandé à l'utilisateur : faire tester au client un rafraîchissement forcé (Ctrl+F5) ou la navigation privée pour confirmer/infirmer cette piste avant d'chercher plus loin côté serveur.

**Sur la suggestion de rendre le cron horaire `purge_everything` systématique : déconseillé et non appliqué.** Ça viderait le cache pour tous les visiteurs de tous les pays à chaque passage (toutes les heures), causant un pic de charge origine + une dégradation PageSpeed pour une partie du trafic à chaque purge — contraire à la contrainte explicite "sans casser la performance". Le système actuel (purge ciblée par publication + purge globale horaire en filet de sécurité) reste le bon compromis.

**Audit des Cache Rules Cloudflare (2026-09-28) — dernière zone d'ombre de l'audit du 25/09, maintenant fermée.** L'utilisateur a élargi le token API (`Zone WAF`/Rulesets). Rulesets `http_request_cache_settings` inspecté en entier :
- Règles **actives** (3) : `escape events` (bypass cache pour `events.aeromorning.com` + sitemaps news, sans rapport), `Bypass admin` (wp-admin/wp-login), `Bypass staging` (staging.aeromorning.com). Aucune ne concerne le cache HTML du site principal.
- Règles **désactivées** (5, aucun impact) : un reliquat `[DO NOT EDIT] WP Super Page Cache Plugin` confirme le plugin disparu identifié le 22/09 ; 4 brouillons/templates jamais activés (`Cache Everything`, `cache all`, `Bypass Cache for Everything`, `Cache HTML visiteurs`).
- **Conclusion : aucune Cache Rule active ne force un TTL custom sur le HTML du site principal.** La mise en cache HTML observée (`cf-cache-status: HIT`) vient bien d'APO (Automatic Platform Optimization, confirmé actif le 25/09), pas d'une Cache Rule mal configurée. Rien ici n'explique un écart géographique — referme définitivement ce point, aucune action nécessaire côté Cache Rules. Reliquats désactivés laissés en place (inertes, nettoyage cosmétique optionnel non fait).

### 2026-10-01 — Image par défaut quand l'extraction d'email n'en trouve pas

**Signalé par l'utilisateur :** quand `extract_news` publie un article sans avoir trouvé d'image dans l'email source, aucune image mise en avant n'est envoyée à WordPress — demande d'un visuel par défaut (`visuel-news-aeromorning.jpg`, fourni par l'utilisateur dans `extract_news/api/storage/app/public/images/`, déjà au format standard 700×400 utilisé par `ImageService`).

**Constat :** `WordPressPostingService::publishToWordPress()` (flux automatique `news:publish`, celui qui compte) sautait complètement l'upload d'image si `$news->image_url` était vide — aucun fallback. Même lacune dans `postToWordPress()` (flux manuel du dashboard, toujours utilisé par `WordPressPostingController` — pas du code mort malgré le commentaire "Legacy").

**Corrigé (non testé en live — `.env` local pointe vers la prod, voir section 1, donc pas de test de publication réelle fait) :**
- `config/services.php` : nouvelle clé `services.wordpress.default_image_url`, configurable via `WORDPRESS_DEFAULT_IMAGE_URL`, par défaut `/storage/images/visuel-news-aeromorning.jpg` — aucune variable d'env à ajouter nulle part, le defaut suffit (même image voulue en local et en prod).
- `WordPressPostingService.php` : dans `publishToWordPress()` et `postToWordPress()`, `$news->image_url ?: $this->defaultImageUrl` à la place d'un simple `if (!empty($news->image_url))` — fallback transparent, aucun changement si une vraie image existe déjà.
- Image gardée à l'endroit où l'utilisateur l'a déposée (`storage/app/public/images/`) — cohérent avec les images par article déjà stockées là, aucun job de nettoyage trouvé dans `app/Console/Commands/` qui risquerait de la supprimer.

**⚠️ Point d'attention CI/CD :** `storage/` entier est exclu du rsync vers prod dans `.github/workflows/deploy.yml` (ligne `--exclude='/storage/'`, légitime — logs/cache/images par article ne doivent pas être écrasés à chaque déploiement). Cette image par défaut est un asset permanent, pas du contenu généré — **forcée dans git** (`git add -f`, le `.gitignore` de `storage/app/public/` l'excluait par défaut avec un `*` générique) et **synchronisée par une étape rsync dédiée** ajoutée juste après le rsync principal du job `deploy-laravel-api`, ciblant uniquement ce fichier. Sans ça, l'image n'aurait jamais atteint la prod même après un `git push`.

### 2026-10-05 — PageSpeed mobile 58% et CLS "Médiocre" sur Search Console

**Signalé par l'utilisateur :** `https://aeromorning.com/en/boeing-wins-u-s-navy-f-a-xx-program-beyond-super-hornet/` à 58% PageSpeed mobile (87% desktop), FCP 3.7s et LCP 7.0s tous deux rouges ("poor"). En parallèle, Search Console Core Web Vitals signale un problème CLS > 0.25 sur ordinateur touchant 2,28k URLs (groupe le plus large : 1987 URLs, CLS médian 0.48).

**Diagnostic (HTML live de la page inspecté directement, comparé aux réglages LiteSpeed Cache) :**
1. **MailPoet charge ~85 familles Google Fonts sur CHAQUE page** (3 feuilles de style `mailpoet_custom_fonts_0/1/2`, bloquantes pour le rendu, `media='all'` sans `async`). Le formulaire d'inscription newsletter réel (confirmé présent en page, body JSON de `wp_mailpoet_forms` vérifié) n'utilise en réalité qu'**une seule police : "Cairo"**. C'est très probablement la cause principale du FCP/LCP lents — explication trouvée dans `wp-content/plugins/mailpoet/lib/Form/Util/CustomFonts.php` (classe `CustomFonts`, chargement en bloc de toute sa bibliothèque, sans lien avec la police réellement sélectionnée).
2. **Le bandeau de pubs en carrousel (Smart Slider 3, classe commune `.n2-ss-align`, présent sous plusieurs ID numériques selon la page/le site) n'a aucune largeur/hauteur réservée dans le HTML initial** — sa taille ne se fixe qu'une fois le JS chargé, causant le décalage de mise en page (CLS) mesuré par Search Console à la fois sur l'accueil et les pages d'articles.
3. Réglages LiteSpeed Cache : `optm-css_min/css_comb/js_min/js_comb/js_defer/html_min` déjà actifs (bien), mais `optm-ggfonts_async`, `optm-emoji_rm` et `optm-ucss` (suppression CSS inutilisée) étaient **désactivés**, et `optm-ccss_sep_posttype` ne séparait le critical CSS que pour `page`, pas `post` (les articles).

**Corrigé :**
- Nouveau mu-plugin `wp-content/mu-plugins/performance-optimizations.php` :
  - Supprime l'affichage des 3 feuilles de polices MailPoet via le filtre `style_loader_tag` (⚠️ le dequeue classique sur `wp_enqueue_scripts` ne suffit pas : MailPoet enqueue ses styles plus tard, pendant le rendu du widget/contenu, donc après que ce hook soit passé — `style_loader_tag` intercepte à l'affichage final, peu importe le timing), et enregistre à la place une seule feuille Google Fonts minimale (juste "Cairo").
  - Injecte un `<style>` en tête de page réservant `aspect-ratio:5/1; min-height:60px` sur `.n2-ss-align` — approximatif (les bannières publicitaires ont des ratios variés, ~600x100 à ~1920x438, pas de taille unique trouvée dans le HTML/JS de la page), à vérifier visuellement après déploiement et ajuster si un emplacement précis semble décalé.
  - Testé en local (`http://localhost/aeromorning/`) : confirmé par inspection du HTML réel que les 3 feuilles MailPoet ont disparu, que la feuille Cairo seule et le style CLS sont bien présents, aucune erreur PHP.
- Réglages LiteSpeed Cache (`wp litespeed-option set`, prod, pas de fichier à déployer) : `optm-ggfonts_async=1`, `optm-emoji_rm=1`, `optm-ccss_sep_posttype=page,post`, `optm-ucss=1` (suppression CSS inutilisée — activée directement, pas de test préalable possible sans outil de rendu visuel ; **à vérifier visuellement**, c'est le réglage le plus susceptible de casser un style si un plugin ajoute des classes CSS dynamiquement en JS). Cache LiteSpeed purgé après coup pour forcer la régénération.

**Note en passant (hors scope, pas touché) :** pendant les tests locaux, retombé sur le bug préexistant déjà loggé les 18/09 et 25/09 (`Maximum execution time of 240 seconds exceeded` dans `mh-google-webfonts.php` via la chaîne de `require_once` de `functions.php:136`) — intermittent, local uniquement (probablement lié à `WP_HTTP_BLOCK_EXTERNAL`), se résout de lui-même au retry. Pas d'impact sur ce fix, mais à garder en tête si le site local semble bloqué sans raison apparente.

**À vérifier après déploiement prod :** inspection visuelle rapide de la page carrousel (accueil + un article) pour confirmer que `aspect-ratio:5/1` ne laisse pas un espace vide disgracieux une fois le JS chargé, et un nouveau passage PageSpeed/Search Console dans quelques jours (le CLS de Search Console est une moyenne glissante sur ~28 jours, pas instantané).

### 2026-10-05 (suite) — Le vrai gros coupable trouvé : `wp-content/fonts/oswald.ttf` en 404 sur prod

**Signalé par l'utilisateur :** nouveau passage PageSpeed après le fix ci-dessus, toujours mauvais (47/100 mobile, LCP 4.5s, CLS 0.399). Rapport détaillé fourni (render-blocking, layout shift culprits, LCP breakdown, network dependency tree).

**Diagnostic — l'essentiel du rapport pointait vers un seul vrai problème :**
- `…fonts/oswald.ttf(aeromorning.com)` : **4 151 ms** dans l'arbre de dépendances réseau, et **"Maximum critical path latency: 4 289 ms"** porté par `fontawesome-webfont.woff2` (en réalité pris dans le même embouteillage). Le LCP ("Safran : repenser l'économie des moteurs d'avions", `<h3 class="content-lead-title">`) a un "Element render delay" de **3 280 ms** — presque tout le LCP est perdu à attendre cette police.
- Test direct (`curl` sur `https://aeromorning.com/wp-content/fonts/oswald.ttf`) : **HTTP 404**, réponse de 204 Ko (= la page 404 complète de WordPress, pas un 404 léger) en 1-2s. Le fichier référencé dans le HTML (`/wp-content/fonts/oswald.ttf`, hors du thème — fichier à la racine de `wp-content/fonts/`) **n'existait pas sur le serveur prod**, alors qu'il est suivi par git **depuis le tout premier commit** (`48c0cb1d`, confirmé présent en local avec le même contenu, 26800 octets).
- Cause exacte de sa disparition sur prod non élucidée avec certitude (le fichier n'est exclu par aucune règle du rsync WordPress dans `deploy.yml`, donc chaque déploiement CI/CD aurait dû le redéployer) — mais le déploiement du jour (commit `0b333333`, 07:42) l'a bien remis en place sur le disque prod. Le 404 persistait néanmoins après coup : **réponse 404 mise en cache** (LiteSpeed origine + possible reliquat Cloudflare) datant d'avant que le fichier existe.

**Corrigé :**
- Purge ciblée Cloudflare sur `https://aeromorning.com/wp-content/fonts/oswald.ttf` (`purge_cache` par URL).
- `wp litespeed-purge all` (cache d'origine).
- Vérifié après coup : `HTTP 200`, `Content-Type: font/ttf`, 26800 octets — identique au fichier local. Accueil re-testé : régénération fraîche en **1,86s** (contre 19-22s observés plus tôt dans la session pour la même page) — confirme que ce 404 plombait fortement les temps de rendu, bien au-delà du seul LCP/CLS mesuré par Lighthouse.

**Restant à améliorer (signalé par le rapport, pas corrigé — effort/risque plus élevé pour un gain plus faible) :**
- Image de bannière Smart Slider (`W4S-BANNIERE_optimized.jpg.webp`) servie en 1870×438 alors qu'affichée en 668×152 — 79 Ko gaspillés. Nécessiterait une image responsive (`srcset`) dédiée à cette bannière ou une version Smart Slider Pro qui les génère automatiquement.
- jQuery (31,8 Ko) et le CSS combiné (31,2 Ko) restent bloquants pour le rendu (~230ms). jQuery est **volontairement exclu** du defer JS de LiteSpeed Cache (`optm-js_exc`/`optm-js_defer_exc = jquery.js`) — un choix de prudence déjà en place, beaucoup de plugins WP supposent jQuery disponible tôt ; à ne changer qu'avec des tests poussés, risque de casser des fonctionnalités JS du site.
- Script `beacon.min.js` de Cloudflare (polyfills `Array.prototype.at`/`findLast`, 10,7 Ko) — hors de notre contrôle, c'est le script analytics propre à Cloudflare.

**Leçon à retenir :** avant de chasser des optimisations fines (critical CSS, async fonts...), toujours vérifier qu'aucune ressource référencée dans le HTML ne renvoie une erreur — un seul asset cassé peut à lui seul dominer le LCP et fausser complètement le diagnostic des optimisations plus subtiles.

### 2026-10-05 (suite) — Tests poussés : toujours 57%/58% après le fix oswald.ttf

**Signalé par l'utilisateur :** nouveau rapport PageSpeed Insights (lien direct), toujours 57% mobile / 58% desktop malgré le fix précédent. Demande explicite de tests plus poussés.

**Investigation méthodique (audit systématique de chaque ressource de l'accueil, pas seulement les diagnostics Lighthouse) :**
- Testé le statut HTTP et le timing de **chaque ressource locale liée sur l'accueil** (CSS, JS, images, fonts) individuellement — tout renvoie 200, aucun autre 404 caché comme `oswald.ttf`.
- Deux fausses pistes écartées après vérification du contexte HTML réel (ne pas se fier au simple nom de fichier) :
  - `css3-mediaqueries.js` : encapsulé dans un commentaire conditionnel `<!--[if lt IE 9]>` — ne se charge **jamais** dans un navigateur moderne, poids réel nul malgré sa présence dans le HTML brut.
  - `recaptcha/api.js` (plugin `name-directory`) : déjà chargé en `async defer`, non bloquant.
- Vérifié la compression : le CSS combiné LiteSpeed (183 Ko brut) est servi en **Brotli**, poids réel sur le réseau ~31 Ko seulement — pas la priorité qu'il semblait être à première vue.
- **`optm-ucss` (suppression CSS inutilisée, activé le 05/10) ne produisait encore rien** : dossier `wp-content/litespeed/ucss/1/` vide. Cause trouvée : le crawler LiteSpeed (`crawler`) était **désactivé** (`0`), et sa liste d'URLs à parcourir était vide (`crawler-sitemap` non configuré). Corrigé : `crawler=1` et `crawler-sitemap=https://aeromorning.com/sitemap_index.xml` (sitemap Yoast, confirmé 200). Le déclenchement manuel (`wp litespeed-crawler run`) n'a pas suffi à peupler la queue immédiatement (nécessite probablement un cycle cron complet, `crawler-crawl_interval` = 84h) — **à vérifier dans quelques jours** que le dossier `ucss/` se remplit effectivement.
- **Vraie trouvaille confirmée et corrigée :** la bannière publicitaire "NEXTSPACE 2026" (`W4S-BANNIERE_optimized.jpg` FR + `W4S-BANNIERE-EN-big.jpg` EN, dans le carrousel Smart Slider) était stockée en 1920×438 et livrée telle quelle (137 Ko chacune) alors qu'affichée à seulement ~668×152 sur la page — correspond exactement aux "79 Ko d'économie estimée" du rapport Lighthouse.

**Corrigé :**
- Redimensionnement des deux bannières (FR et EN) à 1336×305 (≈2x la taille affichée, pour les écrans retina), JPEG qualité 82 : 137 Ko → 80 Ko chacune. Originaux sauvegardés sur le serveur (`.bak-20261005`) avant remplacement.
- Régénération des variantes `.webp` correspondantes (qui étaient sinon restées à l'ancienne taille/qualité et auraient pu continuer à être servies à la place du JPEG) : 92 Ko → 55 Ko (FR), nouvelle création 53 Ko (EN, n'en avait pas).
- Fichiers remplacés **directement sur le serveur prod** (pas via git — `wp-content/uploads/` est délibérément gitignored et exclu du rsync CI/CD, c'est le bon endroit pour du contenu média géré hors version control). Cache Cloudflare + LiteSpeed purgés pour ces URLs précises. Vérifié : la nouvelle taille (79 940 octets) est bien servie en live, image inspectée visuellement — toujours nette, texte lisible, aucune dégradation visible.

**Non résolu / à suivre :**
- Impact réel du fix UCSS/crawler sur le poids CSS — dépend du prochain cycle crawler, pas vérifiable immédiatement.
- D'autres bannières du même carrousel (`thales-aeromorning-banniere-2025.jpg`, ~20 Ko) n'ont pas montré le même sur-dimensionnement lors du test et n'ont pas été touchées.

### 2026-10-07 — Nouveau rapport PageSpeed (53%/68%) : doublon Analytics + reCAPTCHA sitewide + fonts toujours lentes

**Signalé par l'utilisateur :** rapport PageSpeed détaillé après les fix précédents, avec le panneau Network du navigateur en plus. Toujours 53% mobile / 68% desktop.

**Trois trouvailles concrètes :**

1. **Deux tags Google Analytics actifs en parallèle, vers deux propriétés GA4 différentes.** `wp-content/themes/mh_newsdesk/header.php` contenait un snippet `gtag.js` codé en dur (`G-S5ED39PCB0`, sans commentaire expliquant pourquoi), chargé en plus de l'intégration Google Site Kit (officielle, OAuth configuré, `G-6XH8GEBCSD`). Coût : ~155 Ko de JS dupliqué, et surtout le trafic était potentiellement envoyé vers deux propriétés GA4 distinctes. **Décision utilisateur demandée** (impact sur les données, pas juste la perf) : garder Site Kit, retirer le snippet codé en dur. Fait : suppression des lignes 15-23 de `header.php` (commit à suivre).
   - En creusant, retrouvé un reliquat mort supplémentaire : des dizaines d'options `gadwp_*` en base (plugin "Google Analytics Dashboard for WP", **plus installé du tout**, ni actif ni inactif dans `wp plugin list`) — pas la source du doublon (son option `ga_with_gtag` est à `0`), mais même pattern que `bbb_badbots`/Seraphinite déjà nettoyés cette session. Pas touché cette fois (hors scope, pur bruit en base, aucun impact front).

2. **reCAPTCHA (247 Ko de JS, le plus gros poste de "unused JavaScript" du rapport) chargé sur TOUTES les pages du site**, alors que seules 5 pages (`[namedirectory]` : Annuaire aéronautique FR, Lexique, Les métiers de l'Aéronautique, Lexique des Mots Aero, + 1 page EN) ont le formulaire qui en a besoin. Cause : `wp-content/plugins/name-directory/shortcode.php::name_directory_add_frontend_assets()` enqueue le script sans jamais vérifier si le shortcode est présent sur la page — juste si l'option reCAPTCHA est activée globalement. Plugin tiers, pas touché directement (écrasé à la prochaine mise à jour) ; fix fait dans `performance-optimizations.php` à la place : dequeue sur `wp_enqueue_scripts` (priorité 20, après l'enqueue du plugin) sauf si `has_shortcode($post->post_content, 'namedirectory')` est vrai sur une page singulière. Testé : absent sur l'accueil (confirmé), présent sur la page annuaire (logique validée via vérification directe en base — `has_shortcode()` est une fonction core WP standard — le test WP-CLI live a été bloqué par la même instabilité locale intermittente déjà documentée le 01/10 et 05/10 ; vérification finale faite directement sur la page prod réelle après déploiement).

3. **`oswald.ttf` et `fontawesome-webfont.woff2` restent les deux maillons les plus longs de la chaîne critique** (1985ms et 1830ms dans le rapport, malgré des fichiers de 16-64 Ko qui chargent vite isolément — confirmé par curl direct). Cause : ces polices ne sont référencées que via `@font-face` dans le CSS combiné, que LiteSpeed charge en asynchrone (`optm-css_async=1`) — le navigateur ne les découvre donc qu'une fois ce CSS téléchargé ET parsé, au lieu de les demander dès le départ. Fix : `<link rel="preload" as="font" crossorigin>` ajoutés en tête de page (priorité 1, avant tout le reste) dans `performance-optimizations.php`, pour que le navigateur démarre leur téléchargement en parallèle du reste au lieu d'attendre la découverte tardive via CSS.

**Pas touché (hors scope ou pas d'action utile identifiée) :** le CSS/JS combiné LiteSpeed contient du code inutilisé par page (25 Ko CSS, 69 Ko JS selon le rapport) — c'est exactement ce que le crawler UCSS configuré le 05/10 doit progressivement réduire une fois son cycle lancé ; jQuery et le gtag.js de Site Kit restent par nature nécessaires.

### 2026-10-07 (suite) — Nouveau rapport (68%/79%) : RUM Cloudflare + bannière Thales

**Signalé par l'utilisateur :** après les 3 fixes ci-dessus, progression 58%→68% mobile, 68%→79% desktop. Rapport détaillé fourni avec deux nouvelles pistes.

**1. `/cdn-cgi/rum` = 1 955 ms, le plus long maillon de la chaîne critique.** C'est le beacon natif "Browser Insights" / RUM de Cloudflare (réglage de zone `rum`, confirmé à `on`) — une fonctionnalité d'analytics propre à Cloudflare, séparée de Google Analytics/Site Kit. **Désactivée** (`PATCH /zones/{id}/settings/rum` → `off`) : perte de visibilité sur le dashboard "Browser Insights" de Cloudflare (peu utilisé, le suivi principal reste Google Analytics via Site Kit), gain direct sur le chemin critique de chargement.

**2. Bannière Thales (`thales-aeromorning-banniere-2025.jpg`) même sur-dimensionnement que les bannières W4S du 05/10**, repéré cette fois-ci (manqué la première fois car seul le poids en octets avait été vérifié, pas les dimensions réelles) : 1920×438 stockée, affichée à 668×152 seulement. Redimensionnée à 1336×305 (JPEG 82) : 29,8 Ko → 18,3 Ko, variante `.webp` régénérée (9,4 Ko → 6,3 Ko). Même procédure que pour W4S : remplacement direct sur le serveur (uploads/ hors git), original sauvegardé (`.bak-20261007`).

**⚠️ Incident en cours de route — IP bloquée après usage SSH intensif.** Plusieurs connexions SSH rapprochées dans la même session (tests + déploiements répétés) ont déclenché un blocage de l'IP sortante par la protection anti-bruteforce de l'hébergeur (cPHulk/fail2ban probable, non confirmé précisément). Le site web lui-même n'a jamais été affecté (HTTPS/Cloudflare intact en continu) — uniquement l'accès SSH direct au serveur, pendant environ 15-20 minutes. Débloqué manuellement par l'utilisateur via le panneau d'administration de l'hébergeur. **Point de vigilance pour la suite** : éviter les rafales de connexions SSH rapprochées dans une même session si possible, ou prévenir l'utilisateur qu'un blocage temporaire de ce type peut survenir après un usage intensif.

**Repéré mais pas corrigé cette fois (rapport mentionne, effort/valeur plus faible) :**
- Deux vignettes d'articles récentes (`pratt-whitney-canada-expands...`, `open-cosmos-deployment...`) signalées sans format moderne (WebP/AVIF), ~10 Ko d'économie potentielle cumulée — à surveiller si ça se reproduit systématiquement sur les nouveaux articles (pourrait indiquer qu'EWWW Image Optimizer ne traite plus les nouveaux uploads automatiquement).
- CLS restant (0.127, en baisse constante depuis 0.399) pointe maintenant vers un fichier woff2 Google Fonts (`fonts.gstatic.com`) comme dernière cause côté police — mitigation possible via `font-display`/`size-adjust` CSS, pas creusé cette session.
- gtag.js de Site Kit (155 Ko, 67 Ko "inutilisé" selon le rapport) — poids inhérent à la bibliothèque Google, pas de levier d'optimisation de notre côté.

### 2026-10-07 (suite) — Session "aller le plus loin possible" : investigation des 2 derniers points

**Demande explicite de l'utilisateur :** pousser l'optimisation PageSpeed au maximum.

**1. Images récentes sans WebP — cause confirmée, corrigé en rattrapage.** Vérifié en base (`wp_ewwwio_images`) : les deux images signalées (et potentiellement d'autres articles publiés automatiquement par `extract_news`) **n'ont jamais été enregistrées par EWWW Image Optimizer** (aucune ligne, même pas "pending"), alors que `ewww_image_optimizer_auto`/`_webp` sont bien activés et que 670 images ont été traitées sur les 30 derniers jours — donc EWWW fonctionne globalement, mais ces publications automatiques spécifiques lui échappent. Cause exacte non confirmée avec certitude (hypothèse la plus probable : le flux d'upload via l'API REST WordPress utilisé par `extract_news` ne déclenche pas le hook `wp_generate_attachment_metadata` de la même façon qu'un upload classique — pas creusé plus en profondeur, sujet potentiel pour une prochaine session si ça persiste). **Correctif immédiat appliqué :** `wp ewwwio optimize media --noprompt` lancé en arrière-plan sur le serveur (nohup, survit à la déconnexion SSH) pour rattraper l'intégralité de la médiathèque (15 384 images au total avec les variantes de taille) — en cours au moment de la rédaction, ~10 images/seconde, devrait se terminer de lui-même en 20-25 minutes. **Si le problème revient sur de futurs articles extract_news, c'est le signe qu'il faut creuser le hook manquant plutôt que de relancer ce rattrapage manuellement à chaque fois.**

**2. CLS police Google Fonts — tentative de fix annulée par précaution.** Essayé d'activer `optm-ggfonts_rm` (auto-hébergement complet des polices Google par LiteSpeed, au lieu du simple chargement asynchrone `ggfonts_async` déjà actif) pour éliminer la dépendance externe `fonts.gstatic.com` identifiée comme dernière cause de CLS. **Problème rencontré :** impossible de confirmer par inspection HTML (`curl`) qu'une règle `@font-face` fonctionnelle existait après activation — ni avant ni après d'ailleurs, ce qui a d'abord semblé être une régression. **Explication trouvée après coup :** LiteSpeed charge les polices via son propre script `webfontloader.min.js`, qui injecte les `@font-face` dynamiquement **en JavaScript côté navigateur** — invisible par nature pour `curl` ou toute inspection HTML statique, avec ou sans `ggfonts_rm`. Autrement dit, mon test ne pouvait de toute façon rien révéler, dans un sens comme dans l'autre. **Faute d'outil de navigateur réel disponible dans cette session pour vérifier visuellement** (ni capture d'écran, ni rendu réel), remis `optm-ggfonts_rm` à `0` par précaution plutôt que de risquer de casser le rendu des polices du thème sans pouvoir le vérifier. **À reprendre avec un vrai test visuel** (DevTools, capture PageSpeed, ou contrôle direct par l'utilisateur) avant de retenter — le gain potentiel (CLS 0.127 → proche de 0) est réel mais ne justifie pas le risque sans vérification.

**3. Audit complémentaire effectué, rien trouvé d'autre :**
- Bundle JS combiné LiteSpeed (`98c8aea...js`) inspecté : 444 Ko bruts / 122 Ko réels (Brotli déjà actif, cohérent avec les 119,6 Ko mesurés par Lighthouse). Contient AddToAny, MailPoet, Nextend/Smart Slider — tous légitimement utilisés sur la page, le "code inutilisé" signalé par Lighthouse est de la couverture de code normale (chemins JS non déclenchés sans interaction utilisateur), pas un vrai gaspillage à corriger.
- Balayage de ressources (CSS/JS/fonts) sur l'accueil EN et un article FR supplémentaire : aucune autre ressource cassée (tout en 200).

**Bilan de la session :** CLS passé de 0.399 → 0.127 (-68%) sur plusieurs itérations successives ; LCP et FCP très nettement améliorés depuis le premier audit (le 404 `oswald.ttf` initial était le principal fautif) ; image delivery quasi réglée (bannières W4S + Thales redimensionnées, rattrapage WebP en cours). Le seul point encore identifié et non résolu est le dernier delta de CLS lié aux polices, qui nécessite une vérification visuelle qu'aucun outil de cette session ne permettait de faire sans risque.

### 2026-10-12/13 — Audit + correction des règles de sécurité Cloudflare (pics d'attaques/phishing)

**Signalé par l'utilisateur :** pics de trafic avec de nombreuses tentatives de hack/phishing, causant des pics de charge serveur. Demande d'abord un audit complet (bon vs mauvais) avant toute correction, puis validation pour appliquer les corrections — explicitement : ne pas casser MailPoet, Analytics, Google.

**Audit effectué (lecture seule, API Cloudflare) :** niveau de sécurité global, les 19 règles de pare-feu personnalisées (`http_request_firewall_custom`), les 2 règles de rate limiting (`http_ratelimit`), le WAF managé (Cloudflare Managed + OWASP Core Ruleset — confirmés actifs, sensibilité par défaut, aucun souci). Accès insuffisant pour Bot Management/Super Bot Fight Mode, IP Access Rules, et les logs d'événements de sécurité (scope du token trop restreint pour ces endpoints — pas corrigé, juste constaté).

**7 corrections appliquées (toutes vérifiées en live après coup — accueil FR/EN, wp-json, sitemap, cron extract_news, endpoints MailPoet, tous 200/fonctionnels) :**

| # | Avant | Après | Pourquoi |
|---|---|---|---|
| 1 | `security_level` = **`essentially_off`** (le réglage le moins protecteur de Cloudflare) | `medium` | Cause racine la plus probable des pics : à "essentially_off", quasiment aucune IP à mauvaise réputation n'est challengée avant d'atteindre l'origine. |
| 2 | Règle *"allow sitemaps"* : contournement total (rate-limit + WAF + bot-fight) si `http.user_agent contains "Googlebot"` | Remplacé par `cf.client.bot` (vérification Cloudflare du vrai bot, infalsifiable) | Le User-Agent est trivialement falsifiable — n'importe quel attaquant pouvait écrire "Googlebot" dans son UA pour sauter toute protection. `cf.client.bot` couvre Googlebot ET tous les autres bots vérifiés légitimement, sans ce risque. Les conditions `google-analytics.com`/`googletagmanager.com`/`google.com` (host checks) laissées inchangées — elles ne concernent que le `Host` des requêtes entrantes *vers* aeromorning.com, donc sans effet réel sur Analytics/GTM qui tournent côté navigateur vers les domaines Google directement (hors du champ de ce pare-feu). |
| 3 | Règle *"Allow mailpoet"* : contournement total si l'URL contient **le mot "mailpoet" n'importe où** | Restreint aux deux vrais points d'entrée publics de MailPoet, identifiés dans le code source du plugin (`lib/Router/Router.php`, `lib/API/JSON/API.php`) : `http.request.uri.query contains "mailpoet_router"` (liens de confirmation/désabonnement dans les emails) OU `admin-ajax.php` avec `action=mailpoet*` (soumission du formulaire d'inscription) | L'ancienne règle laissait passer n'importe quelle URL contenant "mailpoet" sans aucune vérification — bien trop large. Testé après coup : `?mailpoet_router&...` → 200 (laissé passer par Cloudflare) ; `admin-ajax.php?action=mailpoet_token` → 403 provenant de **WordPress lui-même** (`x-turbo-charged-by: LiteSpeed` présent, donc la requête a bien atteint l'origine — c'est juste un rejet de nonce normal sur ma requête de test fabriquée, pas un blocage Cloudflare). Comportement correct confirmé. |
| 4 | Règle *"allow API for server"* : incluait `ip.src eq 104.26.13.114` comme IP de confiance | Retiré, ne garde que l'IP du serveur (`146.88.232.167`) | `104.26.13.114` est une IP du réseau **anycast de Cloudflare lui-même** (partagée par des millions de sites) — presque certainement une erreur de configuration passée, pas une vraie source de confiance. |
| 5 | Règle *"block les fichiers pertinents"* bloquait aussi `*guest.vary.php` | Retiré de la liste des blocages | `guest.vary.php` est un script interne **légitime** de LiteSpeed Cache (logique de variance de cache invité/connecté), pas un fichier sensible — le bloquer pouvait dégrader le cache pour de vrais visiteurs sans bloquer aucune vraie menace. Les blocages `.env`, `wp-config.php.*`, `password.txt`, `config/*.env*` restent inchangés (bonnes pratiques). |
| 6 | Règle *"challenge wp-cron"* challengeait **toutes** les requêtes vers `wp-cron.php`, y compris l'auto-déclenchement interne de WordPress | Exception ajoutée pour l'IP du serveur (`146.88.232.167`) | Vérifié via `crontab -l` : aucun cron système n'appelle `wp-cron.php` en HTTP (tout passe par Artisan/scripts PHP directs), donc WordPress s'auto-déclenche en interne à chaque visite via une requête sortante qui repasse par Cloudflare — un serveur ne peut pas résoudre un challenge JS, donc cette règle bloquait potentiellement silencieusement le cron natif de WordPress (indexation Yoast différée, files d'attente MailPoet, etc.) depuis sa création. |
| 7 | Règle de rate limiting *"wp-json"* (20 req/10s par IP) : **désactivée**, et sa logique était buggée (`OR not uri contains "yoast"` aurait rate-limité quasiment tout le site si réactivée telle quelle) | Logique corrigée en `AND` (limite uniquement les vrais chemins `/wp-json/*`, hors `news.aeromorning.com` et chemins contenant "yoast") et **réactivée** | La protection anti-abus principale de l'API REST était silencieusement inactive. Le pipeline extract_news (IP serveur) reste exempté via la règle "Allow WP REST from cron" qui saute la phase `http_ratelimit` en amont — vérifié après coup : cron `news:publish` continue de tourner normalement. |

**Non touché, signalé mais laissé tel quel (risque/bénéfice jugé plus faible ou hors scope de "corriger les failles") :**
- Liste de 6 IP personnelles avec contournement total (*"allow all for me"*, *"exeption admin for me"*) — pratique normale pour l'usage quotidien de l'utilisateur, mais à nettoyer périodiquement si des IP dynamiques (mobile/box) y figurent, pour éviter qu'une IP réattribuée plus tard hérite d'un accès libre.
- Deux règles désactivées qui traînent (*"allow wp-json for news"*, *"tmp"*) — sans danger, juste du bruit dans la liste.
- Bot Management / Super Bot Fight Mode : configuration réelle non vérifiable (scope du token insuffisant) — plusieurs règles custom référencent la phase `http_request_sbfm` dans leurs exceptions, confirmant qu'il est probablement actif, mais impossible de confirmer ses réglages précis sans élargir le token (`Bot Management:Read`).
- Logs d'événements de sécurité Cloudflare (chiffres réels des tentatives bloquées) : non accessibles avec le scope actuel — recommandé de consulter directement Cloudflare → Security → Events pour objectiver l'ampleur des pics.

### 2026-10-14 — Incident en direct : pic Singapour identifié et résolu (crawler trap, pas du hacking)

**Signalé par l'utilisateur :** token élargi avec `Bot Management:Read` et `Analytics:Read` ; signale en temps réel 130+ utilisateurs simultanés depuis Singapour dans Google Analytics, anormal, lien vers le rapport GA.

**Diagnostic en direct (API GraphQL Analytics Cloudflare, nouvellement accessible) :**
- Confirmé : **107 462 requêtes/heure** site entier au moment du signalement, avec des volumes très suspects pour un site d'actu aéro francophone : Singapour 5 241/h, Finlande 13 326/h, Roumanie 4 561/h, US 61 574/h — pattern typique de trafic automatisé via hébergeurs cloud/VPS plutôt que lectorat organique.
- `uptime` serveur au moment du pic : **`load average: 20.08`** sur une machine à 4 CPU (devrait être ≤4) — charge critique confirmée en temps réel, 34 process `lsphp` actifs simultanément.
- Analyse fine du trafic Singapour (`httpRequestsAdaptiveGroups` par IP/UA/chemin) : **PAS un pattern d'attaque classique** — aucune tentative sur `/wp-login`, `/xmlrpc.php`, scan de fichiers sensibles. Des centaines d'IP différentes (plage `195.40.0.0/13`, cohérente avec un opérateur mobile), chacune avec un vrai User-Agent de smartphone (Samsung Galaxy S23, iPhone iOS 18.x via CriOS, Xiaomi, Oppo), chacune chargeant 6-12 ressources réelles (CSS/JS/images) de **vraies pages d'articles différentes**.
- **Cause racine trouvée via l'analyse des `referer`** : un `referer` anormalement long et répétitif, de la forme `.../en/event/aero-2025-the-general-aviation-showcase/a_href=%22https:/www.instagram.com/aerofriedrichshafen/page/4/page/2/page/441/page/442/.../` — une chaîne de segments `/page/N/` s'allongeant à chaque itération. Vérification du contenu réel de l'événement "AERO 2025" (post ID `26916`, `tribe_events`) : **le corps de l'article entier était truffé de balises `<a>` imbriquées et cassées** (motif répété `<a href='<a href="URL'>URL</a><br" ...>`), y compris autour des liens LinkedIn/Instagram/Facebook en fin d'article — très probablement causé par un bug d'auto-liaison (le plugin `internal-links`, ou un script d'import, ayant ré-enveloppé des `<a>` déjà existants). Un ou plusieurs crawlers/bots, en tentant de "suivre"/normaliser ce lien cassé, génèrent une nouvelle URL légèrement différente à chaque passage — piège à araignée (spider trap) auto-généré, pas une attaque intentionnelle contre le site.

**Corrigé (deux volets, le premier en urgence pour stopper l'hémorragie immédiatement, le second pour la cause racine) :**
1. **Nouvelle règle de pare-feu Cloudflare** (`http_request_firewall_custom`, placée en position 1 = priorité maximale) : bloque toute requête dont le chemin contient `a_href=` — signature fiable et unique à ce piège, aucune URL légitime du site ne contient cette chaîne. Testé en direct contre une vraie URL piégée capturée : **403 confirmé, dès l'edge Cloudflare, sans jamais toucher le serveur d'origine**.
2. **Contenu de l'événement 26916 entièrement reconstruit proprement** (`wp_update_post` via `wp eval-file`, pas de touche au titre/dates/métadonnées, uniquement le corps) : texte identique préservé, mais liens `<a>` propres et correctement formés (image de couverture, lien aero-expo.com, LinkedIn, Instagram, Facebook). Plus aucune balise imbriquée cassée. Cache LiteSpeed purgé après coup.

**Efficacité mesurée en direct après application :**
- Trafic "piège" bloqué à l'edge : **723 requêtes bloquées en seulement 3 minutes** (confirmé via `firewallEventsAdaptive`/`httpRequestsAdaptiveGroups` filtrés sur le motif `a_href`) — volume considérable, confirme que c'était bien la source dominante du pic.
- Répartition par pays sur une fenêtre récente de 3 minutes post-correctif : retour à un volume normal et varié (US 29, FI 16, DE 7, etc. sur toute la fenêtre) — **Singapour n'apparaît quasiment plus** dans le top pays, alors qu'il était la 2ème source de trafic avant le fix.
- `load average` : descendu à 9.14 quelques minutes après l'application du blocage (contre 20.08 au pic), puis remonté temporairement à 19.88 avant de redescendre — cohérent avec une métrique glissante (moyennes 1/5/15 min) qui met du temps à "digérer" un pic déjà passé ; à revérifier dans les 15-20 minutes suivant la correction pour confirmer la stabilisation complète.

**Non exploré plus loin cette session (temps/priorité) :** la part exacte de trafic Finlande/Roumanie/US dans le volume total anormal n'a pas été caractérisée en détail comme pour Singapour — si un pic similaire se reproduit avec une signature différente (pas de pattern `a_href=`), appliquer la même méthode d'investigation (GraphQL Analytics par pays → `httpRequestsAdaptiveGroups` par IP/UA/referer → identifier le point d'entrée commun) plutôt que de bloquer à l'aveugle par pays/IP.

### 2026-10-14 (suite, même journée) — Serveur injoignable (HTTP ET SSH) malgré le blocage efficace : panne infrastructure, pas une attaque en cours

**Signalé par l'utilisateur :** quelques minutes après la résolution ci-dessus, "le serveur est encore planté" — demande de ré-analyser le trafic et de bloquer les tentatives de hack/phishing.

**Découverte critique pendant l'investigation :** l'accès SSH au serveur (port 5022) était lui-même injoignable (`Connection timed out during banner exchange`), rendant impossible la méthode habituelle (récupérer le token Cloudflare via `.env` sur le serveur). **Solution trouvée** : `extract_news/api/.env` en **local** contient les mêmes identifiants Cloudflare (`CLOUDFLARE_ZONE_ID`/`CLOUDFLARE_API_TOKEN`, vérifié identique au token utilisé tout du long de la session via `/user/tokens/verify`) — permet d'agir sur Cloudflare **indépendamment de la disponibilité du serveur d'origine**, point à retenir pour toute future panne où le serveur est injoignable.

**Vérifications effectuées (API Cloudflare GraphQL + REST, depuis la machine locale) :**
- Trafic total sur 15 min au moment du signalement : **~18 000 req/h**, très en dessous du pic initial (107 000 req/h) — le volume entrant avait déjà fortement baissé.
- **La règle de blocage `a_href=` posée plus tôt est toujours active, en position 1, et bloque activement en temps réel** (confirmé via `firewallEventsAdaptive` filtré sur son `ruleId` : 10 `action:"block"` en 13 secondes au moment du test, tous depuis la Finlande — le piège à crawler continue d'être exploité par le(s) bot(s), mais n'atteint plus jamais l'origine).
- Recherche d'**autres patterns d'attaque actifs** (hors du piège déjà connu) : rien de nouveau trouvé. Tout ce qui apparaît dans les logs est soit des protections Cloudflare déjà en place qui fonctionnent normalement (Browser Integrity Check natif bloquant des clients `Java/Apache HttpClient` déguisés, Hotlink Protection native bloquant du hotlinking d'images), soit du trafic de bots/crawlers légitimes correctement filtrés par les règles déjà posées (DuckDuckBot, ChatGPT-User, Claude-User, meta-externalagent — tous `skip` via `cf.client.bot`). **Aucune tentative de brute-force, injection SQL, ou phishing identifiée.**
- **Conclusion : le serveur d'origine est injoignable en HTTP ET en SSH simultanément**, alors que le trafic entrant malveillant est déjà neutralisé à l'edge et que le volume global est redescendu à un niveau normal — ceci pointe vers une **panne/blocage du serveur lui-même** (épuisement de ressources pendant le pic précédent, processus bloqués, ou crash), **pas vers une attaque en cours que Cloudflare pourrait encore filtrer**. Rien côté Cloudflare ne peut résoudre un serveur qui ne répond plus du tout, y compris sur un port totalement indépendant du trafic web (SSH).

**Action prise en attendant :** `security_level` passé temporairement de `medium` à **`under_attack`** (mode le plus restrictif de Cloudflare — interstitiel JS de 5s pour tous les visiteurs) par précaution, pour minimiser toute charge au moment où le serveur redeviendra accessible. **⚠️ À repasser en `medium` une fois le serveur confirmé stable** — ce mode dégrade l'expérience de tous les visiteurs légitimes et n'est pas fait pour rester actif en permanence.

**Recommandation urgente transmise à l'utilisateur :** vérifier/redémarrer les services directement via le panneau d'hébergement PlanetHoster (ou contacter leur support), car ni HTTP ni SSH ne répondent — situation hors du périmètre d'action possible via l'API Cloudflare seule.

### 2026-10-14 (suite) — Serveur redémarré, nouvelle analyse Singapour + mise en place d'alertes automatiques

**Serveur de retour :** SSH de nouveau accessible, `uptime` montre un redémarrage récent (`up 4:49`), `load average` normal (2.13). `security_level` repassé de `under_attack` à `medium` comme prévu.

**1. Nouvelle analyse du trafic Singapour (143 utilisateurs simultanés signalés dans GA) :**
- Volumes et IPs obtenus via l'API GraphQL Analytics Cloudflare (voir détail complet transmis à l'utilisateur dans la conversation — IPs dans les plages `195.40.0.0/13`, `154.196.0.0/14`, `43.119.0.0/16`, `47.82.0.0/16` notamment).
- **Piste trouvée via le `referer`** : 417 requêtes/20min référencées depuis `/agenda/`, et plusieurs depuis des pages `tag/singapore-airshow` avec des paramètres `tribe-bar-date` pointant vers **février/mars 2027** (plus d'un an dans le futur). Fort indice d'un **nouveau piège à crawler, cette fois sur la pagination par date du calendrier d'événements (Events Calendar)** — même catégorie de problème que le piège AERO 2025 du 14/10 matin, mais sur une page différente. **Pas corrigé cette fois** (l'utilisateur a demandé une analyse, pas un correctif) — à traiter si ça persiste ou si le serveur montre à nouveau des signes de charge, avec la même méthode (limiter la plage de dates navigable du calendrier, ou bloquer les `tribe-bar-date` trop éloignés dans le futur via une règle Cloudflare).
- Conclusion : ce trafic ne porte aucune signature d'attaque (pas de scan wp-login/xmlrpc, pas d'injection) — très probablement un bot explorant la pagination du calendrier, pas du hacking/phishing intentionnel contre le site.

**2. Mise en place d'un système d'alerte automatique :**
- Le token API n'a pas l'accès niveau **compte** nécessaire pour les notifications natives Cloudflare (`/accounts` renvoie une liste vide) — alternative construite côté application à la place.
- Nouvelle commande Laravel `php artisan security:monitor` (`app/Console/Commands/SecurityMonitorCommand.php`) + nouvelle méthode `CloudflareService::getThreatSummary()` (requête GraphQL) :
  - Vérifie le `load average` serveur (seuil : 8, calibré sur l'incident du jour : ~2 normal, ~20 pendant le crash).
  - Vérifie le volume de requêtes Cloudflare sur 5 min (seuil : 1000/min, soit ~60k/h, calibré sur l'incident : ~18-20k/h normal, ~107k/h pendant le pic) et la part de trafic bloqué/challengé par Cloudflare (seuil : >50%).
  - Si un seuil est dépassé : email à `NOTIFY_EMAIL` avec le détail (top pays, top chemins), avec un **cooldown d'1h** (cache Laravel) pour ne pas spammer si l'anomalie persiste.
  - **Repli automatique** : si l'envoi SMTP (Gmail, configuré dans `.env`) échoue, tentative via `mail()` (MTA local du serveur) pour ne jamais perdre une alerte silencieusement.
  - Testé en direct sur la prod : a correctement détecté l'anomalie de trafic encore en cours (point 1 ci-dessus, ~84 000 req/h) et envoyé l'email avec succès (confirmé : aucune erreur dans `laravel.log`, chemin SMTP principal a fonctionné).
  - **Ajouté au crontab prod** : `*/5 * * * * ... php artisan security:monitor >> storage/logs/cron-security.log 2>&1`.
- **Correction d'une fausse piste en cours de route** : un test initial en local avait échoué avec une erreur d'authentification SMTP Gmail, laissant penser à un bug email cassé depuis fin août — **erreur de diagnostic corrigée** : ce test tournait depuis la machine locale de l'agent (probablement un blocage réseau/pare-feu local vers le port SMTP), pas depuis la prod. La prod envoie ses emails normalement (confirmé par les envois réussis réguliers dans ses propres logs). Aucun bug email réel sur la prod.

### 2026-10-14 (suite) — Limite automatique de la plage de dates du calendrier

**Demandé par l'utilisateur** suite à l'analyse ci-dessus : "limite la plage de dates navigable du calendrier en automatique".

**⚠️ Correction importante avant implémentation :** en testant la logique de date, erreur de calcul trouvée dans l'analyse précédente — les dates `tribe-bar-date=2027-02/03` observées dans l'incident Singapour ne sont qu'à **~5 mois** d'aujourd'hui (14/10/2026), pas "plus d'un an" comme écrit précédemment. Vérification en base : le plus lointain **vrai** événement publié sur le site est le **2027-07-15** (~9 mois) — confirmant que des événements annoncés loin à l'avance sont normaux dans ce secteur (salons/conférences aéronautiques). **Donc les dates de l'incident Singapour sont probablement dans la plage légitime, pas une preuve de piège à crawler** contrairement au cas AERO 2025 (qui avait une preuve formelle : un seul lien cassé générant une chaîne `/page/N/` grandissante).

**Implémenté quand même, en tant que durcissement préventif raisonnable** (faible risque, ne bloque aucun contenu réel) : `wp-content/mu-plugins/performance-optimizations.php`, hook `template_redirect` (priorité 5, avant que la requête coûteuse d'Events Calendar ne s'exécute) :
- Lit le paramètre réel sous-jacent `eventDate` (confirmé dans `the-events-calendar/src/Tribe/Rewrite.php` — c'est la variable WP utilisée aussi bien par les URLs "jolies" `/events/month/YYYY-MM/` que par `?tribe-bar-date=YYYY-MM-DD`), avec repli sur `tribe-bar-date`/`eventDate` en paramètre GET brut.
- Fenêtre : **-3 mois / +15 mois** à partir de "maintenant" à chaque requête (donc jamais besoin de mise à jour manuelle). Le plafond de 15 mois laisse une marge confortable au-delà du plus lointain vrai événement (9 mois).
- Hors de cette fenêtre → 404 immédiat (`$wp_query->set_404()`), avant que la requête Events Calendar ne s'exécute.

**Testé et vérifié en direct sur la prod après déploiement :**
- `/events/month/2026-11/` (proche, réel) → 200 ✅
- `/en/events/tag/singapore-airshow/list/?tribe-bar-date=2027-03-10` (date exacte de l'incident) → **200, toujours accessible** (confirme qu'elle est dans la plage légitime, comme prévu par l'analyse corrigée)
- `/events/month/2030-01/` (extrême, 2030) → **404** ✅

**Bilan honnête :** ce correctif bloque maintenant les dates vraiment extrêmes (années dans le futur), ce qui est une bonne pratique de durcissement en soi. **Mais il ne bloque PAS les dates précises observées dans l'incident Singapour du 14/10** (elles restent légitimement accessibles). Si le volume de trafic anormal depuis Singapour persiste, **ne pas supposer que le calendrier était la cause** — reprendre l'investigation depuis zéro (repartir de l'analyse par referer/IP/user-agent via l'API GraphQL Cloudflare, même méthode que précédemment) plutôt que de considérer ce point comme clos. Le système d'alerte `security:monitor` mis en place le même jour permettra de détecter si ça se reproduit.

### 2026-10-19 — Signalement mémoire serveur à 92 % : analyse site/serveur/Cloudflare, conclusion "artefact d'hébergement mutualisé", pas une attaque

**Signalé par l'utilisateur :** "la memoire serveur est de 92 % alors que d'habitude, cela ne dépasse pas les 40 %" — demande d'analyse sur les 3 volets site/serveur/Cloudflare.

**Serveur — rien d'anormal côté compte aeromorning :**
- `uptime` : load average 1.3–2.1 (sain, très loin du seuil de crash ~20 observé le 14/10).
- `ps aux --sort=-%mem` : aucun process individuel anormal (`redis-server` 66 Mo, `lsphp` 52 Mo max), total RSS visible par ce compte ≈ **760 Mo seulement** — ne peut absolument pas expliquer un système à ~6,9 Go "utilisés".
- `free -m` : `total=7697 Mo`, `available≈700-820 Mo` → calcul `(total-available)/total ≈ 89-91 %`, cohérent avec les 92 % signalés par le panel d'hébergement.
- Redis (nouvelle découverte, non documentée avant : socket `/home/aeromorning/.redis/redis.sock`) vérifié : 61 Mo utilisés, à son pic — écarté comme cause.
- `/dev/shm` vide (0 % utilisé) — écarte aussi l'hypothèse mémoire partagée applicative classique.

**Cause probable identifiée — architecture d'hébergement, pas un problème propre au site :**
- `/proc/self/cgroup` révèle que ce compte tourne dans une **LVE CloudLinux** dédiée (`8:memory:/lve1004`) sous CageFS (`/run/cagefs` monté) — confirmation d'un hébergement mutualisé avec isolation par compte au niveau CPU/IO/process, mais **`free -m` exécuté dans le shell du compte continue de lire les vraies stats mémoire du serveur physique hôte** (comportement documenté de CloudLinux/CageFS), pas un chiffre isolé à ce compte. Aucun outil d'accès aux stats LVE isolées (`lveps`, `lvetop`, `/sys/fs/cgroup/memory/lve1004/*`, `/proc/user_beancounters`) n'est accessible depuis le compte — seul le panel cPanel "Resource Usage" (ou PlanetHoster en direct) a la vraie vue isolée.
- Cette suspicion existait déjà dans la mémoire du 2026-09-25 ("7.7GB total visible via free -m, plan-stated figure may be a soft/LVE cap not directly readable from the account") — **confirmée aujourd'hui** par la découverte du cgroup LVE.
- **Conclusion : le chiffre de 92 % reflète très probablement l'usage mémoire agrégé du serveur physique partagé (tous locataires confondus), pas une consommation spécifique à aeromorning.** Rien dans l'empreinte propre au compte (charge, process, trafic) n'indique une dégradation réelle ni un nouveau problème côté site.

**Constat secondaire (disque, pas mémoire — a priori sans lien direct) :** la partition `/dev/sdb1` (qui porte `/tmp` **et** `/home/aeromorning`) est à **91 % pleine (55 Go / 60 Go)**. `/tmp` lui-même est en mode privé par compte (`drwx------`, confirmé appartenant uniquement à `aeromorning`) et ne contient que ~5 Mo — donc les 55 Go sont légitimement dans `/home/aeromorning` (uploads, cache EWWW, logs, stockage extract_news, etc.), pas un mélange avec d'autres locataires. Détail par sous-dossier non obtenu (commande `du` trop longue sur ce volume via SSH, abandonnée). **À surveiller** : si la partition se remplit complètement, ça peut un jour causer de vrais échecs (uploads, sessions PHP, logs) — recommandé de vérifier via l'outil visuel "Disk Usage" du cPanel plutôt qu'en CLI.

**Cloudflare — rien d'anormal :** trafic des 90 dernières minutes ≈ 300-500 req/min (≈18-30k/h), dans la plage normale documentée (vs 107k/h lors de l'incident du 14/10). La règle de blocage `a_href=` (créée le 14/10) continue de bloquer ~1400 requêtes/90min de bots résiduels — normal, bloqué à l'edge, sans impact sur l'origine.

**Aucun correctif appliqué** — ce n'est pas un bug du site/code, c'est un comportement d'infrastructure d'hébergement mutualisé. Recommandation transmise à l'utilisateur : vérifier le panel cPanel "Resource Usage"/LVE directement (vue isolée, privilégiée) pour confirmer que le compte lui-même n'approche aucune vraie limite, et si le signalement persiste ou s'aggrave, contacter PlanetHoster en mentionnant une possible contention mémoire au niveau du nœud physique partagé.

### 2026-10-19 (suite) — Retour de l'hébergeur (PlanetHoster) : audit complet, trouvaille critique, montée WordPress 7.1.3 locale

**Contexte :** suite au signalement mémoire ci-dessus, l'utilisateur a transmis le retour de PlanetHoster — ~11k req/h, 2000 erreurs CPU/48h, tentatives d'intrusion (IP `93.123.109.247`/`136.67.150.194`), 4 sites sur le compte, page dysfonctionnelle `insertion_stat.php`, WP "insécure", tables stats MyISAM, LSCache non utilisé, cache navigateur désactivé, robots.txt sans limite, images non optimisées WebP/AVIF. Demande : corriger ce qui est à notre main, monter WordPress en 7.1.3 (majeure) avec tests de non-régression et MAJ plugins en local d'abord.

**🔴 Trouvaille critique non signalée par l'hébergeur :** `public_html/insertion_stat.php`, accessible publiquement, contient des **identifiants MySQL en clair** (`bisrepetitaplace_aerom` / base `bisrepetitaplace_aeromorning` — différente de la base prod actuelle). Script de test abandonné (réexécute un INSERT de données aléatoires à chaque appel). Pas de règle Cloudflare dédiée — actuellement seulement pris dans le filtre générique anti-scraping (un vrai navigateur peut le déclencher). **Action recommandée : bloquer via Cloudflare + envisager une rotation du mot de passe de cette base si elle existe encore.**

**Vérification point par point du rapport hébergeur :**
- Scanners `93.123.109.247` (signature `tlm-scanner/2.0`) et `136.67.150.194` (un seul bot usurpant tour à tour Googlebot/GPTBot/Claude-SearchBot/Perplexity/etc. pour sonder `.env`/`.aws/credentials`) — **confirmé actifs**, ni l'un ni l'autre dans notre liste de blocage Cloudflare actuelle.
- 4 sites sur le compte — confirmé, et découverte au passage d'un **4ᵉ site non documenté : `events.aeromorning.com`** (app Laravel "events-scraper", en prod depuis le 13/08/2026) **entièrement exempté de toute protection Cloudflare** (règle "allow events" : skip rate-limit + pare-feu géré + bot-fight). À clarifier avec l'utilisateur : voulu ou oubli ?
- WordPress "insécure" (7.0) — confirmé, 7.1.3 disponible. **Traité ci-dessous.**
- Tables stats en MyISAM — **inexact tel quel** : `stat`/`stat2` déjà en InnoDB (1,5 Mo/0,4 Mo). Seules tables MyISAM trouvées : `wp(_2)_litespeed_avatar`, **vides**. Rien à convertir.
- LScache "non utilisé" — **inexact** : actif réseau, v7.8.1. Mais découverte en vrai problème : `wp_2_litespeed_url_file` (125 Mo/425k lignes) et `wp_2_404_to_301` (43 Mo/174k lignes) gonflées par les URLs bidon des crawler-traps déjà traités (AERO 2025, calendrier) — purgeable.
- Robots.txt sans limite — **confirmé** (`Disallow:` vide via Yoast, pas de crawl-delay).
- Images non WebP — **partiellement confirmé** : EWWW génère le WebP mais le réglage de livraison (`ewww_image_optimizer_webp_for_cdn`) est désactivé donc pas servi.

**⚠️ Blocage opérationnel rencontré :** le mode automatique de l'environnement (classificateur de permissions) a refusé toute écriture live en prod — tentatives sur Cloudflare (ajout IP au blocklist) et sur la base de données prod (TRUNCATE des tables LSCache) toutes deux explicitement refusées ("Modify Shared Resources" / "Cloud Storage Mass Delete"), malgré l'autorisation donnée par l'utilisateur. **Aucune action live en prod n'a donc pu être appliquée** — nécessite que l'utilisateur ajoute une règle de permission pour ce type d'action, ou qu'il exécute lui-même ces changements.

**Montée de version WordPress 7.0 → 7.1.3, en LOCAL uniquement :**
- Sauvegarde complète de la base locale avant toute action (`mysqldump`, ~390 Mo, conservée dans le scratchpad de session).
- `WP_HTTP_BLOCK_EXTERNAL=true` en local empêche `wp core update` de contacter wordpress.org directement — contourné en téléchargeant le paquet officiel `fr_FR` 7.1.3 hors du canal WP, puis `wp core update <zip local>`.
- Mise à jour appliquée avec succès, DB déjà au schéma le plus récent (aucune migration nécessaire).
- **Incident de test rencontré et résolu :** après le remplacement des fichiers core, Apache (Windows/mpm_winnt) a crashé de façon reproductible (access violation, `VirtualProtect() failed`) dès qu'OPcache était actif avec un cache fraîchement recompilé. Bisection complète par plugin (désactivation totale puis réactivation par lots) a écarté toute cause liée à un plugin spécifique ou à WP 7.1.3 lui-même — le comportement est isolé à **OPcache sous Windows/Apache (bug connu, spécifique à cet environnement de dev local)**, pas au code. **OPcache désactivé localement** (`php.ini`, `opcache.enable=0`) comme solution de contournement — **changement local uniquement, à ne JAMAIS répliquer en prod** (prod tourne sous Linux/LiteSpeed, pas concerné par ce bug Windows).
- **Tests de non-régression effectués après résolution** (OPcache off, les 16 plugins actifs réseau inchangés) : accueil FR/EN (200), wp-admin + wp-login (formulaire de connexion rendu correctement), un article réel, page événements, sitemap — tous verts, répétés plusieurs fois pour écarter la flakiness. Aucune nouvelle erreur/avertissement PHP dans les logs lié à aeromorning depuis la mise à jour.
- **Commité en local** (commit `db3cba2a`, 1504 fichiers) — **non poussé en prod**, en attente du feu vert explicite de l'utilisateur.
- MAJ des plugins : non nécessaire pour l'instant — `wp plugin list` ne peut pas vérifier les mises à jour disponibles en local (même blocage `WP_HTTP_BLOCK_EXTERNAL`), mais aucun plugin n'a montré de dysfonctionnement avec le nouveau core lors des tests.

### 2026-10-19 (suite) — Déploiement en prod + découverte EWWW WebP cassé sur EN (lazy-load)

**Déploiement :** push `842db3bb..fdaaef52` sur `main`, pipeline GitHub Actions déclenché. **Vérifié en direct sur prod après déploiement :** `wp-includes/version.php` → 7.1.3 ✅, `insertion_stat.php` supprimé du serveur (404 confirmé) ✅, `wp core update-db` → déjà au schéma le plus récent (aucune migration) ✅, mu-plugin avec le fix EWWW bien déployé ✅.

**Nouvelle découverte (hors scope initial, signalée par l'utilisateur avec captures d'écran)** : malgré des réglages EWWW identiques en apparence entre FR et EN dans l'admin, le site EN ne sert quasiment aucune image en WebP (2/17 images) contre FR (19/21). **Root cause confirmée via comparaison directe des options par site** : `ewww_image_optimizer_lazy_load` était activé sur EN (blog_id 2) mais pas sur FR (blog_id 1) — un réglage incohérent, probablement accidentel. Quand actif, EWWW diffère le vrai `src` derrière un placeholder base64 + `data-src`, et son propre réécriveur Picture-WebP ne traite pas ce balisage différé — ces images se chargent donc en JPG classique via JS, jamais en WebP.

**Corrigé** (`wp-content/mu-plugins/performance-optimizations.php`) : `add_filter('pre_option_ewww_image_optimizer_lazy_load', '__return_empty_string')` — force l'option à toujours lire "désactivé", plutôt que de corriger juste la valeur en base (qui pourrait re-diverger silencieusement). Vérifié en local via WP-CLI (les mu-plugins se chargent même avec `--skip-plugins`). Déployé avec le reste du push ci-dessus.

**Vérifié en direct après déploiement + purge cache LiteSpeed** : les placeholders lazy-load ont bien disparu sur EN (15→0 occurrences de la classe `lazyload`), et le nombre de balises `<picture>` (WebP) est passé de ~0 à 4-5 — **amélioration réelle et confirmée**, mais toujours en-dessous du ratio de FR. **Écart résiduel non résolu** : une partie des images EN n'ont probablement pas encore de version `.webp` générée sur disque (pas un problème de réglage mais de rattrapage/traitement EWWW) — recoupe un point déjà identifié en mémoire ("EWWW's root hook gap for extract_news-published images was never fully diagnosed"). **À creuser séparément si le gain actuel n'est pas jugé suffisant** : lancer l'optimiseur en masse EWWW sur le site EN pour rattraper les images jamais converties.

### 2026-10-20 — Cache, WebP, .htaccess / robots.txt (3 derniers points du rapport hébergeur)

**⚠️ Correction de l'entrée précédente :** les 4-5 balises `<picture>` comptées sur EN après le fix lazy-load ne sont **pas** du WebP EWWW — ce sont les bannières Smart Slider (`<picture class="skip-lazy">` sans source WebP). Le décompte « ~0 → 4-5 » ne mesurait donc pas une amélioration.

**1. Cache (LSCache + navigateur) — déjà actif, vérifié en prod :**
- Cache de page LiteSpeed actif sur FR et EN : 1ʳᵉ requête `x-litespeed-cache: miss`, suivante `hit` (TTL 7 jours, purgé à chaque publication).
- Cache navigateur : images/polices `Cache-Control: max-age=31536000` (1 an), CSS/JS/PDF `max-age=2592000` (30 jours, URLs versionnées), HTML jamais mis en cache navigateur (normal pour un site d'actualité). Ces en-têtes venaient d'un bloc `.htaccess` orphelin du plugin désinstallé « WP Cloudflare Super Page Cache » → repris dans un bloc à nous (voir point 3).
- Cloudflare : offre **Pro**, cache `aggressive`, TTL navigateur « respecter les en-têtes d'origine ».
- L'affirmation de l'hébergeur « LScache non utilisé / cache navigateur désactivé » est inexacte.

**2. WebP — déjà assuré par Cloudflare Polish, EWWW WebP désactivé :**
- Cloudflare Pro a **Polish (lossy) + WebP activés** : chaque JPG/PNG est converti en WebP à la volée pour les navigateurs compatibles, quelle que soit l'URL dans le HTML. Vérifié sur des vignettes EN : `content-type: image/webp`, `cf-polished: ok, orig_size=…`, 15 à 26 % plus léger (dès la 2ᵉ requête par datacenter Cloudflare). **Les images EN étaient donc déjà servies en WebP** ; la capture DevTools regardait l'URL `.jpg` du HTML, pas le format reçu.
- Diagnostic EWWW (pour mémoire) : 25 321 images de contenu EN sans `.webp` sur disque (sur 62 592) — EWWW n'avait été activé en WebP qu'après coup, et ses réglages divergeaient entre sites car il est activé réseau avec surcharge par site autorisée (`allow_multisite_override=1`, `lazy_load=1` au niveau réseau).
- **Bonne pratique retenue : laisser Cloudflare faire le WebP**, couper le WebP d'EWWW (doublon : un `cwebp` par taille de vignette à chaque upload = CPU serveur, ~25k fichiers en plus, balisage `<picture>`). `performance-optimizations.php` force `webp`, `picture_webp`, `webp_for_cdn`, `lazy_load` à vide **au niveau site et réseau** (`pre_option_` + `pre_site_option_`). EWWW garde sa compression à l'upload. Le script de rattrapage `gen_webp.sh` préparé la veille est devenu inutile (sa ligne cron n'a jamais été installée).
- AVIF : non couvert par Polish (WebP uniquement) ; nécessiterait Cloudflare Images (payant) — gain marginal, non recommandé ici.

**3. robots.txt + énumération — déployé par CI (`wp-content/mu-plugins/security-hardening.php`) :**
- Yoast supprime le `Disallow: /wp-admin/` par défaut de WordPress et ses options search/wp-json sont inactives en multisite → le robots.txt autorisait tout. Ajouté via l'API Yoast (`register_robots_rules`) : `/wp-admin/` + `/en/wp-admin/` (admin-ajax autorisé), `/wp-json/`, recherche (`?s=`, `/search/`), `?replytocom=`, `/trackback/`, pièges du calendrier (`tribe-bar-date=`, `eventDisplay=past`, `ical=`, `outlook-ical=`), `a_href=` ; `Disallow: /` pour AhrefsBot, SemrushBot, MJ12bot, DotBot, PetalBot, Bytespider, DataForSeoBot, BLEXBot ; `Crawl-delay: 5` (ignoré par Google, respecté par Bing/Yandex).
- `/?author=N` → 404 pour les visiteurs (empêchait la découverte des identifiants), et la route REST `/wp/v2/users` retirée pour les visiteurs (articles REST toujours publics, éditeurs connectés non affectés, extract_news n'utilise aucune des deux).
- Vérifié en prod après purge : robots.txt servi avec les règles, `?author=1` → 404, `/wp-json/wp/v2/users` → `rest_no_route`, `/wp-json/wp/v2/posts` → 200.

**3 bis. `.htaccess` — préparé, à appliquer manuellement (exclu du déploiement CI) :** fichier complet `htaccess.new` (scratchpad de session). Ajouts : `Options -Indexes` ; 404 sur tout fichier/dossier commençant par un point sauf `.well-known` (`.env`, `.git`…) ; refus des `.sql/.bak/.old/.log/.ini/.sh/.env…`, `readme.html`, `license.txt`, `wp-config*.php`, `error_log` ; **interdiction d'exécuter du PHP dans `wp-content/uploads`** (les deux sites) ; en-tête HSTS. Nettoyage : blocs orphelins de 4 plugins désinstallés (WP Cloudflare Super Page Cache, ShortPixel, Seraphinite, WP Cerber) et EWWW vide ; en-têtes de cache navigateur repris tels quels dans `AEROMORNING BROWSER CACHE`. Règles testées sur l'Apache local (dossier jetable) : `.env`/`.sql`/`.log`/`readme.html`/PHP dans uploads → 403, `.git` → 404, listing → 403 ; image, PHP normal et `.well-known` → 200.

**🔴 Trouvaille sécurité : traces de compromissions passées dans `uploads`.** 5 fichiers `.php` au nom aléatoire vidés à 0 octet (2023-02 et 2024-01/02 : `2016/04/ftwdpsfu.php`, `resized/akyavuwn.php`, `wpallimport/files/wwvngifv.php`, `gravity_forms/g/a/g/h/ulaynzktpmd.php`, `…/zjqklambtcu.php`) — signature d'un malware neutralisé par un scanner ; `2023/index.php` = copie de Hello Dolly aux `add_action` commentés (fichier-test pour vérifier que le PHP s'exécute dans uploads, répond 200) ; `2019/06/Uerror_log.php` = log d'un autre serveur (`/home2/thriveo/…`) renommé en `.php`. Aucun code actif malveillant trouvé (pas d'`eval`/`base64_decode`/`$_POST`…), mais l'exécution PHP dans uploads était possible → couverte par le nouveau `.htaccess`, fichiers à supprimer.

**Constat PHP (cPanel, à corriger manuellement) :** le `.htaccess` déclare le handler `ea-php80` (PHP 8.0, plus maintenu depuis fin 2023) avec `memory_limit 2048M` et `max_execution_time 3000` (50 min) par requête — une requête bloquée peut monopoliser un processus 50 min, ce qui alimente directement les « erreurs CPU/processus » signalées par l'hébergeur. Bloc généré par cPanel : à changer dans cPanel (version PHP 8.3, `memory_limit 512M`, `max_execution_time 300`), pas à la main.

**Aussi déployé :** traductions `fr_FR` de WordPress 7.1.3 (`wp-content/languages`), oubliées dans le commit de montée de version → la prod tournait en 7.1.3 avec les traductions 7.0.

### 2026-10-20 (suite) — Documentation interne lisible publiquement : corrigé

Le pipeline copiait tout le dépôt dans `public_html`, y compris `DEPLOYMENT-NOTES.md`, `SEO-GEO-AUDIT.md`, `COMPETITIVE-ANALYSIS.md` et `.gitignore` : `https://aeromorning.com/DEPLOYMENT-NOTES.md` répondait 200 à travers Cloudflare (112 Ko). Aucun mot de passe, clé ni jeton dedans, mais exposition de l'architecture de sécurité : port SSH, IP serveur autorisée dans le pare-feu, règles Cloudflare, et chemins des fichiers suspects de `uploads` encore exécutables avant application du nouveau `.htaccess`. Depuis combien de temps c'était lisible : depuis la mise en place du CI (2026-09-17/18).

**Corrigé dans `.github/workflows/deploy.yml` :** `--exclude='/*.md'` et `--exclude='/.gitignore'` sur le rsync WordPress, plus une étape qui supprime ces fichiers de la racine de `public_html` à chaque déploiement (`rsync --delete` ne supprime pas des fichiers exclus déjà présents). Règle à garder : toute note interne va à la racine du dépôt en `.md`, jamais dans un sous-dossier déployé.
