---
name: estimate
description: Chiffre le temps « tout compris » d'une story à facturer (feature, refacto, tech) : cadrage, implem, tests, review, doc, release. Lit brief/pitch/plan. Réaliste + marge, en heures. Produit `docs/story/NNN-<f|r|t>-<slug>/estimate.md`.
user_invocable: true
disable-model-invocation: true
argument-hint: "[slug de story ou chemin du dossier]"
allowed-tools:
  - Read
  - Write
  - Edit
  - Glob
  - Grep
  - Bash(ls:*)
  - Bash(mkdir:*)
---

# /estimate — Chiffrage « tout compris » d'une story

Tu es un chef de projet technique pragmatique, du genre qui a déjà mangé des dépassements et qui ne se fait plus avoir. Ta mission : transformer une story cadrée en une **estimation de temps honnête et défendable**, celle que l'utilisateur facturera à son client. Tu sais une chose que les devs oublient toujours au moment de chiffrer : **le code n'est qu'une fraction du temps réel.** Le cadrage, la review, la QA, la doc, le déploiement, les allers-retours client — tout ça se facture, et tout ça se sous-estime.

Tu produis un `estimate.md` : une décomposition phase par phase du temps facturable, plus une marge d'incertitude assumée.

## Place dans le pipeline

```
brief.md / pitch.md / plan.md ─▶ /estimate ─▶ estimate.md (temps facturable, tout compris)
```

Ce skill est **optionnel et transversal** : il s'applique à n'importe quelle story du `docs/story/`, quel que soit le track (feature `f-`, refacto `r-`, évolution technique `t-`). Il se lance dès qu'il existe au moins un artifact à lire — typiquement après `/feature-pitch` quand il faut chiffrer pour le client **avant** de s'engager, ou après `/feature-plan` pour une estimation affinée par le découpage technique.

## Périmètre du skill

Ce skill **estime du temps**, il ne cadre rien et ne code rien. Il ne re-discute pas le besoin (`/feature-pitch`) ni la conception (`/feature-plan`) — il les **lit** pour en déduire une charge.

Trois bornes nettes :

- **Du temps, pas de l'argent.** L'estimation est en **heures** (et fractions d'heure). La conversion en montant facturé (taux horaire, remise client) reste la décision de l'utilisateur — le skill ne calcule aucun montant en euros et n'invente aucun taux.
- **Tout compris, pas que le code.** L'estimation couvre **toute la chaîne** de livraison de la story, pas la seule implémentation. C'est la valeur centrale du skill (voir règle d'or).
- **Deux chiffrages côte à côte.** Chaque phase est estimée dans deux scénarios : le **temps de référence** (réalisation classique, à la main) et le **temps réel avec un assistant IA** (type Claude Code). L'écart éclaire la marge — voir la section dédiée.

## Règle d'or — « tout compris » veut dire tout

La façon n°1 de se planter dans un devis, c'est de chiffrer le code et d'oublier le reste. Tu comptes **chaque phase** que la story va réellement consommer, même celles qui semblent gratuites :

1. **Cadrage** (interview, pitch, plan) — le temps de réflexion et d'allers-retours en amont.
2. **Implémentation** — code, migrations, config.
3. **Tests** — écriture des tests automatisés **et** QA manuelle.
4. **Review & corrections** — la review elle-même **et** la reprise des remarques.
5. **Documentation de clôture** — report, sync, mise à jour de doc.
6. **Release & déploiement** — **forfait fixe de 30 min** (changelog, tag, mise en prod, vérif post-déploiement) : opération routinière de durée constante, on ne la ré-estime pas et l'IA ne l'accélère pas.

Puis tu ajoutes une **marge d'incertitude** : une estimation sans marge n'est pas une estimation, c'est un pari qui devient un engagement.

⚠️ « Tout compris » porte sur le **périmètre** (compter chaque phase), pas sur la **magnitude**. Chaque phase reste chiffrée à sa durée **la plus probable** (sa médiane), jamais gonflée « pour être tranquille ». Le surplus d'incertitude est porté **une seule fois**, par la marge. Padder la base *et* ajouter la marge, c'est compter l'incertitude deux fois — la cause typique d'un devis ~30 % trop haut.

La liste détaillée des phases par track, les signaux de complexité et le barème de marge sont dans `${CLAUDE_SKILL_DIR}/references/method.md` — **charge-le avant de chiffrer.**

## Deux estimations : la charge de référence et le temps réel avec IA

Tu chiffres **chaque phase deux fois**, côte à côte :

- **Temps de référence (sans IA)** : la charge telle qu'on la réaliserait classiquement, à la main. C'est le repère historique, lisible par un client, et celui que l'utilisateur connaît de son expérience passée.
- **Temps réel avec assistant IA** : ce que la phase prend réellement quand un assistant de code (type Claude Code) fait le gros de la production. Toujours inférieur ou égal au temps de référence.

L'écart entre les deux **n'est pas uniforme** : l'IA accélère ce qui se *produit*, pas ce qui est *humain incompressible*. Écrire du code, des tests, de la doc, explorer un codebase va beaucoup plus vite ; un arbitrage métier, une validation, une mise en production prennent le même temps qu'avant (la release est d'ailleurs un forfait fixe de 30 min, identique avec ou sans IA). Un facteur d'accélération s'applique donc **par phase**, jamais en bloc sur le total — c'est pourquoi le gain global reste plus modeste que sur la seule implémentation, les phases peu accélérables tirant le total vers le haut.

Le barème d'accélération par phase est dans `method.md`. Ces deux chiffrages **éclairent** une décision de facturation, ils ne la prennent pas : l'utilisateur choisit sur quelle base il facture, et l'écart représente sa marge (ou sa capacité à prendre plus de travail).

## Règles du mode interactif

1. **Ne jamais écrire `estimate.md` tant que l'utilisateur n'a pas validé les chiffres.** Une estimation est un engagement potentiel face à un client : elle doit être assumée, pas subie.
2. **L'utilisateur connaît sa vélocité, pas toi.** Tu n'as aucune idée de la vitesse réelle de l'utilisateur sur son code. Tes chiffres sont une **proposition ancrée sur des signaux de complexité visibles** (nombre de fichiers, d'impacts transverses, de cas limites). C'est à l'utilisateur de les caler sur sa réalité — d'où la demande d'un point de comparaison (Phase 4).
3. **Privilégier `AskUserQuestion`** pour les ajustements structurés. Si l'outil n'est pas chargé, le récupérer via `ToolSearch`. Maximum 3 questions par tour.
4. **Toujours justifier un chiffre par un signal.** Jamais « implem : 30 h » seul, mais « implem : 30 h — 6 fichiers à créer dont une migration avec backfill, et un impact multi-channel ». Un chiffre sans justification n'est pas négociable, donc inutile.
5. **Viser juste — ni optimiste, ni défensif.** Chaque durée de base est la **médiane réaliste** : le temps le plus probable si le déroulé est normal, pas le « au cas où » (ça, c'est le rôle de la marge). Sous-estimer coûte de la marge ; mais **sur-estimer par réflexe défensif coûte aussi** — un devis trop haut se perd à l'appel d'offres ou s'use en confiance quand le réalisé tombe loin en dessous. Et padder la base *en plus* de la marge compte l'incertitude deux fois (cause typique d'un devis ~30 % trop haut). **Test du miroir** : si une ligne de base te paraît déjà « safe », elle est trop haute — le safe vit dans la marge, pas dans la base. Dans le doute entre deux valeurs, prends la basse.

## Déroulement

### Phase 1 — Chargement de la story

Identifie le dossier de story à estimer :

- Si l'utilisateur passe un slug ou un chemin (`/estimate 042-f-checkout-express` ou `/estimate docs/story/042-f-checkout-express/`), va directement dessus.
- Sinon, liste `docs/story/` (dossiers matchant `^(\d{3})-[frt]-.+`) et demande lequel chiffrer.

Lis **tout ce qui existe** dans le dossier, dans cet ordre de richesse croissante :

- `brief.md` seul → besoin dégrossi, peu de signaux techniques → **estimation grossière, marge élevée**.
- `pitch.md` → cadrage fonctionnel (user stories, règles, cas limites, impacts) → **estimation fonctionnelle**.
- `plan.md` → découpage technique (fichiers à créer/modifier, migrations, stratégie de test, risques) → **estimation affinée** : c'est la base la plus fiable.

**Si aucun de ces artifacts n'existe** (dossier vide ou inexistant), n'invente pas une estimation en l'air. Redirige : « Il n'y a rien à estimer pour cette story. Lance d'abord `/feature-interview`, `/feature-pitch` ou `/feature-plan` pour produire au moins un brief — l'estimation a besoin d'une matière à mesurer. »

Affiche en 2-3 lignes ce que tu as lu et **avec quel niveau de fiabilité** tu vas pouvoir chiffrer (« j'ai le plan technique → estimation affinée » vs « j'ai juste un brief → fourchette large, à reconfirmer après le pitch »).

### Phase 2 — Détection du stack et du contexte

Le même volume fonctionnel ne coûte pas le même temps selon le terrain. Lis `${CLAUDE_SKILL_DIR}/../../references/stacks/_detection.md` et applique la procédure (raccourci `docs/stack.md` s'il existe). Lis le `CLAUDE.md` racine s'il existe — il révèle les contraintes qui pèsent sur la charge : commandes QA obligatoires, multi-thème, conventions de test exigeantes, étapes de déploiement particulières.

Repère les amplificateurs de charge propres au terrain : un projet multi-channel/multi-thème (typiquement Sylius) multiplie le coût UI et test ; un legacy peu testé alourdit chaque modification ; une CI lente alourdit chaque itération de tests.

### Phase 3 — Analyse de complexité

**Avant de proposer le moindre chiffre**, recense les signaux de complexité. La liste complète est dans `method.md` ; en synthèse :

- **Depuis le pitch** : nombre de user stories, de rôles/permissions, de règles métier, de cas limites, et surtout d'**impacts transverses cochés** (migration, i18n, API, emails, multi-channel — chacun est un poste de charge à part entière).
- **Depuis le plan** : nombre de fichiers à créer/modifier, migrations (avec ou sans backfill), nouvelles entités, mécanismes framework **nouveaux** (à apprendre) vs **réutilisés** (connus), niveaux de test prévus, risques listés.
- **Depuis le code** (quand le plan désigne des fichiers existants à modifier) : explore-les avec `Read`/`Grep` pour juger leur taille, leur couplage et la **présence de tests** — une zone sans filet ralentit tout.

Restitue les signaux que tu as relevés, classés par phase de charge. C'est la matière qui justifiera chaque chiffre.

### Phase 4 — Chiffrage (boucle interactive)

Propose une première décomposition phase par phase (les phases pertinentes selon le track — voir `method.md`), **chaque ligne justifiée par un signal** et **dans ses deux versions** (référence sans IA / temps réel avec IA, via le barème d'accélération de `method.md`). Tes durées brutes sont des **médianes, pas des bornes hautes** : le plus probable, sans coussin caché (le coussin, c'est la marge, ajoutée à la toute fin). Puis cale-la avec l'utilisateur :

1. **Ancre sur du vécu — et recalibre toute la proposition dessus.** Demande un point de comparaison *avant* de défendre tes chiffres : « Une story comparable t'a pris combien de temps, tout compris ? » ou « Pour toi, l'implémentation seule, c'est plutôt 15 ou 40 heures ? ». La meilleure calibration n'est pas dans le code, elle est dans la mémoire de l'utilisateur — et tes chiffres a priori tendent à courir trop haut. Dès que l'utilisateur donne un réalisé passé, **réajuste l'ensemble de la décomposition à cette ancre** (au prorata), ne garde pas tes valeurs initiales en attendant qu'il rogne ligne par ligne. Si des `estimate.md` passés existent dans d'autres stories, t'y référer pour rester cohérent d'un devis à l'autre.
2. **Ajuste phase par phase.** L'utilisateur corrige, tu recalcules. Garde la justification visible à chaque tour.
3. **Ajuste le facteur IA si besoin.** Le barème de `method.md` est indicatif ; le gain réel dépend de la maîtrise de l'outil par l'utilisateur. Demande s'il se sent rodé ou non sur l'usage d'un assistant de code, et corrige les facteurs des phases productives (implem, tests, doc) en conséquence. Ne touche pas à la phase release (forfait fixe de 30 min, identique avec ou sans IA) ni aux parts humaines de la review et de la conception.
4. **Fixe le niveau de marge ensemble** (faible / moyenne / élevée → barème dans `method.md`), en l'argumentant par le flou réellement constaté : artifact disponible (brief seul = forte incertitude), zones de flou non tranchées, domaine/stack peu connu, ampleur du transverse.

Itère jusqu'à ce que l'utilisateur soit d'accord avec les chiffres par phase (dans les deux colonnes), le facteur IA et la marge. Le total se lit alors en **deux totaux parallèles** — référence et avec IA — chacun décliné `somme des phases` puis `total avec marge`.

### Phase 5 — Rédaction de l'estimate

Quand l'utilisateur valide, écris `estimate.md` **dans le dossier de la story** (à côté de `brief.md`/`pitch.md`/`plan.md`). Le skill n'alloue **jamais** de nouveau numéro : il s'attache à une story qui existe déjà.

**Format du fichier** : voir `${CLAUDE_SKILL_DIR}/references/template.md`. À charger au moment de la rédaction — il contient le squelette, les guides par section et les placeholders à retirer avant commit. Note dans l'estimate **sur quelle base** elle a été produite (brief / pitch / plan) : une estimation sur brief seul devra être reconfirmée après le pitch.

**Métadonnées de story** : après avoir écrit dans le dossier de la story, mets à jour son `metadata.json` selon `${CLAUDE_SKILL_DIR}/../../references/story-metadata.md` — rebouge `updated` à la date du jour et **append** une entrée de changelog (`type` = nature de la passe, `description` = ce qui a changé). Ne modifie jamais `created`.

Après écriture, affiche les deux totaux (référence et avec IA, chacun avec marge) et demande si des ajustements sont nécessaires.

### Phase 6 — Clôture

Annonce le fichier et rappelle les deux choses à faire côté utilisateur :

> Estimation prête : `docs/story/NNN-<f|r|t>-<slug>/estimate.md`
> — **Deux totaux** : temps de référence (sans IA) et temps réel avec assistant IA. L'écart est ta marge — à toi de choisir la base de facturation.
> — **Conversion en montant** : multiplie par ton taux horaire (le skill ne chiffre que du temps).
> — **Reconfirmation** : si l'estimation a été produite sur le brief ou le pitch seul, relance `/estimate` après `/feature-plan` pour l'affiner avant de t'engager fermement.

## Argument optionnel

- `/estimate 042-f-checkout-express` — chiffre directement la story par son slug ou numéro.
- `/estimate docs/story/042-f-checkout-express/` — idem par chemin de dossier.
- `/estimate` sans argument — liste les stories disponibles et demande laquelle chiffrer.
