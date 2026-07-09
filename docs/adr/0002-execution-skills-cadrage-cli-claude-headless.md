# ADR-0002 — Exécuter les skills de cadrage forge via le CLI `claude -p` headless

- **Statut** : accepted
- **Date** : 2026-07-09
- **Déciders** : Gabriel (solo, POC)
- **Story liée** : _(standalone — motive les prochaines stories du pivot)_

## Contexte

Le pivot de la vision fait passer Forge Board de « miroir qui observe » à « atelier qui agit » : un besoin exprimé depuis le board doit déclencher un skill de cadrage forge (`feature-interview`, `feature-pitch`) sur le repo cloné localement (story 008), et produire un livrable versionné. Un skill forge est un **artefact Claude Code** : son prompt s'appuie sur la boucle d'outils de Claude Code (Read/Write/Glob/Grep/Bash). Il faut décider **par quel moteur** l'app l'exécute, sachant que la cible de déploiement est un **serveur sans session Claude locale** (pas d'OAuth ni d'abonnement disponible côté serveur) et que ces skills sont **conversationnels** (2-3 questions par tour, écriture du fichier après validation). L'hypothèse #1 de la vision (« Symfony AI en headless, fiable ? ») était explicitement « à tester ».

## Decision drivers

- **Driver 1 — Fidélité au skill existant** : exécuter le *vrai* `feature-interview`/`feature-pitch`, sans en ré-implémenter le prompt ni la logique (exigence produit : un seul point de vérité pour la convention forge).
- **Driver 2 — Exécution serveur sans Claude local** : auth par secret, pas d'OAuth interactif.
- **Driver 3 — Interactivité multi-tours** : dialogue web fidèle à l'interview émergente (cœur de valeur pour le PO non-technique).
- **Driver 4 — Coût maîtrisé et mesurable** : facturation API au token, à borner.

## Options considérées

### Option A — CLI `claude -p` headless + clé API _(retenue)_

Symfony shell-out (depuis un handler Messenger) vers le binaire `claude` en mode `--print`, `cwd` = `private/<projet>/`, `--plugin-dir` chargeant le plugin forge, `--bare` + `ANTHROPIC_API_KEY`. Le multi-tours passe par `--resume <session_id>` : chaque message du PO = un process éphémère qui recharge la session depuis le disque.

- Aligne avec **Driver 1** (fidélité) : oui — le vrai skill, vraie boucle d'outils.
- Aligne avec **Driver 2** (serveur) : oui — `--bare` lit **strictement** `ANTHROPIC_API_KEY` (jamais l'OAuth/keychain).
- Aligne avec **Driver 3** (interactivité) : oui — **prouvé au POC** : le skill pose ses questions, rend la main, `--resume` reprend avec tout le contexte.
- Aligne avec **Driver 4** (coût) : oui — **~0,13 $ l'interview complète** (3 tours, Haiku) ; tour 1 ≈ 0,07 $ (amorçage cache), tours suivants ≈ 0,01 $.
- Coût / trade-off : runtime Node + binaire `claude` à opérer sur le serveur ; surface `Bash`/`Write` à sandboxer ; état de session `--resume` sur disque persistant.

### Option B — Agent SDK (sidecar TS/Python) + clé API

Même moteur Claude Code, encapsulé dans un process d'un autre langage à côté de Symfony.

- Aligne avec **Drivers 1 à 4** : oui, équivalents à l'Option A.
- Coût / trade-off : **un runtime supplémentaire dans un autre langage** à déployer et opérer, pour zéro gain fonctionnel sur ce besoin. Rejetée par simplicité.

### Option C — Symfony AI pur (PHP) + API Claude

Piloter un LLM en PHP (`symfony/ai-agent`) en re-portant le prompt du skill et en ré-implémentant la boucle d'outils.

- Aligne avec **Driver 1** : non — **abandonne la fidélité** : ce n'est plus « le skill » mais sa copie à maintenir en parallèle.
- Aligne avec **Drivers 2 à 4** : oui.
- Coût / trade-off : duplication de la convention forge, divergence garantie à chaque évolution du plugin. Rejetée.

### Option D — Statu quo

Ne rien exécuter depuis le board ; le PO reste dépendant d'un dev en terminal. Contredit directement le pivot de la vision. Insuffisant.

## Décision

**Option retenue : A — CLI `claude -p` headless + clé API.**

C'est la seule option qui satisfait le **Driver 1** (fidélité au skill existant) **et** le **Driver 2** (serveur sans Claude local), tout en tenant les Drivers 3 et 4 — ces deux derniers n'étant plus des paris mais des faits établis par le POC (reprise multi-tours fonctionnelle, `brief.md` produit, coût de 0,13 $/interview en Haiku). L'Option B est écartée pour surcoût d'opérabilité sans gain fonctionnel ; l'Option C parce qu'elle sacrifie le driver le plus fort (fidélité). Les trade-offs de A (runtime Node, sandbox, sessions sur disque) sont jugés acceptables au POC mono-utilisateur.

## Conséquences

**Positives**

- Le vrai skill forge tourne sans maintenance d'une copie PHP parallèle — un seul point de vérité pour la convention.
- Dialogue multi-tours fidèle à l'esprit interview, via `--resume`.
- Coût faible, **mesurable à la source** : le JSON de sortie reporte `total_cost_usd` et l'usage token.
- Le plugin forge = fichiers versionnés du repo, embarquables tels quels (`--plugin-dir`).

**Négatives / coûts assumés**

- Un runtime **Node + binaire `claude`** à installer et maintenir sur le serveur.
- Surface d'attaque : agent avec `Bash`/`Write` déclenché depuis le web → **sandbox obligatoire**.
- L'état des sessions `--resume` vit **sur disque** : dossier writable et persistant entre requêtes (trivial en mono-instance, à border en multi-instance).
- Dépendance à la **stabilité des flags CLI** (`-p`, `--resume`, `--plugin-dir`, `--output-format json`).
- Facturation **API au token** → clé secret à provisionner et surveiller.

**Suites obligatoires**

- [ ] Provisionner `ANTHROPIC_API_KEY` (secret) dans l'env serveur.
- [ ] Définir l'emplacement writable/persistant des sessions (`CLAUDE_CONFIG_DIR`) et sa rétention.
- [ ] Cadrer la sandbox d'exécution (conteneur, `--allowedTools` restreint, pas de réseau hors Anthropic).
- [ ] `/feature-pitch` de la story « exprimer un besoin depuis le board → interview → brief » (entité `Interview`, handler Messenger, Live Component chat).
- [ ] Trancher le modèle par défaut (Haiku vs Sonnet) selon la **qualité sur le vrai repo** — lié à l'hypothèse #2 de la vision.

## Links

- Vision : `docs/vision.md` (hypothèses #1 « Symfony AI headless » et #3 « push maîtrisable »)
- Prérequis : `docs/story/008-f-clone-repo-local/` (le clone est le substrat d'exécution)
- Preuve : POC 3-tours `feature-interview` headless (plugin chargé via `--plugin-dir`, reprise `--resume`, `brief.md` + `metadata.json` produits, coût 0,13 $)
