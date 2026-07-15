---
name: rules
description: "Écrit les règles projet dans `.claude/rules/` — instructions paths-scopées, chargées seulement quand Claude ouvre un fichier de la zone — et retire du CLAUDE.md ce qui part en règle. Modes Création, Enrichir, Éditer."
user_invocable: true
disable-model-invocation: true
argument-hint: "[zone ciblée ou intention libre]"
allowed-tools:
  - Read
  - Write
  - Edit
  - Glob
  - Grep
  - Bash(ls:*)
  - Bash(find:*)
  - Bash(mkdir:*)
  - Bash(git log:*)
  - Bash(git diff:*)
---

# /rules — Règles projet paths-scopées

Tu graves dans `.claude/rules/` les conventions que ce projet impose à qui écrit son code, et tu les
ranges par **zone** pour que chacune ne coûte du contexte que dans les sessions où elle sert
vraiment. Chaque règle que tu écris est **attestée par le dépôt** — jamais une bonne pratique
générale récitée de mémoire.

## Périmètre du skill

Ce skill écrit `.claude/rules/**`, et **touche au `CLAUDE.md` racine sur un seul geste** : en retirer
ce qu'il vient d'en déplacer vers une règle, et y laisser un renvoi court vers les zones. Déplacer,
c'est retirer à la source — laisser le doublon en place, ce serait payer deux fois le contexte et
attendre que les deux versions divergent.

Tout le reste du `CLAUDE.md` appartient à `/claude-md` : ajouter une convention, restructurer le
fichier, poser la couche comportementale. Tu ne fais que **soustraire ce que tu as emporté**.

Ce skill n'est **pas** :

- La cartographie de la stack (`/stack`) — `docs/stack.md` **constate** ce qui tourne, une règle
  **prescrit** comment coder. « On utilise Doctrine » va dans `stack.md` ; « une requête ne sort pas
  du repository » est une règle.
- Un hook (`.claude/settings.json`) — une règle est du **contexte**, un hook est une **garantie**.
  Si l'utilisateur veut qu'une commande soit *bloquée* ou *réécrite* et pas seulement *conseillée*,
  la règle est le mauvais outil : renvoie-le vers les hooks `PreToolUse`.

Si l'utilisateur dérive vers « documente ma stack » ou « refais mon CLAUDE.md », recadre vers le
skill dédié.

## Ce qu'est une règle, et pourquoi le `paths` est tout

Une règle est un fichier markdown dans `.claude/rules/`, avec un frontmatter à un seul champ utile :

```markdown
---
paths:
  - "src/Repository/**/*.php"
---

# Requêtes Doctrine
- Tout QueryBuilder vit dans un repository, jamais dans un contrôleur ni un service.
- …
```

Le mécanisme mérite d'être compris avant d'écrire quoi que ce soit, parce qu'il commande tout le
reste :

- **Le déclencheur est la lecture d'un fichier**, pas la nature de la tâche ni l'exécution d'une
  commande. Claude ouvre `src/Repository/OrderRepository.php` → la règle entre en contexte.
- **Une règle sans `paths` est chargée au lancement, avec la même priorité que `.claude/CLAUDE.md`.**
  Elle n'apporte donc rien qu'une section de `CLAUDE.md` n'apporterait — c'est du rangement, pas une
  économie. **Le `paths` est la seule raison d'exister d'une règle.**
- **Une règle est du contexte, pas de la configuration appliquée.** Claude la lit et essaie de la
  suivre ; rien ne la force.

D'où les deux tests que tu appliques à **chaque** candidate, avant de l'écrire.

### Test 1 — le test du `paths`

Demande-toi : *quels fichiers Claude doit-il être en train de lire pour que cette règle serve ?*

- Tu sais les nommer par un glob → **c'est une règle**, écris-la.
- Tu n'y arrives pas, ou le glob honnête serait `**/*` → **ce n'est pas une règle de zone.** Ne la
  déguise pas en règle avec un glob bidon : elle ne se déclencherait pas au bon moment, ou se
  déclencherait tout le temps pour rien. Sa place est dans le `CLAUDE.md` → renvoie l'utilisateur
  vers `/claude-md`, en lui disant en une phrase pourquoi.

Le piège classique, et tu le rencontreras souvent : **les règles d'outillage.** « Toujours passer par
le binaire `symfony` », « lance les tests via le Makefile », « jamais `npm`, toujours `pnpm` ». Ce
sont d'excellentes instructions et de mauvaises règles : lancer une commande n'est pas lire un
fichier, donc aucun `paths` ne les déclenchera au moment où elles comptent. Elles vont dans
`CLAUDE.md` — et si l'utilisateur veut la garantie plutôt que le conseil, dans un hook.

### Test 2 — la règle de preuve

Une règle ne s'écrit que si le dépôt l'atteste. Trois preuves recevables :

- **Un fichier** — une config de linter, une structure imposée, un pattern tenu partout dans la zone
  (tu l'as lu, tu peux citer deux ou trois fichiers qui le suivent).
- **Un diff** — la livraison qu'on vient de faire a établi la convention.
- **Un finding** — une `review.md` ou une dette de `report.md` a relevé l'écart, et on grave pour
  qu'il ne revienne pas.

Si aucune des trois, ce n'est pas une règle du projet : c'est ton avis sur le métier. Alors **pose la
question** à l'utilisateur — s'il confirme que c'est bien la convention maison, sa réponse devient la
preuve. Ce qu'on n'écrit jamais, c'est une bonne pratique générique que personne n'a demandée et que
le code ne suit pas : elle ne serait fausse qu'un jour sur deux, ce qui est pire que rien.

**La preuve se compte, elle ne se raconte pas.** Lance la commande qui l'établit (`Grep`, `Glob`) et
lis son résultat avant d'affirmer quoi que ce soit. Un détail plausible — « les contrôleurs passent
tous par des repositories injectés » — est précisément le genre de chose qu'on croit avoir vue, qu'on
écrit avec aplomb, et qui est fausse. Une preuve non comptée n'est pas une preuve : c'est un souvenir,
et il vient de justifier une règle par une invention. Quand tu annonces un chiffre à l'utilisateur
(« 14 repositories, 0 ailleurs »), c'est que tu l'as compté.

**Dis ce que tu comptes.** Des fichiers ou des occurrences ? Les deux chiffres diffèrent — « 11 »
peut être 11 occurrences réparties sur 9 fichiers — et c'est le nombre de **fichiers** qui dit si une
convention est tenue. `grep -rl <motif> <zone> | wc -l` compte les fichiers, `grep -rc` les
occurrences ; ne présente jamais l'un pour l'autre. Un chiffre juste sur la mauvaise unité est un
chiffre faux, et il décrédibilise tout le reste de ta synthèse.

**Ne prescris jamais plus large que ce que tu as mesuré.** C'est la façon la plus courante de rater,
parce qu'elle ne ressemble pas à une invention : tu comptes « 0 sélecteur par classe CSS », et tu
écris « jamais par classe CSS **ni par balise** ». Le second membre n'a été mesuré nulle part — et le
code, lui, cible par balise six fois. Tu viens d'écrire une règle que le projet enfreint déjà, ce qui
apprend à Claude à ignorer le fichier entier.

Relis chaque puce en tenant sa preuve à côté et demande : *ai-je compté exactement ça ?* Ce que tu as
mesuré s'écrit ; ce qui « va sûrement avec » se mesure d'abord, ou se tait. Un « et » de trop dans une
règle coûte plus cher que la règle ne rapporte.

### Ce qui fait une règle utile

- **Courte.** Elle est injectée en entier à chaque session qui touche la zone. Vise une dizaine de
  lignes ; au-delà de trente, tu mélanges probablement deux zones.
- **Actionnable et vérifiable.** « Injection par constructeur, jamais de `new Service()` » se vérifie ;
  « écrire du code propre » ne veut rien dire et ne change aucune décision.
- **Cohérente avec son `paths`.** Aucune puce ne doit s'adresser à une zone que le `paths` ne couvre
  pas. Le test est simple : chaque ligne parle-t-elle à quelqu'un en train d'éditer un des fichiers
  matchés ? Sinon elle est livrée au mauvais moment, à quelqu'un qui n'en fera rien.
  C'est le cas fréquent des conventions qui sont un **contrat entre deux zones** — poser un
  `data-test` dans un template et le cibler depuis un test e2e. Ça fait deux règles, une par zone,
  chacune ne disant que sa moitié : elles ne se lisent jamais dans la même session.
- **Non redondante avec le `CLAUDE.md`.** Deux instructions qui se recouvrent, c'est du contexte payé
  deux fois ; deux instructions qui se contredisent, c'est Claude qui en choisit une au hasard. Quand
  une règle reprend une section du `CLAUDE.md`, retire la section (Phase 6) : c'est ce retrait qui
  donne son sens au déplacement.

## Règles du mode interactif

1. **Ne jamais écrire dans `.claude/rules/` sans avoir montré les règles candidates et obtenu un go**
   (« go », « c'est bon », « écris »). Une règle fausse ou mal scopée pollue silencieusement toutes
   les sessions suivantes de toute l'équipe — c'est le genre de dégât qu'on ne voit pas passer.
2. **Appliquer les deux tests à chaque candidate**, et dire à l'utilisateur laquelle échoue et
   pourquoi. Une candidate recalée n'est pas perdue : elle part vers `/claude-md` ou vers un hook.
3. **Vérifier chaque glob avant de graver** (Phase 5). Un `paths` qui ne matche aucun fichier est une
   règle morte, et elle est morte en silence.
4. **Privilégier `AskUserQuestion`** pour arbitrer (max 3 questions par tour). Si l'outil n'est pas
   chargé, le récupérer via `ToolSearch`, sinon poser en texte libre.
5. **Pas de changelog dans un fichier de règle.** Contrairement à `docs/stack.md` qu'on ouvre à la
   demande, une règle est injectée en contexte à chaque session où son `paths` matche : un historique
   dedans est payé en tokens à chaque fois pour un contenu qui n'aide personne à écrire du code.
   L'historique, c'est `git log` qui le tient.

## Déroulement

### Phase 0 — Inventaire de l'existant et choix du mode

1. **Règles existantes** : `ls .claude/rules/` (récursif). Si des règles existent, les lire toutes —
   c'est la base à faire évoluer, et c'est aussi ce avec quoi il ne faut pas doublonner.
2. **`CLAUDE.md` racine** : le lire intégralement s'il existe. Il te dit ce qui est déjà couvert, et
   en mode Création il **est** la matière première (voir Phase 1).
3. **Artifacts forge** : lire `docs/stack.md` s'il existe — il nomme la stack réelle, donc les zones
   qui existent vraiment. En mode Enrichir déclenché par une livraison, lire aussi la `review.md` /
   `report.md` de la story si l'utilisateur en désigne une : les findings y sont des preuves prêtes.

**Choix du mode** :

- Si `.claude/rules/` est vide ou absent → mode **Création** imposé.
- Sinon, demander explicitement :
  - **Création** — repartir de zéro (rare ; préférer Enrichir/Éditer).
  - **Enrichir** — ajouter une règle, ou une zone entière. *Le cas le plus fréquent.*
  - **Éditer** — corriger une règle devenue imprécise, resserrer un `paths` trop large, ou
    **retirer** une règle devenue fausse. Le retrait fait partie de ce mode et c'est l'entretien le
    plus important : une règle périmée continue d'être obéie.

Ne devine jamais le mode — demande-le.

### Phase 1 — Trouver la matière

Charge `${CLAUDE_SKILL_DIR}/references/zones-catalog.md` : il donne les zones typiques par famille de
projet et les globs qui vont avec. Il t'évite de partir d'une page blanche, mais **c'est le dépôt qui
tranche**, pas le catalogue.

**En mode Création**, deux sources, dans cet ordre :

1. **Le `CLAUDE.md` existant** — c'est là que sont déjà écrites les conventions du projet, en vrac.
   Passe-le au test du `paths`, section par section : ce qui est scopable devient une règle candidate,
   le reste **doit rester où il est**. C'est le cas d'entrée réel de tout projet vivant, et le gain
   est immédiat quand un dépôt porte plusieurs sujets étrangers l'un à l'autre (une app et un
   package, un back et un front) : chacun cesse de payer le contexte de l'autre.
2. **Le code lui-même** — repère les zones (`Glob` sur l'arbre, en t'aidant du catalogue) et, dans
   chacune, les patterns tenus assez systématiquement pour être une convention. Lis avant d'affirmer :
   deux ou trois fichiers qui suivent le pattern, c'est une preuve ; un seul, c'est un hasard.

**En modes Enrichir / Éditer**, restreins-toi à la zone visée par l'utilisateur ou par l'argument. Si
l'ajout vient d'une livraison, la preuve est le diff (`git diff`, `git log -p` sur la zone) ou le
finding.

### Phase 2 — Synthèse

Présente les candidates triées **par verdict**, pas par zone : c'est le tri qui a de la valeur pour
l'utilisateur, et c'est là qu'il va corriger ton jugement.

```
## Règles à écrire (scopables + prouvées)
- .claude/rules/doctrine.md — paths: src/Entity/**, src/Repository/**
  · QueryBuilder confiné au repository (prouvé : 14 repositories, 0 QB ailleurs)
  · snake_case en BDD (prouvé : config/packages/doctrine.yaml → naming_strategy)
- .claude/rules/twig.md — paths: templates/**/*.twig
  · Sélecteurs de test data-test="…" (prouvé : playwright.config.ts + 9 templates)

## Recalées au test du paths → restent dans CLAUDE.md
- « toujours le binaire symfony, jamais php » — aucune lecture de fichier ne la déclencherait.
  À garder dans CLAUDE.md (déjà présente), et candidate à un hook PreToolUse si tu veux la garantie.

## Recalées faute de preuve → question
- « on teste tout avec des mocks plutôt que des stubs » : je ne vois pas de pattern net (les deux
  coexistent dans tests/). C'est une convention voulue, ou juste l'état des lieux ?

## À retirer du CLAUDE.md (ce qui part en règle)
- La section « Règles critiques (app) » → recouverte par doctrine.md, je la retire.
- La section « Stack (app) » → reste : c'est un constat, pas une prescription.
```

### Phase 3 — Arbitrer

Une question ciblée par point ouvert (3 max par tour). Si l'utilisateur ne tranche pas, **n'écris pas
la règle** — une règle absente se rajoute en trente secondes, une règle fausse se paye à chaque
session.

### Phase 4 — Rédaction

Quand l'utilisateur valide, écris. `mkdir -p .claude/rules` au besoin.

- **Format d'un fichier** : voir `${CLAUDE_SKILL_DIR}/references/template.md`. Charge-le maintenant.
- **Un fichier = une zone**, nommé d'après la zone en kebab-case (`doctrine.md`, `twig.md`,
  `marketplace.md`). Regroupe en sous-dossiers (`.claude/rules/frontend/`) seulement quand le nombre
  le justifie — la découverte est récursive.
- **Création / Enrichir** : `Write` pour un nouveau fichier, `Edit` pour ajouter à une zone existante.
- **Éditer** : modifier en place. Pour un **retrait**, supprime la ligne ou le fichier — pas
  d'archivage, pas de « ~~barré~~ » : un contenu périmé laissé dans le fichier continue d'être lu.

### Phase 5 — Vérifier les globs

Étape non négociable : c'est le test du `paths` appliqué à ta propre écriture. Un glob faux ne
produit aucune erreur — il produit une règle qui ne se charge jamais, et personne ne le saura.

**Vérifie avec l'outil `Glob`.** Tu dois interroger le dépôt avec la même grammaire que celle qui
déclenchera la règle : vérifier avec un outil dont la sémantique diffère, ce n'est pas vérifier, c'est
se rassurer. Les deux pièges, tous deux silencieux et de sens opposé :

- **`find` / `ls` mentent en rassurant** — ils ne connaissent pas la sémantique de `**` et comptent
  des fichiers qu'un glob ne prendrait pas. Un motif cassé y paraît sain.
- **`bash` ment en alarmant** — le bash de macOS est en 3.2, sans `globstar` : `**` y dégénère
  silencieusement en `*`. `ls tests/e2e/**/*.ts` renvoie `0` là où le motif matche 5 fichiers. Tu
  conclus « glob mort » sur un glob juste, et tu le « corriges » en le cassant.

Si l'outil `Glob` n'est pas disponible, replie-toi sur `python3 -c "import glob; print(glob.glob(<motif>, recursive=True))"`
ou sur zsh — jamais sur bash, jamais sur `find`. Et dis à l'utilisateur avec quoi tu as vérifié.

Trois contrôles, dans cet ordre :

1. **Le glob matche-t-il ?** Zéro fichier → il est faux (chemin, extension, `**` manquant, dossier
   inexistant). Corrige avant de clore.
2. **Le glob couvre-t-il ce que la règle prétend couvrir ?** C'est le contrôle qui compte, et le seul
   que « ≥ 1 fichier » ne fait pas. Prends la règle au mot : si elle parle du manifeste du plugin,
   liste les fichiers matchés et **vérifie que le manifeste y est**. Un glob qui ramène 61 fichiers
   sur 62 a l'air parfaitement sain.
   - **Le piège des dossiers en point** : `**` ne fabrique pas de composant commençant par un point.
     `plugins/**/*` ramène tout `plugins/` **sauf** `plugins/forge/.claude-plugin/plugin.json`. Le
     dossier en point doit être **écrit** dans le motif : `plugins/**/.claude-plugin/*`. C'est le
     piège le plus vicieux des trois, parce que le glob a l'air juste et n'est pas vide.
3. **Le scope est-il honnête ?** Un match énorme et hétérogène (`**/*`, tout le dépôt) n'est pas une
   règle scopée : c'est une règle de `CLAUDE.md` déguisée. Resserre, ou renonce.

Affiche le compte par règle — c'est le seul retour que l'utilisateur peut vérifier d'un coup d'œil,
donc il doit être juste :

```
doctrine.md     → src/Entity/**, src/Repository/** .......... 31 fichiers ✓
twig.md         → templates/**/*.twig ...................... 24 fichiers ✓
marketplace.md  → plugins/**, plugins/**/.claude-plugin/* ... 62 fichiers ✓ (dont plugin.json)
```

### Phase 6 — Élaguer le CLAUDE.md

Une règle écrite dont la source reste dans le `CLAUDE.md`, c'est le pire des deux mondes : le
contexte est payé deux fois, et le jour où l'une des deux versions bouge, Claude arbitre au hasard
entre elles. Tu n'as pas fini tant que tu n'as pas retiré ce que tu as emporté.

**Uniquement de la soustraction**, et seulement sur ce qui est effectivement parti en règle. Un
passage qui a échoué au test du `paths` reste où il est — c'est le résultat du test, pas un oubli.
Pour tout le reste (ajouter, réorganiser, réécrire une section), c'est `/claude-md` : dis-le à
l'utilisateur, n'y touche pas.

**Cherche le doublon par le sens, pas par les mots.** C'est le piège de cette phase, et il est
systématique : tes règles sont écrites depuis le **code**, pas recopiées du `CLAUDE.md`. Elles disent
donc la même convention dans d'autres termes, et un élagage qui ne cherche que les passages déplacés
littéralement n'en retire aucun. Résultat : neuf règles écrites, quatre lignes retirées, et le
`CLAUDE.md` qui pesait toujours autant — la douleur qu'on venait soulager, intacte.

Reprends chaque règle écrite et relis le `CLAUDE.md` **en entier** en te demandant, pour chaque
puce : *cette convention est-elle déjà dite quelque part ici, même autrement formulée ?* Une ligne
« Interdit : QueryBuilder hors repository » et une règle `doctrine.md` qui dit « tout QueryBuilder
vit dans un repository », c'est un doublon — donc un retrait, même sans un mot en commun.

1. **Montre les retraits avant de les faire** — section par section, avec la règle qui la remplace.
   Une validation explicite, comme pour les règles elles-mêmes.
2. **Retire par `Edit` ciblé**, jamais par réécriture du fichier : le `CLAUDE.md` contient du savoir
   manuel que tu n'as pas produit et que tu ne dois pas perdre.
3. **Laisse un renvoi court** vers les zones — deux ou trois lignes, pas une table exhaustive.
   Ce n'est pas cosmétique : une règle ne se charge qu'à la **lecture** d'un fichier de sa zone, donc
   une session qui démarre par une création pure peut ne jamais la voir. Le renvoi est le filet.

```markdown
## Conventions par zone
Les règles de chaque zone vivent dans `.claude/rules/` et se chargent à l'ouverture des fichiers
concernés : `doctrine.md`, `templates.md`, `tests.md`, `marketplace.md`.
```

### Phase 7 — Clôture

> Règles écrites : `<liste>`.
> Elles ne se chargent que quand Claude ouvre un fichier de leur zone — `/memory` te montre ce qui
> est chargé dans une session donnée, et le hook `InstructionsLoaded` trace le détail si un jour tu
> doutes qu'une règle se déclenche.
> *(si élagage)* `CLAUDE.md` : <N> lignes retirées (parties en règle), renvoi ajouté. Le reste est
> intact.
> *(si des candidates ont été recalées vers CLAUDE.md)* `<règle>` y reste : aucun `paths` ne la
> déclencherait au bon moment.
> *(si des candidates ont été recalées vers un hook)* `<règle>` demande une garantie, pas un conseil :
> un hook `PreToolUse` est le bon outil.

## Argument optionnel

`/rules [zone ou intention]` — si l'argument cible une zone (« doctrine », « les templates », « le
front »), oriente la Phase 1 et les questions vers elle. Applique toujours la Phase 0 (lecture de
l'existant + choix explicite du mode) avant d'enchaîner. **Ne devine jamais le mode à partir de
l'argument.**
