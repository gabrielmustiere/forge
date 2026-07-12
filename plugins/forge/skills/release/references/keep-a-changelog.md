# Format du CHANGELOG

`CHANGELOG.md` à la racine du repo. Basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/),
avec **deux adaptations** propres à ce projet :

1. Chaque version porte un **titre obligatoire** (pas seulement un numéro et une date).
2. Chaque version distingue **deux chapitres** — `✨ Fonctionnel` et `🔧 Technique` — au lieu des sections `Added/Changed/Fixed/…` de Keep a Changelog.

Le fichier est **destiné à être montré à l'utilisateur final** (affiché dans l'app). Il doit rester propre, lisible, sans jargon inutile ni hash de commit.

## Structure de référence

```markdown
# Changelog

Toutes les modifications notables de ce projet sont documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Chaque version porte un **titre** et distingue les **évolutions fonctionnelles**
(perceptibles à l'usage) des **évolutions techniques** (internes, outillage, plomberie).

## [Unreleased]

### ✨ Fonctionnel
- ...

### 🔧 Technique
- ...

## [1.5.0] - 2026-04-27 — Titre de la release

### ✨ Fonctionnel
- **Titre court** — description orientée usage, à l'impératif ou au constat.

### 🔧 Technique
- **Titre court** — description technique.

[Unreleased]: https://github.com/owner/repo/compare/v1.5.0...HEAD
[1.5.0]: https://github.com/owner/repo/compare/v1.4.2...v1.5.0
[1.4.2]: https://github.com/owner/repo/releases/tag/v1.4.2
```

## En-tête de version

Format : `## [X.Y.Z] - YYYY-MM-DD — Titre de la release`

- Le segment `[X.Y.Z]` **entre crochets** reste intact (c'est l'ancre des liens de comparaison en bas de fichier — ne jamais le fusionner avec le titre).
- La date reste au format Keep a Changelog `YYYY-MM-DD`, séparée par ` - `.
- Le **titre** suit, précédé de ` — ` (tiret cadratin). Il est **obligatoire** : jamais de version sans titre.
- `## [Unreleased]` n'a **pas** de titre (il n'est pas encore taggé) mais est déjà découpé en chapitres Fonctionnel / Technique.

Un bon titre nomme **le fil rouge** de la release en 2 à 5 mots, du point de vue produit : « Clone local & interview de cadrage », « Cycle de vie des stories sur le board », « Skill estimate (chiffrage tout compris) ». Pas « Divers correctifs », pas « v1.5.0 ».

## Les deux chapitres

- **`### ✨ Fonctionnel`** — tout ce qu'un utilisateur **perçoit** : nouvelle capacité, écran ou commande, changement de comportement visible, correction d'un bug qu'il subissait, capacité retirée. C'est le chapitre qui compte pour l'affichage user.
- **`### 🔧 Technique`** — tout ce qui est **interne** : refacto, perf, dépendances, outillage de dev, plomberie (interfaces, subagents, modes de permission), réorganisation de doc/CI, corrections invisibles à l'usage.

Règles de découpage :

- **N'inclure un chapitre que s'il a du contenu.** Une release purement technique n'a que `🔧 Technique` ; une release purement produit n'a que `✨ Fonctionnel`. Ne jamais laisser un chapitre vide.
- **Fonctionnel en premier** quand les deux chapitres existent (c'est ce que l'utilisateur lit en priorité).
- Dans le doute sur un item : se demander « l'utilisateur le remarque-t-il en se servant du produit ? » → oui = Fonctionnel, non = Technique.
- Un **breaking change** garde un préfixe **`BREAKING —`** en tête de sa ligne, dans le chapitre adéquat.

## Mapping Conventional Commits → chapitre

| Type / nature du commit                                   | Chapitre par défaut |
|-----------------------------------------------------------|---------------------|
| `feat` visible par l'utilisateur, `fix` d'un bug subi     | ✨ Fonctionnel       |
| `feat` d'outillage/dev interne                            | 🔧 Technique         |
| `perf`, `refactor`                                        | 🔧 Technique         |
| `fix` de plomberie interne (invisible à l'usage)          | 🔧 Technique         |
| `BREAKING CHANGE` ou `type!`                              | chapitre adéquat, préfixe `BREAKING —` |
| Suppression d'une capacité **user-facing**               | ✨ Fonctionnel       |
| Suppression d'un composant **interne** (subagent, config) | 🔧 Technique         |
| `docs`, `chore`, `style`, `test`, `ci`                    | Omis (sauf si visible utilisateur → Fonctionnel) |

Le mapping donne le chapitre **par défaut** ; c'est la perceptibilité par l'utilisateur qui tranche, pas le type de commit à la lettre.

## Règles de rédaction

- **Une ligne = un changement**, formulée `**Titre court** — explication`. Le titre en gras rend le fichier scannable une fois rendu dans l'app.
- **Orientée usage** dans le chapitre Fonctionnel : décrire ce que l'utilisateur gagne, pas l'implémentation. Réserver les noms de classes/services/fichiers au chapitre Technique.
- **Pas de hash de commit** — c'est de la doc humaine, pas un git log déguisé.
- **Regrouper** plusieurs commits qui touchent la même feature en une ligne lisible.
- **Ignorer** les commits triviaux (typos, fix CI, bump deps cosmétique) sauf s'ils sont visibles utilisateur.

## Ordre d'insertion

Toujours insérer les nouvelles entrées **en haut** (sous `[Unreleased]`), pas en bas. Les humains lisent du plus récent au plus ancien.
