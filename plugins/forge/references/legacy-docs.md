# Ancienne convention `docs/` — garde partagée

Jusqu'à la v7, l'atelier forge d'un projet vivait sous `docs/`. Depuis la v8, il vit sous
`forge/` : `forge/vision.md`, `forge/stack.md`, `forge/product-backlog.md`, `forge/story/`,
`forge/adr/`, `forge/feature-map/`. Le dossier `docs/` n'appartient plus au pipeline.

Les skills ne lisent **qu'un seul emplacement**, `forge/`. Ils ne gèrent pas les deux en parallèle :
deux arborescences finiraient par diverger. Un projet resté sur `docs/` se migre une fois, par
`/forge:migrate-forge`.

## La vérification

À faire **au démarrage**, avant de lire ou d'écrire un artefact forge. Un seul `ls` suffit :

```bash
ls -d docs/story docs/vision.md docs/stack.md docs/product-backlog.md forge 2>/dev/null
```

Seuls ces **marqueurs propres à forge** comptent : `docs/story/`, `docs/vision.md`,
`docs/stack.md`, `docs/product-backlog.md`. `docs/adr/` et `docs/feature-map/` **ne déclenchent
rien à eux seuls** : `docs/adr/` est une convention répandue hors de forge (log4brains, ADR
maison), et un projet qui y garde ses propres décisions n'a rien à migrer. Ils ne sont de l'atelier
que s'ils accompagnent un marqueur — c'est `/forge:migrate-forge` qui les arbitre alors.

| Ce que tu trouves | Situation | Conduite |
|---|---|---|
| Aucun marqueur sous `docs/` | Projet migré, neuf, ou `docs/` étranger à forge | Rien à dire, continue |
| Un marqueur sous `docs/`, pas de `forge/` | Projet **non migré** | **Arrête-toi** et renvoie vers la migration |
| Un marqueur sous `docs/` **et** un `forge/` | Migration **partielle** | **Arrête-toi**, signale les deux, renvoie vers la migration |

Pourquoi s'arrêter plutôt que continuer dans `forge/` : un skill qui crée `forge/story/001-…` à
côté d'un `docs/story/` existant **repart le compteur de zéro** (numéros en double), écrit une
vision ou une stack vierges alors qu'elles existent, et coupe l'historique git en deux. Le coût
d'un tour perdu est faible devant celui d'un dépôt à démêler.

Message type :

> Ce projet suit encore l'ancienne convention : l'atelier forge est dans `docs/`
> (`docs/story/`, `docs/stack.md`…). Depuis la v8 il vit dans `forge/`. Lance
> `/forge:migrate-forge` pour le déplacer (historique git conservé), puis relance ce skill.

## Exceptions

- **`status`** est en lecture seule : il **signale** la situation en tête du point et oriente vers
  `/forge:migrate-forge`, sans s'arrêter — il lit alors `docs/` pour rendre le point quand même.
- **`help`**, **`commit`**, **`release`** et **`test-scenario`** ne lisent ni n'écrivent
  d'artefact de l'atelier : ils ne vérifient rien. `grill`, lui, lit les stories : il applique la
  garde.
- **`migrate-forge`** est le seul skill qui écrit sous `docs/` (pour l'en vider) — c'est son objet.
