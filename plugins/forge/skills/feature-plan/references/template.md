# Plan technique — <Titre de la feature>

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/<NNN>-f-<slug>/`
> **Amont** : `pitch.md`
> **ADR** : `docs/adr/<NNNN>-<slug>.md` <!-- guide: ligne à supprimer si aucune ADR n'est rattachée -->

<!--
guide: Plan TECHNIQUE d'une feature (préfixe `-f-`). Produit par `/forge:feature-plan`, consommé par `/forge:feature-implem` (étape build), `/forge:review`, `/forge:report` et `/forge:estimate`.
Format commun à tous les documents de story : voir la charte `${CLAUDE_SKILL_DIR}/../../references/document-format.md`. Les trois plans (`-f-`/`-r-`/`-t-`) partagent le squelette de la charte §5 — ne pas réordonner ni renommer les sections : les skills avals les cherchent par leur nom.
Le pitch décrit l'intention métier ; ce fichier décrit le COMMENT. Le « pourquoi » ne se recopie PAS ici : il vit dans `pitch.md` §Contexte, auquel l'en-tête renvoie (charte §5).
STACK-AGNOSTIQUE (charte §9) : aucun nom de framework en dur. Les mécanismes, commandes QA et conventions viennent de la détection (`references/stacks/_detection.md` → `references/stacks/<stack>.md`) et du `CLAUDE.md` du projet — les exemples ci-dessous sont à remplacer par ceux du stack réel.
L'en-tête ci-dessus RESTE dans le fichier commité. Retirer ce bloc et tous les `> _Skill : ..._` avant commit.
-->

## Approche retenue

> _Skill : 1–2 paragraphes qui décrivent la solution choisie en termes architecturaux (pas une liste de fichiers). Quelle couche porte quoi, comment les pièces s'assemblent._

<Solution retenue en 1–2 paragraphes.>

### Mécanismes mobilisés

> _Skill : les patterns et briques du stack détecté que la feature réutilise (jamais inventés) — hook de cycle de vie, écouteur d'événement, décorateur, composant temps réel, contrôle d'accès, requête dédiée… Pour chaque, dire BRIÈVEMENT pourquoi celui-là plutôt qu'un autre. Nommer les mécanismes avec le vocabulaire du stack réel, tel que le donnent `references/stacks/<stack>.md` et le `CLAUDE.md` du projet._

- **`<Mécanisme>`** : <usage dans la feature et justification courte>.
- **`<Mécanisme>`** : <…>.

### Alternatives écartées

> _Skill : 2–4 options rejetées avec la raison. Sans ce bloc, la review reposera la question. Format de table normatif (charte §6)._

| Alternative | Pourquoi écartée |
|---|---|
| <Alternative A> | <raison en une phrase> |
| <Alternative B> | <…> |

## Modèle de données

> _Skill : section **conditionnelle** (charte §5) — présente si la feature crée ou modifie une structure persistante. Sinon, garder le titre et écrire la phrase « Aucun impact modèle. » : une section supprimée est indistinguable d'un oubli._
>
> _Une sous-section par structure. Préciser les champs (type, nullable, contrainte), les contraintes de niveau structure (unicité, index, hooks), et le rattachement aux conventions transverses du projet (cloisonnement multi-tenant, horodatage, traduction). Pour les relations bidirectionnelles, préciser le côté propriétaire et le comportement en cascade. Utiliser les types et le vocabulaire du stack détecté._

### <Nouvelle structure `<Nom>`> (ou <Modification de `<Nom>`>)

`<chemin du fichier>` :

| Champ | Type | Nullable | Contrainte |
|---|---|---|---|
| `id` | <identifiant, auto> | non | |
| `<champ>` | <type du stack> | <oui/non> | <contrainte de validation ou de stockage> |
| `<relation>` | <cardinalité> (`<Structure cible>`) | non | <comportement en cascade / suppression> |

> _Skill : préciser si la structure adhère aux conventions transverses du projet (cloisonnement par organisation/canal, traçabilité, traduction) **et pourquoi**. Mentionner les contraintes de validation sur mesure à créer si une règle métier du pitch ne tient pas dans une contrainte standard du stack._

## Périmètre

> _Skill : tables exhaustives, format normatif (charte §6) — c'est le point de jonction avec `report.md`, qui reprend ces deux tables en y ajoutant une colonne « Prévu dans le plan ». Un reviewer doit pouvoir cocher chaque ligne contre le diff._

### Fichiers à créer

> _Skill : chaque ligne = un fichier qui n'existe pas encore. Mettre les tests en bas. Une description courte qui aide à comprendre le rôle sans lire le fichier._

| Fichier | Rôle |
|---|---|
| `<chemin>` | <rôle en 1 phrase> |
| `<chemin de migration/script de schéma>` | <description succincte> |
| `<chemin de test unitaire>` | <cas couverts en 1 phrase> |
| `<chemin de test fonctionnel>` | <cas couverts en 1 phrase> |

### Fichiers à modifier

> _Skill : chaque ligne = un fichier existant. Décrire le diff conceptuel (« remplacer X par Y », « ajouter la relation inverse », « retirer la méthode Z ») — pas le diff ligne à ligne._

| Fichier | Modification |
|---|---|
| `<chemin>` | <modification en 1 phrase> |
| `<template / vue>` | <modification UI> |
| `<fichier de configuration>` | <modification config> |
| `<fichier d'environnement>` | <variables ajoutées/retirées> |

## Hors scope

> _Skill : ce que ce plan ne fait PAS, côté technique (le hors-scope métier vit dans le pitch). Refactos voisins non embarqués, optimisations remises à plus tard. Mettre `_(aucun)_` plutôt que de supprimer la section._

- **<Sujet exclu>** : <raison brève>.

## Impacts transverses

> _Skill : miroir **technique** du §Impacts transverses du pitch — le pitch pose la question en langage métier, le plan y répond en mécanismes. Un « non » explicite reste préférable à l'absence d'item. Nommer les mécanismes du stack réel._

- **Cloisonnement des données** : <mécanisme de filtrage touché, ou raison pour laquelle la structure y échappe>.
- **Déclinaisons / thèmes** : <oui/non + mécanisme>.
- **Traduction / i18n** : <libellés concernés, langue par défaut, structure existante>.
- **API / exposition externe** : <oui/non + point d'entrée exposé ou modifié>.
- **Droits d'accès** : <nouveau contrôle d'accès à écrire, ou mécanisme existant suffisant>.
- **Emails / notifications** : <oui/non + brique concernée>.
- **Migration de données** : <aucune, ou nature : création de structure, retrait de champ, reprise de l'existant>.
- **Comportement par défaut** : <pour les utilisateurs/organisations qui n'activent pas la feature>.

## Stratégie de test

> _Skill : table normative « code → type → ce qu'on vérifie » (charte §6). Pas le code des tests, le contrat. Les niveaux (unit / functional / E2E) et les outils viennent du stack détecté._

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `<chemin>` | unit | <cas nominaux + cas d'erreur> |
| `<chemin de la règle de validation>` | unit | <violation A, violation B, combinaisons OK> |
| `<chemin du contrôle d'accès>` | unit | <règle de décision> |
| `<chemin du point d'entrée HTTP>` | functional | <parcours + assertions de réponse> |

**Hors scope tests** :

> _Skill : ce qu'on assume de ne pas couvrir, avec la raison. Un plan sans hors-scope de test explicite est un plan qui n'a pas tranché (charte §6)._

- <ex: pas de test fonctionnel sur l'écran d'administration — couvert par le contrôle d'accès global déjà testé>.
- <ex: pas de scénario E2E dédié — les parcours existants traversent déjà la zone>.

## Ordre d'exécution

> _Skill : étapes exécutables des fondations vers l'UI (modèle → schéma → service → point d'entrée → vue → tests). Structure d'étape normative (charte §6). Numéroter en respectant les dépendances : les cases cochées au fil de l'eau servent de fil rouge pendant `/forge:feature-implem`._

1. [ ] **<Nom de l'étape — ex: structure de données + contraintes>**
   - Objectif : <résultat attendu>.
   - Fichiers : <créés / modifiés>.
   - Vérification : <commande ou critère observable>.
   - Commitable seule : oui/non.

2. [ ] **<Nom de l'étape — ex: service métier>**
   - Objectif : <…>.
   - Fichiers : <…>.
   - Vérification : <…>.
   - Commitable seule : oui/non.

3. [ ] **<Nom de l'étape — ex: migration de schéma générée puis relue>**
   - <…>

4. [ ] **<Nom de l'étape — ex: point d'entrée + vue>**
   - <…>

5. [ ] **<Nom de l'étape — ex: tests + QA finale (analyse statique, style, suite de tests)>**
   - <…>

## Critères de sortie

> _Skill : checkboxes **techniques et vérifiables** cochées à la livraison — le pendant des « Critères d'acceptation » observables du pitch (charte §4). Les commandes QA viennent du stack détecté et du `CLAUDE.md` du projet : ne pas inventer, ne pas écrire un outil en dur qui n'existe pas dans ce projet._

- [ ] <Critère structurel ou comportemental mesurable>.
- [ ] Suite de tests du projet verte, sans nouvelle régression.
- [ ] Analyse statique et style conformes aux exigences du projet.
- [ ] <Critère propre à la feature : ex « le comportement par défaut est inchangé pour les organisations qui n'activent pas l'option »>.

## Risques et mitigations

> _Skill : risques **techniques** (les risques métier vivent dans le pitch). Table normative (charte §6). Couvrir au minimum : cloisonnement/isolation si le code touche au filtrage, performance, migration irréversible, dépendance externe, comportement non couvert par les tests._

| Risque | Probabilité | Mitigation |
|---|---|---|
| <Risque 1> | faible / moyenne / élevée | <mitigation concrète> |
| <Risque 2> | <…> | <…> |

## Questions ouvertes

> _Skill : décisions techniques non tranchées au moment du plan. À trancher en `/forge:feature-implem` ou à l'implémentation. Annoter `→ tranché : <choix>` après coup. Si la question a aussi une dimension métier, la reproduire dans le pitch. Dernière section : un document d'intention ferme sur ses inconnues (charte §1)._

- **<Question 1>** : <énoncé + options techniques>.
- **<Question 2>** : <…>.
