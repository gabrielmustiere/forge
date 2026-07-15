# Report — <Titre de la story>

> **But** : constater l'écart entre l'intention et le code livré — écarts, dette, suites.
> **Registre** : factuel
> **Story** : `docs/story/<NNN>-<f|r|t>-<slug>/`
> **Amont** : `pitch.md` · `plan.md` · `review.md` <!-- guide: retirer `pitch.md` pour `-r-`/`-t-` (pas de pitch), `review.md` si pas encore de review -->

<!--
guide: Compte rendu d'implémentation. Produit par `/forge:report` après livraison, avant `/forge:sync`.
But : comparer l'INTENTION (pitch + plan) au code LIVRÉ. Lister les écarts, les décisions, la dette, les suites. `/forge:sync` s'appuie sur ce rapport pour réaligner la doc d'intention.
Format commun à tous les documents de story : voir la charte `${CLAUDE_SKILL_DIR}/../../references/document-format.md`.
Document de DÉCISION : il ouvre sur sa conclusion (charte §1). On doit pouvoir ne lire que la §Synthèse.
Registre FACTUEL (charte §3) : le report constate, il ne défend pas. Ce qui est, ce qui manque, ce qui a dérivé, avec la raison. Pas d'opinion, pas de projet.
Les tables du §Périmètre reprennent EXACTEMENT celles du `plan.md` + une colonne « Prévu dans le plan » : c'est le point de jonction de la chaîne plan → report (charte §6). Aucune autre divergence de colonnes n'est admise.
Ne pas dupliquer la date d'implémentation ni les SHA de commit dans l'en-tête : ils vivent dans `metadata.json` (charte §2).
L'en-tête ci-dessus RESTE dans le fichier commité. Retirer ce bloc et tous les `> _Skill : ..._` avant commit.
-->

## Synthèse

> _Skill : la conclusion, en premier. Un paragraphe exécutif : taux de conformité au plan en pourcentage, écarts structurants (3 max), critères cochés vs total, statut de la review (bloquants/importants résolus ou non), périmètre quantifié (fichiers, lignes). Registre factuel : des faits, pas un bilan d'humeur._

- **Conformité au plan** : <N> % — <les écarts structurants en une phrase>.
- **Critères** : <N> / <total> cochés.
- **Review** : <N bloquants et N importants résolus / statut>.
- **Périmètre livré** : <N fichiers créés, N modifiés, ~N lignes>.

<Résumé en 1–3 phrases : ce qui a été livré, ce qui a dérivé, ce qui reste.>

## Périmètre livré

> _Skill : les deux tables du `plan.md` §Périmètre, **mêmes colonnes dans le même ordre**, plus une colonne finale « Prévu dans le plan » (charte §6). Valeurs de cette colonne : `Oui` / `Non (ajout — cf. §Écarts)` / `Écart volontaire (cf. §Écarts)`. Les tables doivent refléter le `git diff` réel : un reviewer doit pouvoir cocher chaque ligne contre le diff._

### Fichiers créés

| Fichier | Rôle | Prévu dans le plan |
|---|---|---|
| `<chemin>` | <rôle livré> | Oui |
| `<chemin de test>` | <cas couverts> | Oui |
| `<chemin>` | <rôle> | Non (ajout — cf. §Écarts) |

### Fichiers modifiés

| Fichier | Modification | Prévu dans le plan |
|---|---|---|
| `<chemin>` | <diff conceptuel> | Oui |
| `<chemin>` | <diff conceptuel> | Écart volontaire (cf. §Écarts) |

## Écarts avec le plan

> _Skill : **section centrale du report** — c'est ce que `/forge:sync` consomme pour réaligner la doc d'intention. Trois sous-tables, toujours les trois. Chaque écart volontaire dit « prévu / réalisé / raison ». Quand la raison vient d'un finding de review, citer son tag et son résumé (charte §7)._

### Écarts volontaires

| Prévu | Réalisé | Raison |
|---|---|---|
| <description du plan> | <description du livré> | <raison + référence review/décision le cas échéant> |

### Non implémenté

> _Skill : si tout a été livré, écrire une ligne « Aucun » plutôt que de supprimer le sous-bloc — `/forge:sync` vérifie cette section (charte §5)._

| Élément prévu | Raison | Action requise |
|---|---|---|
| <description> | <raison> | <dette, story future, ticket de suivi> |

### Ajouts non prévus

| Élément ajouté | Raison |
|---|---|
| <description> | <raison — souvent une factorisation découverte en cours d'exécution ou un retour de review> |

## Tests

> _Skill : table « code → type prévu → type réalisé → statut ». Couvrir tous les tests prévus au plan **et** les ajouts. Statuts : « Fait », « Fait — couverture étendue », « Manque mineur — risque <…> », « Conforme (hors scope assumé) ». Le hors-scope assumé du plan se retrouve ici : c'est ce qui prouve qu'il était une décision, pas un oubli._

| Code | Type prévu | Type réalisé | Statut |
|---|---|---|---|
| `<chemin>` | unit (N cas) | unit, N+M cas | Fait — couverture étendue |
| `<chemin>` | unit (adapté) | unit adapté | Fait |
| <élément> | hors scope assumé | pas écrit | Conforme — <raison du plan> |
| <cas limite> | non prévu | **non couvert** | Manque mineur — risque <…> |

## Critères d'acceptation

> _Skill (story `-f-`) : reprise **EXACTE** des critères du `pitch.md` §Critères d'acceptation, cases cochées (`[x]`) ou non (`[ ]`). Pour chaque critère non coché, dire pourquoi (souvent : écart volontaire, à référencer). C'est ce que `/forge:sync` utilise pour réaligner le pitch — d'où l'exigence de formulation identique._
>
> _Skill (story `-r-` / `-t-`) : **renommer ce titre en `## Critères de sortie`** et reprendre les critères du `plan.md` §Critères de sortie (charte §4). Une story `-r-`/`-t-` n'a pas de pitch, donc pas de critères d'acceptation : une seule section de critères, quel que soit le track._

Reprise des critères du `pitch.md` <!-- guide: `plan.md` pour `-r-`/`-t-` --> :

- [x] <Critère atteint>.
- [x] <Critère atteint, vérifié via `<commande/test>`>.
- [ ] <Critère partiellement atteint — écart volontaire (cf. §Écarts)>.
- [ ] <Critère reporté — cf. §Dette technique>.

## Dette technique identifiée

> _Skill : ce qui reste à faire après cette story. Items numérotés, ordonnés par criticité. Pour chaque, dire d'où il vient (mineur de review non traité, contrainte de temps, décision « pas dans cette story »). Les findings repris de la review gardent leur format et leur tag **à l'identique** (charte §7). Signaler en gras les éléments **Critique** (bascule d'infra non exécutée, procédure de déploiement)._

Issus de la review (mineurs non traités) :

1. **[STYLE] <résumé>** — `<chemin>:<ligne>` — <action attendue>.
2. **[I18N] <résumé>** — `<chemin>` — <action attendue>.

Au-delà de la review :

3. **<Élément structurel>** — <action attendue>. **Critique** au prochain déploiement.
4. **<Élément long terme>** — <action attendue>.

## Leçons apprises

> _Skill : 3–6 puces. Décisions méthodologiques qui auraient été utiles AVANT (« si tu refais un plan comme celui-ci, voilà ce que tu n'aurais pas su anticiper »). Pattern réutilisable, point d'attention pour le prochain qui touchera la zone. Pas de bilan d'humeur, pas de « tout s'est bien passé »._

- **<Leçon 1>** : <ce qu'il faut retenir, contexte d'application future>.
- **<Leçon 2>** : <…>.
- **<Leçon 3>** : <…>.
