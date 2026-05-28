# Template — `docs/stack.md`

Structure de référence du fichier produit par `/stack`. Adapte les sections au projet : **supprime une couche absente** plutôt que d'écrire « néant » partout, mais garde l'ordre. Chaque techno cite sa source entre parenthèses. Les couches non renseignées (trous assumés) sont marquées `_non renseigné_`.

---

```markdown
# Stack technique — <nom du projet>

> Dernière mise à jour : AAAA-MM-JJ — cartographie factuelle de la stack. Chaque entrée est prouvée par un fichier du dépôt (source entre parenthèses) ou marquée _non renseigné_.

## Vue d'ensemble

<1-2 phrases : type d'appli (monolithe Symfony, SPA + API, etc.), langages principaux, mode d'hébergement.>

| Couche | Techno principale |
|---|---|
| Langage(s) | <ex: PHP 8.2, TypeScript> |
| Backend | <ex: Symfony 6.4> |
| Frontend | <ex: Webpack Encore + Stimulus> |
| Données | <ex: PostgreSQL 15, Redis> |
| Ops | <ex: Docker, Platform.sh> |
| DevOps | <ex: GitHub Actions> |

## Langages & runtimes

- **<langage>** `<contrainte ou version>` — source : `<fichier>`

## Backend

- **Framework** : <nom + version> (`<source>`)
- **ORM / données** : <nom> (`<source>`)
- **Libs structurantes** : <messenger, auth, admin…> (`<source>`)

## Frontend

- **Framework / méta-framework** : <nom + version> (`<source>`)
- **Bundler / build** : <nom> (`<source>`)
- **CSS** : <Tailwind / Sass / …> (`<source>`)
- **State / data fetching / UI kit** : <…> (`<source>`)
- **TypeScript** : oui/non (`tsconfig.json`)

## Données & stockage

- **Base de données** : <SGBD + version> (`<source>`)
- **Cache / sessions** : <Redis…> (`<source>`)
- **Recherche** : <Elasticsearch / Meilisearch…> (`<source>`)
- **File / queue** : <RabbitMQ / Kafka…> (`<source>`)
- **Stockage objet** : <S3 / MinIO…> (`<source>`)

## Ops / Infrastructure

- **Conteneurisation** : <Docker, image de base> (`<source>`)
- **Orchestration** : <Kubernetes / Compose / …> (`<source>`)
- **IaC** : <Terraform / Ansible / …> (`<source>`)
- **Hébergement de production** : <PaaS / cloud / VPS> — _source : déclaratif utilisateur_ ou _non renseigné_
- **CDN / reverse proxy** : <…> ou _non renseigné_
- **Gestion des secrets** : <…> ou _non renseigné_
- **Environnements** : <dev / staging / prod> ou _non renseigné_

## DevOps / CI-CD

- **Pipeline CI** : <GitHub Actions / GitLab CI…> — jobs : <build, test, lint, deploy> (`<source>`)
- **Tests** : <PHPUnit / Jest / Playwright…> (`<source>`)
- **Analyse statique / style** : <PHPStan, ESLint…> (`<source>`)
- **Hooks git / automatisation deps** : <pre-commit, Dependabot…> (`<source>`)
- **Déploiement** : <comment ça part en prod> ou _non renseigné_

## Monitoring / observabilité

- **Erreurs** : <Sentry…> ou _non renseigné_
- **Métriques / traces** : <Datadog, Prometheus…> ou _non renseigné_
- **Logs** : <logging structuré, centralisation> ou _non renseigné_

## Outillage de développement local

- **Commandes QA / build** : <make test, symfony console…> (`<source : Makefile / CLAUDE.md>`)
- **Services de dev** : <docker compose up…> (`<source>`)

## Contraintes & dette de stack connues

- <version épinglée pour raison X, EOL d'une lib, migration à venir…> ou _aucune connue_

## Changelog

- AAAA-MM-JJ — Création — inventaire initial
```

---

## Notes de rédaction

- **Source obligatoire** : toute ligne « détectée » porte son fichier. Une ligne sans source est soit un trou (`_non renseigné_`), soit une erreur.
- **Contrainte vs résolu** : `^6.4` (depuis le manifeste) ≠ `6.4.12` (depuis le lockfile). Sois explicite.
- **Dev vs prod** : ne mélange pas l'outillage de dev (lint, test, compose local) avec le runtime de production.
- **Couches absentes** : un projet purement backend n'a pas de section Frontend — supprime-la, ne la remplis pas de « néant ».
- **Le changelog vit en pied de fichier** : on n'y touche qu'en ajout (une ligne par passage), jamais en réécriture.
