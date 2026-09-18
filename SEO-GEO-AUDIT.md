# Audit SEO / GEO / Performance — aeromorning.com

Audit réalisé le 2026-09-18 en testant le site de prod en direct (pas seulement en théorie) : pages réelles, robots.txt, sitemaps, schema.org, headers de cache, structure HTML. Organisé par priorité — coche au fur et à mesure.

---

## 0. Question posée : accélérer la prise en compte du hreflang par Google

Il n'existe pas de mécanisme pour "forcer" Google à relire un hreflang instantanément — mais plusieurs leviers accélèrent nettement la découverte :

1. **Search Console → Inspection d'URL → "Demander une indexation"** pour les pages prioritaires (nouveaux articles, pages clés). Google les recrawle en général sous 24-48h. Pas faisable en masse (quota quotidien limité), donc à réserver aux pages qui comptent vraiment.
2. **Resoumettre le sitemap** dans Search Console (Sitemaps → coller à nouveau `sitemap_index.xml` pour FR et EN) après un gros changement comme le rattrapage qu'on vient de faire — ça n'accélère pas le crawl individuel mais confirme à Google que les deux sitemaps sont à jour.
3. **Le hreflang lui-même aide au crawl** : maintenant que chaque article FR linke vers son URL EN (et vice-versa) dans le `<head>`, Googlebot découvre la page sœur dès qu'il crawle l'une des deux — donc le simple fait d'avoir corrigé le hreflang accélère déjà mécaniquement la découverte croisée, sans action supplémentaire.
4. **Vérifier dans Search Console** (rapport "Pages" côté FR et côté EN, si les deux propriétés sont bien enregistrées séparément dans Search Console — sinon c'est la première chose à faire, voir section 1) que les deux sites sont bien indexés et n'ont pas d'erreurs de couverture qui retarderaient le crawl.
5. Il n'y a **pas de rapport "International Targeting"** dédié au hreflang dans la Search Console actuelle (Google l'a retiré) — la seule vérification possible est indirecte : chercher un article dans Google avec `site:aeromorning.com/en "titre"` et voir si la version FR apparaît en lien alternatif dans les résultats enrichis, ou utiliser un outil tiers (Ahrefs, Merkle hreflang tester) pour valider que les tags sont bien lus.

**Aucune action bloquante ici** — la correction déjà en place fait le travail ; le reste n'est que de la patience + les demandes d'indexation manuelles pour les articles prioritaires.

---

## 1. 🔴 Bugs trouvés — à corriger en priorité

### ✅ 1.1 `llm.txt` du site FR affiche le contenu du site EN (corrigé le 2026-09-18)
**Le fichier `/llm.txt` (utilisé par les moteurs IA — ChatGPT, Perplexity, etc. — pour comprendre le site) sert en ce moment le contenu **anglais** même sur le domaine racine FR.**

Cause : `wp-content/plugins/llm-txt/llm-txt.php`, fonction `amweb_generate_all_llm_files()` — elle boucle sur les deux sites du multisite et écrit à chaque fois dans `ABSPATH . 'llm.txt'`. Or `ABSPATH` est le **même dossier physique** pour les deux sites (ce n'est pas parce qu'on `switch_to_blog()` que le chemin disque change) — donc le site traité en second (EN) écrase systématiquement le fichier du premier (FR). Le plugin a *aussi* un mécanisme dynamique correct (`template_redirect` qui génère le contenu à la volée selon le site demandé) — mais comme le fichier statique existe physiquement sur le disque, Apache le sert directement et ne laisse jamais WordPress générer la version dynamique.

**Correctif appliqué** : suppression complète de la génération de fichier statique (cron, hook `save_post`, `file_put_contents`) ; ne reste que le rendu dynamique via `template_redirect`. Ce rendu dynamique avait en fait **son propre bug caché** : WordPress ajoutait automatiquement un `/` final à `/llm.txt` (redirection canonique), ce qui ne correspondait plus à la règle de réécriture `^llm\.txt$` — la route dynamique n'avait donc jamais fonctionné, masquée jusqu'ici par le fichier statique bugué. Corrigé avec un filtre `redirect_canonical` (même technique que pour `ads.txt`/`humans.txt`). Testé en local : FR et EN servent chacun leur propre contenu (`Content-Type: text/plain`, 200). Commits `7551cece`, `401e0807`. **Fichier `llm.txt` physique supprimé du dépôt et ignoré** — le prochain déploiement CI/CD le supprimera aussi du serveur.

### ✅ 1.2 Deux balises `<h1>` par page (corrigé le 2026-09-18)
Chaque page a **2 H1** : le logo du site (`<h1 class="logo-title">`, dans `wp-content/themes/mh_newsdesk/includes/mh-custom-functions.php` ligne 24) **et** le titre de l'article (`<h1 class="entry-title">`). Un seul H1 par page est la bonne pratique SEO — avoir deux dilue le signal sémantique pour les moteurs de recherche.

**Correctif appliqué** : le logo passe en `<div>`/`<p>` partout sauf sur la page d'accueil (`is_front_page()`, même logique déjà utilisée par `mh_newsdesk_page_title()` pour le titre de page). Aucun impact visuel — le CSS cible les classes `.logo-title`/`.logo-tagline`, pas la balise. Vérifié en local : accueil = 1 H1 (`logo-title`), page d'article = 1 H1 (`entry-title`). Commit `401e0807`.

---

## 2. 🟠 Améliorations SEO on-page

- **Meta descriptions** : globalement bonnes et bien rédigées (vérifié sur plusieurs articles), mais au moins un cas trouvé avec un suffixe "News" collé maladroitement en fin de description (`"... à la présidence News"`) — probablement une catégorie concaténée par erreur dans le pipeline `extract_news`. À vérifier/nettoyer côté `OpenAIService`/génération de meta description si le motif se répète sur d'autres articles.
- **Schema.org** : Yoast génère un graphe riche et correct (`NewsArticle`, `BreadcrumbList`, `Person`, `WebSite`...). Mais des types visiblement liés aux événements (`Event`, `Place`, `PostalAddress`, `Offer`) apparaissent aussi sur une page d'article classique — probablement injectés globalement par le plugin Events Calendar Pro. À vérifier que ça ne pollue pas l'éligibilité aux rich snippets d'article (Google peut ignorer un graphe schema trop bruité).
- **Canonical** : présent et correct sur les pages testées. Rien à faire.
- **Alt text des images** : 0 image sans `alt` trouvée sur l'échantillon testé — bon point, rien à faire.
- **hreflang** : corrigé cette session (par article, plus seulement au niveau des accueils) — voir section 0.

## 3. 🟠 GEO (référencement pour moteurs IA / réponses génératives)

- **`llm.txt`** : bug bloquant à corriger (section 1.1) — actuellement la moitié du site (le FR, qui est le site principal) est invisible dans ce fichier.
- **robots.txt** : `Disallow:` vide pour `User-agent: *`, donc tous les crawlers IA (GPTBot, ClaudeBot, PerplexityBot, Google-Extended...) sont déjà autorisés par défaut. Rien à ajouter — juste à surveiller si un jour le robots.txt est resserré, pour ne pas bloquer ces bots par accident.
- **Contenu factuel et citable** : les articles testés sont bien structurés (titre clair, chiffres/dates précis dans le texte) — un format que les moteurs IA aiment citer. Bon point structurel, rien de spécifique à changer.
- **Fraîcheur** : les dates de publication sont bien présentes dans le schema (`NewsArticle`) — bon signal pour les IA qui privilégient le contenu récent sur l'actualité.

## 4. 🟡 Performance / Core Web Vitals

- **Cache** : Cloudflare (`cf-cache-status: HIT`, Age ~1h30 sur la page testée) + LiteSpeed Cache en prod — bonne architecture de cache à deux niveaux, temps de réponse mesuré à 0,52s. Rien à changer ici, c'est du bon travail déjà en place.
- **Images en lazy-loading** : environ la moitié des images de la page testée (22/41) ont `loading="lazy"` — cohérent avec la bonne pratique (ne pas lazy-loader l'image visible immédiatement, la "LCP image"), mais à vérifier que ce n'est pas aléatoire — s'assurer que ce sont bien uniquement les images au-dessus de la ligne de flottaison qui échappent au lazy-loading, pas un oubli.
- **Scripts bloquants** : 2 scripts `<script src="...">` sans `async`/`defer` détectés en tête de page — à identifier lesquels (souvent des scripts de slider/tracking) et voir s'ils peuvent passer en `defer`.
- **Polices web** : aucun `<link rel="preload">` trouvé pour les polices — si le thème charge des polices personnalisées (Google Fonts ou autre), les précharger réduit le risque de décalage visuel (CLS) et améliore le LCP texte.
- **EWWW Image Optimizer** et **LiteSpeed Cache** sont actifs en prod — bon point pour le poids des images et la minification, rien à changer a priori.

## 5. 🟢 Ce qui fonctionne déjà bien (pas d'action)

- Sitemaps FR et EN présents et accessibles (`sitemap_index.xml` sur les deux sites, plus un `sitemap-news.xml` dédié Google News).
- robots.txt correct, référence les deux sitemaps.
- Structured data (schema.org) riche via Yoast.
- Canonical URLs correctes.
- Alt text présent sur toutes les images testées.
- Cache Cloudflare + LiteSpeed opérationnel, temps de réponse rapide.
- hreflang par article maintenant fonctionnel (corrigé cette session, 555 anciennes paires rattrapées + automatique pour les nouveaux articles).

---

## Ce que je n'ai pas pu auditer (hors de portée sans outils/accès supplémentaires)

- **Netlinking / backlinks** (référencement off-page) — nécessite un outil comme Ahrefs, SEMrush ou Google Search Console (rapport "Liens") auquel je n'ai pas accès.
- **Core Web Vitals réels mesurés par Google** (champ CrUX, pas labo) — nécessite Search Console ou PageSpeed Insights avec accès au compte ; j'ai seulement pu mesurer des proxys techniques (TTFB, poids, nombre de ressources bloquantes) via des requêtes directes.
- **Test mobile réel multi-device** — je n'ai testé que les balises techniques (viewport, responsive), pas un rendu visuel réel sur mobile.

Si tu veux, je peux commencer par corriger les deux bugs de la section 1 (llm.txt et double H1) — ce sont des correctifs de code clairs, testables, sans ambiguïté.
