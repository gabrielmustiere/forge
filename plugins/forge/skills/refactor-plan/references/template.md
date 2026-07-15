# Plan technique — <Titre du refacto : ce qui change structurellement>

> **But** : figer le comment d'un refacto — forme cible, verrou de non-régression, exécution incrémentale.
> **Registre** : technique
> **Story** : `docs/story/<NNN>-r-<slug>/`
> **Amont** : aucun <!-- guide: un refacto n'a pas de pitch — ce plan porte motivation ET détail -->
> **ADR** : `docs/adr/<NNNN>-<slug>.md` <!-- guide: ligne à supprimer si aucune ADR n'est rattachée -->

<!--
guide: Plan d'un refacto (préfixe `-r-`). Produit par `/forge:refactor-plan`, consommé par `/forge:refactor-implem` (étape build), `/forge:review`, `/forge:report` et `/forge:estimate`.
Format commun à tous les documents de story : voir la charte `${CLAUDE_SKILL_DIR}/../../references/document-format.md`. Les trois plans (`-f-`/`-r-`/`-t-`) partagent le squelette de la charte §5 — ne pas réordonner ni renommer les sections. Les sections propres au track `-r-` (Forme cible, Clients identifiés, Comportement externe à préserver, Tests de caractérisation) sont additionnelles et rattachées aux sections canoniques qu'elles étendent.
Un refacto ne change PAS le comportement externe. Si du métier change, c'est une feature. Si c'est l'infra ou un composant technique transverse, c'est une évolution technique (`-t-`).
Pas de pitch pour un refacto : la `## Motivation` de ce plan porte le pourquoi — mais en termes techniques (dette, perf, blocage), pas en valeur métier (charte §3).
STACK-AGNOSTIQUE (charte §9) : aucun nom de framework en dur. Mécanismes, commandes QA et seuils viennent de la détection (`references/stacks/_detection.md` → `references/stacks/<stack>.md`) et du `CLAUDE.md` du projet.
L'en-tête ci-dessus RESTE dans le fichier commité. Retirer ce bloc et tous les `> _Skill : ..._` avant commit.
-->

## Motivation

> _Skill : pourquoi refactorer maintenant. État actuel **chiffré** quand c'est possible (lignes, durée mesurée, occurrences, complexité) — au moins un chiffre vérifiable. Conséquences si on ne fait rien : la dette s'aggrave-t-elle ? Y a-t-il un déclencheur (perf, sécurité, prochaine feature bloquée) ? Registre technique : pas de justification en valeur métier._

<État actuel + friction concrète. Au moins un chiffre vérifiable (ex : 1 807 ms cumulés, 80 occurrences résiduelles).>

<Conséquences si on ne fait rien.>

## Approche retenue

> _Skill : 1–2 paragraphes sur la transformation choisie, en termes architecturaux. Quelle couche apparaît, laquelle disparaît, comment les appelants basculent._

<Transformation retenue en 1–2 paragraphes.>

### Forme cible

> _Skill : section propre au track `-r-`. La structure CIBLE, telle qu'elle existera après. Arborescence si une nouvelle couche apparaît, interface(s) clé(s), contrat. Dire comment les anciens appelants consomment la nouvelle forme._

<Description de la forme cible.>

```
<arborescence cible>
```

```
<signature de l'interface principale, dans le langage du projet>
```

### Mécanismes mobilisés

> _Skill : nommer le pattern de refacto (Strangler Fig, extraction de classe, déplacement de méthode, objet-paramètre…) et les briques du stack mobilisées pour l'appliquer. Justifier pourquoi ce pattern convient à cette situation. Si pas de pattern formel (« renommage + extraction »), le dire — c'est une réponse valide._

- **<Pattern de refacto>** : <justification en 1–2 phrases>.
- **`<Mécanisme du stack>`** : <usage + justification courte>.

### Alternatives écartées

> _Skill : 2–4 options rejetées avec la raison. Sans ce bloc, la review reposera la question. Format de table normatif (charte §6)._

| Alternative | Pourquoi écartée |
|---|---|
| <Alternative A> | <raison en une phrase> |
| <Alternative B> | <…> |

## Modèle de données

> _Skill : section **conditionnelle** (charte §5). Un refacto touche rarement la persistance — dans ce cas, garder le titre et écrire « Aucun impact modèle. ». S'il y a un impact (renommage de table, index déplacé), le décrire ici : c'est un signal fort pour la review, et ça ne doit pas se découvrir dans le diff._

Aucun impact modèle.

## Périmètre

> _Skill : tables exhaustives, format normatif (charte §6) — point de jonction avec `report.md`, qui les reprend en ajoutant une colonne « Prévu dans le plan ». Un reviewer doit pouvoir vérifier qu'aucun fichier n'a été oublié. La taille des fichiers touchés (utile pour un refacto) va dans la colonne « Modification », pas dans une colonne en plus._

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `<chemin>` | <rôle en 1 phrase — souvent : la couche extraite> |
| `<chemin de test>` | <cas couverts> |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `<chemin>` | <verbe d'action : extraire / éclater / retirer — + volume si utile (ex: « ~800 lignes → 3 classes »)> |
| `<chemin>` | <…> |

### Clients identifiés

> _Skill : section propre au track `-r-`. Tout ce qui consomme le code visé, par catégorie (vues, tests, configuration, scénarios E2E, scripts). Indiquer pour chaque catégorie si elle est affectée **ou non** : un « aucun client affecté » explicite est un gage de sécurité, une absence d'item est un trou._

- <Vues / templates> — <impacté ou non + raison>.
- <Tests fonctionnels> — <impacté ou non>.
- <Tests E2E> — <impacté ou non>.
- <Configuration> — <impacté ou non>.

## Hors scope

> _Skill : ce qui pourrait être absorbé dans ce refacto mais ne l'est pas. Préserve le périmètre face à la review — un refacto grossit toujours par les bords. Mettre `_(aucun)_` plutôt que de supprimer la section._

- **<Sujet>** : <raison brève (ex: refacto séparé, métier hors structurel)>.

## Impacts transverses

> _Skill : un refacto n'a normalement que des « inchangé » — et c'est précisément l'information utile (charte §5). Passer les axes en revue et le dire explicitement ; tout axe qui n'est PAS « inchangé » mérite un examen : est-ce encore un refacto ?_

- **Cloisonnement des données** : <inchangé / si non : pourquoi>.
- **Déclinaisons / thèmes** : <inchangé>.
- **Traduction / i18n** : <inchangé>.
- **API / exposition externe** : <inchangé>.
- **Droits d'accès** : <inchangé — mécanismes de contrôle répliqués à l'identique>.
- **Emails / notifications** : <inchangé — mêmes déclencheurs>.
- **Migration de données** : <aucune / nature si exception>.

## Comportement externe à préserver

> _Skill : section propre au track `-r-`, et son cœur. Les invariants observables que le refacto ne doit PAS changer : URLs, noms de routes, signatures publiques, statuts et formats de réponse, effets de bord (envois, événements, journaux d'audit), vues rendues, messages utilisateur, chaîne de cloisonnement. Pour chacun, dire explicitement « inchangé ». Cette liste se consulte à CHAQUE étape de l'exécution._

- **URLs** : <tous les chemins préservés / liste des changements si non>.
- **Noms de routes** : <tous préservés / liste sinon>.
- **Signatures publiques** : <…>.
- **Droits d'accès** : <contrôles répliqués à l'identique>.
- **Cloisonnement** : <chaîne inchangée>.
- **Effets de bord** : <envois, journaux, événements — déclenchés aux mêmes endroits>.
- **Vues rendues** : <mêmes chemins, mêmes variables>.
- **Messages utilisateur** : <mêmes clés, mêmes contenus>.

## Stratégie de test

> _Skill : table normative « code → type → ce qu'on vérifie » (charte §6). Pour un refacto, elle se lit avec la sous-section suivante : ici les tests **existants** qui servent de filet, en dessous ceux qu'il faut **écrire avant** de toucher au code._

### Tests existants utilisés comme filet

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `<chemin de test>` | functional | <comportement couvert> |
| `<chemin de test>` | unit | <…> |

### Tests de caractérisation

> _Skill : section propre au track `-r-`. Les tests à écrire **AVANT** de toucher au code de production, pour verrouiller le comportement actuel. **Règle absolue** : aucun code de production touché tant que ces tests ne sont pas écrits, verts et committés. Si la décision est « pas de caractérisation supplémentaire » (refacto purement structurel à risque faible), garder le titre et l'expliciter avec le risque accepté — ne pas supprimer la section._

| Test à créer | Comportement à verrouiller | Type |
|---|---|---|
| `<chemin>` | <invariant> | functional |
| `<chemin de fixture>` | <capture de sortie de référence> | fixture |

**Règle absolue** : aucun code de production touché tant que ces tests ne sont pas écrits, verts et committés.

**Hors scope tests** :

- <ex: pas de caractérisation sur la zone X — non touchée par le refacto, filet existant suffisant>.

## Ordre d'exécution

> _Skill : étapes commitables, chacune déployable seule (revert atomique). Structure d'étape normative (charte §6). L'étape 1 est presque toujours « écrire les tests de caractérisation ». Statuer explicitement sur la coexistence ancien/nouveau dans l'étape concernée (Strangler Fig actif entre les étapes X et Y derrière l'interface I, ou « pas de coexistence : chaque étape est elle-même un toggle par revert »)._

1. [ ] **<Nom de l'étape — ex: tests de caractérisation>**
   - Objectif : <verrouiller le comportement actuel avant toute modification>.
   - Fichiers : <créés>.
   - Vérification : <suite verte + tests committés>.
   - Commitable seule : oui.

2. [ ] **<Nom de l'étape — ex: extraction de la nouvelle couche, ancien code intact>**
   - Objectif : <résultat attendu>.
   - Fichiers : <créés / modifiés>.
   - Vérification : <caractérisation verte + critère mesurable>.
   - Commitable seule : oui/non.

3. [ ] **<Nom de l'étape — ex: bascule des appelants>**
   - <…>

4. [ ] **<Nom de l'étape — ex: retrait de l'ancien code>**
   - <…>

## Critères de sortie

> _Skill : checkboxes **mesurables**, pas qualitatives (charte §4). Comportement préservé, perf mesurée si applicable, tests verts, qualité. Les commandes QA et les seuils viennent du stack détecté et du `CLAUDE.md` du projet — ne rien inventer._

- [ ] Tous les tests de caractérisation passent **avant ET après** chaque étape.
- [ ] Suite complète du projet verte, sans nouvelle régression.
- [ ] <Critère structurel : ex « 0 occurrence résiduelle de `<pattern>`, mesurée par `<commande de recherche>` »>.
- [ ] <Critère de perf le cas échéant : ex « <mesure> < <cible> (vs <baseline> aujourd'hui) »>.
- [ ] Chaque étape committée est déployable seule (revert atomique possible).
- [ ] Analyse statique et style conformes aux exigences du projet.

## Risques et mitigations

> _Skill : table normative (charte §6). Couvrir au minimum : cloisonnement si le code touche au filtrage, casse d'API publique, divergence comportementale subtile (valeur par défaut, conversion de type, null), perf inattendue, dépendance non vérifiée._

| Risque | Probabilité | Mitigation |
|---|---|---|
| <Risque 1> | faible / moyenne / élevée | <mitigation concrète> |
| <Risque 2> | <…> | <…> |

## Questions ouvertes

> _Skill : décisions non prises. À trancher en design détaillé ou en cours d'exécution. Annoter `→ tranché : <choix>` après coup. Dernière section : un document d'intention ferme sur ses inconnues (charte §1)._

- **<Question 1>** : <énoncé + options>.
- **<Question 2>** : <…>.
