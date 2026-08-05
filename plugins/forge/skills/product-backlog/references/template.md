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

> Les cases sont **dérivées** de `docs/story/*/metadata.json` (champ `backlog` + `delivery`) — ne les coche pas à la main, la prochaine passe les recalculerait. Une case est cochée quand la story est **livrée** (`delivery.commit` renseigné) ; les états intermédiaires se lisent sur la ligne « Story ».

### MVP — Lancement initial · `3/8 livrées`

- [x] `slug-feature-1` — Pitch en une ligne.
  - Story `042-f-slug-feature-1` · **livrée** v4.3.0
  - C1.1, C1.2 · P1 · dép. — · Vision : problème principal / audience principale
- [ ] `slug-feature-2` — Pitch en une ligne.
  - Story `047-f-slug-feature-2` · **implémentation en cours**
  - C2.3 · P2 · dép. `slug-feature-1` · Vision : principe X / North Star
- [ ] `slug-feature-3` — Pitch en une ligne.
  - Pas encore cadrée
  - C3.6 · P4 · dép. — · Vision : audience admin

### V2 — Court terme post-lancement · `0/5 livrées`

- [ ] `slug-feature-4` — Pitch en une ligne.
  - Pas encore cadrée
  - C4.1 · P3 · dép. `slug-feature-2` · Vision : horizon V2

### V3 — Long terme · `0/3 livrées`

- [ ] `slug-feature-5` — Pitch en une ligne.
  - Pas encore cadrée
  - C5.2 · P5 · dép. — · Vision : North Star long terme

## Couverture

_(dérivé — recalculé à chaque passe, ne pas maintenir à la main)_

### Capacités par horizon

- **MVP** — livrées : C1.1, C1.2 · planifiées : C2.3, C3.6
- **V2** — livrées : — · planifiées : C4.1
- **V3** — livrées : — · planifiées : C5.2

### Capacités non couvertes (à challenger)

- C2.4 — pourquoi pas dans le backlog ? (anti-objectif ? obsolète ? oubli ?)

### Parcours supportés

- **P1** : entièrement supporté en MVP — **livré**.
- **P2** : partiellement supporté en MVP (étapes C2.5 et C3.2 reportées en V2) — en cours.

## Notes pour `/feature-pitch`

Pointeurs bruts pour aider le cadrage détaillé : sensibilités identifiées, idées d'écrans esquissées, dépendances externes pressenties. **Ne pas concevoir ici** — juste lister.
```

---

## Format d'une ligne de backlog

Trois lignes, toujours dans cet ordre. Le contenu est celui des 7 champs de la Phase 5 — seule la mise en forme change.

```markdown
- [ ] `slug` — Pitch en une ligne.
  - <ligne d'état>
  - <capacités> · <parcours> · dép. <dépendances> · Vision : <justification>
```

1. **La ligne à cocher** — la case, le slug entre backticks, un tiret cadratin, le pitch en une phrase. Rien d'autre : c'est la ligne qu'on scanne.
2. **La ligne d'état** — voir ci-dessous. Elle est **dérivée**, jamais saisie.
3. **La ligne de rattachements** — capacités, parcours, dépendances, justification vision, séparés par ` · `. Un champ vide s'écrit `—`, il ne disparaît pas (la position porte le sens).

Le format liste est imposé par une contrainte technique : une case à cocher Markdown n'est rendue qu'en **début d'item de liste**. Dans une cellule de tableau, `- [ ]` s'affiche en texte brut. C'est pourquoi le backlog priorisé n'est pas une table.

## États : catalogue fermé

La ligne d'état reprend **le vocabulaire de `/status`** (tableau signal → étape de son SKILL.md) — il n'y a pas deux vocabulaires d'avancement dans forge. Sept valeurs, pas une de plus :

| Ligne d'état écrite | Preuve qui la déclenche | Case |
|---|---|---|
| `Pas encore cadrée` | aucune story ne porte ce slug en `backlog` | `[ ]` |
| `Story <dossier>` · **besoin dégrossi** | `brief.md` seul | `[ ]` |
| `Story <dossier>` · **cadrage fonctionnel fait** | `pitch.md` sans `plan.md` | `[ ]` |
| `Story <dossier>` · **cadrage technique fait** | `plan.md` | `[ ]` |
| `Story <dossier>` · **implémentation en cours** | working tree touchant le périmètre du plan | `[ ]` |
| `Story <dossier>` · **clôture en cours** | `review.md` ou `report.md` présent, `delivery.commit` absent | `[ ]` |
| `Story <dossier>` · **livrée** [`vX.Y.Z`] | `delivery.commit` renseigné | `[x]` |

Règles :

- **La case ne se coche que sur `delivery.commit`.** Une story en revue n'est pas livrée.
- **Le numéro de version** ne s'affiche que si `delivery.release` existe. Un commit sans tag est un état valide : `**livrée**`, sans version.
- **Plusieurs stories pour une ligne** : la ligne d'état affiche **la plus avancée**, et la ligne mentionne les autres dossiers (`Stories 042-f-…, 051-f-…`). La case est cochée dès qu'une story est livrée.
- **Rien d'inventé** : un état qui ne peut pas être prouvé (métadonnées absentes, dossier illisible) s'écrit `État non déterminé` — ce n'est pas une 8e valeur du catalogue, c'est l'aveu d'une preuve manquante.

## Compteur d'horizon

Le titre de chaque horizon porte un compteur `n/N livrées` — `n` = lignes cochées, `N` = lignes de l'horizon. Il se recalcule au même moment que les cases (jamais séparément, sinon il ment). Un horizon vide n'affiche pas de compteur.
