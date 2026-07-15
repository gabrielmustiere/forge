# Stack technique — Forge Board

> Dernière mise à jour : 2026-07-15 — cartographie factuelle de la stack. Chaque entrée est prouvée par un fichier (source entre parenthèses) ou marquée _non renseigné_.

> **Statut : stack installée à la racine du repo.** L'application Forge Board (Symfony 8) vit à la racine et cohabite avec la marketplace forge (`plugins/`) et le site public (`site/`). Les versions ci-dessous sont prouvées par les manifestes présents à la racine ; `composer.lock` est figé (versions résolues disponibles).

## Vue d'ensemble

Monolithe **Symfony 8** server-rendered (Twig + Symfony UX / Live Components, pas de SPA), rendu enrichi côté client par Turbo/Stimulus. App **personnelle, mono-utilisateur** : elle scanne les `docs/story/` de repos forge et les projette en kanban. Tourne **en local** via la Symfony CLI, aucun déploiement en V1. Le code vit **à la racine** du repo, aux côtés de la marketplace forge (`plugins/`).

> **Note pivot (2026-07-08)** — la vision a acté que l'app ne se limite plus à *observer* : elle va aussi **agir** (clone local d'un repo, puis exécution de skills de cadrage). Conséquences côté stack, documentées ci-dessous : un **socle git/clone** (déjà mobilisable sans nouvelle dépendance) et une **décision Symfony AI runtime à trancher** (moteur d'exécution des skills — cf. « Décisions d'architecture à trancher »). Le socle technique de base (langage, framework, BDD, front) est **inchangé**.

| Couche | Techno principale |
|---|---|
| Langage(s) | PHP `>=8.5`, Node (build front) |
| Backend | Symfony `8.0.*` |
| Frontend | Symfony UX (Live Component, Turbo, Stimulus) + AssetMapper |
| Données | SQLite (fichier) |
| Ops | Symfony CLI en local ; Docker uniquement pour Mailpit (dev) |
| DevOps | QA en local (Makefile) ; GitHub Actions limité au déploiement du site statique |

## Langages & runtimes

- **PHP** `>=8.5` — source : `composer.json`
- **Node.js** (chaîne de build front, versions non épinglées) — source : `package.json`

## Backend

- **Framework** : Symfony `8.0.*` — `framework-bundle`, `security-bundle`, `form`, `validator`, `serializer`, `translation`, `mailer`, `notifier`, `http-client` (`composer.json`)
- **ORM / données** : Doctrine ORM `^3.6`, `doctrine-bundle ^3.2`, `doctrine-migrations-bundle ^4.0` (`composer.json`)
- **Libs structurantes** :
  - Messenger via `symfony/doctrine-messenger 8.0.*` (transport Doctrine) (`composer.json`)
  - `symfony/flex ^2.10`, `symfony/runtime 8.0.*` (`composer.json`)

## Frontend

- **Approche** : server-rendered Twig + **Symfony UX** — pas de framework JS SPA
  - `symfony/ux-live-component ^3.0` (composants interactifs live), `ux-turbo ^3.0`, `ux-icons ^3.0`, `ux-toolkit ^3.0`, `stimulus-bundle ^3.0` (`composer.json`)
- **Bundler / build** : **AssetMapper** (`symfony/asset-mapper 8.0.*`) — pas de Webpack Encore (`composer.json`)
- **CSS** : Tailwind CSS `^4.3` (`package.json`) intégré via `symfonycasts/tailwind-bundle ^0.12` + `tales-from-a-dev/twig-tailwind-extra` (`composer.json`)
- **UI kit** : Flowbite `^4.0.2` (`package.json`) via `tales-from-a-dev/flowbite-bundle ^1.0` — drawer, toasts, datepicker (`composer.json`)
- **TypeScript** : non (AssetMapper, JS natif via Stimulus)

> **Direction artistique** : **« Nova · Midnight »** (`DESIGN.md`) — thème sombre dense inspiré de Linear, accent iris (violet). Les tokens sémantiques du kit Flowbite sont remappés sur la palette Nova dans `assets/styles/app.css` (bloc `@theme`), point d'entrée unique du re-thème. Techniquement, Tailwind 4 + Flowbite restent le socle ; Nova est la couche de tokens par-dessus.

## Données & stockage

- **Base de données** : **SQLite** — `DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"` (`.env`, `.env.dev`). Fichier local `var/data.db`, sans infra ; cohérent avec un outil perso mono-utilisateur.
- **Cache / sessions** : _non renseigné_ (défauts Symfony — filesystem — présumés)
- **Recherche / queue / stockage objet** : _sans objet_ (app lecture seule, aucun besoin identifié en V1)

## Socle git / clone (intégration repo)

Briques mobilisées pour rapatrier et mettre à jour un repo en local (pivot « l'app agit », story `008-f-clone-repo-local`). **Aucune nouvelle dépendance nécessaire** — tout est déjà présent :

- **`symfony/process 8.0.*`** (`composer.json`) — exécution de commandes système, notamment shell-out `git clone` / `git pull`.
- **`symfony/doctrine-messenger 8.0.*`** (`composer.json`) — traitement **asynchrone** (transport Doctrine déjà configuré) : un clone long ne bloque pas l'UI.
- **`symfony/http-client 8.0.*`** (`composer.json`) — appels API GitHub/GitLab (déjà utilisé par le connecteur de lecture, story 003).
- **`git`** 2.50.1 — binaire système requis pour le clone/pull — _source : environnement local, pas un fichier du repo ; à considérer comme prérequis d'exécution_.
- **Enum `Provider`** GitHub/GitLab avec `host()` (`src/Enum/Type/Provider.php`) — permet de dériver l'URL de clone selon le provider.

> **Cible du clone** : dossier `private/` à la racine. Gitignoré depuis la story `008` — `/private/*` avec exception `!/private/.gitkeep` (`.gitignore`) : cloner dedans ne pollue pas le repo du Board.

## Ops / Infrastructure

- **Conteneurisation** : Docker limité au **dev** — service **Mailpit** (`axllent/mailpit:latest`, SMTP 1027→1025, UI 8027→8025) pour la capture d'e-mails (`compose.yaml`)
- **Mailer (dev)** : `MAILER_DSN=smtp://localhost:1027` → Mailpit (`.env`)
- **Hébergement de production** : **aucun en V1** — exécution locale via la Symfony CLI (proxy HTTPS local) sur le poste de l'utilisateur — _source : déclaratif utilisateur_
- **CDN / reverse proxy / gestion des secrets** : _sans objet_ (exécution locale)
- **Environnements** : `dev` uniquement en V1 — _non renseigné_ au-delà

## DevOps / CI-CD

- **Pipeline CI** : **aucune CI sur le code applicatif**. Le seul workflow du repo (`.github/workflows/pages.yml`) vérifie et déploie le site statique — il ne lance ni PHPUnit, ni Playwright, ni l'analyse statique. La QA de l'app tourne **en local** via le Makefile (`make ci`, `make quality`). Les jobs PHP historiques (`ci.yml`) ont été retirés le 2026-07-15 : rouges depuis le 2026-07-04 et Actions désactivées, ils ne garantissaient plus rien.
- **Tests** (locaux) :
  - PHPUnit `^13.1` (unitaires & fonctionnels) (`composer.json`)
  - Playwright `^1.60` (E2E navigateur), script `test:e2e` (`package.json`)
- **Analyse statique / style** (locaux) :
  - PHPStan `^2.1` **niveau 10** (`phpstan.dist.neon`) + extensions `phpstan-doctrine`, `phpstan-phpunit`, `phpstan-strict-rules`, `phpstan-symfony` (`composer.json`)
  - PHP-CS-Fixer `^3.95` (`composer.json`)
  - `tomasvotruba/cognitive-complexity ^1.1` (`composer.json`)
- **Déploiement** : _sans objet pour l'app_ (V1 locale, pas de mise en production). Seul le **site statique** est déployé — voir ci-dessous.

## Site public (`site/`)

Troisième brique du repo, à distinguer de l'app et de la marketplace : deux pages HTML statiques publiées sur **forge.mustiere.fr**. Vitrine du plugin + documentation. **Aucun build, aucune dépendance** hors Google Fonts.

- **Hébergement** : GitHub Pages en mode `workflow` (`.github/workflows/pages.yml`) — domaine custom `forge.mustiere.fr` (`site/CNAME`), DNS `CNAME forge → gabrielmustiere.github.io` chez LWS, certificat Let's Encrypt automatique.
- **Contenu** : `site/index.html` (vitrine), `site/docs/index.html` (documentation), `assets/forge.css` + `assets/forge.js` partagés, `llms.txt`, `robots.txt`, `sitemap.xml`, `assets/og.png`.
- **Charte** : fond `#0a0e12`, accent cyan `#3fd6f2`, Space Grotesk + JetBrains Mono (`site/assets/forge.css`) — **distincte** de la DA du Board (Nova · Midnight, violet iris).
- **Garde-fou de version** : `tools/check-site-version.py` compare la version affichée par le site à `plugins/forge/.claude-plugin/plugin.json` (source de vérité) et **fait échouer le déploiement** en cas de dérive. Le workflow se déclenche aussi sur `plugin.json` pour attraper une release qui n'aurait pas mis le site à jour.
- **Images** : `site/assets/og.png` et `.github/banner.png` sont générés depuis `tools/og-source.html` et `tools/banner-source.html` (rendus navigateur, procédure en commentaire).

## Monitoring / observabilité

- **Erreurs / métriques / traces** : _sans objet en V1_ (outil perso local)
- **Logs** : Monolog (`symfony/monolog-bundle`) — logging Symfony par défaut (`composer.json`)

## Outillage de développement local

- **Automatisation** : Makefile (`make init`, `serve`, `db-reset`, `phpunit`, `playwright`, `quality`, `ci` — `make help` pour la liste) (`Makefile`)
- **Maker / debug** : `symfony/maker-bundle ^1.67`, `web-profiler-bundle`, `debug-bundle`, `stopwatch` (`composer.json`)
- **Assistance IA (dev)** : `symfony/ai-mate ^0.9` + extensions monolog/symfony (serveurs MCP d'assistance IA) (`composer.json`). ⚠️ **Outil de DEV uniquement** : c'est un serveur MCP qui assiste le développeur, **pas** un moteur d'exécution runtime dans l'app. Le pivot (exécuter un skill de cadrage *depuis l'app*) requiert un autre socle, **non installé** à ce jour — cf. « Décisions d'architecture à trancher » ci-dessous.
- **Services de dev** : `docker compose up` → Mailpit (`compose.yaml`)

## Décisions d'architecture à trancher (hors stack — pour `/tech-plan` ou `/adr`)

Ces points ne sont pas encore matérialisés par un fichier ; ils sont notés ici comme dette de cadrage, pas comme stack détectée :

- **Moteur d'exécution des skills de cadrage (Symfony AI runtime)** — **non tranché → `/adr` requis**. Le pivot veut que l'app exécute un skill de cadrage headless sur le repo cloné. Deux directions ouvertes, **rien n'est installé** :
  - _CLI Claude Code headless_ : shell-out du binaire/SDK Claude Code dans le clone, les `SKILL.md` forge exécutés tels quels. Pilier = binaire/SDK Claude (hors `composer.json`), `symfony/process` déjà là.
  - _Agent Symfony AI custom_ : reconstruire la logique via `symfony/ai-platform` + `symfony/ai-agent` + `symfony/ai-bundle` (**à installer**) + un provider LLM. On n'utilise pas les `SKILL.md` tels quels.

  ⚠️ Ne pas confondre avec `symfony/ai-mate` (déjà présent, mais outil de **dev** MCP, pas un runtime). Le choix conditionne les dépendances à ajouter — à cadrer en `/adr` puis `/feature-plan`.
- **Écriture / push vers le repo distant** : le parcours de production (vision) prévoit un commit + push automatique du `docs/story/` produit. Stratégie (branche dédiée, validation avant push, garde-fous anti-pollution) **non tranchée** — candidat `/adr` (risque externe vision : push de contenu généré non relu).
- **Persistance de l'état de clone** : où stocker statut/chemin local/horodatage/erreur (champs sur `Project` vs entité dédiée) — tranché en `/feature-plan` de la story `008`.
- **Connecteur de lecture repo distant** : API GitHub/GitLab vs serveur MCP, pour récupérer l'arborescence et le contenu de `docs/story/` (cf. `vision.md`, hypothèse #3). À clarifier vis-à-vis du clone local : lecture API (projection kanban) et clone local (production) **cohabitent**, finalités distinctes.
- **Persistance de l'état scanné** : SQLite retenu, mais l'état étant _déduit des fichiers_ (principe produit #2), reste à trancher ce qui est réellement persisté vs recalculé à la volée.
- **Cohabitation dans le repo** : **tranché** — le projet Symfony est instancié à la **racine**, aux côtés de `plugins/`. `.gitignore` et `CLAUDE.md` fusionnés pour couvrir les deux sujets ; le doublon doc `documentation/` résorbé (inventaire → `plugins/forge/SKILLS.md`, banner → `.github/banner.png`).

## Contraintes & dette de stack connues

- **Stack en pré-release / très récente** : Symfony 8.0, PHP 8.5, Tailwind 4, PHPUnit 13, Flowbite 4, `symfony/ai-*` en `^0.9` (API instable, `<1.0`). Socle moderne mais jeune — surveiller les breaking changes au moment de figer le `composer.lock`.
- **Suivi des versions** : `docs/stack.md` reflète l'installé (racine) ; versions résolues dans `composer.lock`. Repasser en mode Éditer à chaque montée de version structurante.

## Changelog

- 2026-07-04 — Création — inventaire initial (socle Symfony 8)
- 2026-07-04 — Éditer — application Symfony installée à la racine ; cohabitation tranchée (racine, aux côtés de `plugins/`) ; SQLite confirmé via `.env` ; sources relocalisées (chemins locaux)
- 2026-07-08 — Enrichir — socle git/clone documenté (`process`, `doctrine-messenger` async, `http-client`, binaire `git`, enum `Provider` — sans nouvelle dépendance) ; distinction `ai-mate` (dev MCP) vs Symfony AI runtime clarifiée ; décisions à trancher ajoutées (moteur d'exécution des skills → ADR, push distant → ADR, persistance état de clone → feature-plan 008). Suite au pivot vision du 2026-07-08.
- 2026-07-15 — Éditer — réalignement sur le code livré. **Site public** ajouté comme troisième brique (`site/`, GitHub Pages, forge.mustiere.fr, garde-fou de version). **CI corrigée** : les jobs PHP (`ci.yml`) ont été retirés, la QA tourne en local, GitHub Actions ne fait plus que déployer le site. **DA corrigée** : « Nova · Midnight » (`DESIGN.md`) et non « Paper » — la note qui renvoyait la DA moderne à un chantier à venir est caduque. **PHPStan** : niveau 10 attesté par `phpstan.dist.neon` (le document disait « niveau élevé », le `CLAUDE.md` disait 9). **Dette `private/` résolue** : gitignoré depuis la story 008.
