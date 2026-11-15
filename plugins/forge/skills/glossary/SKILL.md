---
name: glossary
description: "Produit ou met à jour le glossaire fonctionnel d'une application → `forge/glossaire.md` : les notions visibles à l'écran, pour qui ne lit pas le code. Déclenche sur « fais un glossaire », « mets à jour le glossaire », « définis le vocabulaire »."
user_invocable: true
disable-model-invocation: true
effort: medium
allowed-tools:
  - Read
  - Grep
  - Glob
  - Write
  - Edit
  - Bash(ls:*)
  - Bash(find:*)
  - Bash(test:*)
  - Bash(grep:*)
  - Bash(date:*)
  - Bash(git log:*)
  - Bash(git diff:*)
  - Bash(git show:*)
  - Bash(git status:*)
---

# /glossary — Glossaire fonctionnel de l'application

Tu écris le glossaire d'une application pour des gens qui **n'en liront jamais le code** :
utilisateurs, nouveaux arrivants, support, direction. Ils n'ont que l'interface sous les yeux.
Un terme qu'ils ne peuvent pas rencontrer à l'écran ne leur sert à rien. Un terme défini avec un
autre mot que celui du bouton les égare. Une définition fausse leur coûte plus cher qu'une
définition absente. Tout ce skill découle de ces trois constats.

Le glossaire est un **document vivant** : il se crée une fois, puis se met à jour quand une notion
apparaît, change de nom ou disparaît de l'interface.

## Périmètre du skill

**Ce qu'il fait** : il écrit et tient à jour `forge/glossaire.md`, la liste des notions visibles de
l'application, rangées par thème et définies dans le vocabulaire de l'interface.

**Ce qu'il ne fait pas** :

- Il ne documente pas l'usage d'une feature (comment faire, dans quels cas, avec captures) →
  `/forge:user-doc`. Le glossaire dit **ce que veut dire** un mot, pas **comment s'en servir**.
- Il ne cartographie pas le code pour un développeur → `/forge:doc-feature`.
- Il ne corrige pas le vocabulaire des autres documents. Quand la vision, le backlog ou un pitch
  emploient un mot banni ou un autre terme que l'interface, il le **signale** en clôture et nomme
  le skill propriétaire (`/forge:vision`, `/forge:product-backlog`, `/forge:sync`). Il ne touche
  jamais un libellé dans le code : c'est le travail des `*-implem`.
- Il n'écrit rien dans `forge/story/` : le glossaire est un document projet, pas une passe de
  story. Il ne touche donc à aucun `metadata.json`.
- Il ne commite pas → `/forge:commit`.

Seule écriture hors de son fichier : à la **création**, une ligne de renvoi dans les documents
d'entrée (README, `AGENTS.md`, vision, backlog). C'est l'exception encadrée du contrat
`${CLAUDE_SKILL_DIR}/../../references/skill-boundaries.md` §3, détaillée en phase 7.

| Si la question est… | → alors |
|---|---|
| « Que veut dire ce mot que je vois à l'écran ? » | `/forge:glossary` |
| « Comment je fais X dans l'application, et que se passe-t-il si… ? » | `/forge:user-doc` |
| « Comment ce module est-il construit ? » | `/forge:doc-feature` |
| « La vision ou le backlog emploient-ils encore l'ancien mot ? » | `/forge:glossary` le signale, `/forge:vision` ou `/forge:product-backlog` corrige |

**Quand le lancer** : pour le **créer**, et pour une **reprise complète** (rattraper des commits
passés hors du pipeline, revérifier tous les termes). Le suivi courant ne demande rien : une fois le
glossaire créé, `/forge:sync` le met à jour **à chaque clôture de story**, en appliquant la passe
décrite plus bas (§Passe de `sync`). C'est la co-écriture des documents projet du contrat
`skill-boundaries.md` §3 : `sync` écrit avec la grammaire de ce skill, il n'en invente pas une autre.

## La règle d'or : seulement ce qu'on voit à l'écran

Une notion n'entre dans le glossaire que si on peut la **rencontrer en utilisant l'application** :
un libellé de bouton, un titre de colonne, un badge, un statut, une entrée de menu, un message, un
indicateur affiché.

**Exclure** :

- les noms de classes, de tables, de propriétés, d'enums, de routes et de fichiers ;
- les références aux stories, ADR, tickets internes et documents de conception ;
- les données stockées mais jamais affichées : horodatages techniques, compteurs internes,
  historiques d'audit ;
- les métriques produit que l'application n'affiche pas (une North Star de la vision n'est pas
  une notion d'interface) ;
- les notions de planification interne absentes de l'interface (horizons MVP/V2, priorités de
  backlog) ;
- les fonctionnalités à l'état de maquette, ou prévues au backlog sans être livrées.

**Signaler sans trancher** : une fonctionnalité **en cours** (code non commité, story ouverte) va
peut-être changer avant d'être livrée. C'est donc à l'utilisateur de dire s'il faut l'inclure, la
marquer « à venir » ou la retirer (phase 4).

## Règles de rédaction

1. **Les libellés exacts de l'interface.** Le terme défini est celui du bouton, de la colonne, du
   badge ou du menu, recopié avec sa casse et ses accents. Quand un document de conception dit
   « Livraison » et que le badge affiche « Livrée », c'est « Livrée ». L'écart part dans les
   incohérences à signaler.
2. **La langue de l'interface**, pas celle du code ni celle de la conversation. Elle se lit dans la
   locale par défaut, dans les fichiers de traduction ou dans les templates.
3. **Les calculs en mots**, avec un exemple chiffré simple. Jamais de formule ni de nom de
   variable. Exemple : « si 40 % du travail a pris 8 jours, les 60 % restants en prendront 12 ».
   Le lecteur doit pouvoir refaire le calcul de tête devant l'écran.
4. **Qui voit quoi, qui peut faire quoi.** Quand une page, un bouton ou une donnée dépend du rôle,
   la définition le dit avec le nom du rôle tel que l'interface l'affiche.
5. **Court et juste plutôt que complet et douteux.** Une définition de deux lignes vérifiée vaut
   mieux qu'un paragraphe dont une phrase est inventée. Si un comportement ne se vérifie pas,
   retire la phrase, pas seulement le doute.
6. **Le vocabulaire imposé par le projet.** `AGENTS.md`, `CLAUDE.md`, `.claude/rules/` et les
   consignes déjà en contexte peuvent bannir des mots ou en imposer d'autres. Ils s'appliquent
   au glossaire comme au reste.
7. **Aucune phrase ne suppose la lecture du code ou des documents internes.** Le lecteur ne sait
   pas ce qu'est une story, un ADR ou un enum, et il n'a pas à le savoir.

Le squelette du fichier et des exemples de bonnes et mauvaises définitions sont dans
`${CLAUDE_SKILL_DIR}/references/template.md`. Lis-le avant la phase 5, par ce chemin absolu, car un
chemin relatif se résoudrait depuis la racine du projet.

## Déroulement

> **Ancienne convention `docs/`** : avant tout, applique la garde
> `${CLAUDE_SKILL_DIR}/../../references/legacy-docs.md`. Un projet dont l'atelier est encore sous
> `docs/` (`docs/story/`, `docs/vision.md`…) se migre d'abord par `/forge:migrate-forge` : tu
> t'arrêtes et tu y renvoies. Le glossaire ne s'écrit jamais sous `docs/`.

### Phase 0 — Mode, date, consignes

- `forge/glossaire.md` existe → **mode Mise à jour** (section dédiée plus bas). Sinon → **mode
  Création**.
- Date du jour : `date +%F`. C'est celle qui ira dans l'en-tête.
- Lis les consignes de vocabulaire : `AGENTS.md` et `CLAUDE.md` racine, `.claude/rules/`. Note
  les mots bannis et les termes imposés.
- Repère où vit l'interface, car la stack varie : templates (`templates/`, `resources/views/`,
  `app/views/`), composants front (`*.tsx`, `*.jsx`, `*.vue`, `*.svelte`), fichiers de traduction
  (`translations/`, `locales/`, `i18n/`, `lang/`). `forge/stack.md` le dit souvent. Ces fichiers
  sont **la** source de vérité du glossaire : tout le reste ne sert qu'à ne rien oublier.
- Repère la langue de l'interface (locale par défaut, `<html lang>`, langue des libellés).

### Phase 1 — Recenser les candidats

Dresse une liste de **candidats**, sans rien rédiger. Le but est de ne rien oublier, pas de
définir.

1. **Documents produit** : `forge/vision.md`, `forge/product-backlog.md` (domaines, capacités,
   parcours), les `pitch.md` des stories `f-` livrées (règles métier, critères), et
   `documentation/` s'il existe.
2. **Modèle** : entités ou modèles, enums et leurs libellés (méthodes `label()`, choix
   d'affichage, clés de traduction), services métier qui calculent une valeur affichée (un
   pourcentage, une estimation, un retard).

Chaque candidat garde sa provenance (« backlog », « enum de statut », « service de calcul ») pour
la phase 2.

### Phase 2 — Vérifier chaque terme dans l'interface

Pour chaque candidat, cherche le libellé dans les fichiers d'interface et de traduction :

```bash
grep -rli "<terme>" templates translations   # adapte les dossiers à la stack
```

Un libellé passe souvent par une clé de traduction : suis la clé jusqu'au fichier de la langue de
l'interface, car c'est là qu'est le mot affiché. Pour un statut, un rôle ou un badge, lis la méthode
qui produit le libellé (`label()`, `__str__`, table de choix) et relève **toutes** ses valeurs.

Trois verdicts :

| Le terme… | → conduite |
|---|---|
| s'affiche tel quel | candidat retenu |
| s'affiche sous un autre mot | retenu **avec le mot affiché** ; l'écart part dans les incohérences à signaler |
| ne s'affiche nulle part | écarté, avec la raison (donnée interne, notion de conception, non livré) |

Un calcul affiché (indicateur, pourcentage, estimation) : lis le service qui le produit pour
pouvoir l'expliquer **en mots** et le vérifier sur un exemple chiffré. Si la règle ne se comprend
pas avec certitude à la lecture, définis l'indicateur sans expliquer le calcul plutôt que de
deviner.

### Phase 3 — Relever la navigation dans le code

Lis le layout de base et le composant de menu, **jamais de mémoire** : groupes de menus, entrées,
page atteinte par chacune, et les conditions de rôle qui les masquent (`is_granted`, `@can`,
`v-if="user.isAdmin"`, garde de route…). C'est la matière de la dernière section, et la source la
plus fiable de « qui voit quoi ».

### Phase 4 — Croiser avec l'état de livraison

Classe chaque notion retenue :

| État | Preuve | Conduite |
|---|---|---|
| **Livré** | code commité ; story avec `delivery` dans son `metadata.json` ; ligne cochée au backlog | entre au glossaire |
| **En cours** | code non commité (`git status`) ; story ouverte sans `delivery` | **à arbitrer** |
| **Prévu** | ligne de backlog non cochée, aucun code | écarté |
| **Maquette** | page aux données en dur, prototype, route de démonstration | écarté |

Une notion **en cours** se soumet à l'utilisateur avec `AskUserQuestion` (via `ToolSearch` s'il
n'est pas chargé). Pour chacune, trois options : *inclure* / *marquer « à venir »* / *retirer*.
La recommandation par défaut est *retirer* si le libellé n'est pas encore stable, *à venir* sinon.
Regroupe toutes les notions en cours dans un seul round. En mode Création, ajoute au même round la
question des renvois (phase 7) : un seul aller-retour suffit.

### Phase 5 — Rédiger

Suis le squelette de `references/template.md` :

- **En-tête** : titre, une phrase de résumé en citation, puis la ligne « _Document vivant — à tenir
  à jour quand une notion apparaît ou change dans l'application. Date de dernière mise à jour :
  AAAA-MM-JJ._ », traduite si l'interface n'est pas en français mais **toujours avec la date au
  format AAAA-MM-JJ en fin de ligne** : c'est elle que relit le mode Mise à jour.
- **Introduction** : deux phrases sur ce que fait l'application et pour qui.
- **Sections par grand thème**, dans l'ordre où un utilisateur les découvre, pas dans l'ordre
  alphabétique. Ordre habituel : les personnes et leurs rôles, les objets principaux, leur cycle de
  vie (statuts), les actions, les indicateurs, les pages. Adapte-le à l'application : c'est le
  parcours qui commande.
- **Définitions** : `- **Terme** — définition.` Des sous-listes énumèrent les valeurs d'un statut,
  d'un rôle ou d'un badge.
- **Dernière section** : les menus de navigation et leur contenu, avec qui les voit.
- Une notion **à venir** porte la mention « _(à venir)_ » après son terme.

### Phase 6 — Relire comme un nouvel arrivant

Relis le fichier en te mettant à la place de quelqu'un qui arrive le premier jour, sans accès au
code ni aux documents internes. Chaque phrase doit se comprendre devant l'écran.

Puis traque les identifiants techniques restants :

```bash
grep -nE '`[A-Z][a-zA-Z]+`|::|[0-9]{3}-[frt]-|\bADR\b|\.php|\.ts\b|src/' forge/glossaire.md
```

Chaque occurrence se réécrit avec le mot de l'interface, ou disparaît. Vérifie aussi que les mots
bannis relevés en phase 0 n'apparaissent pas.

### Phase 7 — Renvois vers le glossaire (création seulement)

Un glossaire que personne ne trouve n'existe pas. À la création, ajoute **une ligne** de renvoi
dans les documents d'entrée présents :

- `README.md`, à côté des liens vers la vision et le backlog s'il y en a, sinon dans sa section de
  documentation ;
- `AGENTS.md`, dans la section qui liste les documents de référence. À défaut d'`AGENTS.md`, le
  `CLAUDE.md` racine. Si `CLAUDE.md` n'est qu'un pont `@AGENTS.md`, il ne se touche pas ;
- en tête de `forge/vision.md` et de `forge/product-backlog.md`, sous l'en-tête existant.

Ces écritures sont l'exception du contrat `skill-boundaries.md` §3, et ses garde-fous
s'appliquent :

1. **Une ligne par document, par `Edit` ciblé**, jamais par réécriture du fichier.
2. **Ni changelog ni date touchés** dans la vision et le backlog : leur contenu ne change pas, un
   lien n'est pas une évolution de périmètre.
3. **Proposé et validé** avant écriture (dans le round de la phase 4) : montre la ligne et
   l'endroit exact pour chaque document.
4. **Jamais en mise à jour** : un renvoi existant ne se touche pas, et un renvoi absent ne se
   rajoute pas en silence. Signale-le en clôture.

### Phase 8 — Compte rendu

> **Glossaire créé — `forge/glossaire.md`** *(ou : mis à jour, date AAAA-MM-JJ → AAAA-MM-JJ)*
>
> **Contenu** — N notions en M sections, menus : K entrées.
> *(en mise à jour : **Ajouté** / **Modifié** / **Retiré**, une ligne par notion, avec la preuve :
> template, story livrée, libellé disparu)*
>
> **Écarté** — les candidats non retenus, une ligne chacun avec sa raison (non affiché, prévu,
> maquette, retiré à ta demande).
>
> **Renvois ajoutés** *(création)* — `README.md`, `AGENTS.md`, `forge/vision.md`…
>
> **Incohérences de vocabulaire (non corrigées)**
> - `forge/product-backlog.md` emploie « Livraison », l'interface affiche « Livrée » → correction
>   proposée : « Livrée », via `/forge:product-backlog` (mode Éditer)
> - mot banni « <mot> » encore présent dans `<fichier>` → `<correction>`, via `<skill propriétaire>`
>
> Rien n'est commité : `git diff` pour relire. Prochaine étape : `/forge:commit`.

Un bloc vide se supprime. Les incohérences sont **listées, jamais corrigées** ici : chacune nomme le
document, le mot trouvé, le mot attendu et le skill qui peut corriger. Un libellé de l'interface qui
contredit le vocabulaire imposé par le projet se signale aussi, avec un renvoi vers
`/forge:feature-implem`.

## Mode Mise à jour

Le glossaire existe déjà. Il peut contenir des formulations écrites à la main : tu le **retouches**,
tu ne le réécris pas.

1. **Lis-le en entier** et relève la date de la ligne d'en-tête. Sans date lisible, prends celle du
   dernier commit qui l'a touché : `git log -1 --format=%cs -- forge/glossaire.md`.
2. **Liste ce qui a changé depuis cette date** :
   - `git log --since=<date> --name-only` restreint aux fichiers d'interface et de traduction ;
   - les stories livrées depuis (`delivery` présent, `updated` ≥ date) et leurs `pitch.md` ;
   - les lignes de backlog cochées depuis ;
   - les libellés nouveaux ou modifiés dans les templates et traductions : `git diff
     <commit>..HEAD -- <dossiers d'interface>`, où `<commit>` est le dernier commit antérieur à la
     date (`git log -1 --before=<date> --format=%h`).
3. **Revérifie chaque terme existant dans l'interface**, par le même `grep` qu'en phase 2. Un
   terme disparu de l'interface se retire. Un libellé renommé se corrige.
4. **Passe les nouveautés par les phases 2 à 4** : vérification, navigation si le menu a bougé,
   état de livraison et arbitrage des notions en cours.
5. **Applique par `Edit` ciblé** les seules définitions concernées. Une nouvelle notion se place
   dans la section de son thème, à l'endroit qu'impose l'ordre de découverte. Garde le style du
   document (longueur des définitions, tutoiement ou non, ponctuation) même s'il diffère du
   template.
6. **Mets à jour la date** de la ligne d'en-tête, et rien d'autre dans l'en-tête.
7. **Relis les passages modifiés** comme en phase 6, puis rends le compte rendu (ajouté / modifié /
   retiré).

Rien n'a changé ? Ne touche pas au fichier, pas même à la date. Dis-le en une ligne.

## Passe de `sync`

Ce que `/forge:sync` applique au glossaire en phase 5, à chaque clôture de story. Il tourne en
autonomie et a déjà lu le diff : la passe est donc **bornée à la story** et ne pose **aucune
question**, sauf en mode `--ask` de `sync`, où chaque retouche est validée comme les autres.

- **Condition** : `forge/glossaire.md` existe. Sinon rien, la création reste à `/forge:glossary`.
- **Entrée** : les fichiers d'interface et de traduction du diff de la story (relevés en phase 2 de
  `sync`), les libellés de statut, de rôle ou de badge modifiés, le layout et le menu s'ils ont
  bougé, et les règles métier du `pitch.md` réaligné.
- **Statut des notions** : celles de la story comptent comme **livrées**, car elles partent dans le
  commit qui suit, avec le glossaire. Tout autre travail en cours est hors du diff, donc hors de la
  passe.
- **Gestes** : ajouter, corriger ou retirer les seules définitions que le diff touche, chacune
  vérifiée dans l'interface comme en phase 2, par `Edit` ciblé, dans le style du document. Règle
  d'or, règles de rédaction et relecture de la phase 6 s'appliquent à l'identique.
- **La date de l'en-tête** dit jusqu'où le glossaire est à jour, et c'est d'elle que repart la
  reprise complète. Ne l'avance donc que si rien d'autre n'a pu changer l'interface depuis. Liste
  les commits qui touchent les fichiers d'interface ou de traduction depuis cette date
  (`git log --since=<date> --format=%h -- <dossiers d'interface>`) et retire ceux qui sont le
  commit de livraison d'une story (retrouvé selon `story-metadata.md` §Livraison dans le commit qui
  livre). S'il en reste, des changements sont passés hors du pipeline et
  cette passe ne les a pas lus : garde la date telle quelle, et signale en **Non réaligné** qu'une
  reprise par `/forge:glossary` est conseillée. Sinon, avance-la si une définition a bougé.
- **Ce qui demanderait un arbitrage n'est pas appliqué** : un calcul dont la règle ne se lit pas
  avec certitude, un libellé encore provisoire, une notion qui ne trouve pas de thème. `sync` le
  porte en **Non réaligné**, avec un renvoi vers `/forge:glossary`.
- **Pas fait par cette passe** : le balayage complet depuis la date de l'en-tête, la revérification
  des termes non touchés, les renvois. C'est la reprise complète, par `/forge:glossary`.
- **Compte rendu** : une ligne dans celui de `sync`, sous **Appliqué** ou **Documents projet à
  jour**. Les incohérences de vocabulaire repérées en chemin y sont signalées, pas corrigées.

## Garde-fous

- **Ne jamais inventer un comportement.** Chaque définition doit pouvoir se vérifier dans
  l'interface ou dans une règle livrée. Si tu ne peux pas citer le template, la traduction ou le
  service qui l'atteste, la phrase n'entre pas.
- **L'interface fait foi** sur les documents de conception, pour le mot comme pour le
  comportement. L'écart se signale, il ne s'arbitre pas en faveur du document.
- **Ne rien trancher sur l'en-cours** sans l'utilisateur.
- **Ne corriger aucun autre document** que le glossaire, hormis les renvois de la création.
- **Dans la passe de `sync`, rien de plus que la story** : ce qui dépasse son diff attend une reprise
  complète par `/forge:glossary`.
