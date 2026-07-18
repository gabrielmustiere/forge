# Changelog

Toutes les modifications notables de ce projet sont documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Chaque version porte un **titre** et distingue les **évolutions fonctionnelles**
(perceptibles à l'usage) des **évolutions techniques** (internes, outillage, plomberie).

## [Unreleased]

### 🔧 Technique

- **`commit` — `allowed-tools` complétés** : ajout de `Write` et `Edit`, absents alors que la
  Phase 6 du skill écrit `delivery.commit`, `updated` et le changelog dans le `metadata.json` de
  la story (et le crée au besoin). `commit` déclenchait donc une demande d'autorisation à chaque
  livraison, là où `report` et `sync` — qui écrivent le même fichier — les déclaraient déjà.
  Écrire le champ `delivery` de `metadata.json` est la part légitime de `commit` dans l'exception
  multi-écrivain du contrat de frontières (§3).

## [6.5.0] - 2026-07-18 — Amorçage greenfield dans `stack`

### ✨ Fonctionnel

- **`stack` — cas greenfield** : en mode Création, quand aucune brique technique n'est détectée
  et qu'aucun choix n'a encore été fait, le skill ne grave plus un `docs/stack.md` vide. Il propose
  par défaut d'**amorcer le projet depuis `gabrielmustiere/symfony-template`**, puis **personnalise
  les variables d'identité** au nom du projet (`.symfony.local.yaml`, `.env`, `.env.dev` avec
  régénération de `APP_SECRET`, `compose.yaml`, `README.md`, `CLAUDE.md`). La stack existe alors
  réellement sur disque et est cartographiée par la détection normale.

### 🔧 Technique

- Nouvelle référence `skills/stack/references/bootstrap-template.md` (carte des variables du template
  et procédure de récupération sûre préservant les artefacts forge).
- `stack` : nouvelle Phase 2bis, `allowed-tools` étendus (`git clone`, `cp`, `rm`, `mv`, `php`).

## [6.4.0] - 2026-07-17 — Retrait de report-and-sync

### 🔧 Technique

- **`/forge:report-and-sync` retiré** — l'orchestrateur enchaînait `/forge:report` puis `/forge:sync`, mais aucun skill du pipeline ne peut en invoquer un autre : tous portent `disable-model-invocation: true` (contrat de frontières §5), que l'outil `Skill` refuse. Le contournement — lire puis dérouler leur procédure à la main — restait fragile : le modèle tentait quand même `Skill(forge:report)` en première action. Plutôt que d'entretenir cette indirection, la skill est supprimée ; la clôture s'enchaîne désormais à la main, `/forge:report` puis `/forge:sync`. Le pipeline et son contrat « invocation toujours explicite » restent intacts.

## [6.3.0] - 2026-07-16 — Pipeline en quatre phases

### ✨ Fonctionnel

- **Nomenclature en quatre phases** — le pipeline s'articule explicitement en phases 0 (poser le décor), 1 (cadrer), 2 (implémenter) et 3 (clôturer) ; les trois tracks (feature, refacto, tech) deviennent un second axe qui décide des skills appelées en phases 1 et 2.
- **Clôture réordonnée, commit en dernier** — la phase de clôture suit review → report → sync → commit. Le commit embarque d'un coup le code, le `report.md` et les documents réalignés, au lieu de committer avant de documenter.
- **Vitrine enrichie** — le hero du site gagne une identité terminal (une ligne de commande qui se tape toute seule, un curseur clignotant, une entrée en cascade) et un footer harmonisé sur toute la largeur, à l'image du header.

### 🔧 Technique

- **Skills de clôture alignées sur le working tree** — `report`, `sync`, `review` et `commit` lisent les fichiers en cours de livraison (staged + unstaged + untracked) plutôt que l'historique git, cohérent avec le commit qui passe en dernier.
- **Ordres de skills unifiés** — la clôture et les utilitaires sont dans le même ordre entre l'inventaire du plugin, la page de documentation et `llms.txt`.

## [6.2.1] - 2026-07-15 — Site public & documentation en ligne

### ✨ Fonctionnel

- **Site public [forge.mustiere.fr](https://forge.mustiere.fr)** — une vitrine qui présente le pipeline, les trois tracks et l'inventaire des skills, avec une carte de partage social. Servi en HTTPS, indexable par les moteurs, et lisible par les assistants via un `llms.txt` qui décrit le pipeline en entier.
- **Documentation en ligne** — prérequis, installation pas à pas, concepts (la règle d'or de la validation explicite, les artefacts de `docs/story/`, comment choisir son track, le track fast), référence des skills track par track, configuration des permissions et dépannage. Sommaire navigable, contenu tiré du plugin lui-même — aucune commande inventée.
- **Le plugin est inchangé** — cette version ne touche à aucune skill : rien de nouveau à apprendre, `/plugin marketplace update forge` ne fait que suivre le numéro.

### 🔧 Technique

- **Déploiement GitHub Pages** — un workflow unique vérifie puis publie le site, sur domaine personnalisé avec certificat automatique.
- **Garde-fou de version** — le déploiement échoue si le site annonce une version différente de `plugin.json`, désormais seule source de vérité. La dérive n'était pas théorique : le site a déjà affiché une version périmée sans que rien ne le signale.
- **Intégration continue réduite au site** — les jobs PHP (CS-Fixer, PHPStan, PHPUnit, Playwright) sont retirés : rouges depuis le 4 juillet et Actions désactivées, ils ne garantissaient plus rien. La QA de l'application tourne en local via le Makefile.
- **README recentré** — la documentation vit sur le site ; l'inventaire des skills n'y est plus dupliqué (il avait déjà deux skills de retard). Bannière refaite à la charte du site.
- **Documentation projet réalignée** — `CLAUDE.md` et `docs/stack.md` décrivent le site comme troisième brique du dépôt, et corrigent deux affirmations fausses de longue date : la direction artistique du Board (« Nova · Midnight », pas « Paper ») et le niveau PHPStan (10, pas 9).

## [6.2.0] - 2026-07-15 — Rules de projet

### ✨ Fonctionnel

- **Nouveau skill `/forge:rules`** — écrit et entretient les **règles projet** dans `.claude/rules/`, un mécanisme natif de Claude Code : des instructions **paths-scopées**, que le harness ne charge que lorsque Claude ouvre un fichier de la zone concernée. Le gain est là et nulle part ailleurs — une règle **sans** `paths` est chargée au lancement avec la même priorité que `.claude/CLAUDE.md`, donc n'apporte rien qu'une section du `CLAUDE.md` n'apporterait déjà. Trois modes (Création / Enrichir / Éditer, le **retrait** faisant partie d'Éditer, parce qu'une règle périmée continue d'être obéie).
- **Deux tests appliqués à chaque règle candidate, et c'est tout le skill.** Le **test du `paths`** : quels fichiers Claude doit-il être en train de lire pour que cette règle serve ? Pas de glob honnête → ce n'est pas une règle de zone. C'est le cas de toutes les règles d'**outillage** (« toujours le binaire `symfony` », « passer par le Makefile ») : lancer une commande n'est pas lire un fichier, donc aucun `paths` ne les déclencherait au bon moment — elles restent dans le `CLAUDE.md`, ou deviennent un **hook `PreToolUse`** si l'utilisateur veut une garantie plutôt qu'un conseil. La **règle de preuve** : une règle ne s'écrit qu'attestée par un fichier, un diff ou un finding — comptée, jamais récitée, et **jamais prescrite plus large que ce qui a été mesuré**.
- **`/forge:rules` retire du `CLAUDE.md` ce qu'il en emporte** — troisième exception assumée du contrat de frontières (§3), **en soustraction seule** : ce qui part en règle en est retiré (le doublon se cherche par le sens, pas par les mots — les règles sont écrites depuis le code et ne reprennent jamais la formulation d'origine), un renvoi de deux lignes le remplace. Un déplacement laissé à moitié produirait exactement le mal que l'invariant I1 veut éviter : deux énoncés de la même convention, qui divergeront. Ajouter, restructurer ou réécrire reste à `/forge:claude-md`.
- **Pas de changelog dans les fichiers de règles**, contrairement à `vision.md`/`stack.md`/`product-backlog.md` : une règle est injectée en contexte à chaque session où son `paths` matche — un historique dedans serait payé en tokens à chaque fois pour un contenu qui n'aide personne à écrire du code. `git log` fait le travail.
- **Préséance posée dans `_detection.md`** : `.claude/rules/` scopées > `CLAUDE.md` > références stack. Les skills n'ont **rien à faire** pour obtenir les règles — le harness les charge ; les lire à la main les payerait deux fois.

### 🔧 Technique

- **Trois pièges de glob documentés dans `references/template.md`**, tous silencieux, découverts en évaluant le skill contre un Claude sans skill : le `*` simple qui ne descend pas dans les sous-dossiers ; le chemin inexistant ; et surtout **les dossiers en point** — `**` ne fabrique jamais un composant commençant par `.`, donc `plugins/**/*` rate `plugins/forge/.claude-plugin/plugin.json`, précisément le fichier que la règle marketplace vise. Le glob n'est pas vide, il a l'air juste, et il rate sa cible.
- **Phase 5 de vérification durcie** : vérifier avec l'outil `Glob`, jamais avec `find`/`ls` (qui **rassurent à tort** — sémantique divergente) ni avec `bash` (qui **alarme à tort** — le bash de macOS est en 3.2, sans `globstar` : `**` y dégénère en `*` et renvoie 0 sur un motif valide). Repli documenté sur `python3 -m glob` ou zsh. Et trois contrôles au lieu d'un : le glob matche-t-il, **couvre-t-il ce que la règle prétend couvrir**, et le scope est-il honnête.
- **Contrat de frontières amendé** : `.claude/rules/**` entre dans la table §2 avec `rules` pour écrivain unique ; `CLAUDE.md` passe à `claude-md (+ rules, voir §3)` ; les « deux exceptions assumées » deviennent trois, la nouvelle étant encadrée par trois garde-fous calqués sur ceux de `sync`.
- **`references/zones-catalog.md`** — catalogue d'amorce des zones par famille de projet (PHP/Symfony, JS/TS, Python, transverses), avec les trois lignes de découpe par ordre de rendement (par sujet, par couche, par nature de fichier) et la liste de ce qui **n'est pas** une zone.

## [6.1.0] - 2026-07-15 — Skills d'implémentation libérés

### ✨ Fonctionnel

- **Les trois skills d'implémentation (`feature-implem`, `refactor-implem`, `tech-implem`) ne déclarent plus d'`allowed-tools`.** Chacune énumérait ~45 outillages de stacks (`composer`, `cargo`, `poetry`, `gradlew`, `k6`…) : une liste infinie par nature, fausse dès le premier projet sortant des stacks prévus, et qui coûtait du contexte à chaque invocation. C'est désormais au `.claude/settings.json` du projet de pré-autoriser son propre outillage — le seul endroit où une décision de stack a sa place.
- **Le corps des skills d'implémentation est réellement stack-agnostique.** Les commandes PHP en dur (`vendor/bin/ecs`, `vendor/bin/phpstan`, `vendor/bin/phpunit`, `npm run build`) laissent place à l'intention (style, analyse statique, build, tests du périmètre) : les commandes se lisent dans le `CLAUDE.md` du projet, la référence stack ou le manifeste de tâches réel — avec consigne explicite de **demander plutôt que deviner** une commande plausible. Idem pour les traces de debug du nettoyage et les `Stack : [symfony | sylius]` des bilans.
- **`/forge:help` et le README documentent la contrepartie.** Une section « Outillage et autorisations » explique que c'est désormais au `.claude/settings.json` du projet de pré-autoriser son outillage — sans quoi Claude Code demande confirmation à chaque commande de build ou de test : fonctionnel, mais bavard. Exemple de configuration fourni, et mention de `permissions.deny` comme seul moyen de poser une interdiction dure.

### 🔧 Technique

- **Correction d'une erreur de fait dans le contrat `skill-boundaries.md`.** Le §4 affirmait qu'un frontmatter sans `allowed-tools` est « illimité — donc il détient l'écriture git sans l'avoir demandée », et le §7 en faisait un point de revue. La doc Claude Code dit l'inverse : `allowed-tools` *« does not restrict which tools are available: every tool remains callable »*. C'est une **pré-autorisation**, pas une allowlist — un `allowed-tools` avare n'a donc jamais défendu l'écriture git, il ajoutait seulement une demande de confirmation. Le §4 gagne une sous-section qui pose le comportement réel et nomme les deux seuls mécanismes contraignants (`permissions.deny` du projet, souverain ; `disallowed-tools`, à portée d'un tour), le §7 cesse de vérifier la présence de la clé et renvoie au vrai contrôle : `git log`.
- Le contrat tranche désormais la règle de rédaction : **ne rien déclarer plutôt qu'une liste de stacks** pour les skills dont l'outillage dépend du projet ; déclaration permise quand l'outillage est fini et connu (`commit`, `vision`) ; `Bash(git:*)` toujours évité chez qui ne livre pas — non comme rempart, mais pour préserver la demande de confirmation qui est le dernier signal avant qu'un skill livre à la place de `commit`.
- `tech-implem` ne s'attribue plus les commits d'instrumentation, de baseline et de retrait du kill switch : ils passent par `/forge:commit`, conformément à I1. `refactor-implem` le faisait déjà pour son commit de verrouillage.
- Les `allowed-tools` des autres skills (`commit`, `release`, `vision`, `adr`, `report-and-sync`…) sont inchangés : courts, finis, et alignés sur de vraies responsabilités.

## [6.0.0] - 2026-07-15 — Chartes de format et de frontières

### ✨ Fonctionnel
- **BREAKING — Une charte de format commune aux 8 documents de story** — nouvelle référence partagée `references/document-format.md`, chargée par les 8 skills producteurs (`feature-interview`, `feature-pitch`, les 3 `*-plan`, `review`, `report`, `estimate`). Elle fixe ce que chaque template appliquait jusqu'ici à sa façon : une **matrice des buts** (un document = un registre, une question, un consommateur), un **en-tête normalisé** (but / registre / story / amont — conservé après commit, contrairement aux blocs guides), un **vocabulaire canonique des sections**, un **squelette commun aux trois plans**, des **formats de table normatifs**, un **catalogue fermé de tags** et des **verdicts en français**. En cas de doute sur un titre ou un format, la charte fait foi.
- **BREAKING — Les trois plans partagent un squelette unique** — `feature-plan`, `refactor-plan` et `tech-plan` produisaient trois dialectes du même document (jusqu'à 3 formats pour lister les fichiers touchés, 2 pour les risques, 3 noms pour la séquence d'exécution). Ils suivent désormais le même ordre de sections ; les spécificités de track deviennent des sections **additionnelles** explicites (`Forme cible` et `Tests de caractérisation` pour un refacto, `Rollback et kill switch` et `Métriques (baseline → cible)` pour une évolution technique).
- **BREAKING — Verdicts de review en français** — `READY TO COMMIT` / `CHANGES REQUESTED` / `NEEDS FIXES` sont remplacés par **PRÊT À COMMITER** / **PRÊT À COMMITER SOUS RÉSERVE** / **CORRECTIONS REQUISES**. La `review.md` ouvre désormais sur sa `## Synthèse` (verdict en tête, plus en pied) — comme `report.md` et `estimate.md` : un document de décision se lit par sa conclusion.
- **La chaîne `plan → report` fonctionne enfin pour les refactos et les évolutions techniques** — le `report.md` confrontait le livré à l'intention avec les tables de `feature-plan` ; un `-r-` (table fichier/lignes/action) ou un `-t-` (liste à puces) ne pouvait pas se confronter ligne à ligne. Les tables de `## Périmètre` sont désormais normatives et identiques dans les 3 plans, le report les reprend + une colonne « Prévu dans le plan ». Idem pour les critères : `## Critères de sortie` est la **section unique** de critères d'un plan, quel que soit le track (un `tech-plan` en avait deux).
- **Étanchéité fonctionnel / technique renforcée** — chaque document déclare son **registre** dans son en-tête et s'y tient. Le `pitch.md` perd les noms de classes et de mécanismes qui fuyaient dans ses `Impacts transverses` (redevenus des questions métier : « des libellés à traduire ? », « qui a le droit de voir ça ? ») ; ses « Notes pour le plan technique » deviennent une **`## Annexe — Pistes pour le plan`** explicitement non contractuelle — seule zone d'un document fonctionnel où un nom technique peut apparaître, et que `/forge:sync` ne réaligne jamais.
- **Le `tech-plan` gagne une `## Stratégie de test`** — elle manquait : une cible chiffrée n'est pas un test. Ce qui ne se vérifie qu'en environnement réel (bascule DNS, certificat, tiers) est désormais explicitement distingué et rattaché aux étapes de bascule.
- **BREAKING — Un contrat de frontières entre skills** — nouvelle référence `references/skill-boundaries.md` : trois invariants (**un artifact, un écrivain** ; **on juge le livrable, pas le moyen** ; **aucune invocation automatique**), la matrice de propriété d'écriture artifact par artifact, les deux seules exceptions de co-écriture admises (`metadata.json`, append-only par construction ; les documents projet, que `sync` co-écrit via les modes de leurs propriétaires, sur validation), et une revue qui porte **sur le document produit** — est-ce le bon fichier, est-il dans son rôle, est-il pertinent — plutôt que sur le frontmatter du skill.
- **Les outillages projet ne sont pas restreints** — borner les binaires d'un skill (`composer`, `cargo`, `pytest`, `npm`…) contrôle le chemin, pas le livrable : un `feature-pitch` privé de `cargo` produit exactement le même `pitch.md`, avec une demande d'autorisation en plus. Les skills d'implémentation pré-autorisent une liste large et multi-stack (PHP/JS, Rust, Go, Python, Ruby, JVM, .NET, conteneurs) — un **confort** contre les demandes d'autorisation, pas une frontière. La seule capacité qui soit une frontière est l'**écriture git** : elle seule permet de produire l'artifact d'un autre.
- **Aucun skill ne se déclenche plus automatiquement** — `report`, `sync`, `report-and-sync` et `claude-md` étaient invocables par le modèle, contrairement aux vingt autres. Les trois premiers décrivent trois découpages du **même** moment du cycle : leur triage automatique était intrinsèquement ambigu, donc faux tôt ou tard. Tous portent désormais `disable-model-invocation: true`.

### 🔧 Technique
- **Outillage des skills d'implémentation ouvert aux stacks non-PHP** — leurs `allowed-tools` ne pré-autorisaient que PHP et JS (`php`, `composer`, `symfony`, `bin/console`, `npm`…), ce qui contredisait la promesse stack-agnostique : sur un projet Rust, Go ou Python, chaque commande déclenchait une demande d'autorisation. La liste couvre désormais les outillages courants. Un projet dont l'outillage n'y figure pas fonctionne pareil, et peut le pré-autoriser dans son propre `.claude/settings.json`. `test-scenario` perd un `Bash(curl:*)` qu'il n'utilisait jamais (capacité morte, pas frontière).
- **Capacités des skills resserrées sur leur périmètre** — les trois `*-implem` détenaient `Bash(git:*)`, soit `commit`, `push`, `reset` et `rebase`, alors que leur prose délègue le commit à `/forge:commit` ; ils passent à git en lecture seule. `report-and-sync` n'avait **aucun** `allowed-tools` — donc des capacités illimitées, la plus grande surface ouverte du plugin sur son skill le moins exigeant — et déclare maintenant l'union exacte de `report` et `sync`. `backfill-metadata` avait `Bash(git tag:*)` (création de tags) pour un usage en lecture seule → `Bash(git tag --contains:*)`. `refactor-implem` commitait lui-même le verrou de caractérisation : il passe par `/forge:commit`.
- **Sept frontmatter de skills n'étaient pas du YAML valide** — les descriptions de `commit`, `estimate`, `feature-plan`, `product-backlog`, `refactor-implem`, `stack` et `vision` contenaient un `: ` dans un scalaire nu, ce qu'aucun parseur YAML strict n'accepte. Corrigé par mise entre guillemets.
- **Templates dé-symfonisés** — `> Stack : symfony` en dur disparaît des en-têtes (la stack vient de la détection, pas d'un littéral), et les exemples des templates (entité Doctrine, Foundry, EasyAdmin, `PHPStan level 5`, `make test`) sont rendus génériques. Les mécanismes, commandes QA et seuils viennent des `references/stacks/` et du `CLAUDE.md` du projet, conformément à la promesse stack-agnostique du plugin.
- **Métadonnées dupliquées retirées des en-têtes** — `Date`, `Commits liés` et les tables `## Changelog` en pied de `pitch.md`/`plan.md` disparaissent des templates : leur source de vérité est `metadata.json` depuis la 4.4.0, mais les templates n'avaient pas suivi (les `SKILL.md`, eux, l'interdisaient déjà). `vision.md`, `stack.md` et `product-backlog.md` gardent leur changelog — ils n'ont pas de `metadata.json`.
- **Collision de vocabulaire levée** — « Périmètre » désignait à la fois les fichiers qu'un plan prévoit de toucher et le diff qu'une review examine. Ce dernier devient `Diff examiné` ; « Référence d'intention » devient `Amont`, comme dans tous les autres documents.
- **Renommages canoniques propagés aux skills** — `Problème adressé` → `Motivation`, `Brique retenue`/`Cible` → `Approche retenue`, `Mécanismes framework mobilisés`/`Pattern de refacto` → `Mécanismes mobilisés`, `Ordre d'implémentation`/`Stratégie d'exécution incrémentale` → `Ordre d'exécution`, `Critères de réussite`/`Critères de succès` → `Critères de sortie`, `Résumé` → `Synthèse`. Les axes de challenge des `SKILL.md` et les consignes de `sync`, `report`, `review` et `tech-implem` sont alignés.
- **Stories 001 → 009 rétro-migrées** — les 9 stories du Forge Board sont reformatées sur le nouveau contrat (en-têtes, titres de sections, changelogs redondants retirés). Contenu inchangé : c'est de la doc d'intention livrée, on la reformate, on ne la réécrit pas. La story 007, seule sans `metadata.json`, en reçoit un reconstruit depuis ses tables de changelog et l'historique git.
- **Fuites techniques purgées des 9 pitchs livrés** — les pitchs laissaient passer des noms de classes, mécanismes et configuration dans leurs sections fonctionnelles (`ROLE_USER` dans une règle métier, firewall et voters dans les impacts transverses, `TokenCipher` et `StoryStageMapper` dans des critères d'acceptation, tokens CSS dans des questions ouvertes). Chaque fuite est **traduite** en capacité vécue, jamais supprimée — sens, contraintes et vérifiabilité des critères préservés à l'identique. Les questions purement techniques rejoignent l'annexe non contractuelle.

## [5.0.0] - 2026-07-12 — Refonte du changelog & nettoyage du plugin

### ✨ Fonctionnel
- **`/forge:release` : versions titrées et changelog scindé Fonctionnel/Technique** — chaque release porte désormais un **titre obligatoire** (repris à l'identique dans l'en-tête du `CHANGELOG.md`, le message du tag annoté et le titre de la release GitHub), et ses changements sont répartis en deux chapitres `✨ Fonctionnel` (perceptible à l'usage) / `🔧 Technique` (interne) au lieu des sections `Added/Changed/Fixed`. Le `CHANGELOG.md` racine est restructuré dans ce format sur tout son historique, pensé pour être **montré à l'utilisateur final** dans l'app. `references/keep-a-changelog.md` et les phases 2 à 8 du skill sont réécrites en conséquence.
- **`/forge:sync` propage les écarts aux documents de phase 0** — une nouvelle Phase 5 réaligne aussi `docs/vision.md`, `docs/stack.md` et `docs/product-backlog.md` sur le code livré, pas seulement le `pitch.md`/`plan.md` de la story. Propagation **différenciée** selon le profil de chaque doc : `stack.md` gagne les dépendances/services détectés dans le diff (prouvés par fichier), `product-backlog.md` marque la feature livrée ou ajoute une capacité émergente, `vision.md` **évolue avec le produit** — une feature qui étend le périmètre enrichit la vision, une feature qui contredit un anti-objectif le fait retirer (la vision **suit** les features, elle ne les bloque pas ; seule une divergence stratégique large renvoie vers un `/vision` en mode Pivot). Modifications toujours **proposées et validées** via les modes et changelogs natifs des 3 skills (aucun nouveau format). Une story conforme à son plan déclenche quand même cette phase (une livraison conforme peut introduire une dépendance absente des docs projet). La clôture `/forge:report-and-sync` en bénéficie automatiquement.
- **BREAKING — Skills `migrate-legacy` et `import-external` retirés** — le skill de migration des anciens formats workflow (`<f|r|t>-NNN-<slug>/` → `NNN-<f|r|t>-<slug>/`, `feature.md`/`design.md` → `pitch.md`/`plan.md`) et le skill d'import depuis Spec Kit / BMAD-METHOD / GSD disparaissent du plugin. Leurs références sont nettoyées de `SKILLS.md`, `help`, `README.md` et de la description du plugin.
- **BREAKING — Skill et agent `/forge:autopilot` retirés** (inutilisés) — le plugin ne fournit plus aucun subagent ; la section « Agents » disparaît de `SKILLS.md` et du sommaire `/forge:help`.

### 🔧 Technique
- **`/forge:report-and-sync` s'exécute désormais dans la session courante** — la skill enchaîne directement les skills canoniques `/forge:report` puis `/forge:sync` au lieu de déléguer à un subagent. Les deux SKILL.md deviennent l'**unique source de vérité** de la procédure (fin de la triple recopie).
- **`/forge:sync` : suppression du bloc changelog en pied de fichier** — la Phase 4 ne présente plus de table `## Changelog` à insérer dans `pitch.md`/`plan.md` (consigne contradictoire avec la convention `metadata.json` introduite en 4.4.0). La timeline vit uniquement dans `metadata.json`.
- **Subagent `report-and-sync` supprimé** — au profit de l'enchaînement direct des skills `report` et `sync` en session principale. Ses 235 lignes recopiaient inline `report/SKILL.md` + `sync/SKILL.md` (troisième source de vérité qui divergeait déjà).
- **Écriture du `report.md` réparée pour la clôture documentaire** — le subagent `report-and-sync` ne pouvait pas écrire `report.md` : son `permissionMode: acceptEdits` (interdit aux agents livrés par un plugin, pour raisons de sécurité) était silencieusement ignoré, si bien que le `Write` échouait faute de pouvoir demander l'autorisation en contexte délégué. En exécutant report et sync dans la session principale, l'écriture est de nouveau autorisée normalement.
- **Extraction des notes de release réparée** — la commande `gh release create` de `/forge:release` s'appuyait sur une plage `awk '/début/,/fin/'` dont le motif de début (`## [X.Y.Z]`) matchait aussi le motif de fin (`## [`) : la plage se refermait sur la seule ligne de titre et les notes ressortaient vides. Remplacée par une extraction à flag (impression des lignes après l'en-tête de version jusqu'au prochain `## [`), avec les points de version échappés.

## [4.7.0] - 2026-07-12 — Clone local & interview de cadrage

### ✨ Fonctionnel
- **Clone local du repo d'un projet** (Forge Board) — depuis le kanban, un bouton clone (ou met à jour via `git pull --ff-only`) le dépôt d'un projet en local, en tâche de fond (job Messenger async), avec bascule d'état en direct (Live Component, sans reload). Auth git par `GIT_ASKPASS` (token jamais en argv ni dans `.git/config`). Premier maillon du pivot productif.
- **Expression d'un besoin en interview de cadrage** (Forge Board) — depuis un projet cloné, l'utilisateur exprime un besoin en langage libre ; le skill `feature-interview` tourne en headless (`claude -p`, ADR-0002) dans le clone local et mène l'interview tour par tour, ancrée sur le code réel. Le `brief.md` produit est présenté pour relecture puis, à validation, poussé sur une branche dédiée et ouvert en **PR draft GitHub** (jamais de merge ni d'écriture sur la branche principale). Parcours asynchrone, une interview active par projet.
- **`feature-interview` signale sa conclusion** — le skill indique désormais explicitement quand il est prêt à conclure (Phase 3) et respecte une demande explicite de conclusion sans relancer un tour de questions — pour que l'utilisateur, qui sinon ne sait pas quand l'interview se termine, tienne clairement la fin du dialogue.

### 🔧 Technique
- **Serveur MCP `symfony-ai-mate`** — outillage MCP pour le développement du board (config `mcp.json`, worker dédié).

## [4.6.0] - 2026-07-05 — Cycle de vie des stories sur le board

### ✨ Fonctionnel
- **Colonne « Idée » sur le board** (Forge Board) — une story qui n'a qu'un `brief.md` (idée dégrossie par interview) s'affiche désormais en première colonne du pipeline au lieu d'atterrir en « À vérifier ».
- **Colonnes du board alignées sur le cycle de vie** (Forge Board) — le pipeline passe de « Cadrage / Planifié / Review » à cinq colonnes de cycle de vie : **Idée → Besoin → Cadré → Implémenté → Livré**. « À vérifier » ne contient plus que les dossiers réellement non reconnus et gagne une couleur d'anomalie distincte.
- **Filtre par tag en popover recherchable** (Forge Board) — le mur de chips laisse place à un popover recherchable multi-sélection (OR), tags actifs en pills retirables. Les colonnes vidées par le filtre se rétrécissent, et les libellés de colonne se clampent si besoin.

## [4.5.0] - 2026-07-05 — Reconstruction rétroactive des métadonnées

### ✨ Fonctionnel
- **Skill `backfill-metadata`** — reconstruit rétroactivement le `metadata.json` des stories écrites **avant** l'introduction du contrat de métadonnées. Déduit le `title` du H1 du document principal, `created`/`updated` de l'historique git du dossier de la story, la timeline `changelog` de la date d'apparition de chaque artifact (`pitch`/`plan`/`review`/`report`) avec **fusion des jalons de même date**, et `delivery` (`commit`/`release`) des commits et tags git. Les `tags` sont proposés puis **validés** par l'utilisateur ; aucune date n'est inventée (toujours issue de git) et `delivery` reste **absent** si la livraison n'est pas identifiable avec certitude. Ne réécrit jamais un `metadata.json` valide sauf `--force`, validation par story avant écriture. Écrit le même schéma v1 que les skills de cadrage (`references/story-metadata.md`).

## [4.4.0] - 2026-07-05 — Métadonnées de story & cartes enrichies

### ✨ Fonctionnel
- **Cartes de board enrichies** (Forge Board) — les cartes affichent le vrai titre, la date de dernière activité, les tags et un badge de livraison (release/commit), lus depuis le `metadata.json` des stories en **un seul appel groupé** (GraphQL GitHub, nombre d'appels indépendant du nombre de stories). Le drawer expose le changelog consolidé.
- **Filtre par tag et tri par activité** (Forge Board) — barre d'outils client-side pour isoler un thème à travers le pipeline et réordonner les cartes par date de mise à jour, sans round-trip.

### 🔧 Technique
- **Métadonnées de story (`metadata.json`)** — chaque story forge porte désormais un fichier `metadata.json` (schéma v1 versionné : `title`, `created`, `updated`, `tags`, `changelog`, `delivery`) **produit et maintenu par les skills** via une référence partagée (`plugins/forge/references/story-metadata.md`). Les skills de création écrivent `title`/`created`/`tags`/première entrée ; chaque passe rebouge `updated` et append au changelog ; `commit`/`release` renseignent `delivery`. La timeline consolidée vit dans ce fichier — les tables de changelog en pied de `pitch.md`/`plan.md` sont abandonnées.
- **Lecteur de dépôt bi-protocole** — le `RepositoryReaderInterface` expose `readStoryMetadata()` (lecture groupée du metadata) ; l'implémentation GitHub devient bi-protocole REST + GraphQL. Une story sans `metadata.json`, ou avec un fichier invalide, dégrade gracieusement vers le slug humanisé — aucune régression. `StoryStageMapper` ignore `metadata.json` : la colonne reste déduite des seuls `.md`.

## [4.3.0] - 2026-07-05 — Kanban lecture seule d'un projet forge

### ✨ Fonctionnel
- **Kanban d'un projet forge** (Forge Board) — à l'ouverture d'un projet, ses stories sont scannées en direct dans le dépôt et projetées en tableau lecture seule à quatre colonnes ordonnées par étape du pipeline, triées par numéro décroissant, avec un bandeau « À vérifier ». Un drawer permet de consulter les documents markdown de chaque story (pitch, plan…), rendus sanitizés. Aucun état persisté : le tableau est recalculé à chaque affichage.
- **Déduction de l'étape d'une story depuis ses fichiers** (Forge Board) — l'étape de chaque story (pitch → plan → livraison → vérification) est déduite automatiquement des fichiers présents dans son dossier `docs/story/`, sans aucune saisie manuelle.
- **Vérification d'accès et d'éligibilité d'un dépôt** (Forge Board) — à la déclaration ou l'édition d'un projet, l'application teste l'accès GitHub (token valide, dépôt atteignable) et confirme que le dépôt suit le workflow forge avant de l'accepter.

## [4.2.0] - 2026-07-04 — Projets, authentification & DA Nova

### ✨ Fonctionnel
- **Gestion des projets forge** (Forge Board) — déclarer un dépôt à suivre (provider GitHub/GitLab, URL, token de lecture chiffré au repos), consulter la liste, ouvrir un projet, éditer l'URL / renouveler le token et retirer un projet derrière confirmation. Le token n'est jamais réaffiché ni renvoyé au navigateur. Liste en Live Component (suppression sans rechargement) et sélecteur de provider aux couleurs de marque.
- **Connexion locale mono-utilisateur** (Forge Board) — l'application est protégée derrière une authentification : formulaire de login, option « rester connecté », déconnexion.
- **Direction artistique « Nova · Midnight »** (Forge Board) — design system sombre de référence (tokens de thème, kit de composants Flowbite remappés) pilotant toutes les interfaces.

## [4.1.0] - 2026-07-04 — Naissance de Forge Board

### ✨ Fonctionnel
- **Forge Board** — application Symfony 8 (kanban de suivi des stories du workflow forge) instanciée à la racine du dépôt. Le repo héberge désormais deux sujets : la marketplace `forge` (`plugins/`) et l'app Forge Board (racine). Stack : Symfony 8 / PHP 8.5, Doctrine + SQLite, Symfony UX (Live Component, Turbo, Stimulus), Tailwind 4 + Flowbite 4, PHPUnit 13 + Playwright. Docs : `docs/vision.md`, `docs/stack.md`, `docs/adr/0001`.

### 🔧 Technique
- **Levée du doublon documentaire `documentation/` ↔ `docs/`** — l'inventaire des skills passe de `documentation/forge.md` à `plugins/forge/SKILLS.md` (au plus près du plugin), le banner à `.github/banner.png`. README réorganisé (skills en tête, puis section Forge Board), `CLAUDE.md` scindé en deux parties (marketplace / app Symfony).

## [4.0.0] - 2026-07-04 — Renommage workflow → forge

### 🔧 Technique
- **BREAKING — Renommage du plugin `workflow` → `forge`** pour aligner le nom du plugin sur celui de la marketplace. Le **préfixe d'invocation des skills change** : `/workflow:help` → `/forge:help`, `/workflow:feature-implem` → `/forge:feature-implem`, etc. (tous les skills). Le **namespace des agents** change de même : `workflow:autopilot` → `forge:autopilot`, `workflow:report-and-sync` → `forge:report-and-sync`. Le dossier du plugin passe de `plugins/workflow/` à `plugins/forge/` et la `source` du `marketplace.json` suit. Propagé à l'ensemble des `SKILL.md`, agents, templates de référence, `documentation/forge.md` (ex-`workflow.md`), README et CLAUDE.md. **Action requise** : après `/plugin marketplace update forge`, réinstaller avec `/plugin install forge@forge` puis utiliser les commandes préfixées `/forge:` — les anciennes `/workflow:` n'existent plus.

## [3.3.3] - 2026-06-29 — Barème de marge d'estimation allégé

### ✨ Fonctionnel
- **`estimate` : barème de marge d'incertitude abaissé** — de **+15 / +30 / +50 %** à **+10 / +20 / +35 %** (faible / moyenne / élevée). Dans le prolongement du fix v3.3.2 (base = médiane réaliste), ça allège encore le haut de fourchette : une story « moyenne » passe d'un total `base × 1,30` à `base × 1,20`, sur une base déjà dégonflée. Mis à jour dans `references/method.md` §4 et `references/template.md`.

## [3.3.2] - 2026-06-29 — Correction du sur-chiffrage des estimations

### ✨ Fonctionnel
- **`estimate` : correction d'un biais systématique de sur-chiffrage** (~30 % trop haut, constaté sur des stories réelles). La cause : le skill gonflait les **durées de base** par réflexe défensif *puis* ajoutait la marge d'incertitude par-dessus — l'aléa était donc compté deux fois. Introduction du principe directeur **« ne jamais compter l'incertitude deux fois »** : la durée de base de chaque phase est désormais la **médiane réaliste** (le temps le plus probable si le déroulé est normal), et l'aléa est porté **uniquement par la marge**. Concrètement : nouveau principe en tête de `references/method.md` §4, nouveau piège « Doubler le matelas » + **test du miroir** en §5, somme des phases recadrée en « médiane, pas borne haute » en §6 ; dans `SKILL.md`, la règle d'or distingue **périmètre** (« tout compris » = compter chaque phase) et **magnitude** (chiffrer au plus probable, sans coussin), la règle #5 passe de « être lucide, pas optimiste » à **« viser juste, ni optimiste ni défensif »** (sur-estimer coûte aussi ; dans le doute, prendre la valeur basse), et la Phase 4 demande de **recalibrer toute la décomposition sur le réalisé passé de l'utilisateur** dès qu'il le donne, au lieu de tenir des chiffres hauts rognés ligne à ligne ; `references/template.md` aligné (bloc-guide + synthèse). Les facteurs d'accélération IA et le barème de marge sont inchangés : corriger la base dégonfle mécaniquement les deux colonnes (réf. et avec IA).

## [3.3.1] - 2026-06-29 — Estimation adaptée au workflow solo

### ✨ Fonctionnel
- **`estimate` : adaptation au workflow solo** — les phases **Intégration** et **Coordination & échanges** sont retirées de la décomposition (un développeur seul ne suit ni le merge multi-contributeurs ni les réunions/recette comme postes facturables distincts), et **Release & déploiement** devient un **forfait fixe de 30 min** (0,5 h) — opération routinière de durée constante, qu'on ne ré-estime pas et que l'IA n'accélère pas (identique dans les deux colonnes). La décomposition « tout compris » passe ainsi à six phases : cadrage, implémentation, tests, review, documentation, release. Propagé à `SKILL.md`, `references/method.md` (table des phases + note contexte solo, barème d'accélération IA, pièges du sous-chiffrage) et `references/template.md`, ainsi qu'aux descriptions (`/forge:help`, `documentation/workflow.md`, README, `plugin.json`).

## [3.3.0] - 2026-06-29 — Skill estimate (chiffrage tout compris)

### ✨ Fonctionnel
- **Skill `estimate`** (transversal **optionnel**, applicable à n'importe quelle story — feature `f-`, refacto `r-`, évolution technique `t-`) : chiffre le temps **« tout compris »** d'une story à facturer, pas seulement le code. Compte les huit phases que les devs sous-estiment systématiquement (cadrage, implémentation, tests, review & corrections, intégration, documentation de clôture, release & déploiement, coordination & échanges) plus une **marge d'incertitude** assumée (barème +15 / +30 / +50 % selon le flou réel). Entrée **flexible** : lit `brief.md`, `pitch.md` et/ou `plan.md` selon ce qui existe dans le dossier de story — plus la matière est riche, plus l'estimation est fiable (brief seul → fourchette large à reconfirmer ; plan détaillé → estimation affinée par les fichiers/migrations/tests listés). Chaque chiffre est **justifié par un signal** lu dans les artifacts ou le code, et calé sur le **vécu de l'utilisateur** (point de comparaison demandé — la vélocité réelle n'est pas dans le code). Spécificités par track prises en compte (tests de caractérisation amont en refacto, baseline/kill switch en tech, phases `déjà fait`/`reste` quand le pitch ou le plan existent déjà). Produit `docs/story/NNN-<f|r|t>-<slug>/estimate.md` **en heures** (facturation horaire), sans jamais convertir en montant — la conversion par le taux horaire reste à la charge de l'utilisateur. **Double chiffrage** : chaque phase est estimée dans deux colonnes — temps de référence (réalisation classique, à la main) et temps réel avec un **assistant IA** (type Claude Code) — via un facteur d'accélération **par phase** (fort sur implem/tests/doc, nul sur les phases humaines incompressibles comme la coordination et la recette client). L'écart entre les deux totaux éclaire la marge. Méthode complète (phases par track, accélération IA, signaux de complexité, barème de marge, pièges du sous-chiffrage) dans `references/method.md`. Câblé au sommaire `/forge:help`, à `documentation/workflow.md` et au README.

## [3.2.1] - 2026-06-23 — Brief d'interview 100 % fonctionnel

### ✨ Fonctionnel
- **`feature-interview` : le `brief.md` produit est désormais explicitement 100 % fonctionnel** — la reconnaissance du code reste (elle informe les questions et la compréhension du produit), mais toute trouvaille technique est traduite en capacité vécue par l'utilisateur avant d'entrer dans le brief — plus aucun nom d'entité, de service, de fichier, de framework ni de stack. Ajout d'une « règle d'or » au `SKILL.md` (règle de traduction technique→fonctionnel + exemples), recadrage de la Phase 1 (« comprendre le produit » plutôt que « documenter la technique »), et remplacement de la section « Reconnaissance du code existant » du template par « Ce que le produit fait déjà » (capacités vues par l'utilisateur). Conséquence : le stack n'est plus transporté par le brief — l'optimisation de réutilisation par `/feature-pitch` introduite en 3.2.0 est retirée, le pitch re-détecte le stack lui-même.

## [3.2.0] - 2026-06-23 — Skill feature-interview

### ✨ Fonctionnel
- **Skill `feature-interview`** (amont **optionnel** du track feature) : interview de découverte pour les besoins trop flous pour être pitchés directement — exactement les cas que `/feature-pitch` refuse aujourd'hui en Phase 0 (« améliorer les commandes », « il manque un truc côté relances »). Posture inverse du pitch : bienveillante, sans jargon, ne refuse jamais le vague (c'est la matière de départ). Déroule une interview guidée (exemple récent concret, 5 pourquoi, baguette magique, contraste, reformulation-miroir — détaillée dans `references/techniques.md`) ancrée sur une **reconnaissance ciblée du code existant** (détection stack + grep/glob autour du vocabulaire métier) pour éviter de réinventer une brique native. Produit `docs/story/NNN-f-<slug>/brief.md` (besoin en une phrase, irritant, qui, résultat attendu, reconnaissance code, hors-sujet entrevu, zones de flou). Le brief alimente `/feature-pitch`, qui le détecte et le reprend comme pitch initial riche (sautant son refus de Phase 0) en écrivant `pitch.md` dans le même dossier `NNN-f-<slug>/`. Compteur global partagé avec features/refactos/évolutions techniques. Câblage propagé à `/feature-pitch` (détection du brief amont), au sommaire `/forge:help` (diagramme du track feature + tableau), à `documentation/workflow.md` et au README.

### 🔧 Technique
- **README : diagramme de flux des tracks corrigé** — il portait encore les anciens noms d'exécution (`feature`, `refactor`, `tech`), oubliés lors du renommage `-implem` de la v3.0.0 alors que le tableau juste en dessous était déjà à jour. Corrigés en `feature-implem` / `refactor-implem` / `tech-implem`.

## [3.1.0] - 2026-06-19 — Écriture des subagents en mode plan

### 🔧 Technique
- **Subagents `autopilot` et `report-and-sync` : écriture réparée en mode plan** — ils ne pouvaient pas écrire (`Write`/`Edit`) lorsque la session de l'utilisateur était en mode de permission `plan` (ou `default` sans règle d'autorisation préalable) : un subagent ne peut pas afficher de prompt de permission interactif, ses écritures étaient donc refusées silencieusement. Ajout de `permissionMode: acceptEdits` au frontmatter des deux agents. `autopilot` propage ce mode aux subagents `general-purpose` qu'il délègue (l'`acceptEdits` du parent prime), et `report-and-sync` écrit directement `report.md` / la doc d'intention. Le fix voyage avec le plugin : aucun réglage manuel requis côté utilisateur. Limitation : les skills d'implémentation invoquées en direct (`feature-implem`, etc.) s'exécutent dans la session principale et restent soumises au mode de permission de l'utilisateur.

## [3.0.1] - 2026-06-14 — Références de tracks corrigées

### 🔧 Technique
- **Références résiduelles aux anciens noms de tracks corrigées** (`/feature`, `/refactor`, `/tech`), oubliées lors du renommage `-implem` de la v3.0.0 : les `SKILL.md` des skills de cadrage `feature-plan`, `refactor-plan` et `tech-plan` (mentions « il ne code pas », « prochaine étape », verrou caractérisation) pointaient encore vers les anciennes invocations, ainsi que `adr`, `stack` et la référence `references/stacks/symfony.md`. Toutes les invocations terminales pointent désormais vers `/feature-implem`, `/refactor-implem` et `/tech-implem`.

## [3.0.0] - 2026-06-03 — Renommage des skills d'exécution -implem

### 🔧 Technique
- **BREAKING — Renommage des trois skills d'exécution terminaux** pour rétablir la symétrie verbale avec les skills de cadrage (`*-plan`) : `feature` → `feature-implem`, `refactor` → `refactor-implem`, `tech` → `tech-implem`. Les invocations changent en conséquence : `/forge:feature` → `/forge:feature-implem`, `/forge:refactor` → `/forge:refactor-implem`, `/forge:tech` → `/forge:tech-implem`. Mise à jour propagée aux agents (`autopilot`, `report-and-sync`), au sommaire `/forge:help` (diagrammes ASCII redessinés, tableaux de tracks), aux templates de cadrage, à `_detection.md`, aux skills `import-external` / `test-scenario` / `review`, au README et à `documentation/workflow.md`. **Action requise** : les utilisateurs qui invoquaient `/forge:feature`, `/forge:refactor` ou `/forge:tech` doivent utiliser les nouveaux noms suffixés `-implem`.

## [2.2.0] - 2026-05-31 — Skill claude-md

### ✨ Fonctionnel
- **Skill `claude-md`** — génère ou met à jour le `CLAUDE.md` à la racine d'un projet. Analyse le codebase (nature, stack, architecture, commandes, conventions) avec la discipline « preuve par fichier » du skill `stack` — aucune commande inventée, validation avant écriture — puis injecte les 4 principes comportementaux Karpathy (réflexion avant code, simplicité, changements chirurgicaux, objectif vérifiable), inspirés du repo `multica-ai/andrej-karpathy-skills`. Réutilise `docs/stack.md` et `docs/vision.md` s'ils existent (synthèse + renvoi plutôt que duplication). Modes Création / Mise à jour ; en Mise à jour, propose explicitement d'ajouter la couche comportementale si elle manque, sans l'imposer. Squelette de fichier et bloc de principes dans `references/`.

## [2.1.0] - 2026-05-28 — Skill stack (phase 0 technique)

### ✨ Fonctionnel
- **Skill `stack`** (phase 0 technique) : détecte la stack complète d'un projet (langages, backend, frontend, données, ops, devops/CI) et produit `docs/stack.md`. Document vivant à 4 modes (Création, Enrichir, Éditer, Pivot) avec changelog, sur le modèle de `vision`/`product-backlog`. Chaque techno est prouvée par un fichier source ; les couches non détectables (hébergement, monitoring, secrets) sont comblées par questions ciblées ou marquées `_non renseigné_`. Câblé dans `_detection.md` : `feature`/`refactor`/`tech`/`review` lisent `docs/stack.md` en priorité, avec fallback sur la détection légère.

### 🔧 Technique
- **README réécrit** en version concise et structurée par tables ; ajout des skills manquants au catalogue (`stack`, `autopilot`, `report-and-sync`) et du skill `stack` dans le sommaire `/help` (phase 0 technique).
- **`plugin.json` : `homepage` et `repository` corrigés** — ils pointaient encore vers `gabrielmustiere/skills` au lieu du repo dédié `gabrielmustiere/forge`.

## [2.0.1] - 2026-05-28 — Références de fichiers bundlés réparées

### 🔧 Technique
- **Références de fichiers bundlés réparées** (détection stack, templates de cadrage, mappings d'import), cassées une fois le plugin installé hors du repo source : résolution via `${CLAUDE_SKILL_DIR}` dans les skills, `${CLAUDE_PLUGIN_ROOT}` dans les agents, et pointeurs « même dossier » entre fichiers de référence.

## [2.0.0] - 2026-05-28 — Plugin forge dans son repo dédié

### 🔧 Technique
- **Extraction du plugin `workflow` dans son repo dédié `gabrielmustiere/forge`**, distribué via la marketplace `forge`. L'historique antérieur du plugin reste consultable dans `gabrielmustiere/skills`. Le plugin repart en `2.0.0` pour marquer le nouveau repo dédié.

[Unreleased]: https://github.com/gabrielmustiere/forge/compare/v6.5.0...HEAD
[6.5.0]: https://github.com/gabrielmustiere/forge/compare/v6.4.0...v6.5.0
[6.4.0]: https://github.com/gabrielmustiere/forge/compare/v6.3.0...v6.4.0
[6.3.0]: https://github.com/gabrielmustiere/forge/compare/v6.2.1...v6.3.0
[6.2.1]: https://github.com/gabrielmustiere/forge/compare/v6.2.0...v6.2.1
[6.2.0]: https://github.com/gabrielmustiere/forge/compare/v6.1.0...v6.2.0
[6.1.0]: https://github.com/gabrielmustiere/forge/compare/v6.0.0...v6.1.0
[6.0.0]: https://github.com/gabrielmustiere/forge/compare/v5.0.0...v6.0.0
[5.0.0]: https://github.com/gabrielmustiere/forge/compare/v4.7.0...v5.0.0
[4.7.0]: https://github.com/gabrielmustiere/forge/compare/v4.6.0...v4.7.0
[4.6.0]: https://github.com/gabrielmustiere/forge/compare/v4.5.0...v4.6.0
[4.5.0]: https://github.com/gabrielmustiere/forge/compare/v4.4.0...v4.5.0
[4.4.0]: https://github.com/gabrielmustiere/forge/compare/v4.3.0...v4.4.0
[4.3.0]: https://github.com/gabrielmustiere/forge/compare/v4.2.0...v4.3.0
[4.2.0]: https://github.com/gabrielmustiere/forge/compare/v4.1.0...v4.2.0
[4.1.0]: https://github.com/gabrielmustiere/forge/compare/v4.0.0...v4.1.0
[4.0.0]: https://github.com/gabrielmustiere/forge/compare/v3.3.3...v4.0.0
[3.3.3]: https://github.com/gabrielmustiere/forge/compare/v3.3.2...v3.3.3
[3.3.2]: https://github.com/gabrielmustiere/forge/compare/v3.3.1...v3.3.2
[3.3.1]: https://github.com/gabrielmustiere/forge/compare/v3.3.0...v3.3.1
[3.3.0]: https://github.com/gabrielmustiere/forge/compare/v3.2.1...v3.3.0
[3.2.1]: https://github.com/gabrielmustiere/forge/compare/v3.2.0...v3.2.1
[3.2.0]: https://github.com/gabrielmustiere/forge/compare/v3.1.0...v3.2.0
[3.1.0]: https://github.com/gabrielmustiere/forge/compare/v3.0.1...v3.1.0
[3.0.1]: https://github.com/gabrielmustiere/forge/compare/v3.0.0...v3.0.1
[3.0.0]: https://github.com/gabrielmustiere/forge/compare/v2.2.0...v3.0.0
[2.2.0]: https://github.com/gabrielmustiere/forge/compare/v2.1.0...v2.2.0
[2.1.0]: https://github.com/gabrielmustiere/forge/compare/v2.0.1...v2.1.0
[2.0.1]: https://github.com/gabrielmustiere/forge/compare/v2.0.0...v2.0.1
[2.0.0]: https://github.com/gabrielmustiere/forge/releases/tag/v2.0.0
