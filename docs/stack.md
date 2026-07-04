# Stack technique — Forge Board

> Dernière mise à jour : 2026-07-04 — cartographie factuelle de la stack. Chaque entrée est prouvée par un fichier (source entre parenthèses) ou marquée _non renseigné_.

> **Statut : stack installée à la racine du repo.** L'application Forge Board (Symfony 8) vit à la racine et cohabite avec la marketplace forge (`plugins/`). Les versions ci-dessous sont prouvées par les manifestes présents à la racine ; `composer.lock` est figé (versions résolues disponibles).

## Vue d'ensemble

Monolithe **Symfony 8** server-rendered (Twig + Symfony UX / Live Components, pas de SPA), rendu enrichi côté client par Turbo/Stimulus. App **personnelle, mono-utilisateur, lecture seule** : elle scanne les `docs/story/` de repos forge et les projette en kanban. Tourne **en local** via la Symfony CLI, aucun déploiement en V1. Le code vit **à la racine** du repo, aux côtés de la marketplace forge (`plugins/`).

| Couche | Techno principale |
|---|---|
| Langage(s) | PHP `>=8.5`, Node (build front) |
| Backend | Symfony `8.0.*` |
| Frontend | Symfony UX (Live Component, Turbo, Stimulus) + AssetMapper |
| Données | SQLite (fichier) |
| Ops | Symfony CLI en local ; Docker uniquement pour Mailpit (dev) |
| DevOps | GitHub Actions (PHPUnit, Playwright, PHPStan, PHP-CS-Fixer) |

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
- **UI kit** : Flowbite `^4.0.2` (`package.json`) via `tales-from-a-dev/flowbite-bundle ^1.0` — design system « Paper » (drawer, toasts, datepicker) (`composer.json`)
- **TypeScript** : non (AssetMapper, JS natif via Stimulus)

> **Direction artistique** : l'application embarque le design system « Paper » (Flowbite). L'intention produit d'une **DA moderne** propre au Forge Board est un choix de _design_, hors périmètre de ce document — à cadrer côté produit/feature, pas côté stack. Techniquement, Tailwind 4 + Flowbite restent le socle.

## Données & stockage

- **Base de données** : **SQLite** — `DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"` (`.env`, `.env.dev`). Fichier local `var/data.db`, sans infra ; cohérent avec un outil perso mono-utilisateur.
- **Cache / sessions** : _non renseigné_ (défauts Symfony — filesystem — présumés)
- **Recherche / queue / stockage objet** : _sans objet_ (app lecture seule, aucun besoin identifié en V1)

## Ops / Infrastructure

- **Conteneurisation** : Docker limité au **dev** — service **Mailpit** (`axllent/mailpit:latest`, SMTP 1027→1025, UI 8027→8025) pour la capture d'e-mails (`compose.yaml`)
- **Mailer (dev)** : `MAILER_DSN=smtp://localhost:1027` → Mailpit (`.env`)
- **Hébergement de production** : **aucun en V1** — exécution locale via la Symfony CLI (proxy HTTPS local) sur le poste de l'utilisateur — _source : déclaratif utilisateur_
- **CDN / reverse proxy / gestion des secrets** : _sans objet_ (exécution locale)
- **Environnements** : `dev` uniquement en V1 — _non renseigné_ au-delà

## DevOps / CI-CD

- **Pipeline CI** : GitHub Actions — jobs lint + tests unitaires/fonctionnels + E2E, sur push et PR vers `main` (`.github/workflows/ci.yml`)
- **Tests** :
  - PHPUnit `^13.1` (unitaires & fonctionnels) (`composer.json`)
  - Playwright `^1.60` (E2E navigateur), script `test:e2e` (`package.json`)
- **Analyse statique / style** :
  - PHPStan `^2.1` niveau élevé + extensions `phpstan-doctrine`, `phpstan-phpunit`, `phpstan-strict-rules`, `phpstan-symfony` (`composer.json`)
  - PHP-CS-Fixer `^3.95` (`composer.json`)
  - `tomasvotruba/cognitive-complexity ^1.1` (`composer.json`)
- **Déploiement** : _sans objet_ (V1 locale, pas de mise en production)

## Monitoring / observabilité

- **Erreurs / métriques / traces** : _sans objet en V1_ (outil perso local)
- **Logs** : Monolog (`symfony/monolog-bundle`) — logging Symfony par défaut (`composer.json`)

## Outillage de développement local

- **Automatisation** : Makefile (`make init`, `serve`, `db-reset`, `phpunit`, `playwright`, `quality`, `ci` — `make help` pour la liste) (`Makefile`)
- **Maker / debug** : `symfony/maker-bundle ^1.67`, `web-profiler-bundle`, `debug-bundle`, `stopwatch` (`composer.json`)
- **Assistance IA (dev)** : `symfony/ai-mate ^0.9` + extensions monolog/symfony (serveurs MCP d'assistance IA) (`composer.json`)
- **Services de dev** : `docker compose up` → Mailpit (`compose.yaml`)

## Décisions d'architecture à trancher (hors stack — pour `/tech-plan` ou `/adr`)

Ces points ne sont pas encore matérialisés par un fichier ; ils sont notés ici comme dette de cadrage, pas comme stack détectée :

- **Connecteur de lecture repo distant** : API GitHub/GitLab vs serveur MCP, pour récupérer l'arborescence et le contenu de `docs/story/` (cf. `vision.md`, hypothèse #3).
- **Persistance de l'état scanné** : SQLite retenu, mais l'état étant _déduit des fichiers_ (principe produit #2), reste à trancher ce qui est réellement persisté vs recalculé à la volée.
- **Cohabitation dans le repo** : **tranché** — le projet Symfony est instancié à la **racine**, aux côtés de `plugins/`. `.gitignore` et `CLAUDE.md` fusionnés pour couvrir les deux sujets ; le doublon doc `documentation/` résorbé (inventaire → `plugins/forge/SKILLS.md`, banner → `.github/banner.png`).

## Contraintes & dette de stack connues

- **Stack en pré-release / très récente** : Symfony 8.0, PHP 8.5, Tailwind 4, PHPUnit 13, Flowbite 4, `symfony/ai-*` en `^0.9` (API instable, `<1.0`). Socle moderne mais jeune — surveiller les breaking changes au moment de figer le `composer.lock`.
- **Suivi des versions** : `docs/stack.md` reflète l'installé (racine) ; versions résolues dans `composer.lock`. Repasser en mode Éditer à chaque montée de version structurante.

## Changelog

- 2026-07-04 — Création — inventaire initial (socle Symfony 8)
- 2026-07-04 — Éditer — application Symfony installée à la racine ; cohabitation tranchée (racine, aux côtés de `plugins/`) ; SQLite confirmé via `.env` ; sources relocalisées (chemins locaux)
