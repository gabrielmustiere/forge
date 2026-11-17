---
name: user-doc
description: Rédige la documentation fonctionnelle au standard forge (`documentation/`) — Diataxis, tous les cas couverts, captures — depuis une story livrée ou un thème, et reprend les pages annotées en relecture.
user_invocable: true
disable-model-invocation: true
argument-hint: "[NNN-f-slug | --theme <dossier> | --reprendre [page]] [--refresh] [--no-capture]"
allowed-tools:
  - Read
  - Grep
  - Glob
  - Write
  - Edit
  - Bash(ls:*)
  - Bash(find:*)
  - Bash(mkdir:*)
  - Bash(mv:*)
  - Bash(test:*)
  - Bash(git log:*)
  - Bash(git diff:*)
  - Bash(git show:*)
  - Bash(git status:*)
  - Bash(printf:*)
  - Bash(shasum:*)
  - mcp__playwright__*
  - mcp__chrome-devtools__*
---

# /user-doc — Documentation fonctionnelle d'une feature

Tu rédiges la documentation **destinée aux utilisateurs** d'une feature passée par le track
feature : ce qu'ils peuvent faire, comment, ce qui se passe dans chaque cas, et pourquoi ça
marche ainsi. Tu suis la méthode **Diataxis** (quatre types de pages, un seul type par page), tu
illustres par des **captures prises dans l'application qui tourne**, et tu couvres **tous les
cas**, pas seulement le parcours nominal. La documentation est **vivante** : chaque story qui fait
évoluer la feature la fait évoluer, et ses métadonnées permettent d'en retracer l'histoire dans
GitHub. Elle suit le **standard forge** du dossier `documentation/`, qui la rend lisible par
n'importe quel outil de lecture ou de relecture.

## Périmètre du skill

**Ce qu'il fait** : il écrit des pages Diataxis sous `documentation/`, au standard, avec captures
et métadonnées de traçabilité. Trois entrées :

- **une story feature livrée** (`forge/story/NNN-f-<slug>/`) : il crée les pages de la feature, les
  enrichit quand une nouvelle story l'étend, et les rafraîchit quand l'interface a changé ;
- **un thème** (`--theme <dossier>`) : il documente une zone de l'application qui n'est passée par
  aucune story, ou qu'aucune story ne couvre en entier, à partir du code livré ;
- **une reprise** (`--reprendre`) : il réécrit les blocs que la relecture a annotés, sur les pages
  `a-reprendre`, et les renvoie en relecture.

**Ce qu'il ne fait pas** :

- Il ne valide pas une page : la validation appartient à la relecture (un outil de relecture, ou la
  commande de secours du projet). Il n'écrit jamais `validee`, `validee_par` ni `validee_le`, et
  dans `_relecture/` il ne note que `reprise: faite`.
- Il n'écrit aucune navigation dans les pages (fil d'Ariane, page suivante, liste des pages d'un
  dossier, pages par profil) : l'outil de lecture la calcule (standard §Navigation calculée).

- Il ne cartographie pas le code pour un développeur (entités, services, points d'extension) →
  `/forge:doc-feature`.
- Il ne réaligne ni le pitch ni le plan quand l'application diverge de l'intention : il signale
  l'écart et renvoie vers `/forge:sync`. Si l'écart est un bug, il renvoie vers
  `/forge:feature-implem`. Il n'écrit jamais dans `pitch.md`, `plan.md` ou `report.md` (contrat
  `${CLAUDE_SKILL_DIR}/../../references/skill-boundaries.md` §2).
- Il ne rejoue pas un scénario pour valider une feature : c'est `/forge:test-scenario`. Ici, le
  navigateur sert à **illustrer**, et l'observation des écarts n'est qu'une conséquence.
- Il ne commite pas. Le commit appartient à `/forge:commit`.
- Il ne documente pas un refacto (`r-`) ni une évolution technique (`t-`) : leur comportement
  observable ne change pas, par construction, et il n'y a donc rien à expliquer à l'utilisateur.
  Si un refacto a changé l'interface, c'était une feature, et il faut le dire.

| Si la question est… | → alors |
|---|---|
| « Comment l'utilisateur se sert-il de cette feature, dans tous ses cas ? » | `/forge:user-doc` |
| « Comment ce module est-il construit, où l'étendre ? » | `/forge:doc-feature` |
| « Le parcours marche-t-il dans un vrai navigateur ? » | `/forge:test-scenario` |
| « Le pitch dit-il encore ce que le code fait ? » | `/forge:sync` |

**Place dans le pipeline** : étape **optionnelle** de la phase 3 du track feature, **après
`/forge:sync` et avant `/forge:commit`**. Après `sync`, le pitch est réaligné sur le code livré
et ses critères sont cochés : c'est la meilleure source d'intention. Avant `commit`, la
documentation part dans le même commit que le code qu'elle décrit, et c'est sur ce lien que repose
la traçabilité (voir `page-metadata.md` §Traçabilité). Le skill
fonctionne aussi après coup, sur une story déjà livrée (modes Enrichir et Rafraîchir) : la
documentation part alors dans son propre commit. Les entrées Thème et Reprendre sont **hors
pipeline** : elles ne touchent à aucune story, et leurs pages partent dans un commit de
documentation.

**Place dans la relecture** : une page écrite ou réécrite part `a-relire`, y compris une page
`validee` qu'il modifie : sa dernière version validée reste en ligne jusqu'à la validation suivante
(standard §Versions publiées). Le projet la fait
relire (par un outil de relecture qui ouvre une PR, ou par sa commande de secours). Une relecture
qui annote des blocs met la page `a-reprendre` ; `/user-doc --reprendre` la traite et la renvoie
`a-relire`.

## Références à charger

À charger avant la phase 1, une fois chacune. Ces fichiers vivent dans le plugin, pas dans le
projet : lis-les par leur chemin `${CLAUDE_SKILL_DIR}/…`, car un chemin relatif se résoudrait
depuis la racine du projet et échouerait.

- `${CLAUDE_SKILL_DIR}/../../references/documentation-standard.md` — le standard : arborescence,
  frontmatter, statuts, blocs, trace de relecture, navigation calculée, captures, et ce qui
  appartient au projet.
- `${CLAUDE_SKILL_DIR}/references/diataxis.md` — les quatre types, la boussole, les règles et
  anti-motifs de chaque type, les cas limites.
- `${CLAUDE_SKILL_DIR}/references/templates.md` — le squelette de chaque type de page, des
  sommaires (dossier et racine) et des emplacements de capture.
- `${CLAUDE_SKILL_DIR}/references/page-metadata.md` — les champs de traçabilité, les identifiants
  de cas, le manifeste de captures par défaut, la procédure de mise à jour et la traçabilité GitHub.

Lis aussi **`documentation/_conventions.md`** du projet s'il existe : il dit comment le projet
range ses pages, quels gabarits il précise et comment il produit ses captures. Pour tout ce que le
standard laisse au projet, il l'emporte sur les références du skill.

## Règles

1. **On documente ce que l'application fait, vérifié.** Un comportement n'entre dans une page que
   s'il est prouvé par le code livré **et**, quand il est visible, observé dans le navigateur. Un
   comportement prévu mais absent n'est pas documenté : c'est un écart, et il se signale (phase 3).
   Le lecteur suit la documentation à la lettre : une page qui décrit un comportement absent lui
   fait perdre plus de temps qu'une page qui n'existe pas.
2. **Tous les cas, numérotés.** La matrice de la phase 1 est le contrat d'exhaustivité : chaque
   cas est documenté dans au moins une page, ou exclu avec une raison écrite. Aucun cas ne
   disparaît en silence.
3. **Un type par page.** Un tutoriel ne contient pas de table de référence, un guide n'explique
   pas le pourquoi, une référence ne donne pas d'instructions. Ce qui déborde part dans la page du
   bon type, avec un lien (voir `diataxis.md` §Anti-motifs).
4. **Registre fonctionnel**, comme le pitch : aucun nom de classe, de fichier, de table, de
   service ou de framework. On nomme ce que l'utilisateur voit : écrans, menus, boutons, champs,
   messages. Les chemins de menu s'écrivent `Clients › Importer`.
5. **Messages cités mot pour mot.** Un message d'erreur, un libellé ou une notification se recopie
   tel qu'il s'affiche, lu dans les fichiers de traduction ou les templates, et confirmé à l'écran
   quand il est capturé. On ne le paraphrase jamais.
6. **Langue** : celle du pitch.
7. **Environnement local uniquement.** Les captures se prennent sur un environnement de
   développement ou de recette, jamais en production. Aucune donnée personnelle réelle ni aucun
   secret (jeton, clé, mot de passe) ne doit apparaître à l'écran.
8. **Une page existante se modifie par `Edit` ciblé**, jamais en la réécrivant entièrement : elle
   peut contenir des ajouts manuels que ce skill n'a pas produits.
9. **Valider avant d'écrire.** Le plan documentaire (phase 2) est présenté et validé avant toute
   capture et toute rédaction. Utilise `AskUserQuestion` (via `ToolSearch` s'il n'est pas chargé).
10. **Le standard, sans exception.** Frontmatter complet et conforme (types, profils déclarés à la
    racine, `ordre`, `statut`), aucun champ propre à un générateur de site, aucune navigation
    écrite, images dans un `img/`. Une page qui sort du standard n'est plus lisible par l'outil de
    lecture ni relisible bloc par bloc.

## Déroulement

> **Ancienne convention `docs/`** : avant tout, applique la garde `${CLAUDE_SKILL_DIR}/../../references/legacy-docs.md`. Un projet dont
> l'atelier est encore sous `docs/` (`docs/story/`, `docs/stack.md`…) se migre d'abord par
> `/forge:migrate-forge` — tu t'arrêtes et tu y renvoies.

### Phase 0 — Standard, entrée, sources et mode

**Détecter l'état de `documentation/`** (standard §Détecter le standard), avant toute autre chose :

- **`documentation/index.md` porte `standard:`** → la documentation est au standard. Si sa version
  est plus récente que celle que tu connais, arrête-toi et dis-le.
- **`documentation/README.md` commence par `<!-- forge:user-doc -->`** → ancien format de ce skill
  (dossiers `tutorials/`, `how-to/`, `reference/`, `explanation/`, `assets/`). **N'écris rien** :
  un mélange des deux formats ne serait lisible par aucun outil. Explique l'écart et propose une
  migration, à cadrer par `/forge:tech-plan` : ranger les pages dans des dossiers d'organisation
  avec `ordre`, convertir `type` (`tutorial` → `tutoriel`, `how-to` → `guide`, `explanation` →
  `explication`), retirer `created`, `updated` et `history`, déplacer les images dans `img/`,
  remplacer le `README.md` par la racine `index.md`.
- **`documentation/` existe sans l'un ni l'autre** → le dossier appartient à autre chose (site
  mkdocs, doc existante, ou documentation écrite avant le standard). Demande s'il faut la migrer au
  standard (même renvoi vers `/forge:tech-plan`) ou écrire ailleurs. Ne restructure jamais
  l'existant de ton propre chef.
- **Pas de `documentation/`** → la première passe crée la racine (`templates.md` §Racine). Demande
  à l'utilisateur le nom du produit, ses profils de lecteurs (identifiant, libellé, description)
  et, s'il en a un, son moyen de produire les captures. Ne les devine pas.

**Choisir l'entrée** selon l'argument : `--reprendre [page]` → **mode Reprendre** (section
dédiée, après la phase 6) ; `--theme <dossier>` → **mode Thème** (ci-dessous) ; sinon une story.

**Identifier la story** :

- Argument fourni (`/user-doc 042-f-import-clients-csv`, ou le slug seul) → le dossier
  `forge/story/` correspondant.
- Sinon, déduis-la du working tree (`git status --porcelain forge/story/`) : un seul dossier `-f-`
  modifié, c'est lui. Plusieurs candidats ou aucun → demande.
- Story `-r-` ou `-t-` → refuse et explique en une phrase (voir §Périmètre).

**Lire les sources**, dans cet ordre d'autorité. En cas de contradiction, la première l'emporte et
l'écart se note pour la phase 3 :

1. **Le code livré** : le diff de la story. Avant le commit, `git diff HEAD` plus les fichiers non
   suivis listés par `git status`. Après la livraison, `git show <commit de livraison>` : le
   `delivery.commit` du `metadata.json` s'il existe, sinon le commit retrouvé par la commande de
   `${CLAUDE_SKILL_DIR}/../../references/story-metadata.md` §Livraison dans le commit qui livre. Le §Périmètre du plan donne la liste des fichiers à parcourir.
2. **`pitch.md`** : critères d'acceptation, règles métier, cas limites, hors périmètre, problème
   et audience (la matière des explications).
3. **`report.md`** s'il existe : écarts réalignés et non-livré. Un élément non livré ne se
   documente pas.
4. **`plan.md`** : tests prévus (souvent une liste de cas déjà rédigée) et périmètre.
5. **`metadata.json`** : titre, tags, `backlog`, `delivery`, et la présence d'une entrée `Sync`.

En lisant le diff, **relève les identifiants techniques** qu'il introduit (noms de classes, de
fichiers, de routes internes, de tables). Cette liste sert au contrôle de registre de la phase 5 :
c'est le moyen le plus sûr de repérer un nom de code glissé dans une page utilisateur.

**Garde-fou de fraîcheur** : si le `metadata.json` ne porte aucune entrée `Sync` alors qu'une
`Implémentation` existe, le pitch n'est peut-être pas réaligné. Signale-le et propose de lancer
`/forge:sync` d'abord. L'utilisateur peut passer outre : le code reste de toute façon la source
qui fait foi.

**Déterminer la feature documentée et le mode** :

- **Clé de feature** : le slug de backlog (`backlog` du `metadata.json`) s'il existe, sinon le
  slug de la story sans son préfixe `NNN-f-`.
- Cherche dans `documentation/` les pages dont le frontmatter `features` contient cette clé
  (`Grep` sur `^features:.*<clé>`), puis celles dont `stories` contient la story
  (`^stories:.*<story>`). Les listes du frontmatter s'écrivent sur une seule ligne, justement pour
  que cette recherche suffise (`page-metadata.md` §Frontmatter).
  - **Aucune page** → mode **Création**. Si une feature documentée semble proche (mêmes tags,
    titre voisin), demande si la story l'étend : la réponse bascule en Enrichir sur cette clé.
  - **Des pages existent, la story n'y figure pas** → mode **Enrichir** : la story fait évoluer
    une feature déjà documentée.
  - **La story figure déjà dans les pages**, ou `--refresh` → mode **Rafraîchir** : re-vérifier
    chaque cas contre le code actuel et reprendre les captures, sans changer le périmètre.

**Mode Thème** (`--theme <dossier>`) : le dossier de `documentation/` à écrire ou à compléter
(`equipe/personnes-et-acces`), ou un sujet en clair si le dossier n'existe pas encore (le plan
proposera son emplacement). Il n'y a pas de story : les sources sont, dans cet ordre d'autorité,

1. **le code livré** de la zone : écrans, contrôles d'accès, validations, messages, traductions ;
2. **le glossaire** (`forge/glossaire.md`) s'il existe, pour le vocabulaire et les règles
   d'affichage, et la cartographie fonctionnelle si le projet en tient une ;
3. **les pages déjà présentes** dans le dossier : elles se complètent par `Edit`, et leurs cas
   gardent leur ID.

Relève les identifiants techniques de la zone pour le contrôle de registre, comme pour une story.
La garde de fraîcheur ne s'applique pas. `--refresh` sur un thème re-vérifie chaque cas du dossier
contre le code actuel et reprend ses captures.

Annonce en deux lignes l'entrée (story ou thème), la feature ou le dossier, le mode et les sources
lues.

### Phase 1 — Inventaire exhaustif des cas

Construis la **matrice des cas** de la feature. C'est le cœur du skill : la documentation n'est
exhaustive que si cette liste l'est.

**Passe chaque catégorie**, l'une après l'autre, contre le code et le pitch (en mode Thème : contre
le code seul). Une catégorie vide se note « aucun cas » : c'est une conclusion, pas un oubli.

| Catégorie | Où chercher |
|---|---|
| **Parcours nominaux**, un par objectif utilisateur | critères d'acceptation, routes et écrans ajoutés |
| **Variantes** : options, choix, filtres, chemins alternatifs | champs facultatifs, branches conditionnelles, paramètres |
| **Rôles et permissions** : qui peut, qui ne peut pas, ce que voient les autres | contrôles d'accès, voters, gardes, menus conditionnels |
| **Saisies invalides** : une ligne par contrainte, message exact | contraintes de validation, fichiers de traduction |
| **États vides et premier usage** | templates « aucun résultat », listes vides, onboarding |
| **Limites** : tailles, quotas, pagination, délais | constantes, configuration, contraintes de longueur |
| **États et transitions** : cycle de vie, ce qui est possible dans chaque état | statuts, machines à états, boutons conditionnels |
| **Effets visibles ailleurs** : e-mails, notifications, exports, autres écrans | événements, envois, listeners, mises à jour croisées |
| **Annulation, retour arrière, double action** | confirmations, annulations, idempotence, soumissions répétées |
| **Erreurs système vues par l'utilisateur** : service indisponible, fichier corrompu | exceptions transformées en message, pages d'erreur |
| **Paramétrage** par un administrateur | écrans de configuration, réglages qui changent le comportement |
| **Hors périmètre** : ce que la feature ne fait pas et qu'on pourrait attendre | §Hors périmètre du pitch, non-livré du report |

**Format** : un cas par ligne, identifiant stable `C01`, `C02`… propre à la feature.

| ID | Catégorie | Situation | Comportement (message exact entre guillemets) | Preuve | Page(s) | Capture |
|---|---|---|---|---|---|---|

- **Identifiant** : propre à la feature en mode story, propre au sujet du dossier en mode Thème.
- **Preuve** : `fichier:ligne` du code et/ou critère du pitch. Pas de preuve, pas de cas.
- **Capture** : `oui`, ou `non` avec la raison (« invisible à l'écran », « identique à C03 »).
  Tout cas visible et distinct mérite sa capture : un état d'erreur s'illustre autant qu'un succès.

**Contrôles croisés** avant de passer à la suite :

- chaque critère d'acceptation du pitch correspond à au moins un cas (mode story) ;
- chaque contrainte de validation, chaque contrôle d'accès et chaque message utilisateur ajoutés
  par le diff correspondent à un cas ;
- en Enrichir et Rafraîchir, la matrice part de la table « Cas et comportements » de la page de
  référence existante. Les cas existants gardent leur ID, les nouveaux prennent la suite, et un cas
  supprimé par la story est **retiré** (son ID n'est jamais réutilisé).

### Phase 2 — Plan documentaire (validation)

Applique la boussole Diataxis (`diataxis.md` §Boussole) pour décider des pages. Le principe est de
ne pas forcer le cadre : une page n'existe que si son lecteur existe.

| Type | Quand le produire | Granularité |
|---|---|---|
| **Référence** | **Toujours** pour une feature visible : c'est le registre de tous les cas | une page par feature |
| **Guide pratique** | **Toujours** au moins un : un par objectif réel de l'utilisateur, y compris les objectifs de récupération (« corriger un import rejeté ») | une page par objectif |
| **Tutoriel** | Seulement si la feature ouvre un **nouveau parcours** qu'un débutant doit apprendre de bout en bout, ou si elle est la première de son domaine | zéro ou une page |
| **Explication** | Seulement si un **pourquoi** conditionne l'usage : règle métier contre-intuitive, arbitrage du pitch, concept nouveau | zéro ou une page **par sujet**, pas par feature |

**Placer chaque page** dans l'arborescence du projet : le dossier selon l'organisation que décrit
`_conventions.md` (par thème, par module), sinon selon les dossiers existants ; dans le dossier,
l'`ordre` qui suit la lecture Comprendre → Faire → Référence, en laissant des trous (10, 20, 30)
pour insérer plus tard ; `public` parmi les profils déclarés à la racine. Un nouveau dossier
reçoit son `index.md` (`templates.md` §Sommaire de dossier). Les noms de fichiers disent le sujet
(`importer-des-clients.md`), pas le type.

Présente le plan **avant toute capture** :

```
## Plan documentaire — <titre de la feature ou du dossier>  (mode : Création | Enrichir | Rafraîchir | Thème)

| Page | Type | Titre | Public | Ordre | Cas couverts | Captures |
|---|---|---|---|---|---|---|
| documentation/clients/import/index.md | Sommaire | Importer des clients | admin | 20 | — | 0 |
| documentation/clients/import/importer-des-clients.md | Guide | Importer des clients depuis un fichier CSV | admin | 20 | C01, C02, C04 | 3 |
| documentation/clients/import/import-de-clients.md | Référence | Import de clients — référence | admin | 30 | C01 à C14 | 2 |
| … | | | | | | |

Pas de tutoriel : <raison>.  Pas d'explication : <raison>.   ← ou la page prévue
Couverture : 14/14 cas rattachés · exclus : C09 (<raison>)
Captures prévues : 9 (dont 4 d'états d'erreur), par <moyen du projet | manifeste captures.yml>
```

En Enrichir et Rafraîchir, ajoute pour chaque page existante ce qui change : section ajoutée, cas
modifié ou retiré, capture à reprendre.

Fais valider ce plan. L'utilisateur peut retirer ou ajouter une page, ou exclure un cas (la raison
qu'il donne est consignée).

### Phase 3 — Captures

**Le moyen du projet d'abord.** Si `_conventions.md` déclare comment le projet produit ses
captures (specs Playwright, script de capture…), utilise-le : ajoute ou modifie la description de
chaque capture prévue dans ce moyen, selon ses règles (cadrage, taille, données), puis lance la
commande de régénération qu'il indique, après accord de l'utilisateur. Vérifie ensuite chaque
image écrite comme tu vérifierais une prise de vue (ci-dessous) et relève les textes visibles. Les
règles de cette phase sur le manifeste et le MCP ne s'appliquent alors pas ; l'arbitrage des écarts,
si.

**À défaut**, le skill prend les vues lui-même, par MCP navigateur, et les décrit dans le manifeste
`img/captures.yml` (`page-metadata.md` §Manifeste de captures par défaut).

**Option `--no-capture`**, ou aucun moyen disponible : saute cette phase. Chaque capture prévue
devient un emplacement explicite dans la page (`templates.md` §Capture manquante), avec
`status: à-faire` dans le manifeste. La clôture les liste, et `/user-doc --refresh` les prendra
plus tard.

**Environnement**, comme `/forge:test-scenario` : lis la section environnement de l'`AGENTS.md`
(et ce que le `CLAUDE.md` y ajoute) pour les URLs, les comptes de test et la locale. Si une
information manque, demande-la et suggère de l'ajouter à l'`AGENTS.md`. N'invente jamais une URL
ni un identifiant. Une URL qui ne ressemble pas à un environnement local ou de recette
(`localhost`, `*.wip`, `*.test`, `*.local`, `staging`…) → demande confirmation avant de naviguer.

**Application injoignable** (première navigation en échec) : ne lance pas la campagne. Demande s'il
faut démarrer l'application, avec la commande lue dans l'`AGENTS.md` et jamais devinée, ou
continuer comme avec `--no-capture`. Une campagne lancée contre une application arrêtée produit
seulement des captures de pages d'erreur.

**Manifeste de captures** : écris (ou complète) le `captures.yml` du dossier `img/` où vont les
images, selon `page-metadata.md` §Manifeste. Chaque capture y décrit comment la reproduire : URL, compte,
état préalable des données, étapes, cadrage, viewport. C'est ce manifeste qui rend la
documentation **re-capturable** à chaque évolution.

**Exécution déléguée** : la prise de vue produit des snapshots volumineux. Si l'outil `Agent` est
disponible, confie la campagne à un sous-agent `general-purpose` et donne-lui le manifeste,
l'environnement chargé, les règles ci-dessous et le format de retour attendu : pour chaque
capture, le fichier écrit, `ok` ou `échec`, et les textes visibles à l'écran, recopiés mot pour
mot (messages, libellés). Précise-lui qu'il **constate sans trancher** : quand l'écran diffère de
l'attendu, il prend quand même la capture, décrit la différence et passe à la suivante. Il ne peut
pas interroger l'utilisateur, et l'arbitrage des écarts (ci-dessous) revient à la session. Si
l'outil `Agent` n'est pas disponible, exécute la campagne dans la session.

Règles de prise de vue :

- **Outils** : Playwright MCP (`browser_resize`, `browser_navigate`, `browser_snapshot`,
  `browser_take_screenshot` avec `filename`, et `ref` pour cadrer un élément) ou Chrome DevTools
  MCP (`resize_page`, `navigate_page`, `take_snapshot`, `take_screenshot` avec `filePath`, et
  `uid` pour un élément). Un outil incapable d'écrire la capture sur disque ne convient pas.
- **Viewport** par défaut `1280x800`, identique pour toutes les captures d'une feature. Ajoute
  `390x844` seulement si le pitch vise le mobile.
- **Cadrage** : la page entière pour situer un écran, l'élément seul (formulaire, message, modale)
  pour un cas. Une capture montre un seul cas.
- **Données** : comptes et jeux de test uniquement. Si un cas exige un état particulier (liste
  vide, erreur), crée-le par l'interface sur l'environnement local, et ne modifie rien d'autre.
  Une donnée personnelle réelle ou un secret visible → n'enregistre pas la capture et demande.
- **Fichiers** : `documentation/<dossier>/img/<feature>-<NN>-<description-kebab>.png` (le `img/`
  du dossier de la page, ou celui d'un dossier parent si le projet les regroupe), numérotés dans
  l'ordre du manifeste. Si le MCP écrit dans son propre dossier (`.playwright-mcp/` par exemple),
  déplace le fichier final avec `mv` puis supprime les fichiers temporaires de la campagne. En
  Rafraîchir, **réécris le même nom de fichier** : GitHub affiche alors l'avant/après de l'image
  dans le diff.
- **Snapshot après chaque action**, et sélecteurs lus dans le snapshot, jamais devinés.

**Écart observé** : si l'écran ne montre pas ce que la matrice attend (message différent, bouton
absent, comportement autre), **ne documente pas ce cas**, ni sous sa forme attendue ni sous sa
forme observée, tant que l'utilisateur n'a pas tranché :

| Constat | Option proposée |
|---|---|
| Le code fait autre chose que le pitch, sans trace d'écart | bug → `/forge:feature-implem`, ou intention dérivée → `/forge:sync` |
| Le comportement observé est correct, la matrice s'était trompée | corriger la matrice et documenter l'observé |
| L'environnement est en cause (données, configuration) | corriger l'environnement et reprendre la capture |

### Phase 4 — Rédaction

Rédige chaque page selon son template (`templates.md`) et les règles de son type
(`diataxis.md`). Points d'attention :

- **Frontmatter** complet dès la création (standard §Frontmatter d'une page, et
  `page-metadata.md` §Champs de traçabilité) : `ordre` et `public` du plan, `statut: a-relire`,
  `cases` pour les cas que la page couvre, `features` et `stories` pour dire d'où elle vient (mode
  story). Le statut des pages existantes suit `page-metadata.md` §Procédure de mise à jour.
- **Référence** : la table « Cas et comportements » contient **tous** les cas de la matrice (hors
  exclus), avec leur ID. C'est le registre que les modes Enrichir et Rafraîchir reliront.
- **Guides** : le titre dit l'objectif (« Importer des clients depuis un fichier CSV », pas
  « Import CSV »). Les cas d'échec liés à l'objectif vont dans la section « Si ça ne se passe pas
  comme prévu », chacun avec un lien vers sa ligne de référence.
- **Images** : `![<ce qu'on voit, et ce qu'il faut remarquer>](../img/<fichier>.png)` (chemin
  relatif vers le `img/` choisi), suivie d'une légende en italique. Le texte alternatif décrit la
  capture pour qui ne la voit pas.
- **Liens** entre les pages, relatifs, à chaque endroit où une page renvoie vers un autre type,
  plutôt que d'en absorber le contenu.
- **Blocs autonomes** : la relecture se fait bloc par bloc (standard §Blocs et empreintes). Un
  paragraphe ne s'appuie pas sur « ci-dessus » sans le nommer, une capture garde sa légende juste
  sous elle, une étape tient dans son propre titre et ses paragraphes : un relecteur doit pouvoir
  valider, refuser ou réécrire un bloc sans casser ses voisins.
- **Sommaires** : un nouveau dossier reçoit son `index.md` (`templates.md` §Sommaire de dossier),
  un texte de présentation **sans** liste de pages. Aucun index global à tenir : l'outil de
  lecture calcule les sommaires.
- **Enrichir** : `Edit` ciblé sur les sections touchées, nouvelles pages si le plan en prévoit,
  la story ajoutée à `stories` et `cases` mis à jour sur **chaque page modifiée**. Une page
  inchangée n'est pas touchée.
- **Rafraîchir** : captures reprises, textes recalés sur l'observé validé, seulement sur les pages
  réellement modifiées. `stories` n'est pas modifié : la story passée en argument sert à retrouver
  la feature, mais ce n'est pas elle qui a changé l'interface.
- **Thème** : comme Création ou Enrichir, sans `stories` ni `features`.

### Phase 5 — Contrôle qualité

Vérifie, et corrige avant de clôturer :

1. **Couverture** : chaque cas de la matrice est cité dans le `cases` d'au moins une page, ou
   exclu avec une raison. Compte-les.
2. **Images** : chaque image référencée existe (`test -f`), et chaque image que la passe a écrite
   est référencée par une page. Une image orpheline est retirée du manifeste et signalée, pas
   supprimée d'office.
3. **Liens relatifs** : chaque cible existe.
4. **Registre** : aucun identifiant technique du diff (nom de classe, de fichier, de route
   interne) dans les pages. Repère-les avec `Grep` sur les noms relevés en phase 0.
5. **Type** : chaque page passe le test de son type (`diataxis.md` §Contrôle par type).
6. **Standard** : champs obligatoires présents (`title`, `description`, `type`, `public`, `ordre`
   hors racine, `statut`) ; `type` parmi les valeurs du standard ; chaque profil de `public`
   déclaré à la racine ; aucun `ordre` en double dans un dossier ; aucun champ interdit
   (`created`, `updated`, `history`, champs de générateur de site) ; aucune navigation écrite ;
   chaque dossier touché a son `index.md`.

### Phase 6 — Métadonnées de story et clôture

**`metadata.json` de la story**, selon `${CLAUDE_SKILL_DIR}/../../references/story-metadata.md` :

- **`delivery` absent** (cas normal, avant le commit) → rebouge `updated` et ajoute une entrée
  `{ "type": "Documentation", "description": "<n> pages (types), <m> captures, <x>/<y> cas couverts." }`.
- **`delivery` présent** (story déjà livrée, modes Enrichir ou Rafraîchir après coup) → **ne
  touche pas** le fichier : la timeline de la story s'arrête à sa livraison. La trace vit alors
  dans le champ `stories` des pages et dans l'historique git.
- **Mode Thème** : aucune story, rien à faire.

**Compte rendu** :

```
## Documentation — <titre de la feature ou du dossier>

Mode : <Création | Enrichir | Rafraîchir | Thème> · <Story : forge/story/<NNN>-f-<slug>/ | Dossier : documentation/<dossier>/>
Pages : <n> créées, <n> modifiées, toutes a-relire  (tutoriel <0|1> · guides <n> · référence <n> · explications <n>)
Captures : <n> prises, <n> reprises, <n> à faire
Couverture : <x>/<y> cas · exclus : <IDs et raisons>
Écarts arbitrés : <liste, ou « aucun »>

Fichiers : <liste des chemins>
```

Puis l'étape suivante :

- avant livraison → `/forge:commit` embarquera le code, les documents réalignés et la
  documentation dans le même commit ;
- story déjà livrée → `/forge:commit` produira un commit de documentation seul ;
- mode Thème → `/forge:commit` produira un commit de documentation seul ;
- captures à faire → `/forge:user-doc <story> --refresh` quand l'environnement sera disponible ;
- dans tous les cas, les pages attendent leur relecture : le moyen de relecture du projet
  (`_conventions.md`) prend le relais.

## Mode Reprendre

Entrée : `/user-doc --reprendre` (toutes les pages `a-reprendre`) ou `/user-doc --reprendre <page>`
(une seule). Il traite les **demandes de réécriture** laissées par la relecture, et rien d'autre :
pas de matrice, pas de nouvelle page, pas de refonte.

1. **Lister** : les pages dont le frontmatter porte `statut: a-reprendre` (`Grep` sur
   `^statut: a-reprendre`). Pour chacune, lis sa trace `documentation/_relecture/<page>.yml` et
   retiens les blocs `decision: annotee` avec `reprise: a-faire`. Une page `a-reprendre` sans trace,
   ou sans demande ouverte, est une incohérence : signale-la, ne la traite pas.
2. **Retrouver chaque bloc** dans la page par son empreinte (standard §Blocs et empreintes) :
   calcule celle des blocs candidats par `printf '%s' '<texte normalisé>' | shasum -a 256`, et
   garde les 12 premiers caractères. Un
   bloc introuvable (la page a changé depuis la relecture) se signale avec son annotation : la
   demande reste `a-faire`, et l'utilisateur dit où elle s'applique.
3. **Présenter** les demandes, une ligne par bloc : la page, le début du bloc, l'annotation, et la
   réécriture que tu proposes. Une annotation qui contredit le code (« le bouton s'appelle
   Archiver » alors que le template dit « Supprimer ») se vérifie dans le code avant d'être suivie :
   le code fait foi, et l'écart se signale (règle 1). Fais valider avant d'écrire.
4. **Réécrire** chaque bloc par `Edit` ciblé, sans toucher ses voisins. Si la demande impose une
   nouvelle capture, elle passe par la phase 3, limitée à cette capture.
5. **Noter la reprise** : dans la trace, `reprise: faite` sur chaque demande traitée. C'est la seule
   écriture du skill dans `_relecture/`.
6. **Statut** : quand une page n'a plus de demande `a-faire`, `statut: a-relire`. À la relecture
   suivante, seuls les blocs réécrits attendront une décision.
7. **Contrôle** : phase 5, limitée aux pages reprises (images, liens, registre, standard).

**Compte rendu** : les pages reprises, le nombre de demandes traitées, celles laissées `a-faire` et
pourquoi, les écarts au code signalés. Puis `/forge:commit` pour un commit de documentation seul.

## Arguments

- `/user-doc 042-f-import-clients-csv` : documente cette story (le mode se déduit).
- `/user-doc` : déduit la story du working tree, ou demande.
- `/user-doc <story> --refresh` : force le mode Rafraîchir (re-vérification et reprise des
  captures).
- `/user-doc <story> --no-capture` : rédige sans navigateur, captures laissées « à faire ».
- `/user-doc --theme <dossier>` : documente ou complète un dossier de `documentation/` à partir du
  code, sans story. Accepte `--refresh` et `--no-capture`.
- `/user-doc --reprendre [page]` : réécrit les blocs annotés par la relecture, sur toutes les pages
  `a-reprendre` ou sur une seule.
