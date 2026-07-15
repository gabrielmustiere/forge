# Frontières des skills (contrat partagé)

Référence partagée par **tous** les skills du plugin. Elle répond à une seule question : où
s'arrête un skill, où commence son voisin. Elle est le pendant de
[`document-format.md`](document-format.md) (qui régit le **contenu** des artifacts) et de
[`story-metadata.md`](story-metadata.md) (qui régit la **timeline**) : celle-ci régit les
**responsabilités**.

Le pipeline forge n'est pas un gros skill découpé en morceaux : c'est une chaîne de skills qui
se passent des artifacts. La valeur de la chaîne tient à une propriété simple — **chaque maillon
fait une chose et fait confiance aux autres pour le reste**. Un skill qui déborde ne rend pas
service : il crée une deuxième source de vérité, et deux sources de vérité divergent toujours.

## Sommaire

- [1. Les trois invariants](#1-les-trois-invariants)
- [2. Propriété d'écriture : un artifact, un écrivain](#2-propriété-décriture--un-artifact-un-écrivain)
- [3. Les deux exceptions assumées](#3-les-deux-exceptions-assumées)
- [4. Ce qui est une frontière, et ce qui n'en est pas](#4-ce-qui-est-une-frontière-et-ce-qui-nen-est-pas)
- [5. Invocation : jamais automatique](#5-invocation--jamais-automatique)
- [6. Déclarer sa frontière dans un SKILL.md](#6-déclarer-sa-frontière-dans-un-skillmd)
- [7. La revue de frontière](#7-la-revue-de-frontière)

## 1. Les trois invariants

**I1 — Propriété d'écriture.** Un artifact a **un seul** skill qui l'écrit. Les autres le
**lisent**. Deux écrivains sur un fichier, c'est deux intentions qui se recouvrent en silence.

**I2 — On juge le livrable, pas le moyen.** Ce qui compte d'un skill, c'est le fichier qu'il
produit : le bon fichier, pertinent, dans son rôle. **Pas** les commandes qu'il a lancées en
chemin. Lancer `cargo`, `composer` ou `pytest` ne produit aucun artifact du pipeline — donc ça
n'engage rien et ça ne se restreint pas. Une capacité ne devient une frontière que si elle permet
de **produire l'artifact d'un autre** : c'est le cas de l'écriture git, et c'est le seul.

**I3 — Invocation explicite.** Un skill du pipeline se déclenche parce que l'utilisateur l'a
tapé, jamais parce qu'un modèle a jugé que ça ressemblait. Le triage automatique entre skills
voisins est probabiliste — donc faux un jour sur N, et un mauvais aiguillage produit le mauvais
document : c'est bien un défaut de livrable, pas de moyen.

Le corollaire de I2, qui vaut d'être dit parce qu'il est contre-intuitif : **une liste
`allowed-tools` généreuse n'est pas un problème**, et une liste avare n'est pas une garantie.
Elles ne parlent que du chemin. La discipline se lit dans les documents produits — d'où le §7.

Et ce n'est pas qu'une affaire de principe : `allowed-tools` **ne restreint techniquement rien**
(§4). Une liste avare n'est donc pas seulement une faible garantie — c'en est zéro.

## 2. Propriété d'écriture : un artifact, un écrivain

| Artifact | Écrivain **unique** | Lecteurs |
|---|---|---|
| `docs/story/<NNN>-f-<slug>/brief.md` | `feature-interview` | `feature-pitch`, `estimate` |
| `docs/story/<NNN>-f-<slug>/pitch.md` | `feature-pitch` | `feature-plan`, `review`, `report`, `estimate`, `adr` |
| `docs/story/<NNN>-f-<slug>/plan.md` | `feature-plan` | `feature-implem`, `review`, `report`, `estimate`, `adr` |
| `docs/story/<NNN>-r-<slug>/plan.md` | `refactor-plan` | `refactor-implem`, `review`, `report`, `estimate`, `adr` |
| `docs/story/<NNN>-t-<slug>/plan.md` | `tech-plan` | `tech-implem`, `review`, `report`, `estimate`, `adr` |
| `docs/story/<NNN>-*/review.md` | `review` | `report`, `adr` |
| `docs/story/<NNN>-*/report.md` | `report` | `sync`, `adr` |
| `docs/story/<NNN>-*/estimate.md` | `estimate` | — |
| `docs/story/<NNN>-*/metadata.json` | **partagé** (voir §3) | Forge Board (lecture seule) |
| `docs/vision.md` | `vision` (+ `sync`, voir §3) | `product-backlog`, `feature-pitch`, `claude-md` |
| `docs/product-backlog.md` | `product-backlog` (+ `sync`, voir §3) | `feature-pitch` |
| `docs/stack.md` | `stack` (+ `sync`, voir §3) | tous les skills techniques, `claude-md` |
| `docs/adr/<NNNN>-<slug>.md` + `docs/adr/README.md` | `adr` | tous |
| `docs/feature-map/<NNN>-<slug>/overview.md` | `doc-feature` | — |
| `CHANGELOG.md` + tags de version | `release` | — |
| Le code du projet (`src/`, tests, config, migrations…) | les trois `*-implem` | `review`, `report`, `doc-feature`, `stack` |
| L'historique git (commits, push) | `commit` (+ `release` pour le commit de version) | — |
| `CLAUDE.md` racine | `claude-md` | tous |

**Le corollaire le plus important** : un skill qui a besoin d'un artifact qu'il ne possède pas
**ne l'écrit pas** — il renvoie vers son propriétaire. `feature-implem` qui découvre que le plan
est faux ne corrige pas le plan : il remonte à l'utilisateur et propose `/forge:feature-plan`.
`review` qui trouve une divergence d'intention ne corrige pas le pitch : elle produit un finding
`[PLAN]`, et c'est `sync` qui réalignera. C'est plus lent d'un tour, et c'est le prix de la
traçabilité.

## 3. Les deux exceptions assumées

Deux artifacts ont plusieurs écrivains. Ce sont des exceptions **délibérées, encadrées et
documentées** — pas des zones grises.

### `metadata.json` — un contrat, pas une intention

Presque tous les skills de story écrivent ce fichier : c'est **voulu**. Il ne porte aucune
intention, seulement des faits (qui est passé, quand, avec quel résultat). Chacun n'y touche que
**son** champ, selon la procédure figée de [`story-metadata.md`](story-metadata.md) : les skills
de création posent `title`/`created`/`tags`, chaque passe rebouge `updated` et **append** au
changelog, `commit` et `release` renseignent `delivery`. Personne ne réécrit le champ d'un
autre ; `created` n'est jamais modifié. Le multi-écrivain est sûr ici parce que le fichier est
**append-only par construction**.

### Les documents projet — `sync` co-écrit, mais n'invente rien

`sync` écrit `vision.md`, `stack.md` et `product-backlog.md`, qui appartiennent à `vision`,
`stack` et `product-backlog`. C'est la contrepartie assumée de son rôle : une story livrée fait
dériver les documents de phase 0, et personne d'autre n'est là pour le voir. Trois garde-fous
rendent l'exception sûre :

1. **`sync` n'écrit que via les modes des propriétaires** (Enrichir / Éditer) — il applique leur
   grammaire, il n'en invente pas une deuxième.
2. **Chaque changement est proposé et validé** par l'utilisateur avant écriture. `sync` ne
   décide jamais seul de ce que devient la vision.
3. **`sync` propage, il ne cadre pas.** Une divergence stratégique large n'est pas absorbée : il
   renvoie vers `/forge:vision` en mode Pivot. La frontière est là — `sync` constate ce que le
   code a fait à la doc, il ne décide pas de la direction du produit.

Aucune autre exception n'est admise. Un besoin de co-écriture qui ne rentre pas dans ces deux
cas est le signe qu'une frontière est mal placée — c'est le découpage qu'il faut corriger, pas
le contrat qu'il faut élargir.

## 4. Ce qui est une frontière, et ce qui n'en est pas

**Ce paragraphe existe surtout pour dire ce qui *n'est pas* une frontière.** L'intuition tentante
— « bornons les outils de chaque skill » — est une fausse piste : elle contrôle le chemin, alors
que le pipeline ne se juge que sur ses livrables (I2). Un `feature-pitch` privé de `cargo` ne
produit pas un meilleur `pitch.md` ; il produit le même, avec une demande d'autorisation en plus.

**Les outillages projet (`composer`, `cargo`, `pytest`, `npm`, `make`, `docker`…) ne sont donc pas
restreints** — et les skills d'implémentation n'en énumèrent aucun. Le projet pré-autorise ce
qu'il veut dans son propre `.claude/settings.json`, seul endroit où une décision de stack a sa
place ; à défaut Claude Code demande, ce qui est un coût de confort, pas un défaut de frontière.

### `allowed-tools` ne restreint rien

Le point technique qui règle le débat, parce qu'il est contre-intuitif et que la formulation
inverse a longtemps figuré ici : **`allowed-tools` n'est pas une allowlist qui borne le skill,
c'est une pré-autorisation.** La doc est explicite — *« It does not restrict which tools are
available: every tool remains callable, and your permission settings still govern tools that are
not listed. »* Un outil absent de la liste reste appelable ; il déclenche simplement une demande
d'autorisation.

Ce qui s'ensuit, et qui vaut d'être posé noir sur blanc :

- **Un `allowed-tools` avare ne défend pas l'écriture git.** Un implem qui liste `Bash(git
  status:*)` peut lancer `git commit` — l'utilisateur sera juste sollicité. La liste n'a jamais
  été le rempart qu'on croyait.
- **Un frontmatter sans `allowed-tools` n'est donc pas plus permissif qu'un autre.** Il est
  identique, aux prompts près. L'omettre est même plus honnête : ça n'affiche pas une garantie
  qui n'existe pas.
- **La frontière git tient à la règle écrite** (I1 + §2), pas au frontmatter. C'est de la prose
  qu'un skill respecte, comme il respecte « ne réécris pas le plan d'un autre ».

Deux mécanismes contraignent réellement, et **aucun n'est du ressort d'un skill** :
`permissions.deny` dans le `.claude/settings.json` du projet (souverain — il bat tout
`allowed-tools`) et `disallowed-tools` dans le frontmatter (retire l'outil du pool, mais
s'efface au message suivant : c'est une portée de tour, pas un contrat). Un projet qui veut
rendre la frontière git *dure* pour les implem le fait chez lui, dans ses permissions — pas ici.

**La seule capacité qui est une frontière, c'est l'écriture git** — parce qu'elle seule permet de
produire l'artifact d'un autre. L'historique est le livrable de `commit` (et le tag celui de
`release`) : un implem qui commite ne franchit pas une limite technique, il livre à la place d'un
voisin, avec un message qui n'est pas passé par le seul skill qui sait les écrire. C'est I1, pas
une question d'outil.

| Famille | Skills | Écriture git | Écrit des fichiers |
|---|---|---|---|
| **Cadrage** | `vision`, `product-backlog`, `stack`, `feature-interview`, `feature-pitch`, `feature-plan`, `refactor-plan`, `tech-plan`, `estimate`, `adr`, `claude-md`, `doc-feature`, `help` | non | ses documents (§2) |
| **Implémentation** | `feature-implem`, `refactor-implem`, `tech-implem` | **non** — le commit appartient à `commit` | le code du projet |
| **Clôture** | `review`, `report`, `sync`, `report-and-sync`, `backfill-metadata` | non | ses documents (§2) |
| **Livraison** | `commit`, `release` | **oui** — c'est son livrable | `CHANGELOG.md` (release) |
| **Vérification** | `test-scenario` | non | **rien** — elle observe, elle ne corrige pas |

Trois règles de rédaction des `allowed-tools`, toutes de confort — puisque rien n'y est
contraignant :

- **Ne rien déclarer plutôt que déclarer une liste de stacks.** Un skill dont l'outillage dépend
  du projet (les trois `*-implem`) **n'a pas d'`allowed-tools`**. Énumérer `cargo`, `poetry`,
  `gradlew`… serait une liste infinie par nature, fausse dès le premier projet Elixir ou le
  premier wrapper maison, et qui n'achèterait aucune garantie en échange de son coût de
  contexte. La décision de stack appartient au `.claude/settings.json` du projet.
- **Un skill dont l'outillage est fini et connu peut le déclarer** — `commit` sait qu'il fera du
  git, `vision` qu'il ne fera que lire et écrire du Markdown. C'est un confort légitime : moins
  de prompts, et une déclaration d'intention lisible. Ça reste une intention, pas une barrière.
  Un orchestrateur (`report-and-sync`) déclare l'**union** de ce qu'il enchaîne, jamais plus :
  non pour se brider, mais pour que sa déclaration reste le reflet fidèle des deux skills dont
  il tient lieu.
- **`Bash(git:*)` est à éviter chez qui ne livre pas.** Non parce que le joker « donnerait »
  `commit` et `push` — il sont disponibles de toute façon — mais parce qu'il les pré-autorise,
  et supprime ainsi la demande de confirmation qui est, en pratique, le dernier signal avant
  qu'un skill livre à la place de `commit`. Énumérer la lecture (`Bash(git log:*)`,
  `Bash(git diff:*)`, `Bash(git show:*)`, `Bash(git status:*)`) garde ce signal.

Le reste — la longueur de la liste, les binaires qui s'y trouvent — est une affaire de confort,
pas de frontière. Ne pas y consacrer d'énergie : elle se dépense mieux sur les documents produits.

## 5. Invocation : jamais automatique

**Tous les skills du plugin portent `disable-model-invocation: true`.** Sans exception.

La raison n'est pas la prudence, c'est le voisinage : le pipeline aligne des skills dont les
descriptions se ressemblent nécessairement (`report` « compte rendu d'écarts », `sync`
« réalignement de la doc », `report-and-sync` « compte rendu **puis** réalignement »). Aucune
formulation ne rendra ce triage fiable — trois skills qui décrivent trois découpages du même
moment du cycle sont *intrinsèquement* ambigus pour un classifieur. Le seul aiguillage sûr est
celui de l'utilisateur qui tape le nom.

C'est aussi ce qui autorise les descriptions à être **riches et honnêtes** plutôt que
défensivement disjointes : elles servent le `/forge:help` et le choix humain, pas un
classifieur.

## 6. Déclarer sa frontière dans un SKILL.md

Chaque `SKILL.md` porte une section **`## Périmètre du skill`** qui dit, dans cet ordre :

1. **Ce que le skill fait** — une phrase, un seul but.
2. **Ce qu'il ne fait pas, et à qui il renvoie.** Nommer le skill voisin (`/forge:feature-plan`),
   pas « une autre étape ». Un renvoi sans destinataire n'est pas un renvoi.
3. **La table d'aiguillage** quand plusieurs skills sont voisins — format « Si <question> → alors
   <skill> » (voir `tech-plan` §Quand utiliser ce skill vs les autres, qui sert de modèle).

Le piège à éviter : le « en profiter pour ». Un implem qui *en profite pour* restructurer, un
tech-plan qui *en profite pour* changer un comportement métier, un review qui *en profite pour*
corriger le pitch. Le test : **si le diff ne peut pas se scinder proprement en deux, le
périmètre est déjà franchi.**

## 7. La revue de frontière

**Elle se fait sur le document produit, pas sur le skill.** C'est le sens de I2 : un skill est du
Markdown, sa prose ne s'exécute pas, et ses `allowed-tools` ne disent rien de la qualité de ce
qu'il livre. Lire le frontmatter rassure ; lire le `pitch.md` renseigne.

**Les trois questions, dans cet ordre** — la première est de loin la plus rentable :

1. **Est-ce le bon fichier ?** Le skill a-t-il écrit *son* artifact, et lui seul (§2) ? Un
   `review` qui a corrigé le `pitch.md`, un `feature-implem` qui a réécrit son plan, un skill qui
   a commité : c'est là que les vraies violations se voient, et elles se voient dans un `git
   diff`, en une ligne. C'est le contrôle le plus fiable du lot.
2. **Le contenu est-il dans son rôle ?** C'est-à-dire dans son **registre** et son **format** —
   et ça, c'est [`document-format.md`](document-format.md) qui le dit : un `pitch.md` sans nom de
   classe, un `plan.md` qui ne re-justifie pas le métier, un `report.md` qui constate au lieu de
   défendre. Cette charte est le vrai référentiel de « respecter son rôle » ; celle-ci ne fait que
   dire qui écrit quoi.
3. **Le document est-il pertinent ?** Les sections canoniques sont-elles remplies de contenu réel
   plutôt que de placeholders ? Les critères sont-ils vérifiables ? Un document conforme au format
   mais creux a franchi une frontière plus grave qu'un `allowed-tools` trop large.

**Ce qui se vérifie accessoirement, dans le frontmatter** — deux points, parce qu'ils touchent
au livrable et pas au chemin :

- L'écriture git (`add`, `commit`, `push`, `tag`) **pré-autorisée** seulement chez `commit` et
  `release`. Ailleurs, c'est la demande de confirmation qui doit rester en place (§4).
- `disable-model-invocation: true` (un mauvais aiguillage produit le mauvais document).

**Ce qui ne se vérifie pas** : la présence d'`allowed-tools`. Son absence n'ouvre aucune porte —
elle n'en fermait aucune (§4). Les trois `*-implem` n'en ont pas, délibérément. Quels binaires,
combien, lesquels : idem, **ne se revoit pas**. Une capacité déclarée mais jamais utilisée se
retire par hygiène si on la croise (`test-scenario` a perdu ainsi un `curl` mort), mais ce n'est
pas un défaut de frontière : c'est du ménage.

Le vrai contrôle de l'écriture git n'est pas dans le frontmatter, il est dans `git log` : un
commit dont le message n'a pas la forme que `commit` produit est le signe qu'un skill a livré à
la place d'un voisin. C'est la question 1, encore.

**Le signal le plus fiable reste le frottement.** Un skill qui a besoin d'écrire dans le document
d'un voisin, un renvoi vers un autre skill qu'on trouve pénible et qu'on est tenté de
court-circuiter : ce sont des symptômes de frontière mal placée. La réponse est de revenir ici et
de corriger le découpage — jamais d'élargir la zone d'écriture.
