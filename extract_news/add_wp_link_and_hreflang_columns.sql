-- Migration : ajout de wp_link et hreflang_linked sur t_news
-- Correspond à la migration Laravel 2026_09_17_000001_add_wp_link_and_hreflang_to_t_news_table
-- A lancer sur la prod AVANT de déployer le code (BDD extract_news, pas la BDD WordPress).
-- Idempotent : les colonnes ne sont ajoutées que si elles n'existent pas.

ALTER TABLE `t_news`
  ADD COLUMN IF NOT EXISTS `wp_link`         VARCHAR(255) NULL     DEFAULT NULL AFTER `wp_post_id`,
  ADD COLUMN IF NOT EXISTS `hreflang_linked` TINYINT(1)   NOT NULL DEFAULT 0    AFTER `wp_link`;

-- Si ce script est exécuté directement en SQL (au lieu de `php artisan migrate --force`),
-- il faut aussi déclarer la migration comme jouée pour que `php artisan migrate` ne
-- tente pas de la rejouer plus tard. Adapter {batch} au numéro de batch suivant
-- (SELECT MAX(batch) FROM migrations;) si la ligne n'existe pas déjà :
--
-- INSERT INTO `migrations` (`migration`, `batch`)
-- SELECT '2026_09_17_000001_add_wp_link_and_hreflang_to_t_news_table', (SELECT MAX(batch) + 1 FROM migrations)
-- WHERE NOT EXISTS (
--   SELECT 1 FROM migrations WHERE migration = '2026_09_17_000001_add_wp_link_and_hreflang_to_t_news_table'
-- );
