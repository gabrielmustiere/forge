---
name: user-doc
description: Rédige la documentation fonctionnelle d'une feature livrée par le track feature — Diataxis (tutoriel, guide, référence, explication), captures via Playwright/Chrome MCP, tous les cas couverts → `documentation/`.
user_invocable: true
disable-model-invocation: true
argument-hint: "[NNN-f-slug] [--refresh] [--no-capture]"
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
GitHub.

## Périmètre du skill

**Ce qu'il fait** : il transforme une story feature livrée (`forge/story/NNN-f-<slug>/`) en pages
Diataxis sous `documentation/`, avec captures et métadonnées de traçabilité. Il crée les pages, les
enrichit quand une nouvelle story étend la feature, et les rafraîchit quand l'interface a changé.

**Ce qu'il ne fait pas** :

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
documentation part alors dans son propre commit.

## Références à charger

À charger avant la phase 2, une fois chacune. Ces fichiers vivent dans le plugin, pas dans le
projet : lis-les par leur chemin `${CLAUDE_SKILL_DIR}/…`, car un chemin relatif se résoudrait
depuis la racine du projet et échouerait.

- `${CLAUDE_SKILL_DIR}/references/diataxis.md` — les quatre types, la boussole, les règles et
  anti-motifs de chaque type, les cas limites.
- `${CLAUDE_SKILL_DIR}/references/templates.md` — le squelette de chaque type de page, de l'index
  `documentation/README.md` et du manifeste de captures.
- `${CLAUDE_SKILL_DIR}/references/page-metadata.md` — le frontmatter des pages, le manifeste
  `captures.yml`, la procédure de mise à jour et la traçabilité GitHub.

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

## Déroulement

> **Ancienne convention `docs/`** : avant tout, applique la garde `${CLAUDE_SKILL_DIR}/../../references/legacy-docs.md`. Un projet dont
> l'atelier est encore sous `docs/` (`docs/story/`, `docs/stack.md`…) se migre d'abord par
> `/forge:migrate-forge` — tu t'arrêtes et tu y renvoies.

### Phase 0 — Story, sources et mode

**Identifier la story** :

- Argument fourni (`/user-doc 042-f-import-clients-csv`, ou le slug seul) → le dossier
  `forge/story/` correspondant.
- Sinon, déduis-la du working tree (`git status --porcelain forge/story/`) : un seul dossier `-f-`
  modifié, c'est lui. Plusieurs candidats ou aucun → demande.
- Story `-r-` ou `-t-` → refuse et explique en une phrase (voir §Périmètre).

**Lire les sources**, dans cet ordre d'autorité. En cas de contradiction, la première l'emporte et
l'écart se note pour la phase 3 :

1. **Le code livré** : le diff de la story. Avant le commit, `git diff HEAD` plus les fichiers non
   suivis listés par `git status`. Après la livraison, `git show <delivery.commit>` lu dans le
   `metadata.json`. Le §Périmètre du plan donne la liste des fichiers à parcourir.
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
- **`documentation/` existe sans le marqueur d'index forge** (`<!-- forge:user-doc -->` dans
  `documentation/README.md`) → le dossier appartient à autre chose (site mkdocs, doc existante).
  Demande s'il faut s'y intégrer, en ajoutant les quatre sous-dossiers et une section d'index, ou
  utiliser un autre dossier. Ne restructure jamais l'existant.

Annonce en deux lignes la story, la feature, le mode et les sources lues.

### Phase 1 — Inventaire exhaustif des cas

Construis la **matrice des cas** de la feature. C'est le cœur du skill : la documentation n'est
exhaustive que si cette liste l'est.

**Passe chaque catégorie**, l'une après l'autre, contre le code et le pitch. Une catégorie vide se
note « aucun cas » : c'est une conclusion, pas un oubli.

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

- **Preuve** : `fichier:ligne` du code et/ou critère du pitch. Pas de preuve, pas de cas.
- **Capture** : `oui`, ou `non` avec la raison (« invisible à l'écran », « identique à C03 »).
  Tout cas visible et distinct mérite sa capture : un état d'erreur s'illustre autant qu'un succès.

**Contrôles croisés** avant de passer à la suite :

- chaque critère d'acceptation du pitch correspond à au moins un cas ;
- chaque contrainte de validation, chaque contrôle d'accès et chaque message utilisateur ajoutés
  par le diff correspondent à un cas ;
- en Enrichir et Rafraîchir, la matrice part de la table « Cas et comportements » de la page de
  référence existante. Les cas existants gardent leur ID, les nouveaux prennent la suite, et un cas
  supprimé par la story est **retiré** (son ID n'est jamais réutilisé).

### Phase 2 — Plan documentaire (validation)

Charge les trois références, puis applique la boussole Diataxis (`diataxis.md` §Boussole) pour
décider des pages. Le principe est de ne pas forcer le cadre : une page n'existe que si son lecteur
existe.

| Type | Quand le produire | Granularité |
|---|---|---|
| **Référence** | **Toujours** pour une feature visible : c'est le registre de tous les cas | une page par feature |
| **Guide pratique** | **Toujours** au moins un : un par objectif réel de l'utilisateur, y compris les objectifs de récupération (« corriger un import rejeté ») | une page par objectif |
| **Tutoriel** | Seulement si la feature ouvre un **nouveau parcours** qu'un débutant doit apprendre de bout en bout, ou si elle est la première de son domaine | zéro ou une page |
| **Explication** | Seulement si un **pourquoi** conditionne l'usage : règle métier contre-intuitive, arbitrage du pitch, concept nouveau | zéro ou une page **par sujet**, pas par feature |

Présente le plan **avant toute capture** :

```
## Plan documentaire — <titre de la feature>  (mode : Création | Enrichir | Rafraîchir)

| Page | Type | Titre | Cas couverts | Captures |
|---|---|---|---|---|
| documentation/how-to/<feature>-<objectif>.md | Guide | Importer des clients depuis un fichier CSV | C01, C02, C04 | 3 |
| documentation/reference/<feature>.md | Référence | Import de clients — référence | C01 à C14 | 2 |
| … | | | | |

Pas de tutoriel : <raison>.  Pas d'explication : <raison>.   ← ou la page prévue
Couverture : 14/14 cas rattachés · exclus : C09 (<raison>)
Captures prévues : 9 (dont 4 d'états d'erreur)
```

En Enrichir et Rafraîchir, ajoute pour chaque page existante ce qui change : section ajoutée, cas
modifié ou retiré, capture à reprendre.

Fais valider ce plan. L'utilisateur peut retirer ou ajouter une page, ou exclure un cas (la raison
qu'il donne est consignée).

### Phase 3 — Captures

**Option `--no-capture`**, ou aucun MCP navigateur disponible : saute cette phase. Chaque capture
prévue devient un emplacement explicite dans la page (`templates.md` §Capture manquante), avec
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

**Manifeste de captures** : écris (ou complète) `documentation/assets/<feature>/captures.yml`
selon `page-metadata.md` §Manifeste. Chaque capture y décrit comment la reproduire : URL, compte,
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
- **Fichiers** : `documentation/assets/<feature>/<NN>-<description-kebab>.png`, numérotés dans
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

- **Frontmatter** complet dès la création (`page-metadata.md` §Frontmatter) : `cases` liste les
  cas que la page couvre, `stories` et `history` disent d'où elle vient.
- **Référence** : la table « Cas et comportements » contient **tous** les cas de la matrice (hors
  exclus), avec leur ID. C'est le registre que les modes Enrichir et Rafraîchir reliront.
- **Guides** : le titre dit l'objectif (« Importer des clients depuis un fichier CSV », pas
  « Import CSV »). Les cas d'échec liés à l'objectif vont dans la section « Si ça ne se passe pas
  comme prévu », chacun avec un lien vers sa ligne de référence.
- **Images** : `![<ce qu'on voit, et ce qu'il faut remarquer>](../assets/<feature>/<fichier>.png)`
  suivie d'une légende en italique. Le texte alternatif décrit la capture pour qui ne la voit pas.
- **Liens** entre les pages de la feature, relatifs, à chaque endroit où une page renvoie vers un
  autre type, plutôt que d'en absorber le contenu.
- **Index** `documentation/README.md` : crée-le (`templates.md` §Index) ou mets-le à jour, avec la
  ligne de la feature et une entrée par page dans la section de son type.
- **Enrichir** : `Edit` ciblé sur les sections touchées, nouvelles pages si le plan en prévoit,
  `updated` rebougé et une entrée `history` ajoutée sur **chaque page modifiée**. Une page
  inchangée n'est pas touchée.
- **Rafraîchir** : captures reprises, textes recalés sur l'observé validé, une entrée `history`
  « Rafraîchissement » seulement sur les pages réellement modifiées. Son champ `story` vaut `—` :
  la story passée en argument sert à retrouver la feature, mais ce n'est pas elle qui a changé
  l'interface. `stories` n'est pas modifié.

### Phase 5 — Contrôle qualité

Vérifie, et corrige avant de clôturer :

1. **Couverture** : chaque cas de la matrice est cité dans le `cases` d'au moins une page, ou
   exclu avec une raison. Compte-les.
2. **Images** : chaque image référencée existe (`test -f`), et chaque fichier de
   `assets/<feature>/` (hors `captures.yml`) est référencé par une page. Une image orpheline est
   retirée du manifeste et signalée, pas supprimée d'office.
3. **Liens relatifs** : chaque cible existe.
4. **Registre** : aucun identifiant technique du diff (nom de classe, de fichier, de route
   interne) dans les pages. Repère-les avec `Grep` sur les noms relevés en phase 0.
5. **Type** : chaque page passe le test de son type (`diataxis.md` §Contrôle par type).
6. **Frontmatter** : champs obligatoires présents, dates `YYYY-MM-DD`, `type` dans le catalogue.

### Phase 6 — Métadonnées de story et clôture

**`metadata.json` de la story**, selon `${CLAUDE_SKILL_DIR}/../../references/story-metadata.md` :

- **`delivery` absent** (cas normal, avant le commit) → rebouge `updated` et ajoute une entrée
  `{ "type": "Documentation", "description": "<n> pages (types), <m> captures, <x>/<y> cas couverts." }`.
- **`delivery` présent** (story déjà livrée, modes Enrichir ou Rafraîchir après coup) → **ne
  touche pas** le fichier. Un `metadata.json` modifié ferait croire à `/forge:commit` que le commit
  de documentation livre la story, et il écraserait `delivery.commit`. La trace vit alors dans le
  `history` des pages.

**Compte rendu** :

```
## Documentation — <titre de la feature>

Mode : <Création | Enrichir | Rafraîchir> · Story : forge/story/<NNN>-f-<slug>/
Pages : <n> créées, <n> modifiées  (tutoriel <0|1> · guides <n> · référence 1 · explications <n>)
Captures : <n> prises, <n> reprises, <n> à faire
Couverture : <x>/<y> cas · exclus : <IDs et raisons>
Écarts arbitrés : <liste, ou « aucun »>

Fichiers : <liste des chemins>
```

Puis l'étape suivante :

- avant livraison → `/forge:commit` embarquera le code, les documents réalignés et la
  documentation dans le même commit ;
- story déjà livrée → `/forge:commit` produira un commit de documentation seul ;
- captures à faire → `/forge:user-doc <story> --refresh` quand l'environnement sera disponible.

## Arguments

- `/user-doc 042-f-import-clients-csv` : documente cette story (le mode se déduit).
- `/user-doc` : déduit la story du working tree, ou demande.
- `/user-doc <story> --refresh` : force le mode Rafraîchir (re-vérification et reprise des
  captures).
- `/user-doc <story> --no-capture` : rédige sans navigateur, captures laissées « à faire ».
