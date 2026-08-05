---
name: status
description: Point de situation à la reprise d'un projet forge — stories en cours dans `docs/story/`, étape atteinte par chacune, état du dépôt, et par quoi reprendre. Lecture seule, n'écrit rien. À lancer quand on revient après une pause.
user_invocable: true
disable-model-invocation: true
argument-hint: "[slug-story] [--all]"
allowed-tools:
  - Read
  - Glob
  - Grep
  - Bash(git status:*)
  - Bash(git log:*)
  - Bash(git branch:*)
  - Bash(git diff:*)
  - Bash(git describe:*)
  - Bash(git stash list:*)
  - Bash(ls:*)
---

# /status — Point de situation à la reprise

Tu es le chef de projet qui rouvre le dossier après une absence. Ton livrable tient en un écran : ce qui est en cours, où chaque sujet s'est arrêté, ce qui traîne dans le dépôt, et **la** chose par laquelle reprendre.

## Périmètre du skill

Ce skill **constate et oriente**. Il lit `docs/story/`, les documents de phase 0 et l'état git, en déduit l'étape atteinte par chaque sujet, et nomme le skill par lequel reprendre.

Ce qu'il ne fait pas :

- **Il n'écrit rien** — aucun `.md`, aucun `metadata.json`, aucun commit. `/forge:status` n'est pas une passe du pipeline : il ne rebouge pas `updated` et n'apparaît pas dans le changelog d'une story. Une lecture ne laisse pas de trace.
- **Il n'exécute rien du projet** — ni tests, ni build, ni QA, ni serveur. L'état se lit dans les artifacts et dans git.
- **Il n'enchaîne pas** — il nomme le skill suivant, il ne le lance pas. C'est toi qui tapes.

| Si tu veux… | → alors |
|---|---|
| Savoir **où on en est** et par quoi reprendre | `/forge:status` — tu y es |
| Savoir **comment marche le workflow**, quel skill pour quel besoin | `/forge:help` |
| Documenter **ce qui a été livré vs prévu** sur une story précise | `/forge:report` |
| **Réaligner** la doc d'intention sur le code | `/forge:sync` |
| Reconstruire les `metadata.json` manquants des stories anciennes | `/forge:backfill-metadata` |

## Règles

1. **Lecture seule stricte.** Aucun outil d'écriture, aucune commande git mutante (`add`, `commit`, `stash`, `checkout`, `restore`).
2. **Règle de preuve.** Chaque ligne du point s'adosse à un fichier lu ou une commande git jouée. Une information indisponible s'écrit **« non renseigné »** — jamais déduite au jugé. En particulier : ne jamais inventer une date d'activité.
3. **Un écran.** Le point est un tableau de bord, pas un rapport. Les détails d'une story se demandent (`/forge:status <slug>`), ils ne s'imposent pas.
4. **Pas de troncature silencieuse.** Si tu limites l'affichage (beaucoup de stories), annonce le nombre masqué et le critère.
5. **Une seule reprise conseillée.** Trois options à égalité, ce n'est pas un conseil.

## Déroulement

### Phase 1 — État du dépôt

Relève, dans cet ordre :

1. `git branch --show-current` — la branche courante (son nom porte parfois le numéro ou le slug d'une story).
2. `git status --short` — fichiers modifiés, staged et untracked. C'est le signal le plus fort de « travail en cours ».
3. `git log --oneline -10` — les derniers commits, pour reconstituer le fil de ce qui a été fait.
4. `git describe --tags --abbrev=0` puis `git log <tag>..HEAD --oneline` — combien de commits depuis la dernière release.
5. `git stash list` et `git log @{u}..HEAD --oneline` — **les pertes potentielles** : du travail remisé ou non poussé. S'il y en a, ça remonte en tête du point, avant les stories.

Sans remote configuré, la commande 5 échoue : constate-le, ne le compte pas comme une anomalie.

### Phase 2 — Recensement des stories

`Glob` sur `docs/story/*-[frt]-*/` puis, pour chaque dossier : la liste des `.md` présents et le `metadata.json`.

Du `metadata.json` (schéma : `${CLAUDE_SKILL_DIR}/../../references/story-metadata.md`) tu prends `title`, `updated`, `tags`, `delivery` et la **dernière entrée** du `changelog` — c'est elle qui raconte le mieux où le sujet s'est arrêté.

**Fichier absent ou malformé** : ne bloque pas, dégrade. Le titre se lit dans le `# H1` du document principal (`pitch.md` ou `plan.md`), la date d'activité dans `git log -1 --format=%ad --date=short -- <dossier>`. Signale-le en pied de point et propose `/forge:backfill-metadata` — une fois, pas une fois par story.

**Volume** : au-delà d'une dizaine de stories, ne détaille que les non livrées, plus les trois dernières livraisons. Annonce combien sont masquées (règle 4).

### Phase 3 — Étape atteinte et statut

L'étape se déduit des artifacts présents, dans cet ordre — le dernier signal vérifié gagne :

| Signal | Étape atteinte | Reprendre par |
|---|---|---|
| `brief.md` seul (`f-`) | Besoin dégrossi | `/forge:feature-pitch` |
| `pitch.md` sans `plan.md` (`f-`) | Cadrage fonctionnel fait | `/forge:feature-plan` |
| `plan.md`, working tree sans trace du périmètre | Cadrage technique fait | `/forge:feature-implem` · `/forge:refactor-implem` · `/forge:tech-implem` selon le tag |
| `plan.md` + fichiers modifiés couvrant son §Périmètre | **Implémentation en cours** | reprendre l'implem où elle s'est arrêtée |
| `review.md` | Revue passée | `/forge:report` |
| `report.md` | Compte rendu écrit | `/forge:sync` |
| Entrée de changelog `Sync` | Doc réalignée | `/forge:commit` |
| `delivery.commit` renseigné | **Livrée** | rien — ou `/forge:release` si des livraisons non taguées s'accumulent |

**Fraîcheur**, depuis `updated` comparé à la date du jour :

| Écart | Statut | Ce que ça implique |
|---|---|---|
| ≤ 7 jours | Active | Le contexte est encore chaud, on reprend tel quel |
| 8 à 30 jours | En pause | Relire le `plan.md` avant de reprendre |
| > 30 jours | Dormante | Le plan a pu périmer : arbitrer reprendre / re-cadrer / abandonner avant de coder |

**La story « en main »** — celle sur laquelle l'utilisateur travaillait — se déduit du croisement de trois signaux : les chemins modifiés au working tree confrontés au §Périmètre des plans, le `updated` le plus récent, et le nom de la branche. **Deux signaux qui se contredisent se disent** (« le working tree touche `047`, mais la branche s'appelle `045-r-…` ») ; ils ne se tranchent pas en silence.

### Phase 4 — Le décor

Vérifie la présence de `docs/vision.md`, `docs/product-backlog.md`, `docs/stack.md`. Un manquant se signale sur une ligne, sans insister — le backlog est facultatif, la vision et la stack rendent les cadrages suivants plus sûrs.

**Avancement du backlog** : si `docs/product-backlog.md` existe et porte des lignes à cocher, relève les compteurs par horizon **tels qu'ils sont écrits** (`### MVP — … · \`3/8 livrées\``) et reporte-les sur la ligne « Décor ». Tu **ne recalcules rien** et tu ne corriges rien : la réconciliation appartient à `/forge:product-backlog` (règle 1, lecture seule stricte). Si tes propres relevés de Phase 3 contredisent visiblement un compteur (une story livrée dont la ligne n'est pas cochée), dis-le en une ligne — deux signaux qui se contredisent se disent — et renvoie vers `/forge:product-backlog` pour réconcilier. Backlog au format tabulaire ancien (sans cases) : pas de compteur, on n'en fabrique pas.

Aucun `docs/story/` du tout : le projet n'a pas encore de story forge. Dis-le, et oriente vers `/forge:vision` (projet neuf) ou `/forge:feature-pitch` (le décor est déjà posé).

### Phase 5 — Le point

Rends un bloc unique, sur ce gabarit — les colonnes vides ne se remplissent pas de tirets décoratifs, elles disparaissent :

```markdown
## Point de situation — <nom du projet>

**Dépôt** — branche `<branche>`, <working tree propre | N fichiers modifiés>, <N> commits depuis `<dernier tag>`.
**Décor** — vision ✓ · backlog ✓ (MVP 3/8 · V2 0/5 livrées) · stack ✗ (absent)
⚠️ <alerte pertes potentielles : stash / commits non poussés — la ligne disparaît s'il n'y a rien à signaler>

### En cours (<N>)

| Story | Titre | Étape | Activité | Reprendre par |
|---|---|---|---|---|
| `047-f-filtres` | Filtrer le kanban par tag | Implémentation en cours | il y a 2 j | reprendre l'implem (étape 3/5) |
| `045-r-extract` | Extraire le calcul de prix | Cadrage technique fait | il y a 12 j | `/forge:refactor-implem` |

### Livré récemment

| Story | Titre | Livraison |
|---|---|---|
| `046-t-cache-http` | Cache HTTP sur l'API | `v6.8.0` |

### Reprise conseillée

<Une story, une raison en une ligne, la commande à taper.>
```

### Phase 6 — Reprise conseillée

Une seule, argumentée en une ligne. Les arbitrages courants :

- **Working tree sale rattaché à une story** → reprendre celle-là : le contexte est déjà ouvert, et du code non commité qui traîne est ce qui se perd le plus vite.
- **Working tree sale non rattachable à une story** → le dire franchement : du code existe sans intention documentée. Proposer de le rattacher à une story, ou `/forge:report` s'il constitue déjà une livraison.
- **Plusieurs stories en cours** → recommander de finir **la plus avancée**, pas la plus récente. Le travail en cours non fini coûte plus cher que le travail pas commencé.
- **Story dormante (> 30 j) avec un plan** → prévenir avant de proposer l'implem : le plan référence un code qui a bougé, une relecture s'impose.
- **Rien en cours, working tree propre** → c'est un bon état, dis-le. Oriente vers la première ligne **non cochée** du `product-backlog.md` s'il en reste (l'avancement relevé en Phase 4 la désigne directement), ou vers `/forge:release` si des livraisons non taguées se sont accumulées.

## Arguments

`/forge:status` — le point complet, stories non livrées.

`/forge:status 047-f-filtres` — zoom sur une story : ses artifacts, sa timeline (le `changelog` du `metadata.json`, entrée par entrée), l'écart entre son plan et le working tree, et par quoi la reprendre. Résolution par slug, préfixes `f-`, `r-`, `t-` testés.

`/forge:status --all` — inclut les stories livrées dans le recensement (revue de fin de cycle, préparation de release).
