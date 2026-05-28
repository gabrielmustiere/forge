# Format de `docs/vision.md`

```markdown
# Vision — [Nom du projet]

> Pitch en une phrase : [ce que c'est] pour [audience] qui résout [problème] en [comment].

_Document vivant — enrichi au fil du cycle de vie, refondu lors d'un pivot stratégique. Date de dernière mise à jour : AAAA-MM-JJ._

## Changelog

Historique des évolutions structurantes (création, enrichissements, éditions ciblées, pivots). Lecture du haut vers le bas = ordre chronologique. Détails fins dans `git log`.

| Date | Nature | Axe | Motif |
|------|--------|-----|-------|
| AAAA-MM-JJ | Création | — | Vision initiale |
| AAAA-MM-JJ | Enrichir | Audience | Ajout audience secondaire « fleet manager » |
| AAAA-MM-JJ | Éditer | Principes | Reformulation du principe P2 (trop vague) |
| AAAA-MM-JJ | Pivot | — | Refonte : changement d'audience principale (cf. archive du AAAA-MM-JJ) |

## Le problème

L'irritant concret que ce produit résout, au présent, avec une situation typique.

**Comment c'est résolu aujourd'hui** : [alternative ou bricolage actuel].
**Pourquoi c'est insuffisant** : [limites concrètes].
**Ampleur** : [fréquence, volume, coût pour l'utilisateur].

## L'audience

### Utilisateur principal

- **Persona** : [rôle, contexte, journée type].
- **Volume cible** : ordre de grandeur.
- **Ce qui le bloque aujourd'hui** : [verbatim ou observation].

### Utilisateurs secondaires

- [Rôle 1] — [besoin distinct].
- [Rôle 2] — [besoin distinct].

### Hors cible explicite

[Qui on n'adresse pas et pourquoi.]

## La proposition de valeur

### Bénéfice utilisateur

[Ce que l'utilisateur gagne, exprimé en chiffre ou en bénéfice nommé concret.]

### Pourquoi nous, plutôt qu'eux

[Raison concrète vs alternatives existantes.]

### Unfair advantage

[Ce qu'on a/fait qui n'est pas reproductible facilement.]

## Métriques de succès

### North Star

[La métrique unique qui dit « ça marche ». Définition + comment elle se mesure.]

### Métriques secondaires

- **Acquisition** : [...]
- **Activation** : [...]
- **Rétention** : [...]
- **Monétisation** : [...]

### Seuils

- À 6 mois : [...]
- À 1 an : [...]
- À 3 ans : [...]

### Signal d'arrêt

[À quel signe on dit « on arrête, ça ne marche pas ».]

## Principes produit

1. **[Principe 1]** — [explication courte, exemple de décision tranchée].
2. **[Principe 2]** — ...
3. ...

## Anti-objectifs

Ce qu'on **refuse explicitement** de faire, et pourquoi :

- [Anti-feature ou anti-marché 1] — [raison].
- ...

## Hypothèses critiques

| # | Hypothèse | Comment l'invalider | Statut |
|---|-----------|---------------------|--------|
| 1 | ... | ... | À tester / Validée / Invalidée |
| 2 | ... | ... | ... |

## Risques externes

- **[Risque 1]** : [description, mitigation envisagée].
- ...

## Horizons

### 3-6 mois

[Jalons clés. Pas un Gantt.]

### 1 an

[...]

### 3 ans

[...]

## Notes pour les features à venir

Pointeurs bruts pour `/feature-pitch` : grandes initiatives évoquées, parcours pressentis, dépendances identifiées. **Ne pas concevoir de feature ici** — juste lister.
```
