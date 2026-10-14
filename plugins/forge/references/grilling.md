# Challenger en arbre de décision (référence partagée)

Référence chargée par les skills qui **challengent** avant d'écrire : `feature-pitch`, les trois
plans (`feature-plan`, `refactor-plan`, `tech-plan`), `vision` (modes Création et Pivot), `adr` —
et par `grill`, qui n'en applique que la méthode, sans rien écrire. Elle répond à une question :
comment mener le challenge pour qu'aucune décision ne reste implicite au moment de rédiger.

Elle ne remplace pas les axes de chaque skill — elle dit **comment les parcourir**.

## Pourquoi cette référence existe

Un challenge mené par axes explore **en largeur** : on pioche deux ou trois thèmes par tour, on
s'arrête quand l'utilisateur dit « go ». Ce qui manque à ce parcours, ce sont les décisions qui
**dépendent** d'une autre et qu'on n'a jamais atteintes, parce que la première a été tranchée
tard ou pas du tout. Elles ne disparaissent pas : elles réapparaissent à l'implémentation sous
forme d'écart au plan, ou à la review sous forme de finding — c'est-à-dire au moment où les
trancher coûte le plus cher.

Toutes les déviations ne sont pas évitables : un nommage, l'ordre de deux étapes, une contrainte
d'outillage découverte en codant sont des découvertes normales de l'implémentation, et aucun
challenge ne les aurait vues. Celles que cette méthode vise sont les autres — une règle métier
changée en cours de route, un cas limite trouvé par la review, une stratégie de test absente du
plan : des **branches de décision que personne n'a parcourues**.

## Les quatre règles

### 1. L'arbre, pas la liste

Tiens un **arbre de décisions**. Chaque décision a ses prérequis : on ne choisit pas le niveau
de test avant de savoir ce qu'on teste, ni les permissions d'une API avant de savoir s'il y a
une API. Les axes du skill sont la **graine** de l'arbre — ils disent où chercher des décisions,
pas dans quel ordre les poser.

- **Un round = la frontière.** Un round ne pose que des questions dont **tous les prérequis sont
  tranchés**. Jamais une question dont la réponse dépend d'une autre posée dans le même round.
- **1 à 3 questions par round.** Une seule quand la question est **structurante** — quand sa
  réponse redessine l'arbre (« le client final y a-t-il accès, ou seulement l'admin ? »).
- **Chaque réponse retaille l'arbre** : elle ferme des branches (« pas d'API » ferme
  authentification, sérialisation, versionnage), en ouvre d'autres, débloque celles qui
  l'attendaient. Dis-le en une ligne quand c'est le cas — c'est ce qui rend la méthode lisible.

### 2. Une recommandation par question

Chaque question arrive avec **ta réponse recommandée** et sa raison en une ligne, adossée à ce
que tu as lu : le code, la vision, la stack, un ADR, la convention du projet. Avec
`AskUserQuestion`, l'option recommandée vient **en premier** et porte `(Recommandé)` dans son
libellé ; sa description dit pourquoi.

```
Question : Une remise peut-elle s'appliquer à une commande déjà payée ?
  ● Non, verrouillée au paiement (Recommandé)
    `src/Order/OrderTotals.php:58` gèle le total à l'état paid ; l'autoriser imposerait un remboursement partiel.
  ○ Oui, avec remboursement de la différence
  ○ Oui, sous forme d'avoir sur la prochaine commande
```

- **Recommander n'est pas décider** : l'utilisateur tranche. Une recommandation sert à ce qu'il
  n'ait pas à tout formuler lui-même, et à ce qu'un « oui » soit un vrai choix.
- **Pas d'avis honnête vaut mieux qu'un avis inventé.** Quand la réponse tient à une
  connaissance que seul l'utilisateur a (une priorité commerciale, une contrainte client), dis-le
  (« pas de recommandation : ça dépend de X ») plutôt que de fabriquer une préférence.

### 3. Le code avant l'utilisateur

Une question à laquelle le dépôt répond **ne se pose pas** : elle se lit. Avant chaque round,
vérifie si le code, `docs/vision.md`, `docs/stack.md`, `docs/product-backlog.md`, `docs/adr/`,
l'`AGENTS.md` ou un document amont de la story (brief, pitch) tranche déjà. Si oui, **annonce le
fait et sa source** et ferme la branche :

> `src/Security/Voter/ProjectVoter.php:31` — seul le chef de projet édite un lot → branche « droits d'édition » fermée.

N'interroge l'utilisateur que sur ce que le dépôt ne peut pas savoir : l'intention, la priorité,
la préférence, la contrainte extérieure. Au pitch, la lecture reste **fonctionnelle** (ce que le
produit fait aujourd'hui) — elle ne conçoit pas.

### 4. La frontière visible

Après chaque round, une ligne d'état :

> **Arbre** — 7 tranchées · 3 lues dans le code · 4 ouvertes, dont 2 débloquées

Le challenge se termine de l'une de deux façons :

- **La frontière est vide** — toutes les branches sont tranchées, lues ou écartées. Dis-le, et
  propose de rédiger.
- **L'utilisateur dit « on rédige » avant.** C'est son droit. Mais avant d'écrire, **montre les
  branches encore ouvertes**, chacune avec sa recommandation. Celles qu'il laisse ouvertes
  partent dans la section d'inconnues du document (table ci-dessous) — **assumées, pas
  oubliées**.

## Où s'arrête une branche

Une branche est **close** quand sa décision est dans l'un de ces états :

| État | Où ça atterrit |
|---|---|
| **Tranchée** par l'utilisateur | dans la section du document qui la porte |
| **Lue** dans le dépôt | idem, avec sa source si elle surprend |
| **Écartée** — hors du sujet | `## Hors scope` du document |
| **Déléguée** — elle appartient à l'étape suivante | section d'inconnues, avec l'étape qui tranchera |
| **Laissée ouverte** par l'utilisateur | section d'inconnues, avec sa recommandation |

**Ne descends pas sous le niveau du document.** Le pitch s'arrête à la règle métier et au
critère observable ; une branche technique y est notée pour le plan, pas creusée. Le plan
s'arrête à ce que l'implémentation **ne devrait pas avoir à décider seule** ; le nommage d'une
classe, la forme d'une requête, l'ordre interne d'une étape restent à l'implémentation.
Interroger plus bas, c'est faire le travail du skill suivant sans en avoir la matière.

## Application par skill

| Skill | Graine de l'arbre | Une branche s'arrête à | Branches ouvertes à la rédaction |
|---|---|---|---|
| `feature-pitch` | zones de flou du `brief.md` s'il existe, puis les axes de la Phase 3 | la règle métier, le critère d'acceptation observable | `## Questions ouvertes` du pitch ; le technique → `## Annexe — Pistes pour le plan` |
| `feature-plan` | `## Questions ouvertes` du pitch, puis les axes de la Phase 4 | ce que l'implem ne devrait pas trancher seule | `## Questions ouvertes` du plan |
| `refactor-plan` | les axes de la Phase 4 | le comportement à préserver, le test qui le verrouille, l'étape réversible | `## Questions ouvertes` du plan |
| `tech-plan` | les axes de la Phase 4 | la métrique chiffrée, le kill switch | `## Questions ouvertes` du plan |
| `vision` (Création, Pivot) | les 7 axes de `axes-challenge.md` | le fait observable qui invaliderait l'énoncé | `## Hypothèses critiques`, statut « À tester » |
| `adr` | contexte → drivers → options → décision : l'ordre de dépendance est déjà celui du document | une décision justifiée par les drivers | une branche ouverte sur les drivers ou les options = décision pas mûre → statut `proposed`, la branche citée au `## Contexte` |
| `grill` | le sujet ou le document passé en argument | aussi bas que l'utilisateur le veut | la synthèse finale — aucun fichier |

Format d'une branche ouverte dans `## Questions ouvertes` : celui du template du document,
complété de la recommandation — `**<Question>** : <énoncé>. Options : (a) <…>, (b) <…>.
Recommandé : (a), <raison>.` Le skill aval qui la tranchera hérite ainsi de l'avis déjà formé.

## Garde-fous

- **Rien de ce que l'amont a tranché.** Une question à laquelle le brief, le pitch ou la vision
  répondent déjà ne se repose pas — sauf pour signaler une contradiction, et alors dis-le.
- **Pas de question sans conséquence.** Si les deux réponses produisent le même document, la
  question ne mérite pas d'être posée.
- **« Je ne sais pas » est une réponse.** Elle ne se force pas : la branche part en inconnues, ou
  en hypothèse à tester. Une question qui demande un essai pour être tranchée (un rendu, un
  ressenti d'interaction, une mesure) se note « à prototyper », elle ne se spécule pas.
- **L'utilisateur garde la main sur le périmètre.** Il peut contester une question, recadrer le
  sujet, fermer une branche d'autorité. Le challenge sert sa décision ; ce n'est pas un
  interrogatoire.
- **Trop d'arbre, c'est un signal.** Au-delà d'une quinzaine de branches ouvertes, ou quand
  l'arbre se scinde en sous-arbres indépendants, propose de **découper la story** plutôt que de
  continuer à interroger : deux sujets se cadrent mieux séparément.
