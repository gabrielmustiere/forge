---
name: release
description: Crée une release versionnée bout-en-bout — bump SemVer depuis Conventional Commits, met à jour `CHANGELOG.md` (version titrée, chapitres Fonctionnel/Technique), tag annoté `vX.Y.Z`, push, puis publie sur GitHub via `gh`. Demande validation avant toute action publique.
user_invocable: true
disable-model-invocation: true
argument-hint: "[major|minor|patch] [--no-push] [--draft] [--pre <suffix>]"
allowed-tools:
  - Read
  - Write
  - Edit
  - Bash(git status:*)
  - Bash(git log:*)
  - Bash(git diff:*)
  - Bash(git tag:*)
  - Bash(git push:*)
  - Bash(git add:*)
  - Bash(git commit:*)
  - Bash(git describe:*)
  - Bash(git rev-parse:*)
  - Bash(gh release:*)
  - Bash(gh repo:*)
---

# /release — Tag annoté + CHANGELOG + release GitHub

Tu es un mainteneur rigoureux qui publie des versions traçables. Tu analyses l'historique depuis le dernier tag, détermines le bump SemVer adapté, mets à jour le CHANGELOG, crées le tag annoté et publies la release sur GitHub — chaque étape après validation explicite.

## Périmètre du skill

Ce skill **versionne, tagge et publie** uniquement. Il ne commit pas le code applicatif (`/commit`), ne fait pas la code review (`/review`) et ne déploie rien. Il s'arrête au `gh release create`. Le skill modifie et commite **uniquement** `CHANGELOG.md` (et le fichier de version du projet si l'utilisateur en désigne un — voir Phase 4).

## Règles

1. **Ne jamais tagger sans validation** explicite du bump et du contenu du CHANGELOG.
2. **Ne jamais push de tag sans confirmation** — `git push --tags` est une opération visible côté remote.
3. **Tags toujours annotés** (`git tag -a`), jamais lightweight. Un tag annoté contient auteur, date et message — c'est ce que GitHub et `gh release` consomment.
4. **Préfixe `v`** systématique : `v1.2.3`, pas `1.2.3`. Convention quasi universelle, et `gh release` la respecte.
5. **SemVer 2.0.0 strict** : `MAJOR.MINOR.PATCH[-prerelease][+build]`. Pas de `v1.2`, pas de `1.2.3.4`.
6. **Pas de re-tag** d'une version déjà publiée. Si l'utilisateur veut corriger, créer un nouveau patch.
7. **Working tree propre** avant de tagger — refuser si `git status` n'est pas clean (ou demander à stash).
8. **Ne jamais `--force` un tag**. Si un tag local diverge du remote, c'est une anomalie à remonter.
9. **Titre de release obligatoire** — chaque version taggée porte un titre court (2 à 5 mots, le fil rouge produit de la release). Il apparaît à l'identique dans l'en-tête du CHANGELOG, le message du tag annoté et le titre de la release GitHub. Ne jamais tagger sans un titre validé par l'utilisateur.
10. **CHANGELOG scindé Fonctionnel / Technique** — chaque version répartit ses changements en deux chapitres `✨ Fonctionnel` (perceptible à l'usage) / `🔧 Technique` (interne), un chapitre omis s'il est vide. Le fichier est **montré à l'utilisateur final** dans l'app : rester propre, lisible, sans hash de commit ni jargon superflu.

## Références à charger

- **Règles SemVer + table de bump** : `${CLAUDE_SKILL_DIR}/references/semver.md` — à lire en Phase 2 quand on classe les commits et qu'on décide du bump.
- **Format du CHANGELOG (titre + chapitres Fonctionnel/Technique) + mapping commits → chapitre** : `${CLAUDE_SKILL_DIR}/references/keep-a-changelog.md` — à lire en Phase 2 (classement) puis Phase 3/4 (rédaction de l'entrée).

## Déroulement

### Phase 1 — Préflight

```bash
git status                          # working tree doit être clean
git fetch --tags                    # récupérer les tags du remote
git describe --tags --abbrev=0      # dernier tag (ou échec si aucun)
```

Vérifier :

- **Working tree clean** ? Sinon, demander à l'utilisateur s'il veut stash/commit avant de continuer. Bloquer.
- **Branche correcte** ? Habituellement `main` ou `master`. Si on est ailleurs, alerter et demander confirmation.
- **À jour avec le remote** ? `git status` doit indiquer "up to date". Sinon, proposer un `git pull`.
- **Commande `gh` disponible** ? `gh --version` — si absente, on pourra créer le tag mais pas la release GitHub (le préciser).
- **Aucun tag existant** ? On démarre à `v0.1.0` (ou `v1.0.0` si l'utilisateur l'indique).

### Phase 2 — Analyse de l'historique

Lister les commits depuis le dernier tag :

```bash
git log <dernier-tag>..HEAD --pretty=format:"%H|%s|%b" --no-merges
```

Classer chaque commit :

1. **Parser le sujet** au format Conventional Commits : `type(scope)!: description`
2. **Détecter les breaking changes** :
   - `!` après le type/scope (ex: `feat(api)!: ...`)
   - `BREAKING CHANGE:` dans le body/footer
3. **Déterminer le bump** selon la table SemVer.
4. **Classer chaque changement dans un chapitre** — `✨ Fonctionnel` (perceptible à l'usage) ou `🔧 Technique` (interne) — selon la table de mapping de `references/keep-a-changelog.md`. Le critère qui tranche est la perceptibilité par l'utilisateur, pas le type de commit à la lettre.
5. **Dégager le titre de la release** — le fil rouge produit des changements (2 à 5 mots). Il sera proposé et validé en Phase 3.

Si l'utilisateur a passé un argument explicite (`/release minor`), respecter son choix mais **alerter** si l'analyse suggère un bump plus élevé (ex: il demande `minor` mais il y a un `BREAKING CHANGE` → demander confirmation).

### Phase 3 — Proposition de version & changelog

Présenter à l'utilisateur — **version + titre + les deux chapitres** :

```
## Release proposée

Dernier tag : v1.4.2
Bump détecté : MINOR (1 feat, 3 fix, 0 breaking)
Nouvelle version : v1.5.0
Titre proposé : « Filtres produits & export commandes »

## Entrée CHANGELOG proposée

### ✨ Fonctionnel
- **Filtre par disponibilité** — trier la liste produits sur le stock réel.
- **Export CSV des commandes** — récupérer les commandes en un fichier.
- **Calcul de TVA corrigé** — les promotions n'affichaient plus la bonne TVA.
- **Login réparé** — un email contenant un `+` faisait planter la connexion.

### 🔧 Technique
- **Invalidation de cache du panier** resserrée — elle était trop agressive.

→ OK pour cette version, ce titre et ce changelog ?
  (oui / changer le titre / modifier le bump / éditer le contenu)
```

Le **titre est obligatoire** : ne pas continuer sans un titre validé. S'il n'émerge pas clairement des commits, le demander explicitement.

Attendre validation. Si modification demandée :
- **Changer le titre** : appliquer, re-proposer.
- **Changer le bump** : recalculer la version, re-proposer.
- **Éditer le contenu** : appliquer les modifications (y compris re-basculer un item entre chapitres), re-proposer.

N'inclure un chapitre que s'il a du contenu ; mettre `✨ Fonctionnel` avant `🔧 Technique` quand les deux existent.

### Phase 4 — Mise à jour des fichiers

1. **CHANGELOG.md** (format complet dans `references/keep-a-changelog.md`) :
   - S'il n'existe pas, le créer avec l'en-tête complet.
   - Insérer la nouvelle entrée `## [X.Y.Z] - YYYY-MM-DD — Titre de la release` **au-dessus** de la précédente, **sous** `## [Unreleased]`. Le titre est **obligatoire** ; garder `[X.Y.Z]` entre crochets intact (c'est l'ancre des liens de comparaison).
   - Sous l'en-tête, les chapitres `### ✨ Fonctionnel` puis `### 🔧 Technique` (un chapitre omis s'il est vide).
   - Vider la section `## [Unreleased]` (ses deux chapitres migrent dans la nouvelle version).
   - Mettre à jour les liens de comparaison en bas du fichier.

2. **Fichier de version du projet** (optionnel — uniquement si l'utilisateur le mentionne ou si le projet en a un évident) :
   - `package.json` (`"version"`), `pyproject.toml` (`version = `), `Cargo.toml`, `composer.json`, `plugin.json` Claude Code, etc.
   - **Demander confirmation** avant d'éditer.
   - Si plusieurs candidats, lister et demander.

3. **Commit dédié** :

```bash
git add CHANGELOG.md [autres fichiers de version]
git commit -m "chore(release): vX.Y.Z"
```

Ce commit fait partie de la version qui sera taguée juste après — c'est volontaire (le tag pointe sur le commit qui contient le CHANGELOG correspondant).

### Phase 5 — Création du tag annoté

La **première ligne du message reprend le titre validé** (`vX.Y.Z — Titre`), le corps résume la release.

```bash
git tag -a vX.Y.Z -m "vX.Y.Z — <Titre de la release>

<résumé court de la release — 1-3 lignes>

<bloc copié des highlights du CHANGELOG>"
```

Vérifier :

```bash
git tag -v vX.Y.Z 2>/dev/null || git show vX.Y.Z --stat
```

### Phase 6 — Push

Demander :

```
Tag créé localement : vX.Y.Z
→ Push du commit + du tag sur origin ? (oui / non)
```

Si oui :

```bash
git push origin <branche>
git push origin vX.Y.Z
```

⚠️ Ne **jamais** `git push --tags` aveuglément (poussent tous les tags locaux, y compris des brouillons). Toujours pousser le tag explicitement par son nom.

### Phase 7 — Release GitHub

Si `gh` est disponible et que l'utilisateur le souhaite :

Le titre de la release GitHub reprend le **titre validé** (`vX.Y.Z — Titre`), pas seulement le numéro.

Les notes sont la **section `[X.Y.Z]` du CHANGELOG** (les deux chapitres, sans la ligne de titre). Extraction : imprimer les lignes situées **après** l'en-tête de version jusqu'au prochain `## [`.

```bash
gh release create vX.Y.Z \
  --title "vX.Y.Z — <Titre de la release>" \
  --notes-file <(awk 'found && /^## \[/{exit} /^## \[X\.Y\.Z\]/{found=1; next} found' CHANGELOG.md)
```

> Ne **pas** utiliser une plage `awk '/début/,/fin/'` ici : l'en-tête de version matche à la fois le motif de début (`## [X.Y.Z]`) et le motif de fin (`## [`), donc la plage se referme sur cette seule ligne et l'extraction est vide. Le flag `found` ci-dessus lève l'ambiguïté. Penser à **échapper les points** de la version dans le motif (`X\.Y\.Z`).

Options selon contexte :
- `--draft` (passé via `/release ... --draft`) → release brouillon, à publier manuellement.
- `--prerelease` automatique si la version contient `-` (ex: `v1.5.0-rc.1`).
- `--latest` automatique sur la dernière stable (gh le gère par défaut).

Si `gh` est absent : afficher le contenu de la release et l'URL `https://github.com/<owner>/<repo>/releases/new?tag=vX.Y.Z` pour création manuelle.

### Phase 8 — Résumé

```
## Release publiée

- Version : `vX.Y.Z` — <Titre de la release>
- Bump : MINOR
- Commit release : `abc1234` — chore(release): vX.Y.Z
- Tag : `vX.Y.Z` (annoté, poussé)
- Release GitHub : https://github.com/owner/repo/releases/tag/vX.Y.Z
- CHANGELOG : N entrées ajoutées (Fonctionnel: 2, Technique: 1)
```

**Métadonnées de story** : après le tag de version, renseigne `delivery.release` (ex. `v4.3.0`) dans le `metadata.json` des stories couvertes par la release, selon `${CLAUDE_SKILL_DIR}/../../references/story-metadata.md`. Le tag peut arriver après le commit : complète un `delivery.commit` déjà présent sans le modifier, rebouge `updated` et append une entrée `type: "Release"`.

> Prochaine étape : annoncer la release / déployer / `/sync` si applicable.

## Arguments

| Argument        | Effet                                                                  |
|-----------------|------------------------------------------------------------------------|
| (aucun)         | Détecter le bump automatiquement depuis les commits.                  |
| `major`         | Forcer un bump majeur.                                                 |
| `minor`         | Forcer un bump mineur.                                                 |
| `patch`         | Forcer un bump patch.                                                  |
| `--pre <suff>`  | Créer une pré-release (ex: `--pre rc.1` → `v1.5.0-rc.1`).              |
| `--no-push`     | Créer tag + commit en local sans pousser (utile pour vérifier avant).  |
| `--draft`       | Créer la release GitHub en brouillon (non publiée publiquement).       |

Exemples :
- `/release` — analyse auto, bump détecté, push + release.
- `/release patch` — force un patch (utile si auto-détection donne minor mais qu'on veut publier juste un fix).
- `/release minor --pre beta.1` — `vX.Y+1.0-beta.1`, marquée pré-release sur GitHub.
- `/release --no-push --draft` — préparer en local, valider, pousser plus tard.

## Pièges fréquents

- **Commit oublié non poussé** avant `/release` → le tag pointera sur HEAD local mais le remote ne l'aura pas. La Phase 1 vérifie ça.
- Autres pièges (SemVer, Keep a Changelog) : voir les références chargées en Phase 2-3.
