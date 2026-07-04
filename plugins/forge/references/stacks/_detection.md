# Détection du stack projet

Procédure partagée par `/feature-pitch`, `/feature-plan`, `/feature-implem`, `/refactor-plan`, `/refactor-implem`, `/tech-plan`, `/tech-implem`, `/review` pour identifier le framework en usage et charger les bonnes règles. À faire **au démarrage** de ces skills, avant toute proposition technique.

## Raccourci : `docs/stack.md` (source riche prioritaire)

**Avant la détection légère ci-dessous, vérifie la présence de `docs/stack.md`** (produit par `/stack`). S'il existe, lis-le : c'est la cartographie complète et validée par l'utilisateur (langages, backend, frontend, données, ops, devops), bien plus riche que la détection inline. Tu y trouves directement le framework, les versions, les services et l'outillage réel.

- Si `docs/stack.md` existe → utilise-le comme source principale. Tu peux sauter le scan `composer.json`/`package.json` (le fichier le résume déjà), sauf si tu as besoin de vérifier un détail absent ou de t'assurer qu'il n'est pas périmé (mtime/changelog très ancien vs deps récentes → suggérer `/stack` en mode Éditer/Enrichir).
- Si `docs/stack.md` n'existe pas → applique la détection légère ci-dessous. Tu peux suggérer à l'utilisateur de lancer `/stack` une fois pour cartographier durablement le projet.

La détection légère qui suit reste le **fallback** quand `docs/stack.md` est absent.

## Étapes (fallback sans `docs/stack.md`)

1. **Lire `composer.json`** (`Read` à la racine du projet) s'il existe.
2. **Lire `package.json`** (`Read` à la racine du projet) s'il existe.
3. **Appliquer les règles de résolution** ci-dessous.
4. **Afficher le résultat à l'utilisateur en une ligne** puis continuer.

## Règles de résolution

Traitées dans l'ordre — la première qui matche gagne.

| Signal                                                                    | Stack       | Références à charger                                  |
|---------------------------------------------------------------------------|-------------|-------------------------------------------------------|
| `composer.json` → dépendance `sylius/sylius` (ou `sylius/*-bundle` core)  | **sylius**  | `symfony.md` puis `sylius.md`                         |
| `composer.json` → dépendance `symfony/framework-bundle` sans `sylius/...` | **symfony** | `symfony.md`                                          |
| Aucun des deux signaux                                                    | **inconnu** | Demander à l'utilisateur quel stack, ou continuer sans référence spécifique |

Les références (`symfony.md`, `sylius.md`) sont dans le **même dossier que ce fichier `_detection.md`**. Une fois le stack identifié, lis-les via `Read` en réutilisant le **chemin absolu du dossier d'où tu viens de lire `_detection.md`** (la skill te l'a passé via `${CLAUDE_SKILL_DIR}/../../references/stacks/`).

⚠️ **Ne lis jamais un chemin relatif au projet** comme `plugins/forge/references/stacks/symfony.md` : ce chemin n'existe que dans le repo source de la marketplace. Chez l'utilisateur, le plugin est installé **hors du projet** (`~/.claude/plugins/...`) — seul le chemin dérivé de `${CLAUDE_SKILL_DIR}` est correct.

## Skills dédiés disponibles (stack Symfony / Sylius)

Quand le stack détecté est `symfony` ou `sylius`, les skills du plugin `symfony` sont disponibles pour approfondir les opérations Doctrine. Les skills du workflow (`/feature-implem`, `/refactor-implem`, `/tech-implem`, `/review`) peuvent y **rediriger** l'utilisateur plutôt que de détailler ces opérations inline — elles restent focalisées sur le pipeline :

| Besoin pendant une sous-tâche                               | Skill à suggérer                 |
|-------------------------------------------------------------|----------------------------------|
| Créer ou modifier une entité, ajouter une relation          | `/symfony:doctrine-entity`       |
| Écrire/réviser une requête repository (DQL, QueryBuilder)    | `/symfony:doctrine-query`        |
| Générer, relire, exécuter ou annuler une migration           | `/symfony:doctrine-migration`    |

Règle : quand une sous-tâche de `/feature-implem` ou `/refactor-implem` touche principalement un de ces trois domaines, proposer à l'utilisateur d'invoquer la skill dédiée (« Cette sous-tâche est centrée sur le mapping — tu veux enchaîner via `/symfony:doctrine-entity` ? ») plutôt que de tout dérouler en ligne. Les skills du pipeline gardent leur orchestration (checkpoints, QA, report), les skills `symfony` fournissent la procédure précise.

## Résumé à afficher

Une ligne juste après la détection, pour que l'utilisateur sache ce qui va être appliqué :

> Stack détecté : **sylius** (via `composer.json`) — j'applique Symfony + Sylius.

ou :

> Stack détecté : **symfony** — j'applique les règles Symfony.

ou :

> Stack non détecté automatiquement — on part sur quoi : `symfony`, `sylius`, autre, ou rien ?

## Conventions projet (CLAUDE.md)

En plus du stack, **lire `CLAUDE.md` à la racine du projet** s'il existe. Il contient les conventions personnelles qui complètent les règles framework :

- Commandes QA exactes (préfixe `symfony`, `docker compose exec`, Makefile, etc.)
- Credentials de test (admin, clients, hostnames multi-channel)
- Noms de thèmes shop utilisés, overrides custom
- Convention de branches, de commits scope spécifique au projet

Le `CLAUDE.md` prime sur les références stack en cas de conflit — c'est la source de vérité de l'utilisateur pour son projet.
