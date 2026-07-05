# Métadonnées de story (`metadata.json`)

Référence partagée par tous les skills qui écrivent dans un dossier de story
(`/feature-pitch`, `/feature-plan`, `/refactor-plan`, `/tech-plan`, les trois `*-implem`,
`/report`, `/sync`, `/adr`, `/estimate`, `/review`, `/commit`, `/release`). Elle décrit le
**contrat unique** du fichier de métadonnées d'une story : son schéma, ses formats, et la
**procédure d'écriture/mise à jour** à appliquer.

Objectif : le Forge Board (app de visualisation) lit ce fichier pour afficher des cartes
riches (vrai titre, âge, dernière activité, tags, badge de livraison), sans jamais l'écrire.
**La source de vérité est ce fichier, produit par les skills.** L'app ne fait que lire.

## Emplacement et nom

Un fichier **`metadata.json`** à la racine du dossier de la story :

```
docs/story/NNN-<f|r|t>-<slug>/metadata.json
```

Un seul par story, quel que soit le track (`f`, `r`, `t`). Il cohabite avec `pitch.md`,
`plan.md`, `report.md`, etc. Il n'est **jamais** compté comme un document de pipeline : le
Board déduit l'étape des seuls `.md` (`pitch`/`plan`/`review`/`report`), `metadata.json` est
ignoré par ce calcul. Ne le référence pas comme un livrable dans les autres documents.

## Schéma v1 (figé)

Le schéma est **embarqué chez tous les utilisateurs forge** : il doit rester stable. Toute
évolution incrémente `version` et reste rétrocompatible côté lecture.

```json
{
  "version": 1,
  "title": "Afficher le kanban d'un projet",
  "created": "2026-07-01",
  "updated": "2026-07-05",
  "tags": ["board", "kanban"],
  "changelog": [
    { "date": "2026-07-01", "type": "Création", "description": "Pitch initial rédigé." },
    { "date": "2026-07-03", "type": "Planification", "description": "Plan technique validé." }
  ],
  "delivery": { "release": "v4.3.0", "commit": "b7964b4" }
}
```

| Champ       | Type        | Règle                                                                                     |
|-------------|-------------|-------------------------------------------------------------------------------------------|
| `version`   | entier      | Version du schéma. **`1`** au lancement. Ne pas omettre.                                   |
| `title`     | chaîne      | Le **vrai titre** de la story (le `# H1` du document principal), pas le slug.              |
| `created`   | `YYYY-MM-DD`| Date de création. **Écrite une seule fois**, jamais modifiée ensuite.                      |
| `updated`   | `YYYY-MM-DD`| Date de la **dernière** passe d'un skill sur la story. Rebougée à chaque écriture.         |
| `tags`      | liste       | Étiquettes **kebab-case**, proposées par le skill, **validées par l'utilisateur**. Dédupliquées. |
| `changelog` | liste       | Timeline consolidée, ordre chronologique. Chaque entrée : `{ date, type, description }`.   |
| `delivery`  | objet/absent| `{ release, commit }`. `release` et `commit` peuvent être `null`. Absent tant que non livrée. |

**Formats** : dates toujours `YYYY-MM-DD` (aligné sur les changelogs existants). `type` d'une
entrée de changelog = un mot court capitalisé (`Création`, `Planification`, `Implémentation`,
`Review`, `Report`, `Sync`, `ADR`, `Estimation`, `Livraison`…). `description` = une phrase.

## Procédure d'écriture

À chaque passe d'un skill concerné, **avant de terminer** :

1. **Lis** `metadata.json` s'il existe (`Read` sur le chemin ci-dessus).
   - Absent → tu es en création (voir plus bas).
   - Présent → charge l'objet, tu vas le **fusionner** (jamais l'écraser en aveugle).
2. **Détermine la date du jour** au format `YYYY-MM-DD`. Si tu n'as pas de date fiable en
   contexte, demande-la ou déduis-la de l'environnement — ne l'invente pas.
3. **Applique les mutations propres à ton skill** (tableau ci-dessous).
4. **Réécris le fichier complet** (`Write`), JSON indenté 2 espaces, `version: 1`.

### Qui écrit quoi

| Skill / moment                                              | Mutations                                                                                   |
|------------------------------------------------------------|---------------------------------------------------------------------------------------------|
| **Création** (`feature-pitch` pour `f` ; `refactor-plan` / `tech-plan` pour `r`/`t`) | Crée le fichier : `version:1`, `title` (le H1 réel), `created` = aujourd'hui, `updated` = aujourd'hui, `tags` (proposés puis **validés** par l'utilisateur), `changelog` = une 1re entrée `Création`, `delivery` absent. |
| **Toute passe ultérieure** (`feature-plan`, `refactor-plan`/`tech-plan` en édition, `*-implem`, `report`, `sync`, `adr`, `estimate`, `review`) | Rebouge `updated` = aujourd'hui ; **append** une entrée de changelog (`type` = nature de la passe, `description` = ce qui a changé). Ne touche pas `created`. Peut affiner `tags` (toujours validés). |
| **`commit`**                                               | Renseigne `delivery.commit` = SHA court du commit de clôture. Rebouge `updated` + entrée `Livraison`. Si `delivery` absent, le crée avec `release: null`. |
| **`release`**                                              | Renseigne `delivery.release` = tag de version (ex. `v4.3.0`) sur la/les story(s) livrées par la release. Peut arriver **après** le commit : édite le `metadata.json` existant pour compléter `release` sans toucher `commit`. Rebouge `updated` + entrée `Release`. |

**Release en différé** : le tag arrive parfois après la livraison. `release` relit chaque
`metadata.json` concerné et complète `delivery.release` ; un `commit` présent sans `release`
est un état **valide** (la carte affiche « livré » sans numéro de version).

### Tags : proposés, validés

Le skill de cadrage **propose** des tags déduits du contenu (domaine, thème), en kebab-case.
L'utilisateur **tranche** à la rédaction (garde-fou anti-dérive : pas d'étiquette non validée).
Pas de taxonomie imposée au MVP — la validation utilisateur est le seul garde-fou.

## Changelog : source unique

La timeline vit **uniquement** dans `metadata.json`. Les anciennes tables de changelog en pied
de `pitch.md` / `plan.md` sont **abandonnées** : ne les produis plus, n'y append plus. `/sync`
qui documentait une divergence écrit désormais une entrée dans le changelog du `metadata.json`.

## Chemin d'accès (installation utilisateur)

Le fichier vit dans le **projet** de l'utilisateur (`docs/story/…`), pas dans le plugin. Tu y
accèdes par un chemin **relatif au projet** (`docs/story/NNN-<f|r|t>-<slug>/metadata.json`),
contrairement aux références du plugin qui, elles, se lisent via `${CLAUDE_SKILL_DIR}`.

## Dégradation (rappel côté app)

Une story **sans** `metadata.json`, ou avec un fichier **malformé**, s'affiche quand même : la
carte retombe sur le slug humanisé, sans tags/dates/badge. Mieux vaut pas de donnée qu'une
donnée fausse — d'où l'exigence de n'écrire que des valeurs vraies (surtout `updated`).
