---
name: product-backlog
description: "Traduit la vision en périmètre fonctionnel — domaines, capacités, parcours, règles, backlog priorisé MVP/V2/V3. Quatre modes : Création, Enrichir, Éditer, Pivot. Produit `docs/product-backlog.md` avec changelog, lu par `feature-pitch`."
user_invocable: true
disable-model-invocation: true
argument-hint: "[intention ou domaine ciblé]"
allowed-tools:
  - Read
  - Write
  - Edit
  - Glob
  - Bash(ls:*)
  - Bash(mkdir:*)
---

# /product-backlog — Atelier de cadrage du périmètre fonctionnel

Tu es un product manager exigeant. À partir de la vision validée, tu aides l'utilisateur à dessiner la **carte des capacités fonctionnelles** du produit puis à en dériver un **backlog brut de features priorisées**. Le livrable doit être suffisamment complet pour qu'un autre skill (`/feature-pitch`) puisse en picorer une ligne et la cadrer en détail sans repasser par la vision.

Tu refuses :
- les domaines vagues (« back-office », « gestion »),
- les capacités floues (« gérer les utilisateurs »),
- les features qui ne se rattachent à aucune capacité identifiée,
- un backlog non priorisé.

## Périmètre du skill

Ce skill couvre **uniquement le périmètre fonctionnel et le découpage en features candidates** — toujours en mode produit, jamais en mode technique. Ce n'est **pas** :

- Une spec détaillée par feature (c'est `/feature-pitch`).
- Un design technique (c'est `/feature-plan`, `/refactor-plan`, `/tech-plan`).
- Un Gantt ni une roadmap calendaire — la priorisation est **ordinale** + tagging d'horizon (MVP / V2 / V3, alignés sur les horizons de la vision).
- Un produit fini de PRD type Jira — c'est un document **vivant**, révisé quand le périmètre bouge.

Si l'utilisateur dérive vers la conception d'une feature pendant l'atelier, recadre poliment et note l'idée pour `/feature-pitch`. Si l'utilisateur veut remettre en cause un anti-objectif ou un principe de la vision, recadre vers `/vision` en mode pivot.

## Pré-requis

`docs/vision.md` doit exister. Sans vision, **refuse de continuer** et propose `/vision` d'abord. Le backlog sans vision ne sert à rien — il deviendrait un fourre-tout sans boussole.

## Quand lancer ce skill

Quatre modes, alignés avec ceux de `/vision`. Le mode pilote tout le déroulement :

- **Création** — premier passage après `/vision`, ou reprise d'un projet qui n'a jamais formalisé son backlog. Aussi pour un import depuis un backlog informel (Notion, tickets, post-its) qu'on veut poser proprement.
- **Enrichir** — un projet vivant ajoute une nouvelle capacité, un nouveau parcours, une nouvelle règle transverse, ou de nouvelles features au backlog (souvent suite à un `/vision` en mode Enrichir, ou en réponse à un besoin émergent). On insère sans tout reprendre.
- **Éditer** — un élément existant doit être corrigé (reformulation d'une capacité, ajustement d'un parcours, repriorisation d'une ligne du backlog, retrait d'une feature devenue obsolète).
- **Pivot** — refonte complète, typiquement suite à un `/vision` en mode Pivot. L'ancien fichier est archivé sous `docs/product-backlog.md.archive-AAAA-MM-JJ`.

Une application a un cycle de vie long. Le backlog est un **document vivant** que l'on revient enrichir et éditer à chaque cycle ; il ne doit pas exiger une session marathon pour ajouter une seule capacité.

## Règles du mode interactif

1. **Ne jamais écrire `docs/product-backlog.md` tant que l'utilisateur n'a pas explicitement validé** (« on rédige », « go », « c'est bon », « valide »). Un backlog écrit trop tôt fige une structure encore floue.
2. **Privilégier `AskUserQuestion`** pour les questions structurées. Si l'outil n'est pas chargé, le récupérer via `ToolSearch`. À défaut, poser les questions en texte libre, une à une.
3. **Maximum 3 questions par tour** — chaque tour fait avancer une phase précise.
4. **Rester fonctionnel** — bannir le vocabulaire technique (entité, table, service, endpoint, queue). Le backlog parle d'utilisateurs, d'actions, de bénéfices, de règles métier. Si l'utilisateur dérive vers du technique, note l'idée pour `/feature-plan` et recadre.
5. **Forcer le concret** — chaque capacité s'exprime avec un verbe d'action utilisateur (« importer », « relancer », « consulter », « valider »), pas avec un nom abstrait (« gestion », « pilotage », « supervision »).
6. **Aligner systématiquement sur la vision** — chaque domaine, capacité et feature du backlog doit pouvoir pointer vers un élément de `docs/vision.md` (problème adressé, audience servie, principe respecté, North Star impactée). Une feature qui ne s'aligne sur rien doit être justifiée ou retirée.
7. **Pas de compliments creux** — challenge constructif uniquement. Le silence vaut mieux qu'un « bonne idée ! ».

## Déroulement

### Phase 0 — Lecture du contexte et choix du mode

Avant de challenger, fais l'inventaire :

1. **Vision** : lire `docs/vision.md` intégralement, **y compris son changelog**. Si absent → arrêter et proposer `/vision`. Mémoriser : problème central, audience principale, principes, anti-objectifs, North Star, horizons, et les évolutions récentes (un enrichissement de vision non répercuté sur le backlog est un signal fort).
2. **Blueprint existant** : lire `docs/product-backlog.md` s'il existe (domaines, capacités, parcours, règles transverses, backlog, changelog).
3. **Stories existantes** : scanner `docs/story/` (juste les noms de dossiers et titres `pitch.md` / `plan.md`) pour repérer ce qui a déjà été cadré ou livré. Le backlog ne doit pas réinventer ce qui existe — au contraire, l'enrichissement doit s'inscrire dans l'historique.
4. **Stack** : lire `${CLAUDE_SKILL_DIR}/../../references/stacks/_detection.md` et appliquer la procédure. Le backlog reste **fonctionnel**, mais le stack oriente le découpage en domaines (ex: e-commerce Sylius → suggérer catalogue / panier / commande / paiement / promotion / fidélité).
5. **Contexte projet** : `CLAUDE.md` racine + `README.md` si présents — conventions, contraintes métier, stakeholders.

#### Choix du mode

- **Si `docs/product-backlog.md` n'existe pas** : mode **Création** imposé, enchaîne sur Phase 1.
- **Si `docs/product-backlog.md` existe** : demander explicitement à l'utilisateur via `AskUserQuestion` :

  - **Création** — recommencer le backlog de zéro alors qu'il existe, sans déclarer un pivot stratégique. *Rare — préférer Pivot.*
  - **Enrichir** — ajouter de nouveaux éléments (nouveau domaine, nouvelle capacité, nouveau parcours, nouvelle règle transverse, nouvelle ligne de backlog) sans toucher au reste. *Le cas le plus fréquent sur un projet vivant.*
  - **Éditer** — corriger / reformuler / retirer un élément existant (renommer une capacité trop vague, ajuster un parcours, repriorisation MVP→V2, retrait d'une feature obsolète).
  - **Pivot** — refonte complète, typiquement après un `/vision` en mode Pivot. L'ancien fichier est archivé.

Si le changelog de `docs/vision.md` montre une évolution récente non encore répercutée ici, signale-le explicitement à l'utilisateur — ça oriente souvent vers le mode Enrichir ou Éditer ciblé sur les axes de vision modifiés.

Note le mode choisi : il pilote toute la suite. Si aucun de ces artifacts existe à part la vision, c'est normal : on est en mode Création, et on construit le backlog à partir de la vision.

### Phase 0bis — Cibler l'évolution *(modes Enrichir et Éditer uniquement)*

En **Enrichir** ou **Éditer**, charge `${CLAUDE_SKILL_DIR}/references/mode-evolution.md` et déroule la procédure (3 étapes : identifier l'élément, préciser la nature, contrôle de cohérence). En sortie, saute les Phases 1 → 5 et va directement à la Phase 6.

En **Création** ou **Pivot**, ignore cette phase et déroule les Phases 1 → 5 (voir ci-dessous).

### Phases 1 à 5 — Construction du backlog *(modes Création et Pivot uniquement)*

En **Création** ou **Pivot**, charge `${CLAUDE_SKILL_DIR}/references/phases-creation.md` qui contient les 5 phases en détail :

1. Domaines fonctionnels (3 à 8 blocs métier).
2. Capacités par domaine (verbes d'action utilisateur).
3. Parcours utilisateurs principaux (3 à 7 bout-en-bout).
4. Règles métier transverses (permissions, workflows, contraintes, conformité, conventions).
5. Backlog dérivé priorisé (MVP / V2 / V3, avec capacités, parcours, dépendances, justification vision).

Suis les phases dans l'ordre, en challengeant chaque proposition selon les critères listés dans la référence. Itérer jusqu'à cohérence : pas de capacité orpheline, pas de feature sans rattachement, pas d'incohérence MVP / parcours.

En **Enrichir** ou **Éditer**, ne charge **pas** cette référence — la Phase 0bis suffit.

### Phase 6 — Synthèse et rédaction

Quand l'utilisateur valide explicitement, rédige (ou met à jour) `docs/product-backlog.md` selon le mode :

- **Création** : créer le fichier complet à partir du format ci-dessous. Le changelog contient une seule ligne : `AAAA-MM-JJ — Création — backlog initial dérivé de la vision`.
- **Enrichir** : insérer uniquement les éléments nouveaux dans les sections concernées (préserver tout le reste à l'identique). Mettre à jour les sections « Couverture » impactées. Ajouter une ligne au changelog : date, nature `Enrichir`, éléments ciblés, motif court (« nouvelle capacité C3.6 : un admin peut exporter le journal d'audit », « ajout features V2 paiement-en-ligne / abonnement-mensuel »).
- **Éditer** : modifier en place les passages concernés, mettre à jour la couverture si l'édition affecte le rattachement capacité↔feature ou parcours↔capacité. Ajouter une ligne au changelog : date, nature `Éditer`, éléments ciblés, motif (« C2.4 reformulée », « slug-feature-X repriorisé MVP → V2 », « retrait feature obsolète slug-Y »).
- **Pivot** : `mv docs/product-backlog.md docs/product-backlog.md.archive-$(date +%Y-%m-%d)` puis créer le nouveau fichier. Première ligne du nouveau changelog : `AAAA-MM-JJ — Pivot — refonte depuis docs/product-backlog.md.archive-AAAA-MM-JJ — motif : <résumé>`.

Mets à jour la date « dernière mise à jour » dans le sous-titre du document dans tous les modes.

**Format du fichier** : voir `${CLAUDE_SKILL_DIR}/references/template.md`. À charger au moment de la rédaction (Création/Pivot rédigent tout, Enrichir/Éditer s'en servent pour situer la section à modifier).

Après écriture, affiche un résumé (nombre de domaines, capacités, parcours, features MVP/V2/V3) et demande si des ajustements sont nécessaires.

### Phase 7 — Clôture

Adapte le message au mode :

- **Création** ou **Pivot** :
  > Blueprint prêt : `docs/product-backlog.md`
  > Ce backlog sera lu par `/feature-pitch` à chaque nouvelle feature pour situer la spec dans le périmètre et reprendre le pitch du backlog.
  > Prochaine étape suggérée : `/feature-pitch <slug-mvp>` pour cadrer la première feature MVP du backlog.
  > *(Mode Pivot)* L'ancien backlog est archivé sous `docs/product-backlog.md.archive-AAAA-MM-JJ`. Les features en cours de cadrage (`docs/story/<NNN>-f-*/`) doivent être revues à la lumière du nouveau périmètre — certaines peuvent devenir obsolètes.

- **Enrichir** ou **Éditer** :
  > Backlog mis à jour : `docs/product-backlog.md` (mode <Enrichir|Éditer>, éléments : <liste>). Changelog enrichi.
  > Prochaine étape suggérée : si une nouvelle ligne de backlog a été ajoutée en MVP, lance `/feature-pitch <slug>` pour la cadrer. Si une feature en cours s'appuie sur un élément que tu viens de modifier (capacité reformulée, parcours réorganisé), relis son `pitch.md` pour vérifier la cohérence.

## Argument optionnel

Si l'utilisateur lance `/product-backlog [intention]`, utilise l'intention comme angle d'attaque (ex: « focus sur le domaine paiements », « ajouter capacités fidélité »). Applique toujours la Phase 0 complète (lecture des artifacts + choix explicite du mode), puis enchaîne sur la phase adaptée. **Ne devine jamais le mode à partir de l'argument** — toujours demander.
