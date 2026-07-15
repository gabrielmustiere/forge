# Exprimer un besoin depuis le board et le cadrer en brief soumis en revue

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `docs/story/009-f-expression-besoin-interview/`
> **Amont** : aucun

> Depuis un projet déjà cloné, l'utilisateur exprime un besoin en langage libre ; une interview conversationnelle — le vrai skill `feature-interview`, mené côté serveur sur le clone local — le fait émerger tour par tour, ancré sur le code réel. Une fois validé, le `brief.md` produit est déposé sur le repo comme **proposition en revue (brouillon)**, isolée sur sa propre branche, sans jamais toucher la branche principale.

## Contexte

C'est **la première brique « productive » du pivot** : la story 008 a rapatrié le repo en local ; celle-ci s'en sert pour *agir*. Jusqu'ici, entrer dans le workflow forge — exprimer un besoin, le cadrer — imposait un terminal, un repo et une commande. Cette story ouvre une **porte d'entrée dans l'app** : le besoin s'exprime à l'écran, le skill de cadrage tourne côté serveur, et le résultat atterrit versionné sur le repo, en revue.

La faisabilité a été **prouvée par un POC** (interview 3-tours produisant un `brief.md` réel pour ~0,13 $) et la décision d'architecture gravée en **ADR-0002**. Cette story cadre le **fonctionnel** de ce parcours ; elle ne rejoue pas ce choix technique.

Le dépôt du brief adopte le garde-fou que la vision préconisait pour dérisquer le push (hypothèse #3) : **branche dédiée + proposition en brouillon**, jamais de merge automatique, jamais d'écriture sur la branche principale.

## Alignement vision

- **Problème adressé** : la barrière d'accès terminal pour exprimer et cadrer un besoin — cœur du pivot. Le besoin s'exprime enfin sans terminal, et l'intention n'est plus déformée par une transmission orale.
- **Audience servie** : l'utilisateur solo du POC (le dev jouant le PO), qui valide le parcours de bout en bout ; prépare l'entrée du PO non-technique (horizon 1 an).
- **Impact North Star (primaire)** : c'est **le** parcours de la North Star — « besoin exprimé → story cadrée poussée, sans dev ». Un brief déposé en proposition de revue *est* un cadrage produit et poussé.
- **Principes respectés** : #1 « l'app agit mais reste bornée » (cadrage only, brouillon jamais mergé) ; #2 « cadrage only — jamais de code » (le brief est 100 % fonctionnel, aucun code du projet touché) ; #3 « projection lecture-seule préservée » (branche dédiée, `main` et le kanban intacts) ; #4 « la convention forge est le contrat » (`brief.md`, `docs/story/NNN-f-<slug>/`) ; #5 « prouver avant d'ouvrir » (POC mono-utilisateur).
- **Anti-objectifs honorés** : pas de génération de code ; pas de client git généraliste (on crée une branche, on pousse, on ouvre un brouillon — rien de plus, pas de merge, pas de gestion de conflits) ; pas de multi-utilisateur.
- **Hypothèses touchées** : #1 « headless fiable » (prouvée, ADR-0002) ; #2 « le PO exprime assez net » (**cette story la met à l'épreuve**) ; #3 « push maîtrisable » (abordée via branche + brouillon).

> **Note de cohérence** : `docs/product-backlog.md` date d'avant le pivot (2026-07-05) et porte encore l'anti-objectif « éditer / déclencher des skills depuis l'app ». Cette story s'appuie sur la **vision pivotée** (2026-07-08), qui révoque assumément cet anti-objectif. Le backlog est à resynchroniser (`/product-backlog` mode Pivot) — dette signalée, non bloquante.

## Utilisateurs concernés

- **Utilisateur connecté (solo au POC)** — sur un projet déjà cloné, il exprime un besoin, mène l'interview, valide le brief et déclenche son dépôt en revue. Seul rôle impacté.
- Aucun autre rôle : l'app reste mono-utilisateur au POC. Le PO non-technique reste une cible d'horizon, pas un acteur du POC.

## User Stories

- En tant qu'**utilisateur connecté**, je veux **exprimer un besoin en langage libre** depuis un projet cloné, afin d'amorcer un cadrage sans ouvrir de terminal.
- En tant qu'**utilisateur connecté**, je veux **dialoguer tour par tour** avec l'interview qui me pose des questions ancrées sur mon produit, afin de faire émerger un besoin que je ne savais pas encore formuler.
- En tant qu'**utilisateur connecté**, je veux **relire le brief produit et le valider** avant qu'il ne parte, afin de garder la main sur ce qui est déposé.
- En tant qu'**utilisateur connecté**, je veux qu'à ma validation le brief soit **déposé comme proposition en revue (brouillon) sur une branche dédiée**, afin de le retrouver côté repo sans polluer la branche principale ni le kanban.
- En tant qu'**utilisateur connecté**, je veux **voir l'état du parcours** (interview en cours / brief à valider / proposition ouverte / échec + raison), afin de savoir où j'en suis sans quitter l'écran.
- En tant qu'**utilisateur connecté**, je veux que l'interface **ne se bloque pas** pendant un tour d'interview ou un dépôt, afin de continuer à naviguer pendant que l'opération tourne.

## Règles métier

1. **Précondition de clone** : le bouton « Exprimer un besoin » n'est actif que sur un projet dont le repo est **déjà cloné** (état livré par la story 008). Sinon, l'action est indisponible et l'utilisateur est invité à cloner d'abord.
2. **Une interview active à la fois par projet** : tant qu'une interview est en cours ou en attente de validation sur un projet, on n'en démarre pas une seconde sur le même projet.
3. **Skill exécuté = `feature-interview`** : c'est le seul skill de cette story. Il produit un `brief.md` (le pitch enchaîné et les autres skills sont hors scope).
4. **Ancrage sur le code réel** : l'interview tourne dans le clone local du projet — elle lit le code pour poser de bonnes questions. Le brief produit reste **100 % fonctionnel** (aucun nom technique), conformément au skill.
5. **Dialogue tour par tour** : chaque message de l'utilisateur fait avancer l'interview d'un tour ; le contexte de la conversation est conservé d'un tour à l'autre jusqu'à la production du brief.
6. **Validation obligatoire avant dépôt** : le brief produit est **présenté pour relecture** ; rien n'est poussé sur le repo tant que l'utilisateur n'a pas validé. Il peut abandonner (rien n'est déposé).
7. **Dépôt en proposition de revue** : à la validation, le brief (et ses métadonnées) est déposé sur une **branche dédiée** dérivée de l'identité de la story, poussé sur le repo distant, et ouvert comme **proposition en brouillon**. Jamais de merge, jamais d'écriture sur la branche principale.
8. **Accès en écriture requis** : le dépôt réutilise le **token stocké du projet**, qui doit désormais disposer du **droit d'écriture**. Si le token n'a que la lecture (ou en cas d'échec réseau / conflit), le parcours affiche un **échec lisible** et le brief produit **n'est pas perdu** (il reste disponible en local pour re-tenter). Le token n'apparaît jamais en clair (ni dans les traces d'exécution, ni dans le clone local).
9. **GitHub d'abord** : l'ouverture de la proposition en brouillon est livrée **pour GitHub** dans cette story. GitLab est un suivant (le clone 008 supporte déjà les deux, mais l'ouverture d'une proposition diffère d'un hébergeur à l'autre).
10. **Opérations asynchrones** : chaque tour d'interview et le dépôt final sont exécutés en tâche de fond ; l'app expose l'état sans se bloquer.
11. **État déduit / projection préservée** : le kanban continue de lire le `docs/story/` **distant** (story 003) ; la proposition en brouillon vit sur sa branche dédiée et n'apparaît donc pas comme carte tant qu'elle n'est pas mergée — comportement assumé (Fidélité).

## Critères d'acceptation

- [ ] Sur un projet cloné, un bouton « Exprimer un besoin » ouvre une conversation ; il est indisponible si le projet n'est pas cloné.
- [ ] L'utilisateur saisit un besoin libre et reçoit des questions d'interview **ancrées sur son produit**, tour par tour, le contexte étant conservé entre les tours.
- [ ] À l'issue de l'interview, un `brief.md` **fonctionnel** est produit et **présenté pour relecture** avant tout dépôt.
- [ ] Tant que l'utilisateur n'a pas validé, **rien n'est poussé** ; il peut abandonner sans trace côté repo.
- [ ] À la validation, une **branche dédiée** est créée, le brief y est déposé et poussé, et une **proposition en brouillon (GitHub)** est ouverte ; l'app affiche un lien vers elle.
- [ ] Un **token en lecture seule** (ou un échec réseau/conflit) produit un **état d'échec lisible**, sans planter l'app, et le brief local reste récupérable.
- [ ] Une **seule interview active** par projet à la fois.
- [ ] Pendant un tour d'interview long ou un dépôt, l'interface reste **utilisable** (asynchrone).
- [ ] Le token n'apparaît jamais en clair et la branche principale / le kanban ne sont jamais modifiés.

## Hors scope

- **Enchaîner `feature-pitch`** (ou tout skill aval) après le brief — story suivante.
- **GitLab** pour l'ouverture de la proposition — suivant (GitHub d'abord).
- **Merge / revue / cycle de vie de la proposition** dans l'app — on l'ouvre en brouillon, on ne la pilote pas ensuite (anti-objectif « client git généraliste »).
- **UX dédiée au PO non-technique** (formulaire guidé, onboarding) — horizon 1 an ; ici, une conversation simple suffit au POC solo.
- **Multi-interviews parallèles** sur un même projet et **multi-utilisateur** — hors POC.
- **Reprise d'une interview interrompue** après fermeture du navigateur / redémarrage — candidat à une story ultérieure (à confirmer au plan selon le coût).
- **Nettoyage des branches** créées / gestion de leur cycle de vie — non couvert.

## Impacts transverses

- **Traduction / langues** : oui — libellés du bouton, des états et des messages du parcours.
- **Droits d'accès** : inchangé au sens des rôles (tout reste réservé à l'utilisateur connecté), mais **nouvelle exigence sur le token du projet** : il doit désormais autoriser l'écriture sur le repo — à annoncer là où l'utilisateur déclare ou modifie son projet.
- **Sécurité** : sensible — le cadrage fait tourner une IA qui lit les fichiers du projet sur le serveur (à borner : ce qu'elle peut faire et qui elle peut joindre — cf. ADR-0002) **et** manipule un token en écriture (jamais en clair). L'app a besoin d'un accès au fournisseur d'IA, à garder secret.
- **Cloisonnement des données** : non (mono-utilisateur, POC).
- **Apparence / déclinaisons** : non (mono-utilisateur, POC).
- **Exposition à des tiers** : **oui, sortante** — le Board va parler à GitHub pour y ouvrir la proposition en brouillon ; il n'expose lui-même aucune donnée à des tiers.
- **Emails / notifications** : non.
- **Données existantes** : **oui** — l'app doit désormais garder la mémoire de chaque parcours (où en est l'interview, le fil de conversation en cours, la story produite, la branche et la proposition ouvertes, la raison d'un échec éventuel, les dates), pour que l'utilisateur le retrouve. Aucune reprise des données déjà présentes.
- **Comportement par défaut** : un projet cloné gagne le bouton « Exprimer un besoin » ; un projet non cloné est inchangé (bouton indisponible). Le kanban reste inchangé.

## Questions ouvertes

- **Sort d'un brief validé mais dont le dépôt échoue** : re-tenter le seul dépôt sans rejouer l'interview ? Combien de temps garde-t-on le brief local récupérable ? → plan / implem.
- **Ce qu'on autorise à l'IA sur le serveur** : jusqu'où borner ce qu'elle peut lire, écrire et joindre pendant l'interview — niveau d'isolation à cadrer (suite obligatoire ADR-0002). → plan.
- **Reprise après interruption** : si une interview est coupée en cours, la reprend-on ou repart-on de zéro ? (hors scope pressenti, à confirmer selon coût). → plan.

---

## Annexe — Pistes pour le plan

- **Moteur d'exécution** : CLI `claude -p` headless + clé API, `cwd` = clone local, plugin forge chargé, dialogue multi-tours via reprise de session — **déjà décidé (ADR-0002)**, le plan en fait la traduction Symfony (job Messenger par tour, comme la story 008 pour le clone).
- **Persistance de session de dialogue** : la reprise multi-tours s'appuie sur un état de session **sur disque** (cf. ADR-0002) ; le plan tranche son emplacement writable/persistant et sa rétention.
- **Modèle IA par défaut** : Haiku a suffi au POC (qualité du brief au rendez-vous) ; le plan tranche Haiku vs un modèle plus fin selon la qualité sur un **vrai** repo (hypothèse vision #2).
- **Dépôt en proposition** : branche + commit + push (token write, jamais en clair, credential éphémère façon 008) puis ouverture de PR draft via l'API GitHub. Nommage branche / titre PR dérivés de `NNN-f-<slug>`.
- **État & UI** : entité dédiée type `Interview` / `ScopingSession` (extensible pour les skills à venir) vs champs sur `Project` — à trancher ; rafraîchissement Live Component / Turbo sans reload, comme 008.
- **Sandbox d'exécution serveur** : conteneur dédié, liste blanche d'outils, coupure réseau hors fournisseur IA — pistes d'isolation à cadrer (suite obligatoire ADR-0002).
- **Découpe possible** : si le lot « interview → brief » + « dépôt en proposition » s'avère trop gros, il est fractionnable en deux stories (le brief produit et affiché d'abord, le dépôt ensuite). À arbitrer au plan.
