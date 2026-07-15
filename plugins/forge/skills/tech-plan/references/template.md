# Plan technique — <Titre : ce qui change techniquement / infra>

> **But** : figer le comment d'une évolution technique — brique retenue, métriques, bascule et rollback.
> **Registre** : technique
> **Story** : `docs/story/<NNN>-t-<slug>/`
> **Amont** : aucun <!-- guide: une évolution technique n'a pas de pitch — ce plan porte motivation ET détail -->
> **ADR** : `docs/adr/<NNNN>-<slug>.md` <!-- guide: ligne à supprimer si aucune ADR n'est rattachée -->

<!--
guide: Plan d'une évolution technique (préfixe `-t-`). Produit par `/forge:tech-plan`, consommé par `/forge:tech-implem` (étape build), `/forge:review`, `/forge:report` et `/forge:estimate`.
Format commun à tous les documents de story : voir la charte `${CLAUDE_SKILL_DIR}/../../references/document-format.md`. Les trois plans (`-f-`/`-r-`/`-t-`) partagent le squelette de la charte §5 — ne pas réordonner ni renommer les sections. Les sections propres au track `-t-` (Rollback et kill switch, Métriques) sont additionnelles.
Une évolution technique change l'infrastructure ou un composant technique transverse sans impact métier visible (montée de version, paramétrage par l'environnement, bibliothèque remplacée, déploiement). Si du métier change, c'est une feature ; si seule la structure du code change, c'est un refacto.
Pas de pitch pour une évolution technique : la `## Motivation` de ce plan porte le pourquoi, en termes techniques (charte §3).
STACK-AGNOSTIQUE (charte §9) : aucun nom de framework en dur. Mécanismes, commandes QA et outils viennent de la détection (`references/stacks/_detection.md` → `references/stacks/<stack>.md`) et du `CLAUDE.md` du projet.
L'en-tête ci-dessus RESTE dans le fichier commité. Retirer ce bloc et tous les `> _Skill : ..._` avant commit.
-->

## Motivation

> _Skill : pourquoi cette évolution maintenant. État actuel, déclencheur (déploiement bloqué, dépendance obsolète, montée en charge, sécurité, projet à venir incompatible), quantifié si possible (occurrences, durée, volume). Conséquences si on ne fait rien — souvent une date qui bloque. Le facteur temporel est le cœur d'un `-t-` : l'expliciter._

<État actuel + friction concrète + déclencheur.>

**Pourquoi maintenant** : <facteur temporel précis>.

## Approche retenue

> _Skill : 1–2 paragraphes sur la brique retenue et son ancrage dans l'existant. Où elle s'insère, ce qu'elle remplace, comment les consommateurs actuels la traversent. Terminer par l'impact sur les clients existants — c'est ce qui décide si l'évolution est transparente ou non._

<Brique retenue et point d'ancrage, en 1–2 paragraphes.>

**Impact sur les clients existants** :

- <ex: aucune signature publique modifiée — les consommateurs gardent leurs appels>.
- <ex: le format attendu change — tous les appelants qui forgeaient la valeur à la main doivent migrer>.

### Mécanismes mobilisés

> _Skill : le pattern (configuration par l'environnement, fabrique dynamique, décorateur, intergiciel…), la lib ou le composant retenu, et le **mécanisme d'extension** du stack utilisé pour l'ancrer. Préciser si ça introduit une **nouvelle dépendance** (et laquelle) ou si tout se fait avec l'existant — c'est une information de review. Nommer les mécanismes avec le vocabulaire du stack réel._

- **Pattern** : <ex: configuration pilotée par l'environnement, fabrique dynamique, intergiciel décorateur>.
- **Lib / composant** : <existant du projet OU nouvelle dépendance (préciser le gestionnaire de paquets et le nom)>.
- **Mécanisme d'extension** : <ex: les mécanismes standards du stack (injection d'une variable d'environnement) — aucune modification de dépendance tierce>.

### Alternatives écartées

> _Skill : 2–4 options rejetées avec la raison. Sans ce bloc, la review reposera la question. Format de table normatif (charte §6)._

| Alternative | Pourquoi écartée |
|---|---|
| <Alternative A> | <raison en une phrase> |
| <Alternative B> | <…> |

## Modèle de données

> _Skill : section **conditionnelle** (charte §5). Une évolution technique touche rarement la persistance — dans ce cas, garder le titre et écrire « Aucun impact modèle. ». Sinon décrire l'impact (nouvelle table technique, index, changement de type) : ça ne doit pas se découvrir dans le diff._

Aucun impact modèle.

## Périmètre

> _Skill : tables exhaustives, format normatif (charte §6) — point de jonction avec `report.md`, qui les reprend en ajoutant une colonne « Prévu dans le plan ». Inclure les fichiers de configuration et d'environnement : sur un `-t-`, ce sont eux qui portent le vrai changement._

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `<chemin>` | <rôle en 1 phrase> |
| `<chemin de test>` | <cas couverts> |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `<chemin>` | <modification en 1 phrase> |
| `<fichier de configuration>` | <modification config> |
| `<fichier d'environnement>` | <variables ajoutées/retirées> |

## Hors scope

> _Skill : ce que cette évolution ne fait PAS. Souvent : les migrations voisines qu'on serait tenté d'embarquer dans la même bascule. Sur un `-t-` qui touche la prod, le hors-scope protège la fenêtre de bascule. Mettre `_(aucun)_` plutôt que de supprimer la section._

- **<Sujet exclu>** : <raison brève>.

## Impacts transverses

> _Skill : effets sur les zones que la brique ne touche pas directement. Toujours statuer sur la migration de données (« aucune » ou sa nature) et sur les impacts d'infrastructure — sur un `-t-`, c'est souvent là que se cache le vrai risque._

- **Modules clients impactés** : <liste>.
- **Migration de données** : <aucune / nature>.
- **Impacts prod / infra** : <DNS, authentification déléguée, envoi d'emails, certificats, secrets — ce qui doit être configuré côté infra AVANT déploiement>.
- **Droits d'accès** : <inchangé / nature du changement>.
- **Sécurité** : <nouveau vecteur ? vecteur supprimé ? tests à ajouter explicitement>.

## Rollback et kill switch

> _Skill : section propre au track `-t-`, **obligatoire** — c'est le seul track dont l'impact touche la prod. Décrire le mécanisme de retour arrière **sans redéploiement applicatif** (variable d'environnement, alias, bascule DNS, drapeau), le comportement en cas de panne de la dépendance, la cohabitation ancienne/nouvelle version, et les effets sur les sessions/cookies. Un `-t-` sans kill switch doit dire pourquoi il n'en a pas besoin._

- **Kill switch** : <variable / alias / mécanisme de rollback sans redéploiement>.
- **Comportement en panne de dépendance** : <ex: aucune nouvelle dépendance d'infra — logique entièrement en processus>.
- **Cohabitation ancienne/nouvelle version** : <oui/non + justification>.
- **Sessions / cookies** : <impact de la bascule, action utilisateur requise>.

## Métriques (baseline → cible)

> _Skill : section propre au track `-t-`. Table « métrique → baseline → cible → méthode de mesure ». **Quantifier** : c'est la différence majeure avec un plan de feature ou de refacto. La baseline se mesure AVANT (et se cite avec le commit où elle a été prise). Si l'évolution n'a pas de métrique chiffrable (« éradiquer une convention »), le dire explicitement et donner un critère **binaire vérifiable** — les critères binaires se reprennent ensuite en §Critères de sortie._

| Métrique | Baseline | Cible | Méthode de mesure |
|---|---|---|---|
| <ex: occurrences résiduelles de X> | <chiffre + commit> | **0** | `<commande de recherche>` |
| <ex: durée de <traitement>> | <chiffre mesuré> | **< <cible>** | `<méthode>` |
| <ex: accès sur les cibles> | n/a | **<statut attendu>** | `<commande>` |

## Stratégie de test

> _Skill : table normative « code → type → ce qu'on vérifie » (charte §6). Sur un `-t-`, ne pas se contenter des métriques : il faut dire ce qui est testé automatiquement, et ce qui ne peut l'être que par une vérification en environnement réel (bascule, certificat, service tiers) — ces derniers vont en « Hors scope tests » et se retrouvent dans l'§Ordre d'exécution comme étapes de bascule._

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `<chemin>` | unit | <cas nominaux + cas d'erreur, y compris entrée hostile> |
| `<chemin>` | functional | <comportement traversant après bascule> |

**Hors scope tests** :

- <ex: la bascule DNS ne se teste pas automatiquement — vérification manuelle en preprod, cf. §Ordre d'exécution>.
- <ex: la délivrance du certificat dépend d'un tiers — vérification en env réel>.

## Ordre d'exécution

> _Skill : séquence d'étapes commitables, **incluant les actions d'infrastructure**. Structure d'étape normative (charte §6). Chaque étape doit dire si elle inclut une bascule (preprod/prod) ou si elle est purement applicative ; pour une bascule, lister les pré-requis externes dans « Objectif » et la méthode de contrôle dans « Vérification »._

1. [ ] **<Nom de l'étape — ex: refacto du code + tests unitaires (sans effet fonctionnel)>**
   - Objectif : <résultat attendu>.
   - Fichiers : <créés / modifiés>.
   - Vérification : <suite verte>.
   - Commitable seule : oui.

2. [ ] **<Nom de l'étape — ex: bascule en local + tests fonctionnels et E2E>**
   - <…>

3. [ ] **<Nom de l'étape — ex: bascule preprod>**
   - Objectif : <bascule + pré-requis côté infra : DNS, authentification déléguée, emails>.
   - Fichiers : <configuration>.
   - Vérification : <navigation, réception d'emails, …>.
   - Commitable seule : <non — dépend de l'infra>.

4. [ ] **<Nom de l'étape — ex: bascule prod>**
   - <…>

5. [ ] **<Nom de l'étape — ex: nettoyage de la documentation>**
   - <CLAUDE.md, docs projet, ADR éventuelle>.

## Critères de sortie

> _Skill : checkboxes vérifiables avant clôture — **la seule section de critères du plan** (charte §4) : les cibles chiffrées de §Métriques s'y reprennent sous forme binaire (« atteinte / non atteinte »). Plus exigeant qu'un plan de feature, car l'impact peut être prod : la bascule et le kill switch **testé** en font partie. Les commandes QA viennent du stack détecté et du `CLAUDE.md` du projet._

- [ ] Suite de tests du projet verte (unit, functional, E2E selon ce qui existe).
- [ ] <Critère binaire repris de §Métriques : ex « `<commande de recherche>` retourne 0 occurrence »>.
- [ ] <Cible de perf de §Métriques atteinte : ex « <mesure> < <cible> »>.
- [ ] Accès validé en local : <commande + statut attendu>.
- [ ] Bascule preprod validée : <parcours de contrôle>.
- [ ] Bascule prod validée : <parcours de contrôle>.
- [ ] Kill switch **testé** (pas seulement documenté) : <méthode + résultat attendu>.
- [ ] Analyse statique et style conformes aux exigences du projet.
- [ ] Documentation projet alignée (`CLAUDE.md`, docs, ADR).

## Risques et mitigations

> _Skill : table normative (charte §6). Couvrir : sessions/cookies perdus, certificats non délivrés, redirections d'authentification non enregistrées, délivrabilité des emails, test resté sur l'ancien pattern, perte d'accès administrateur, dépendance externe indisponible._

| Risque | Probabilité | Mitigation |
|---|---|---|
| <Risque 1> | faible / moyenne / élevée | <mitigation concrète> |
| <Risque 2> | <…> | <…> |

## Questions ouvertes

> _Skill : à clarifier avant ou pendant l'exécution. Souvent : accès aux consoles externes (registrar, cloud, service d'emailing), ADR à créer ou non, tests d'entrée hostile à ajouter. Annoter `→ tranché : <choix>` après coup. Dernière section : un document d'intention ferme sur ses inconnues (charte §1)._

- **<Question 1>** : <énoncé + options>.
- **<Question 2>** : <…>.
