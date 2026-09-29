# Stack du projet : chargement ou détection

Procédure partagée par les skills du workflow (`/vision`, `/product-backlog`, `/feature-interview`,
`/feature-pitch`, `/feature-plan`, `/feature-implem`, `/refactor-plan`, `/refactor-implem`,
`/tech-plan`, `/tech-implem`, `/review`, `/adr`, `/estimate`) pour établir le framework en usage et
charger les bonnes règles. À faire **au démarrage** de ces skills, avant toute proposition technique.

Trois cas, du moins coûteux au plus coûteux — le premier qui s'applique gagne :

1. **Stack déjà établi dans la session** → rien à relire.
2. **`docs/stack.md` existe** → le lire : c'est un **chargement**, pas une détection.
3. **Ni l'un ni l'autre** → **détection** légère depuis les manifestes.

## Cas 1 — Stack déjà établi dans la session

Si une skill précédente de la même session a déjà appliqué cette procédure (ex. `/feature-pitch`
enchaîné sur `/feature-plan`) et que son résultat est toujours dans ton contexte — `docs/stack.md`
et la ou les références stack lus en entier —, **ne relis rien** : ni `docs/stack.md`, ni les
références, ni ce fichier. Reprends le stack établi et affiche la ligne de résumé.

Relis quand même si :

- `docs/stack.md` a changé depuis (ex. `/stack` lancé entre-temps dans la session) ;
- le contexte a été résumé et tu n'as plus le contenu intégral des références — une référence
  résumée n'est pas une référence chargée.

Au moindre doute, relis : une relecture coûte moins cher qu'une règle framework appliquée de mémoire.

## Cas 2 — `docs/stack.md` existe (chargement)

Vérifie la présence de `docs/stack.md` (produit par `/stack`). S'il existe, lis-le : c'est la
cartographie complète et validée par l'utilisateur (langages, backend, frontend, données, ops,
devops), bien plus riche que la détection légère. Tu y trouves directement le framework, les
versions, les services et l'outillage réel.

Utilise-le comme source principale et **ne scanne pas** `composer.json`/`package.json` : le fichier
les résume déjà. Exception : un détail absent de `stack.md`, ou un soupçon qu'il est périmé
(mtime/changelog très ancien vs deps récentes → suggérer `/stack` en mode Éditer/Enrichir).

## Cas 3 — Détection légère (sans `docs/stack.md`)

1. **Lire `composer.json`** (`Read` à la racine du projet) s'il existe.
2. **Lire `package.json`** (`Read` à la racine du projet) s'il existe.
3. **Appliquer les règles de résolution** ci-dessous.
4. **Afficher le résultat à l'utilisateur en une ligne** puis continuer.

Suggère à l'utilisateur de lancer `/stack` une fois pour cartographier durablement le projet : les
skills suivantes passeront alors par le cas 2, sans rien détecter.

## Règles de résolution

Le signal est le framework backend déclaré par `docs/stack.md` (cas 2) ou, à défaut, les
dépendances de `composer.json` (cas 3). Traitées dans l'ordre — la première qui matche gagne.

| Signal                                                                    | Stack       | Références à charger                                  |
|---------------------------------------------------------------------------|-------------|-------------------------------------------------------|
| Sylius — `sylius/sylius` (ou `sylius/*-bundle` core)                      | **sylius**  | `symfony.md` puis `sylius.md`                         |
| Symfony — `symfony/framework-bundle` sans `sylius/...`                    | **symfony** | `symfony.md`                                          |
| Autre framework déclaré par `docs/stack.md`                               | **autre**   | Aucune référence dédiée : `docs/stack.md` suffit, ne rien demander |
| Aucun signal (cas 3)                                                      | **inconnu** | Demander à l'utilisateur quel stack, ou continuer sans référence spécifique |

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

Une ligne, pour que l'utilisateur sache ce qui va être appliqué **et d'où ça vient**. Le verbe dit
ce qui s'est réellement passé : « détecté » seulement quand tu as scanné les manifestes (cas 3). Ne
parle jamais de détection quand tu as simplement lu `docs/stack.md` : l'utilisateur croirait à un
scan coûteux qui n'a pas eu lieu.

Cas 1 :

> Stack déjà chargé dans la session : **sylius** — j'applique Symfony + Sylius.

Cas 2 :

> Stack chargé depuis `docs/stack.md` : **sylius** — j'applique Symfony + Sylius.

> Stack chargé depuis `docs/stack.md` : **Laravel 11** — pas de référence framework dédiée, je m'appuie sur `stack.md`.

Cas 3 :

> Stack détecté via `composer.json` : **symfony** — j'applique les règles Symfony.

> Stack non détecté automatiquement — on part sur quoi : `symfony`, `sylius`, autre, ou rien ?

## Conventions projet (`.claude/rules/` et `CLAUDE.md`)

Deux sources, à ne pas confondre.

**`.claude/rules/**`** (produites par `/forge:rules`) — des règles **paths-scopées** : elles sont
chargées par le harness, automatiquement, quand Claude ouvre un fichier de leur zone. Tu n'as donc
**rien à faire** pour les obtenir : si elles s'appliquent, elles sont déjà dans ton contexte. Ne les
lis pas à la main « pour vérifier » — tu les payerais deux fois. Elles sont la parole la plus précise
du projet sur une zone donnée, donc **elles priment sur tout le reste** en cas de conflit.

**`CLAUDE.md` à la racine** — à lire s'il existe. Il porte ce qui est vrai partout, quelle que soit
la zone touchée :

- Commandes QA exactes (préfixe `symfony`, `docker compose exec`, Makefile, etc.)
- Credentials de test (admin, clients, hostnames multi-channel)
- Noms de thèmes shop utilisés, overrides custom
- Convention de branches, de commits scope spécifique au projet

La préséance en cas de conflit, du plus fort au plus faible : **`.claude/rules/` scopées > `CLAUDE.md` > références stack**. Les deux premières sont la source de vérité de l'utilisateur pour son projet ; les références stack ne sont qu'un défaut de framework. Et entre les deux premières, la plus spécifique gagne : une règle de zone a été écrite en regardant précisément ces fichiers-là.
