---
name: migrate-forge
description: Migre un projet forge de `docs/` vers `forge/` — vision, stack, backlog, stories, ADR, feature-maps déplacés par `git mv` (historique conservé), chemins réécrits, références relevées. Déclenche sur « migrer docs vers forge », « passer à forge/ ».
user_invocable: true
disable-model-invocation: true
model: sonnet
effort: low
argument-hint: "[--dry-run]"
allowed-tools:
  - Read
  - Edit
  - Glob
  - Bash(ls:*)
  - Bash(find:*)
  - Bash(mkdir:*)
  - Bash(mv:*)
  - Bash(perl:*)
  - Bash(xargs:*)
  - Bash(rmdir:*)
  - Bash(grep:*)
  - Bash(git status:*)
  - Bash(git ls-files:*)
  - Bash(git mv:*)
  - Bash(git diff:*)
---

# /migrate-forge — De `docs/` à `forge/`

Jusqu'à la v7, l'atelier forge d'un projet vivait sous `docs/`. Depuis la v8, il vit sous
`forge/`, et les skills ne lisent plus que cet emplacement (garde partagée :
`${CLAUDE_SKILL_DIR}/../../references/legacy-docs.md`). Ce skill fait la transition **une fois** :
il déplace les artefacts, réécrit les chemins qu'ils contiennent, et relève les références au
dossier `docs/` qui traînent ailleurs dans le projet.

Le dossier `docs/` n'a plus de rôle dans un projet forge. Le but est qu'il disparaisse : soit tout
ce qu'il contenait était de l'atelier forge et il est vide après migration, soit il reste des
fichiers que l'utilisateur range lui-même, sur ta proposition.

## Périmètre

**Ce qui est de l'atelier forge** — liste fermée, rien d'autre ne se déplace sans validation :

| Sous `docs/` | Devient |
|---|---|
| `vision.md`, `vision.md.archive-*` | `forge/vision.md`, `forge/vision.md.archive-*` |
| `stack.md`, `stack.md.archive-*` | `forge/stack.md`, `forge/stack.md.archive-*` |
| `product-backlog.md`, `product-backlog.md.archive-*` | `forge/product-backlog.md`, `forge/product-backlog.md.archive-*` |
| `story/` (tous les dossiers `NNN-<f\|r\|t>-<slug>/`, `metadata.json` compris) | `forge/story/` |
| `adr/` (ADR et index `README.md`) | `forge/adr/` — **sous condition**, voir ci-dessous |
| `feature-map/` | `forge/feature-map/` — **sous condition**, voir ci-dessous |

`docs/story/`, `docs/vision.md`, `docs/stack.md` et `docs/product-backlog.md` sont des
**marqueurs** : leur présence prouve un atelier forge. `docs/adr/` et `docs/feature-map/` n'en sont
pas — `docs/adr/` est une convention répandue hors de forge (log4brains, ADR maison). Ils ne se
déplacent qu'**avec** un marqueur, et même alors sur arbitrage : montre un ADR pris au hasard, et
demande s'il vient de `/forge:adr` (format MADR léger, renvois vers `docs/story/`) ou d'ailleurs.
Sans marqueur, ils restent où ils sont : il n'y a rien à migrer.

**Ce que tu ne fais pas** :

- **Aucun commit.** `git mv` prépare l'index, il ne livre rien. L'historique reste le livrable de
  `/forge:commit`.
- **Aucune réécriture de contenu** au-delà du préfixe de chemin `docs/` → `forge/`. Pas de
  reformulation, pas de mise au format, pas de correction au passage.
- **Pas de passe de story.** Un déplacement n'est pas un événement de la story : tu ne rebouges
  ni `updated` ni le `changelog` des `metadata.json`.
- **Rien d'écrasé.** Un fichier qui existe déjà sous `forge/` n'est jamais remplacé.
- **Pas d'historique réécrit.** Un `CHANGELOG.md` raconte le passé : ses entrées publiées gardent
  `docs/`. Tu le signales, tu ne le modifies pas.

`$ARGUMENTS` : `--dry-run` s'arrête après le plan (Phase 2) sans rien toucher.

## Règles

1. **Inventaire avant action.** Rien ne bouge tant que le plan complet n'a pas été montré et
   validé.
2. **`git mv` pour ce qui est suivi**, pour que `git log --follow` relie chaque fichier à son
   passé. Un fichier non suivi (`git ls-files` ne le connaît pas) n'a pas d'historique à
   conserver : il se déplace quand même, et le bilan le signale (voir Phase 3).
3. **Une référence se classe avant de se réécrire.** Un `docs/` hors de l'atelier peut viser
   autre chose que forge. Seuls les chemins qui visent un artefact de la liste fermée se
   réécrivent d'office dans `forge/` ; ailleurs, chaque réécriture est proposée.
4. **Idempotent.** Relancé sur un projet migré, le skill le constate et s'arrête.

## Workflow

### Phase 0 — Préconditions

1. **Dépôt git** : `git status --porcelain`. Pas un dépôt git → préviens que l'historique ne pourra
   pas suivre les fichiers, et demande s'il faut continuer avec de simples déplacements.
2. **Working tree** : si des fichiers **suivis** de `docs/` ou `forge/` ont des modifications non
   commitées (`M`, `A`, `D`, `R` dans `git status --porcelain -- docs forge`), arrête-toi. Une
   migration mélangée à du travail en cours donne un diff illisible. Propose de commiter
   (`/forge:commit`) ou de remiser d'abord. Les fichiers **non suivis** (`??`) ne bloquent pas :
   ils se déplacent avec le reste (Phase 3) et le bilan les signale.
3. **État des lieux** :

   ```bash
   ls -d docs/story docs/vision.md docs/stack.md docs/product-backlog.md 2>/dev/null   # marqueurs
   ls -d docs/adr docs/feature-map 2>/dev/null                                         # sous condition
   ls -d forge forge/* 2>/dev/null
   ```

   | Constat | Conduite |
   |---|---|
   | Aucun marqueur sous `docs/` | « Rien à migrer. » Arrête-toi. Un `docs/adr/` ou `docs/feature-map/` seul n'est pas un atelier forge : dis-le et laisse-le. Si `forge/` existe, le projet est déjà migré |
   | Un marqueur sous `docs/`, pas de `forge/` | Cas nominal, continue |
   | Un marqueur sous `docs/` **et** un `forge/` | Migration partielle : continue, mais chaque collision passe en arbitrage (Phase 1) |

### Phase 1 — Inventaire

**L'atelier** : liste ce qui se déplace, par ligne de la liste fermée, avec les comptes (`N stories
dont F feature / R refacto / T tech`, `N ADR`, `N feature-maps`, archives).

**Les collisions** (migration partielle seulement) :

- un fichier présent des deux côtés (`docs/stack.md` et `forge/stack.md`) → les deux sont montrés,
  l'utilisateur choisit lequel garde la place ;
- deux stories qui portent **le même numéro** (`docs/story/012-f-…` et `forge/story/012-t-…`) →
  le compteur est global, un doublon casse `status`, le board et les renvois. Ne renumérote pas
  d'autorité : signale, et laisse l'utilisateur trancher ;
- même règle pour les ADR (`NNNN`) et les feature-maps (`NNN`).

**Le reste de `docs/`** : tout fichier hors liste fermée. Liste-le sans le juger, il sera traité en
Phase 2.

**Les références au dossier `docs/` hors atelier** :

```bash
grep -rIn --exclude-dir={.git,vendor,node_modules,var,docs,forge} -E '(^|[^A-Za-z0-9_./-])docs/' .
grep -In -E '(^|[^A-Za-z0-9_./-])docs(/|\*\*|$)' .gitignore .gitattributes 2>/dev/null
```

Classe chaque occurrence :

| Classe | Exemples | Conduite |
|---|---|---|
| **Vise l'atelier forge** | `docs/story/…`, `docs/stack.md`, `docs/adr/0007-…` dans `AGENTS.md`, `CLAUDE.md`, `README.md` | Réécriture proposée → `forge/…` |
| **Glob de règle ou d'outil** | `paths: docs/**` dans `.claude/rules/*.md`, `.markdownlint`, CI, `mkdocs.yml`, `.gitattributes` | Réécriture proposée, en disant ce que le glob couvrait |
| **Vise autre chose** | une page de `docs/` hors liste fermée, une URL `…/docs/` | Laissée, signalée |
| **Historique** | entrée publiée d'un `CHANGELOG.md` | Laissée, signalée |

### Phase 2 — Le plan (validation)

Présente un bloc unique :

```markdown
## Migration docs/ → forge/ — <nom du projet>

**Déplacés** (git mv, historique conservé)
- docs/vision.md (+ 1 archive) → forge/vision.md
- docs/stack.md → forge/stack.md
- docs/story/ → forge/story/ — 14 stories (9 f, 3 r, 2 t)
- docs/adr/ → forge/adr/ — 6 ADR + index

**Chemins réécrits dans forge/** : ~42 occurrences dans 23 fichiers (`docs/story/…` → `forge/story/…`)

**À ranger** (hors atelier, restent dans docs/ tant que tu n'as pas décidé)
- docs/api.md
- docs/architecture.png

**Références hors atelier**
1. AGENTS.md:31 — `docs/stack.md` → `forge/stack.md`
2. .claude/rules/docs.md:3 — `paths: docs/**/*.md` → `forge/**/*.md` (le glob couvrait aussi docs/api.md)
3. CHANGELOG.md:88 — entrée publiée, laissée telle quelle

**Collisions** : aucune

→ J'applique ? (oui / corrige une ligne / --dry-run : on s'arrête là)
```

Pour les fichiers **à ranger**, propose une destination par fichier, sans l'imposer :
`documentation/` si la page s'adresse aux utilisateurs du produit, un dossier existant du projet,
ou les laisser où ils sont (`docs/` reste alors en place). Note qu'à partir de cette migration,
`documentation/` est écrit par `/forge:user-doc`, au standard
(`references/documentation-standard.md`) : un fichier qu'on y range à la main doit le suivre, ou
porter un nom préfixé de `_` pour ne pas être publié.

`--dry-run` : rends le plan et arrête-toi.

### Phase 3 — Déplacement

Sur validation :

```bash
mkdir -p forge
git mv docs/vision.md forge/vision.md          # et chaque archive
git mv docs/stack.md forge/stack.md
git mv docs/product-backlog.md forge/product-backlog.md
git mv docs/story forge/story                   # si forge/story n'existe pas
git mv docs/adr forge/adr                       # si arbitré comme atelier forge (Phase 1)
git mv docs/feature-map forge/feature-map       # idem
```

- **Dossier cible déjà présent** (migration partielle) : déplace **élément par élément**
  (`git mv docs/story/012-f-x forge/story/012-f-x`), jamais le dossier entier, et saute les
  collisions non arbitrées.
- **Fichiers non suivis** : `git mv` d'un dossier embarque les fichiers non suivis qu'il contient.
  Un fichier isolé non suivi, lui, fait échouer `git mv` (`not under version control`) : déplace-le
  avec `mkdir -p` + `mv`, et signale-le au bilan — il n'aura pas d'historique à suivre.
- Les **fichiers à ranger** se déplacent selon la destination validée, par `git mv` aussi.
- `docs/` vide à la fin → `rmdir docs`.

La profondeur est la même (`docs/` et `forge/` sont tous deux à la racine) : les liens **relatifs**
entre artefacts (`../../adr/0007-….md`, `../../../src/…`) restent valides sans retouche.

### Phase 4 — Réécriture des chemins

**Dans `forge/`** (d'office, c'est le périmètre validé) : remplace le préfixe `docs/` par `forge/`
devant un artefact déplacé, et nulle part ailleurs. Sur tout l'atelier, ce sont des dizaines ou des
centaines d'occurrences : passe par **une seule commande scriptée**, pas par `Edit` (qui ne
connaît pas les regex et multiplierait les appels et les oublis). Tous les `.md` et `metadata.json`
de `forge/` sont concernés — les descriptions de changelog d'un `metadata.json` peuvent citer un
chemin :

```bash
find forge -type f \( -name '*.md' -o -name 'metadata.json' \) -print0 \
  | xargs -0 perl -pi -e 's{(?<![A-Za-z0-9_./-])docs/(?=story/|adr/|feature-map/|vision\.md|stack\.md|product-backlog\.md)}{forge/}g'
```

Si `docs/adr/` ou `docs/feature-map/` sont restés en place (arbitrage de la Phase 1), retire
`adr/|` ou `feature-map/|` du motif : leurs chemins doivent continuer de pointer vers `docs/`.

Contrôle aussitôt avec `git diff --stat -- forge` puis un échantillon de `git diff -- forge` : seules
des lignes de chemin doivent bouger. Un `docs/` qui ne vise pas l'atelier (une page laissée dans
`docs/`) n'est pas touché : signale-le.

**Hors `forge/`** : applique les réécritures validées en Phase 2, une par une, par `Edit` ciblé.
Jamais de réécriture d'un fichier entier — `AGENTS.md`, `CLAUDE.md` et les règles portent du savoir
que ce skill n'a pas produit.

### Phase 5 — Vérification et bilan

1. Relance la recherche de la Phase 1, sur `forge/` et sur les fichiers touchés : plus aucun chemin
   `docs/` ne doit viser l'atelier.
2. `git status --porcelain` : les déplacements suivis apparaissent en `R` (renommés). Un `D` + `??`
   à la place signale un déplacement que git n'a pas reconnu : dis-le.
3. Rends le bilan :

```
Migration terminée :
- 23 fichiers déplacés sous forge/ (21 renommés suivis, 2 non suivis)
- 42 chemins réécrits dans forge/, 3 références réécrites ailleurs (AGENTS.md, .claude/rules/docs.md)
- docs/ supprimé (vide) | docs/ conservé : 2 fichiers à ranger (docs/api.md, docs/architecture.png)
- Laissés : CHANGELOG.md:88 (historique)

Rien n'est commité : `git diff --cached -M --stat` pour relire.
Prochaine étape : /forge:commit — `chore(forge): déplacer l'atelier forge de docs/ vers forge/`
```

Si le projet est suivi par un outil externe qui lit `docs/story/` (une ancienne version du Forge
Board, un script maison, une CI), ajoute une ligne : il doit désormais lire `forge/story/`.

## Pièges courants

- **Réécrire tout `docs/`** : une URL `https://exemple.fr/docs/` ou une page de doc du projet n'ont
  rien à voir avec l'atelier. Le motif ne vise que les chemins de la liste fermée.
- **Migrer par copie** : un `cp` puis `rm` coupe l'historique ; `git log --follow` ne relie plus
  rien. Toujours `git mv`.
- **Renuméroter pour résoudre une collision** : un numéro de story apparaît dans les branches, les
  commits, les renvois entre documents et le board. Le changer est une décision de l'utilisateur,
  pas un effet de bord de la migration.
- **Toucher aux `metadata.json` comme à une passe** : la migration ne rebouge pas `updated`. La
  fraîcheur que calcule `/forge:status` doit rester celle du travail réel.
- **Oublier les globs** : une règle `.claude/rules/` en `paths: docs/**` ne se chargera plus
  jamais si rien ne vit plus sous `docs/`. C'est la référence la plus facile à manquer.

## Substitutions disponibles

- `$ARGUMENTS` — `--dry-run` pour s'arrêter après le plan.
- `${CLAUDE_SKILL_DIR}/../../references/legacy-docs.md` — la garde que les autres skills
  appliquent ; ce skill en est la sortie.
