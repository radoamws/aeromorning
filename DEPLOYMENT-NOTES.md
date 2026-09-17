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

**Pourquoi séparé :** `wp-config.php` et `.htaccess` sont dans `.gitignore` — chaque environnement a sa propre copie, donc rien de tout ça ne peut fuiter vers un commit. Le tableau ci-dessus sert de rappel humain, pas de garde-fou technique.

## 2. Corrections de code à déployer en prod

Ces changements sont des vrais bugs (indépendants de l'environnement), commités dans le dépôt git local. **À reporter manuellement sur le serveur de prod** (ce dépôt git n'est pas encore relié à la prod).

- **2026-09-17** — `wp-content/themes/mh_newsdesk/header.php` et `footer.php` : les liens hreflang, le lien/l'image des drapeaux FR/EN, et le lien "Mentions légales" étaient codés en dur vers `https://aeromorning.com` / `/en` au lieu d'utiliser `get_home_url()`. Corrigé pour utiliser `get_home_url(1, ...)` / `get_home_url(2, ...)`. Sans impact visuel en prod (même URL produite), mais rend le thème correct sur n'importe quel environnement. Commits `4b845b9c`, `5eeeee77`.

## 3. Scripts SQL à exécuter en prod

Aucun à ce jour. (La migration d'URL `aeromorning.com` → `localhost/aeromorning` faite en base locale via WP-CLI `search-replace` le 2026-09-17 est **strictement locale** — ne jamais la rejouer sur la base de prod.)

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
