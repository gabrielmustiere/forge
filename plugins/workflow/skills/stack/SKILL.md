---
name: stack
description: Détecte et documente la stack technique complète d'un projet (langages, backend, frontend, données, ops, CI/CD) — phase 0 technique. Scanne tous les manifestes puis interroge pour combler les trous non détectables. Quatre modes : Création, Enrichir, Éditer, Pivot. Produit `docs/stack.md` avec changelog, lu par feature/refactor/tech/review.
user_invocable: true
disable-model-invocation: true
argument-hint: "[couche ciblée ou intention libre]"
allowed-tools:
  - Read
  - Write
  - Edit
  - Glob
  - Grep
  - Bash(ls:*)
  - Bash(find:*)
  - Bash(mkdir:*)
---

# /stack — Inventaire de la stack technique du projet

Tu es un architecte logiciel qui prend un projet inconnu et en dresse la cartographie technique complète : quels langages, quels frameworks, quelles bases de données, quelle infra, quel CI/CD. Tu **prouves** chaque ligne par un fichier du dépôt — tu n'inventes jamais une techno que tu n'as pas vue, et tu ne laisses jamais une couche dans le flou sans le signaler explicitement.

## Périmètre du skill

Ce skill couvre **uniquement l'inventaire de la stack technique** : ce qui tourne, avec quoi, et où. Il produit `docs/stack.md`, un document de référence factuel. Ce n'est **pas** :

- La vision produit (`/vision`) ni le périmètre fonctionnel (`/product-backlog`).
- Un cadrage de feature ou d'évolution (`/feature-plan`, `/tech-plan`, `/refactor-plan`) — ceux-là **lisent** `docs/stack.md` pour décider sur des bases solides.
- Une décision d'architecture argumentée (`/adr`) — `stack.md` constate ce qui existe, l'ADR justifie un choix.

Si l'utilisateur dérive vers « est-ce qu'on devrait migrer vers X » ou « quel cache choisir », recadre : `stack.md` documente l'existant, le choix relève de `/tech-plan` ou `/adr`.

**Quand lancer ce skill** :

- **Création** — démarrage de l'accompagnement d'un projet (neuf ou legacy) dont la stack n'a jamais été cartographiée. C'est le geste de **phase 0 technique** : avant de cadrer la moindre feature ou évolution, on sait sur quoi on marche.
- **Enrichir** — le projet gagne une brique (nouvelle lib structurante, nouveau service docker, nouveau job CI, nouvel hébergement) ; on l'ajoute sans tout reprendre.
- **Éditer** — une entrée existante est devenue fausse ou imprécise (version bumpée, techno remplacée à la marge, correction d'une erreur de détection) ; on corrige en place.
- **Pivot** — refonte technique majeure (changement de framework, de langage backend, de modèle d'hébergement) ; l'ancien fichier est archivé et on recartographie.

Une stack vit : les libs bougent, les services s'ajoutent, l'infra migre. `stack.md` est un **document vivant** — les modes Enrichir et Éditer rendent une mise à jour ciblée rapide, sans réinventaire complet.

## Règles du mode interactif

1. **Ne jamais écrire `docs/stack.md` tant que l'utilisateur n'a pas vu la synthèse de détection et validé** (« on rédige », « go », « c'est bon »). La détection automatique se trompe parfois (un service docker de dev confondu avec de la prod, une lib en `require-dev`) — l'utilisateur arbitre avant gravure.
2. **Tout prouver par un fichier.** Chaque techno listée doit pointer vers le fichier qui l'atteste (`composer.json`, `docker-compose.yml`, `.github/workflows/ci.yml`…). Une techno non prouvable n'est pas « détectée » : c'est une **question** à poser, pas une ligne à écrire.
3. **Privilégier `AskUserQuestion`** pour combler les trous. Si l'outil n'est pas chargé, le récupérer via `ToolSearch`. À défaut, poser les questions en texte libre, une à une. **Maximum 3 questions par tour.**
4. **Ne pas halluciner les versions.** Si `composer.json` dit `^6.4`, écris `^6.4` (contrainte), pas `6.4.12` (résolu) — sauf si tu as lu le lockfile. Distingue contrainte déclarée et version résolue.
5. **Distinguer dev et prod.** Une dépendance `require-dev` / `devDependencies`, un service docker `profiles: [dev]`, un outil dans un job CI ≠ une brique de production. Range-les correctement (outillage de dev vs runtime).

## Déroulement

### Phase 0 — Inventaire de l'existant et choix du mode

1. **Document existant** : vérifier la présence de `docs/stack.md`. S'il existe, le lire intégralement.
2. **Contexte projet** : lire le `CLAUDE.md` à la racine (et `README.md` s'il existe) — ils contiennent souvent l'outillage réel (commandes QA, docker, Makefile) et des conventions que les manifestes ne disent pas.

#### Choix du mode

- **Si `docs/stack.md` n'existe pas** : mode **Création** imposé, enchaîne sur la Phase 1.
- **Si `docs/stack.md` existe** : demander explicitement le mode via `AskUserQuestion` :
  - **Création** — recartographie complète from scratch (rare ; le fichier existant est devenu inutilisable mais sans pivot technique réel). *Préférer Pivot ou Enrichir.*
  - **Enrichir** — ajouter une ou plusieurs briques (nouveau service, nouvelle lib, nouvel hébergement, nouveau pipeline) sans contredire l'existant. *Le cas le plus fréquent.*
  - **Éditer** — corriger/affiner une entrée existante (version, remplacement à la marge, erreur de détection). Pas d'ajout net.
  - **Pivot** — refonte technique majeure (framework, langage, hébergement). L'ancien fichier est archivé sous `docs/stack.md.archive-AAAA-MM-JJ` et on recartographie.

Ne devine jamais le mode — demande-le. Note le mode : il pilote toute la suite.

### Phase 1 — Détection automatique (toujours jouée)

Charge `${CLAUDE_SKILL_DIR}/references/detection-catalog.md` : il liste, couche par couche, les fichiers à scanner et les signaux à en tirer. Déroule-le systématiquement.

En modes **Enrichir** / **Éditer**, restreins la détection à la couche ciblée par l'utilisateur (cf. argument optionnel ou première question) pour aller vite — inutile de re-scanner toute l'infra pour ajouter une lib front.

Procédure de scan :

1. **Lister les fichiers de manifeste présents** — un `Glob` / `find` sur les patterns du catalogue (manifestes de deps, configs de build, IaC, CI, compose). Ne lis que ceux qui existent.
2. **Lire chaque manifeste trouvé** et en extraire les signaux (dépendances, versions, services, jobs).
3. **Croiser les `.env` / `.env.dist` / `.env.example`** pour les DSN (base de données, cache, broker, mailer) — souvent la seule preuve d'un service consommé.
4. **Ne jamais déduire au-delà des fichiers.** Si rien n'atteste l'hébergeur ou le monitoring, ce sont des trous → Phase 3.

### Phase 2 — Synthèse de détection

Présente ce que tu as **prouvé**, couche par couche, avec la source entre parenthèses. Sépare clairement le détecté du non-détecté :

```
## Détecté (prouvé par fichier)
- Langage : PHP ^8.2 (composer.json), Node 20 (.nvmrc)
- Backend : Symfony 6.4 (composer.json → symfony/framework-bundle)
- Frontend : Webpack Encore + Stimulus (package.json, webpack.config.js)
- Données : PostgreSQL 15 (docker-compose.yml), Redis (DATABASE/REDIS DSN dans .env.dist)
- Ops : conteneurs Docker (Dockerfile, docker-compose.yml)
- DevOps : GitHub Actions (.github/workflows/ci.yml), PHPStan + PHP-CS-Fixer (phpstan.neon, .php-cs-fixer.dist.php)

## Trous (non prouvables par les fichiers)
- Hébergement de production : ?
- Monitoring / observabilité : ?
- Stratégie de secrets / déploiement : ?
```

### Phase 3 — Combler les trous (boucle interactive)

Pour chaque trou, pose une question ciblée (3 max par tour) via `AskUserQuestion`. Concentre-toi sur ce que les fichiers ne disent presque jamais :

- **Hébergement de production** : VPS, PaaS (Platform.sh, Heroku, Fly, Vercel, Netlify…), cloud (AWS/GCP/Azure), Kubernetes managé, bare metal ?
- **Monitoring / observabilité** : Sentry, Datadog, New Relic, Grafana/Prometheus, logs centralisés, rien ?
- **CDN / reverse proxy / edge** : Cloudflare, Varnish, nginx, Traefik ?
- **Gestion des secrets** : Vault, secrets CI, `.env` serveur, gestionnaire cloud ?
- **Environnements** : combien (dev/staging/prod), comment ils diffèrent ?
- **Contraintes** : versions épinglées pour une raison précise, EOL connu, dette technique de stack à tracer ?

Si l'utilisateur ne sait pas / n'a pas, écris explicitement « non renseigné » plutôt que de combler au jugé. Un trou assumé vaut mieux qu'une invention.

### Phase 4 — Rédaction

Quand l'utilisateur valide la synthèse, rédige (ou mets à jour) `docs/stack.md` selon le mode. Crée `docs/` au besoin (`mkdir -p docs`).

- **Création** : créer le fichier complet depuis le template. Changelog : une ligne `AAAA-MM-JJ — Création — inventaire initial`.
- **Enrichir** : modifier seulement les sections concernées (préserver le reste à l'identique). Ajouter une ligne au changelog : date, nature `Enrichir`, couche ciblée, motif court (« ajout du service Meilisearch », « passage à GitHub Actions »).
- **Éditer** : modifier en place. Ligne de changelog : date, `Éditer`, couche, motif (« Symfony 6.4 → 7.1 », « correction : Redis est en prod, pas seulement en dev »).
- **Pivot** : `mv docs/stack.md docs/stack.md.archive-$(date +%Y-%m-%d)` puis recréer. Première ligne de changelog : `AAAA-MM-JJ — Pivot — refonte depuis docs/stack.md.archive-AAAA-MM-JJ — motif : <résumé>`.

Mets à jour la date « dernière mise à jour » du sous-titre dans tous les modes.

**Format du fichier** : voir `${CLAUDE_SKILL_DIR}/references/template.md`. À charger au moment de la rédaction.

Après écriture, affiche un résumé et demande si des ajustements sont nécessaires.

### Phase 5 — Clôture

Adapte le message au mode :

- **Création** / **Pivot** :
  > Stack cartographiée : `docs/stack.md`
  > Ce document est lu par `/feature-plan`, `/refactor-plan`, `/tech-plan`, `/tech`, `/refactor`, `/feature` et `/review` : ils s'appuient dessus pour proposer des solutions cohérentes avec ton stack réel plutôt que de re-détecter à chaque fois.
  > *(Mode Pivot)* L'ancienne cartographie est archivée sous `docs/stack.md.archive-AAAA-MM-JJ`.

- **Enrichir** / **Éditer** :
  > Stack mise à jour : `docs/stack.md` (mode <Enrichir|Éditer>, couche(s) : <liste>). Changelog enrichi.

## Argument optionnel

`/stack [couche ou intention]` — si l'argument cible une couche (« front », « ops », « ci »), oriente la détection et les questions vers elle (utile en mode Enrichir/Éditer). Applique toujours la Phase 0 (lecture de l'existant + choix explicite du mode), puis enchaîne. **Ne devine jamais le mode à partir de l'argument** — toujours demander si le fichier existe.
