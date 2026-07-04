# Format de `docs/product-backlog.md`

```markdown
# Product Backlog — [Nom du projet]

> Carte des capacités fonctionnelles et backlog priorisé dérivé de `docs/vision.md`.

_Document vivant — enrichi/édité au fil du cycle de vie, refondu lors d'un pivot. Date de dernière mise à jour : AAAA-MM-JJ._

## Changelog

Historique des évolutions structurantes (création, enrichissements, éditions ciblées, pivots). Lecture chronologique. Détails fins dans `git log`.

| Date | Nature | Éléments | Motif |
|------|--------|----------|-------|
| AAAA-MM-JJ | Création | — | Backlog initial dérivé de la vision |
| AAAA-MM-JJ | Enrichir | C3.6, V2/`export-audit-log` | Demande d'export audit (audience admin) |
| AAAA-MM-JJ | Éditer | `slug-feature-X` | Repriorisation MVP → V2 (dépendance externe) |
| AAAA-MM-JJ | Pivot | — | Refonte suite au pivot de la vision du AAAA-MM-JJ |

## Domaines fonctionnels

| # | Domaine | Résumé en une ligne |
|---|---------|---------------------|
| D1 | [Nom] | [Ce que le domaine couvre] |
| D2 | ... | ... |

## Capacités

### D1 — [Nom du domaine]

- **C1.1** — <acteur> peut <verbe> <objet métier> (pour <bénéfice>).
- **C1.2** — ...

### D2 — [Nom du domaine]

- **C2.1** — ...

_(répéter pour chaque domaine)_

## Parcours utilisateurs principaux

### P1 — [Nom du parcours]

- **Acteur** : [persona].
- **Déclencheur** : [ce qui lance].
- **Étapes** : C1.1 → C1.3 → C2.5 → C3.2.
- **État final** : [ce qui a changé].
- **Fréquence** : [estimation].

### P2 — ...

## Règles métier transverses

### Permissions et rôles

- ...

### Workflows et états

- ...

### Contraintes de gestion

- ...

### Exigences réglementaires

- ...

### Conventions transverses

- ...

## Backlog priorisé

### MVP — Lancement initial

| Slug | Pitch | Capacités | Parcours | Dépendances | Justification vision |
|------|-------|-----------|----------|-------------|----------------------|
| `slug-feature-1` | Pitch en une ligne | C1.1, C1.2 | P1 | — | Problème principal / audience principale |
| `slug-feature-2` | ... | C2.3 | P2 | `slug-feature-1` | Principe X / North Star |

### V2 — Court terme post-lancement

| Slug | Pitch | Capacités | Parcours | Dépendances | Justification vision |
|------|-------|-----------|----------|-------------|----------------------|
| ... | ... | ... | ... | ... | ... |

### V3 — Long terme

| Slug | Pitch | Capacités | Parcours | Dépendances | Justification vision |
|------|-------|-----------|----------|-------------|----------------------|
| ... | ... | ... | ... | ... | ... |

## Couverture

### Capacités couvertes par horizon

- **MVP** : C1.1, C1.2, C2.3, ...
- **V2** : C1.4, C3.1, ...
- **V3** : ...

### Capacités non couvertes (à challenger)

- C2.4 — pourquoi pas dans le backlog ? (anti-objectif ? obsolète ? oubli ?)

### Parcours supportés

- **P1** : entièrement supporté en MVP.
- **P2** : partiellement supporté en MVP (étapes C2.5 et C3.2 reportées en V2).

## Notes pour `/feature-pitch`

Pointeurs bruts pour aider le cadrage détaillé : sensibilités identifiées, idées d'écrans esquissées, dépendances externes pressenties. **Ne pas concevoir ici** — juste lister.
```
