# Format des documents (charte partagée)

Référence partagée par tous les skills qui **produisent un document de story**
(`/feature-interview`, `/feature-pitch`, `/feature-plan`, `/refactor-plan`, `/tech-plan`,
`/review`, `/report`, `/estimate`). Elle décrit le **contrat de format commun** : à quoi sert
chaque document, dans quel registre il écrit, comment il s'ouvre, et quel nom porte chaque
section.

Objectif : un lecteur qui a lu un document forge sait lire tous les autres. Un skill qui
consomme un document en amont (`/feature-plan` lit le pitch, `/report` lit le plan, `/sync`
lit le report) trouve les sections **au nom et au format attendus**, sans deviner.

Le pendant de cette charte est [`story-metadata.md`](story-metadata.md) : elle régit le
**contenu** des `.md`, l'autre régit la **timeline** dans `metadata.json`. Les deux se lisent
via `${CLAUDE_SKILL_DIR}/../../references/`.

## Sommaire

- [1. Matrice des documents](#1-matrice-des-documents)
- [2. En-tête normalisé](#2-en-tête-normalisé)
- [3. Registres : la règle d'or de chaque document](#3-registres--la-règle-dor-de-chaque-document)
- [4. Vocabulaire canonique des sections](#4-vocabulaire-canonique-des-sections)
- [5. Squelette commun des plans](#5-squelette-commun-des-plans)
- [6. Formats partagés](#6-formats-partagés)
- [7. Criticité et tags](#7-criticité-et-tags)
- [8. Verdicts](#8-verdicts)
- [9. Ce qui ne va PAS dans un document](#9-ce-qui-ne-va-pas-dans-un-document)
- [10. Conventions d'écriture des templates](#10-conventions-décriture-des-templates)

## 1. Matrice des documents

Chaque document sert **un seul but** et répond à **une seule question**. Le format sert ce
but : une section qui ne sert pas la question du document n'a rien à y faire — elle appartient
à un autre document de la chaîne.

| Document | Fichier | Registre | Répond à | Produit par | Consommé par |
|---|---|---|---|---|---|
| **Brief** | `brief.md` | fonctionnel | De quoi parle-t-on ? | `/feature-interview` | `/feature-pitch` |
| **Pitch** | `pitch.md` | fonctionnel | Quoi livrer ? | `/feature-pitch` | `/feature-plan`, `/estimate`, `/sync` |
| **Plan** | `plan.md` | technique | Comment livrer ? | `/feature-plan`, `/refactor-plan`, `/tech-plan` | `/*-implem`, `/review`, `/report`, `/estimate` |
| **Review** | `review.md` | technique | On commite ou pas ? | `/review` | humain, `/report` |
| **Report** | `report.md` | factuel | Quel écart, quelle dette ? | `/report` | `/sync`, humain |
| **Estimation** | `estimate.md` | économique | Combien de temps ? | `/estimate` | humain (facturation) |

Conséquences de format, à appliquer sans exception :

- **Les documents de décision** (`review`, `report`, `estimate`) **ouvrent sur leur conclusion**
  — première section `## Synthèse`. On doit pouvoir ne lire qu'elle.
- **Les documents d'intention** (`pitch`, `plan`) ouvrent sur leur objet et **ferment sur leurs
  inconnues** — dernière section `## Questions ouvertes`.
- **Le brief** n'est pas un document d'intention : c'est de la matière première non validée. Il
  le dit dans son en-tête et ne porte ni critères d'acceptation, ni règles métier arrêtées.

## 2. En-tête normalisé

Tout document de story ouvre sur le **même bloc**, dans **cet ordre**. Contrairement aux guides
`> _Skill : …_`, ce bloc **reste dans le fichier commité** : un document livré doit dire à quoi
il sert et d'où il vient.

```markdown
# <Type> — <Titre réel de la story>

> **But** : <une ligne — ce que ce document décide ou apporte, du point de vue du lecteur>
> **Registre** : <fonctionnel | technique | factuel | économique>
> **Story** : `docs/story/<NNN>-<f|r|t>-<slug>/`
> **Amont** : `pitch.md` · `plan.md` <!-- les documents dont celui-ci dépend, ou « aucun » -->
```

Règles :

- **`<Type>`** = `Brief` · `Pitch` · `Plan technique` · `Review` · `Report` · `Estimation`.
  Le `Pitch` fait exception : son H1 est le titre seul (il **est** l'énoncé de la feature).
- **`<Titre réel>`** = une phrase qui dit ce que la story change, jamais un nom de ticket ni un
  slug. C'est ce titre qui alimente `title` dans `metadata.json`.
- **Amont** liste les documents dont celui-ci dépend, pas ceux qui le consomment. Un `plan.md`
  de refacto ou de tech n'a pas de pitch : il écrit « aucun ».

**Ce qui ne va PAS dans l'en-tête** — trois champs sont bannis, ils ont déjà une source de vérité :

| Banni | Pourquoi | Où ça vit |
|---|---|---|
| `Date : YYYY-MM-DD` | Dupliqué et vite faux | `metadata.json` (`created` / `updated`) |
| `Commits liés : <SHA>` | Dupliqué et vite faux | `metadata.json` (`delivery.commit`) |
| `Stack : symfony` | Le plugin est stack-agnostique | Détection (`references/stacks/_detection.md`) |

Un lien `ADR : docs/adr/<NNNN>-<slug>.md` est en revanche **autorisé en fin de bloc** quand une
ADR est rattachée : c'est un lien de navigation, pas une métadonnée dupliquée.

## 3. Registres : la règle d'or de chaque document

Le registre n'est pas une nuance de ton : c'est une **contrainte de contenu vérifiable**. Il est
annoncé dans l'en-tête et tenu dans tout le fichier.

**Registre fonctionnel** (`brief`, `pitch`) — le document décrit ce que vit l'utilisateur.
Interdits : nom de classe, d'entité, de service, de fichier, de table, de framework, de
bibliothèque. Toute trouvaille technique se **traduit en capacité vécue** avant d'entrer
(« `class Cart` » → « le client peut remplir un panier »). Un lecteur non technicien doit
pouvoir tout lire.

**Registre technique** (`plan`, `review`) — le document décrit le code. Il ne re-justifie pas le
besoin métier : pour une feature, le pourquoi vit dans le pitch et le plan y renvoie. Pour un
refacto ou une évolution technique il n'y a pas de pitch, donc le plan porte sa `## Motivation`
— mais en termes techniques (dette, perf, blocage), pas en termes de valeur métier.

**Registre factuel** (`report`) — le document constate. Il compare l'intention au livré et ne
défend rien. Pas de projet, pas d'opinion : ce qui est, ce qui manque, ce qui a dérivé, avec la
raison.

**Registre économique** (`estimate`) — le document chiffre du **temps**, jamais un montant.

**La seule passerelle autorisée entre registres** est explicitement étiquetée comme telle : la
section `## Annexe — Pistes pour le plan` du pitch (§4) est une **annexe non contractuelle**.
Elle est la seule zone d'un document fonctionnel où un nom technique peut apparaître, et elle le
dit.

**Exemption — quand le logiciel est le domaine métier.** Certains produits ont pour objet des
artefacts techniques : un outil de développement, un CI, un explorateur de logs, le Forge Board
lui-même. Un terme comme `pitch.md`, `docs/story/` ou « dépôt Git » y est le **vocabulaire
métier de l'utilisateur**, pas une fuite de registre : le traduire en « capacité vécue » le
dénaturerait. Le test qui tranche n'est pas « est-ce que ça ressemble à du technique ? » mais :

> **L'utilisateur emploierait-il ce mot pour décrire son besoin ?**

Si oui, il est fonctionnel, quelle que soit son apparence (« je veux voir mes stories du dépôt »).
Si non, c'est une fuite, même déguisée en métier (« le `StoryStageMapper` ignore le fichier de
métadonnées » — l'utilisateur dit « une story sans métadonnées reste affichée »). Un nom de
classe, de service ou de champ de base ne passe **jamais** ce test : personne ne formule son
besoin en `StoryStageMapper`. Un nom de fichier que l'utilisateur ouvre et lit, si.

## 4. Vocabulaire canonique des sections

Un concept = **un seul titre**, partout où il apparaît. Ce tableau tranche les synonymes
historiques ; toute nouvelle section doit s'y raccrocher ou y être ajoutée.

| Concept | Titre canonique | Où | Format |
|---|---|---|---|
| Conclusion d'un document de décision | `## Synthèse` | review, report, estimate | prose courte + puces |
| Pourquoi maintenant | `## Motivation` | plan `-r-` / `-t-` | prose + un chiffre vérifiable |
| Contexte métier | `## Contexte` | pitch | prose |
| Alignement à la vision projet | `## Alignement vision` | pitch (conditionnel : si `docs/vision.md` existe) | puces |
| Solution choisie | `## Approche retenue` | les 3 plans | prose + sous-sections |
| Options rejetées | `### Alternatives écartées` | sous `## Approche retenue` | table |
| Patterns/briques mobilisés | `### Mécanismes mobilisés` | sous `## Approche retenue` | puces |
| Impact persistance | `## Modèle de données` | les 3 plans (conditionnel) | table par entité |
| Fichiers touchés | `## Périmètre` → `### Fichiers à créer` / `### Fichiers à modifier` | les 3 plans | 2 tables |
| Ce qu'on ne fait pas | `## Hors scope` | pitch + les 3 plans | puces |
| Axes systémiques | `## Impacts transverses` | pitch (fonctionnel) + les 3 plans (technique) | puces |
| Contrat de test | `## Stratégie de test` | les 3 plans | table + « Hors scope tests » |
| Séquence de travail | `## Ordre d'exécution` | les 3 plans | checklist d'étapes |
| Sortie observable côté utilisateur | `## Critères d'acceptation` | pitch, report (`-f-`) | checkboxes |
| Sortie vérifiable côté technique | `## Critères de sortie` | les 3 plans, report (`-r-`/`-t-`) | checkboxes |
| Risques | `## Risques et mitigations` | les 3 plans | table |
| Inconnues | `## Questions ouvertes` | pitch + les 3 plans | puces |

Renommages actés (l'ancien titre ne doit plus être produit) :

| Ancien titre | Titre canonique | Où |
|---|---|---|
| `Problème adressé` | `Motivation` | tech-plan |
| `Brique retenue` · `Cible` | `Approche retenue` | tech-plan · refactor-plan |
| `Pattern de refacto` | `Mécanismes mobilisés` | refactor-plan |
| `Mécanismes framework mobilisés` | `Mécanismes mobilisés` | feature-plan |
| `Point d'intégration` | réparti : `Approche retenue` + `Périmètre` | tech-plan |
| `Entités et modèle de données` | `Modèle de données` | feature-plan |
| `Code visé` | `Périmètre` (2 tables canoniques) | refactor-plan |
| `Ordre d'implémentation` · `Stratégie d'exécution incrémentale` · `Plan d'exécution incrémental` | `Ordre d'exécution` | les 3 plans |
| `Critères de réussite` · `Critères de succès mesurables` | `Critères de sortie` | refactor-plan · tech-plan |
| `Résumé` | `Synthèse` | report |
| `Ce qui a été implémenté` | `Périmètre livré` | report |
| `Notes pour le plan technique` | `Pistes pour le plan` | pitch |
| `Périmètre : <N fichiers du diff>` (en-tête review) | `Diff examiné` | review |
| `Référence d'intention` (en-tête review) | `Amont` | review |

Le dernier renommage lève une **collision de vocabulaire** : `Périmètre` désigne les fichiers
qu'un plan prévoit de toucher ; le diff qu'une review examine s'appelle `Diff examiné`.

## 5. Squelette commun des plans

Les trois plans (`-f-`, `-r-`, `-t-`) partagent **le même squelette, dans cet ordre**. Les
différences de track ne sont pas des dialectes : ce sont des **sections additionnelles**,
explicitement rattachées à leur track.

| # | Section | `-f-` | `-r-` | `-t-` |
|---|---|:---:|:---:|:---:|
| 1 | `## Motivation` | — (dans le pitch) | ✅ | ✅ |
| 2 | `## Approche retenue` | ✅ | ✅ | ✅ |
| 2a | `### Forme cible` | — | ✅ | — |
| 2b | `### Mécanismes mobilisés` | ✅ | ✅ | ✅ |
| 2c | `### Alternatives écartées` | ✅ | ✅ | ✅ |
| 3 | `## Modèle de données` | conditionnel | conditionnel | conditionnel |
| 4 | `## Périmètre` (créer / modifier) | ✅ | ✅ | ✅ |
| 4a | `### Clients identifiés` | — | ✅ | — |
| 5 | `## Hors scope` | ✅ | ✅ | ✅ |
| 6 | `## Impacts transverses` | ✅ | ✅ | ✅ |
| 7 | `## Comportement externe à préserver` | — | ✅ | — |
| 8 | `## Rollback et kill switch` | — | — | ✅ |
| 9 | `## Métriques (baseline → cible)` | — | — | ✅ |
| 10 | `## Stratégie de test` | ✅ | ✅ | ✅ |
| 10a | `### Tests existants utilisés comme filet` | — | ✅ | — |
| 10b | `### Tests de caractérisation` | — | ✅ | — |
| 11 | `## Ordre d'exécution` | ✅ | ✅ | ✅ |
| 12 | `## Critères de sortie` | ✅ | ✅ | ✅ |
| 13 | `## Risques et mitigations` | ✅ | ✅ | ✅ |
| 14 | `## Questions ouvertes` | ✅ | ✅ | ✅ |

Notes de lecture :

- **`## Motivation` sur `-f-`** : absente, le pourquoi vit dans `pitch.md` §Contexte. Le plan
  n'y renvoie que par l'`Amont` de son en-tête — pas de résumé du pitch, pas de duplication.
- **`## Modèle de données`** est *conditionnelle* : présente si la story crée ou modifie une
  structure persistante, sinon **remplacée par la phrase** « Aucun impact modèle. » Ne pas
  supprimer le titre en silence : une section absente est indistinguable d'un oubli.
- **`## Impacts transverses` sur `-r-`** : un refacto n'a normalement que des « inchangé » — et
  c'est précisément l'information utile. La section reste, ses items disent « inchangé ».
- Une section canonique **sans contenu se remplit d'un « non » explicite ou d'un `_(aucun)_`**,
  jamais d'un vide. C'est ce qui distingue « vérifié, rien à dire » de « pas regardé ».

## 6. Formats partagés

Les tables ci-dessous sont **normatives** : mêmes colonnes, même ordre, dans tous les documents
qui les portent. C'est ce qui permet à `/report` de confronter le livré au plan ligne à ligne,
quel que soit le track.

### Périmètre — fichiers à créer

| Fichier | Rôle |
|---|---|
| `<chemin>` | `<rôle en une phrase>` |

### Périmètre — fichiers à modifier

| Fichier | Modification |
|---|---|
| `<chemin>` | `<diff conceptuel : « remplacer X par Y », « ajouter la relation inverse »>` |

Le `report.md` reprend ces deux tables **en y ajoutant une colonne finale** `Prévu dans le plan`
(`Oui` / `Non (ajout)` / `Écart volontaire (cf. §)`). Aucune autre divergence de colonnes n'est
admise : c'est le point de jonction de la chaîne `plan → report`.

### Risques

| Risque | Probabilité | Mitigation |
|---|---|---|
| `<risque>` | faible / moyenne / élevée | `<mitigation concrète>` |

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| `<option>` | `<raison en une phrase>` |

### Stratégie de test

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `<chemin>` | unit / functional / E2E | `<cas nominaux + cas d'erreur>` |

Suivie d'un bloc **« Hors scope tests »** qui liste ce qu'on assume de ne pas couvrir, avec la
raison. Un plan sans hors-scope de test explicite est un plan qui n'a pas tranché.

### Étape de l'ordre d'exécution

Structure unique pour les trois tracks. Une étape = une unité commitable.

```markdown
1. [ ] **<Nom de l'étape>**
   - Objectif : <résultat attendu>.
   - Fichiers : <créés / modifiés>.
   - Vérification : <commande ou critère observable>.
   - Commitable seule : oui/non.
```

### Critères (acceptation et sortie)

Checkboxes `- [ ]`, cochées à la livraison, **une par ligne, observable ou mesurable**. Un
critère qu'on ne sait pas vérifier n'est pas un critère : c'est une intention, elle va en
`## Questions ouvertes`. Le `report.md` **reprend ces lignes à l'identique** et les coche — d'où
l'exigence de stabilité de leur formulation.

## 7. Criticité et tags

Catalogue partagé par `/review` (qui émet les findings) et `/report` (qui reprend les non
traités en dette). Un finding = un niveau + un tag + un emplacement.

**Niveaux** (sections de `review.md`, dans cet ordre) :

| Niveau | Définition |
|---|---|
| **Bloquants** | Empêchent le commit. Bug, faille, régression mesurable, divergence comportementale avec le plan. |
| **Importants** | À corriger avant commit, sauf arbitrage explicite de l'utilisateur. |
| **Mineurs** | Améliorations utiles, non bloquantes. Non traités → dette du `report.md`. |

**Tags** (catalogue fermé — ne pas en inventer) :

| Tag | Ce qu'il désigne |
|---|---|
| `BUG` | Comportement incorrect démontrable. |
| `SECU` | Faille, fuite de données, contrôle d'accès manquant, isolation cassée. |
| `PLAN` | Écart avec l'intention (`plan.md` / `pitch.md`) non justifié. |
| `ARCHI` | Violation de l'architecture ou des couches du projet ; duplication, abstraction manquante ou de trop. |
| `MIGRATION` | Migration non réversible, destructrice ou non testée. |
| `TEST` | Test manquant, faux, ou qui ne teste pas ce qu'il prétend. |
| `PERF` | Régression de performance ou requête pathologique. |
| `CONV` | Non-respect d'une convention du projet (`CLAUDE.md`, stack). |
| `ROBUSTESSE` | Cas limite non géré, erreur avalée, état incohérent possible. |
| `I18N` | Libellé en dur, traduction manquante. |
| `UX` | Parcours ou affordance qui dessert l'utilisateur, sans être un bug. |
| `A11Y` | Accessibilité : contraste, navigation clavier, rôle ARIA, alternative textuelle. |
| `DOC` | Documentation absente ou fausse. |
| `STYLE` | Lisibilité, nommage, formatage. |

Le catalogue est **fermé** : il couvre ce que les reviews trouvent réellement, et se lit d'un
coup d'œil — deux propriétés qu'une liste ouverte perd immédiatement. Un finding qui semble
n'entrer nulle part entre presque toujours dans `ARCHI` (duplication, `DRY`), `PLAN` (dérive de
périmètre, `SCOPE`) ou `ROBUSTESSE`. S'il résiste vraiment, c'est que le catalogue a un trou :
l'ajouter **ici**, dans la charte, plutôt qu'inventer un tag dans un `review.md` — un tag qui ne
vit que dans un document est invisible pour le `report.md` qui le reprendra.

**Format d'un finding**, identique dans `review.md` et dans la dette du `report.md` :

```markdown
- [ ] **[TAG] <résumé en une phrase>** — `<chemin>:<ligne>` — <pourquoi + correctif appliqué OU action requise>.
```

La case cochée signifie **corrigé pendant la passe**, pas « lu ».

## 8. Verdicts

Le corpus forge est en français, verdicts compris. Valeurs **fermées** :

| Document | Champ | Valeurs |
|---|---|---|
| `review.md` | `Statut` | **PRÊT À COMMITER** · **PRÊT À COMMITER SOUS RÉSERVE** (préciser laquelle) · **CORRECTIONS REQUISES** |
| `report.md` | `Conformité` | un pourcentage + les écarts structurants (3 max) |
| `estimate.md` | `Total à retenir` | des heures, jamais un montant |

`READY TO COMMIT` et `CHANGES REQUESTED` ne sont plus produits.

## 9. Ce qui ne va PAS dans un document

- **Une table de changelog en pied de fichier.** La timeline vit **uniquement** dans
  `metadata.json` (voir [`story-metadata.md`](story-metadata.md) §Changelog). Ne pas en produire,
  ne pas en alimenter. *(Exception hors story : `vision.md`, `stack.md` et `product-backlog.md`
  n'ont pas de `metadata.json` — ils gardent leur changelog.)*
- **Un nom de stack en dur.** Ni dans l'en-tête, ni dans les exemples des templates. Les
  mécanismes, commandes QA et conventions viennent de la détection
  (`references/stacks/_detection.md` puis `references/stacks/<stack>.md`) et du `CLAUDE.md` du
  projet. Un template écrit `<mécanisme du stack détecté>`, jamais `EntityListener`.
- **Une métadonnée dupliquée** (date, SHA, tag de release) : elle diverge, et la copie fait foi
  à tort. Lien vers la story, et `metadata.json` tranche.
- **Un `metadata.json` cité comme livrable** dans une table de périmètre : le Board l'ignore
  dans le calcul d'étape, les documents ne le référencent pas comme un artifact.

## 10. Conventions d'écriture des templates

Ces conventions concernent les `references/template.md` des skills, pas les documents produits.

- **`<!-- guide: … -->`** en tête : rappel de destination et règles du fichier. **Retiré** à la
  rédaction.
- **`> _Skill : …_`** sous chaque titre : la consigne de remplissage de la section. **Retirée**
  à la rédaction.
- **`<placeholder>`** entre chevrons : à remplacer intégralement. Un chevron restant dans un
  document commité est un bug.
- **L'en-tête normalisé (§2), lui, reste.** C'est le seul bloc `>` conservé.
- Les titres de sections **suivent le vocabulaire canonique (§4) au caractère près** : les skills
  avals les cherchent par leur nom.
