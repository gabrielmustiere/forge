# Template ADR — MADR léger

Format à appliquer pour `docs/adr/NNNN-<slug>.md`. Conserver l'ordre des sections — un futur lecteur navigue par section.

```markdown
# ADR-NNNN — <Titre court et factuel>

- **Statut** : proposed | accepted | superseded by ADR-XXXX
- **Date** : YYYY-MM-DD
- **Déciders** : <noms ou rôles, optionnel>
- **Story liée** : `docs/story/NNN-<f|r|t>-slug/` (optionnel — vide si ADR standalone)

## Contexte

Quel signal a déclenché cette décision ? Quel problème on cherche à résoudre ? Quelles contraintes (techniques, organisationnelles, légales, deadline) cadrent le choix ?

3 à 6 phrases. Un lecteur qui ne connaît rien au projet doit comprendre **pourquoi on a dû trancher**.

## Decision drivers

Critères de choix ordonnés par importance. 2 à 5 max.

- **Driver 1** — formulation courte (ex: "tenir 10k req/s en pointe")
- **Driver 2** — …
- **Driver 3** — …

## Options considérées

### Option A — <nom court>

Description en 2-3 phrases : composant, ce que ça change concrètement, opérabilité, coût.

- Aligne avec **Driver 1** : <oui/non/partiel + pourquoi>
- Aligne avec **Driver 2** : <…>
- Coût / trade-off : <…>

### Option B — <nom court>

Idem.

### Option C — Statu quo (si pertinent)

Ne rien changer. Pourquoi c'est tentant, pourquoi c'est insuffisant.

## Décision

**Option retenue : <A | B | …>**

Justification accrochée aux drivers — pas de "parce que c'est mieux". Exemple : "Option A retenue car elle est la seule à satisfaire Driver 1 sans alourdir l'opérabilité (Driver 3). Le coût d'apprentissage (trade-off) est jugé acceptable car l'équipe a déjà manipulé Redis sur le projet X."

## Conséquences

**Positives**

- <Ce que la décision permet ou simplifie>
- <…>

**Négatives / coûts assumés**

- <Latence, dépendance opérationnelle, surcoût, courbe d'apprentissage, dette potentielle>
- <…>

**Suites obligatoires**

- [ ] <Action concrète induite — migration, dashboard, runbook, ADR de suivi>
- [ ] <…>

## Links

- Artifact source : `docs/story/NNN-<f|r|t>-slug/plan.md` (ou `pitch.md`, `review.md`, `report.md`)
- ADR superseded : ADR-XXXX (si applicable)
- ADR liés : ADR-YYYY (dépendances ou contraintes — si applicable)
- Références externes : docs framework, RFC, post-mortem
```

## Règles de rédaction

- **Titre factuel et court** : `0007-cache-sessions-redis` plutôt que `0007-on-choisit-redis`. Le titre doit décrire la décision, pas le processus.
- **Pas de superlatifs** : "la meilleure solution" → bannir. Une option est retenue **par rapport à des drivers**, pas dans l'absolu.
- **Pas de futurologie** : ne pas écrire "on pourra plus tard…" sans tâche concrète associée. Si une suite est obligatoire, elle va dans **Suites obligatoires** (cochable). Si elle est hypothétique, ne pas la mentionner.
- **Statut `superseded`** : éditer l'ADR ancien pour passer son statut à `superseded by ADR-NNNN`, et lier le nouveau dans la section `Links` de l'ancien. L'ancien ADR n'est jamais supprimé — il reste lisible pour comprendre la trajectoire.
- **Markdown valide** : table d'index dans `README.md` triée par numéro croissant, lignes de table alignées si possible.
