# Report — <Titre de la story>

> Pitch : `docs/story/<NNN>-<f|r|t>-<slug>/pitch.md` <!-- guide: ligne supprimée pour `-r-` et `-t-` (pas de pitch) -->
> Plan : `docs/story/<NNN>-<f|r|t>-<slug>/plan.md`
> Date d'implémentation : YYYY-MM-DD
> Commits liés : <SHA(s) ou « working tree non commité au moment du report »>
> Référence review : `review.md` <!-- guide: supprimer si pas de review encore -->

<!--
guide: Compte rendu d'implémentation. Produit par `/workflow:report` après livraison, avant `/workflow:sync`.
But : comparer l'INTENTION (pitch + plan) au code LIVRÉ. Lister les écarts, les décisions, la dette, les suites.
Le sync s'appuiera sur ce rapport pour réaligner la doc d'intention.
Retirer ce bloc et tous les `> _Skill : ..._` avant commit.
-->

## Résumé

> _Skill : 1 paragraphe exécutif. Taux de conformité au plan en pourcentage, principaux écarts structurants (3 max), nombre de cases d'acceptation cochées vs total, statut review (bloquantes/importants résolus ou non), périmètre quantifié (lignes, fichiers)._

<Résumé en 1–3 phrases. Mentionner : pourcentage conforme au plan, principaux écarts, dette résiduelle.>

## Ce qui a été implémenté

> _Skill : deux tables — fichiers créés et fichiers modifiés. Colonne « Prévu dans le plan » avec « Oui » / « Non (ajout) » / « Écart volontaire (cf. §) ». La table doit refléter le `git diff` réel — un reviewer doit pouvoir cocher chaque ligne contre le diff._

### Fichiers créés

| Fichier                                              | Rôle                                                              | Prévu dans le plan |
|------------------------------------------------------|-------------------------------------------------------------------|--------------------|
| `src/<…>.php`                                        | <rôle livré>                                                      | Oui                |
| `tests/<…>Test.php`                                  | <cas couverts>                                                    | Oui                |
| `<chemin>`                                           | <rôle>                                                            | Non (ajout — cf. §Ajouts non prévus) |

### Fichiers modifiés

| Fichier                                              | Modification                                                      | Prévu dans le plan |
|------------------------------------------------------|-------------------------------------------------------------------|--------------------|
| `src/<…>.php`                                        | <diff conceptuel>                                                 | Oui                |
| `src/<…>.php`                                        | <diff>                                                            | Écart volontaire (cf. §) |
| `tests/<…>Test.php`                                  | <adaptation>                                                      | Oui                |

## Écarts avec le plan

> _Skill : section centrale du report — c'est ce que `/workflow:sync` va consommer. Trois sous-tables. Chaque écart volontaire doit dire « prévu / réalisé / raison ». Si une raison se relie à une décision de review, citer le bloquant/important par numéro._

### Écarts volontaires

| Prévu                                       | Réalisé                                  | Raison                                                       |
|---------------------------------------------|------------------------------------------|--------------------------------------------------------------|
| <description plan>                          | <description livré>                      | <raison + référence review/decision le cas échéant>          |
| <description plan>                          | <description livré>                      | <…>                                                          |

### Non implémenté

> _Skill : si tout a été livré, mettre une ligne « Aucun » plutôt que de supprimer le sous-bloc — le sync vérifie cette section._

| Élément prévu                               | Raison                                   | Action requise                                               |
|---------------------------------------------|------------------------------------------|--------------------------------------------------------------|
| <description>                               | <raison>                                 | <ticket de suivi, dette, future story>                       |

### Ajouts non prévus

| Élément ajouté                              | Raison                                                                              |
|---------------------------------------------|-------------------------------------------------------------------------------------|
| <description>                               | <raison — souvent factorisation découverte en cours d'exécution ou retour de review>|

## Tests

> _Skill : table « code → type prévu → type réalisé → statut ». Couvrir tous les tests prévus dans le plan + ajouts. Statuts possibles : « Fait », « Fait — couverture étendue », « Manque mineur — risque … », « Conforme (hors scope assumé) »._

| Code                                                        | Type prévu       | Type réalisé                                  | Statut                       |
|-------------------------------------------------------------|------------------|-----------------------------------------------|------------------------------|
| `src/<…>.php`                                               | Unit (N cas)     | Unit, N+M cas (`<…>Test`)                     | Fait — couverture étendue    |
| `src/<…>.php`                                               | Unit (adapté)    | Unit adapté                                   | Fait                         |
| <Functional CRUD `/admin/…`>                                | Hors scope assumé| Pas écrit                                     | Conforme — couvert par firewall global |
| <cas d'edge X>                                              | Non prévu        | **Non couvert**                               | Manque mineur — risque <…>   |

## Dette technique identifiée

> _Skill : ce qui reste à faire après cette story. Items numérotés, ordonnés par criticité. Pour chaque, dire d'où vient l'item (review mineure non traitée, contrainte de timing, décision « pas dans cette story »). Mentionner explicitement les éléments **critiques** (runbook de déploiement, bascules infra non exécutées) avec le mot **Critique** en gras._

Issus de la review (mineurs non traités) :

1. **[STYLE] <description>** — `<chemin>:<ligne>` — <action attendue>.
2. **[I18N] <description>** — `<chemin>` — <…>.
3. **[CONV] <description>** — `<chemin>` — <…>.

Au-delà de la review :

4. **<Élément structurel>** — <action attendue>. **Critique** au prochain déploiement.
5. **<Élément long terme>** — <action attendue>.

## Critères d'acceptation

> _Skill (feature `-f-`) : reprise EXACTE des critères du `pitch.md`, avec cases cochées (`[x]`) ou non (`[ ]`). Pour chaque critère non coché, expliquer pourquoi (souvent : écart volontaire référencé en §Écarts). C'est ce que le sync utilise pour réaligner le pitch._
>
> _Skill (refactor `-r-` / tech `-t-`) : remplacer ce titre par `## Critères de succès` et reprendre les critères du plan (mesurables, vérifiés)._

Reprise des critères du `pitch.md` (ou `plan.md`) :

- [x] <Critère atteint>.
- [x] <Critère atteint, vérifié via `<commande/test>`>.
- [ ] <Critère partiellement atteint — écart volontaire (cf. §Écarts)>.
- [ ] <Critère reporté — voir §Dette technique>.

## Leçons apprises

> _Skill : 3–6 puces. Décisions méthodologiques qui auraient été utiles à connaître AVANT (« si tu refais un plan comme celui-ci, voilà ce que tu n'aurais pas su anticiper »). Pas de bilan d'humeur, pas de « tout s'est bien passé ». Pattern réutilisable, point d'attention pour le prochain qui touchera la même zone._

- **<Leçon 1>** : <ce qu'il faut retenir, contexte d'application future>.
- **<Leçon 2>** : <…>.
- **<Leçon 3>** : <…>.
