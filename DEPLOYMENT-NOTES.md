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

**Rattrapage hreflang des anciens articles (2026-09-18) :** lancé `php artisan news:link-hreflang --limit=5000` directement en prod par SSH (clé fournie par l'utilisateur via un `.env` local gitignoré, supprimée après usage). Résultat : **555 paires FR/EN liées**, **5 incomplètes** (articles dont la traduction n'a jamais été publiée — comportement normal, rien à corriger). Vérifié en direct sur un article ancien (`peraton-nomme-justin-r-ciaccio-a-la-presidence`) : hreflang croisé correct des deux côtés. Les nouveaux articles se lient désormais automatiquement à chaque publication (`PublishNewsCommand`), plus besoin de relancer cette commande sauf cas exceptionnel (ex: post republié après suppression).
