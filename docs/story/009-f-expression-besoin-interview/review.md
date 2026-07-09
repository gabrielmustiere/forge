# Review — Exprimer un besoin depuis le board et le cadrer en brief soumis en revue

> Date : 2026-07-10
> Stack : symfony
> Périmètre : working tree + non suivis, story 009 (~35 fichiers source/tests, 2 migrations). Les deltas 008 présents dans le diff (`CloneRepositoryHandler`, `ClonePathResolver`, `.php-cs-fixer.dist.php`) sont **exactement les correctifs de la review 008** — non re-jugés.
> Référence d'intention : `docs/story/009-f-expression-besoin-interview/plan.md` + `pitch.md`

## Bloquants

_(aucun)_

## Importants

- [x] **[BUG] Contamination inter-interviews : aucun nettoyage du dossier de story non suivi** — `src/Service/Interview/ProducedBriefLocator.php:33` + `src/MessageHandler/SubmitBriefHandler.php` / `InterviewManager::abandon()`. Le brief est écrit **non suivi** dans le clone maintenu (008), qui n'est jamais nettoyé (seul le `workDir` de push isolé est supprimé, `GitBriefPusher.php:79`). `git pull --ff-only` (008) ne touche pas les fichiers non suivis → le dossier `docs/story/NNN-f-slug/` persiste après `Submitted` **comme** après `Abandoned`. Conséquence : dès qu'une **2ᵉ** interview démarre sur le même projet, son tout premier tour appelle `locate()`, qui détecte le brief resté de la précédente et bascule en `BriefReady` **sur le mauvais slug**, avant même que le skill n'ait rien produit. Le parcours North Star casse à la 2ᵉ utilisation par projet. **Corrigé** : nouveau service best-effort `StoryWorkspaceCleaner` qui purge `docs/story/<slug>/` du clone à chaque état terminal — après `markSubmitted` (`SubmitBriefHandler`) et à l'abandon si un brief a été produit (`InterviewManager::abandon`). Tests fonctionnels ajoutés : `testTerminalInterviewDoesNotContaminateTheNextOne` (la 2ᵉ interview reste `Awaiting`, slug `null`), `testAbandonPurgesAProducedBrief`, assertion de purge sur le happy path, + unit `StoryWorkspaceCleanerTest`.

- [x] **[ROBUSTESSE] Re-tentative de dépôt non récupérable après un push réussi** — `src/Service/Interview/GitBriefPusher.php:69`. Le push était `branch:branch` sans `--force`. Si le push aboutit mais l'ouverture de PR échoue (GitHub 5xx transitoire, quota), le `retry()` rejoue `SubmitBrief` : nouvelle copie isolée → nouveau commit (SHA différent, horodatage courant) → `git push` vers la branche distante déjà existante = **non-fast-forward rejeté** → `Failed` définitif. **Corrigé** : push passé en `--force`. La branche `forge/<slug>` est **entièrement possédée par Forge Board** (jamais mergée par l'app), donc réécrasable sans risque — le dépôt devient idempotent et `retry()` aboutit. _Reste non couvert (edge rare) : le 422 « PR déjà ouverte » renvoie toujours `Failed` plutôt que de resurface la PR existante — hors scope, à consigner._

## Mineurs

- [ ] **[SECU] `Bash` dans la liste blanche d'outils avec `acceptEdits`** — `.env` / `.env.example` (`CLAUDE_ALLOWED_TOOLS=Read,Write,Glob,Grep,Bash`) + `ClaudeInterviewRunner.php:74`. L'agent tourne aux droits de l'utilisateur, `--permission-mode acceptEdits`, piloté par du texte utilisateur libre → surface RCE locale. **Arbitré : gardé (dette assumée)** — le skill `feature-interview` a besoin de `Bash` (`git status`, exploration du repo), sandbox conteneur déférée serveur (ADR-0002 suite), POC local mono-utilisateur. À consigner au `/report`.
- [ ] **[SCOPE] Fichiers hors périmètre 008/009 dans le working tree** — `mate/`, `mcp.json`, `.ai/mcp/mcp.json`, `composer.json` (allow-plugin `symfony/ai-mate-composer-plugin` + PSR-4 `Mate\`), worker `mate` dans `.symfony.local.yaml`. Outillage MCP sans rapport avec l'interview — idéalement à isoler dans un commit dédié pour garder l'historique lisible.
- [ ] **[ROBUSTESSE] `locate()` renvoie le 1er brief non suivi dans l'ordre porcelain (alphabétique)** — `ProducedBriefLocator.php:48`. Non déterministe si plusieurs dossiers non suivis coexistent (corollaire du BUG). Sans objet une fois le nettoyage en place.

## Points positifs

- **Sécurité du token exemplaire, reconduite de 008** : `GIT_ASKPASS` + `GIT_ASKPASS_TOKEN` en env (jamais argv ni `.git/config`), `auth_bearer` côté API GitHub, `#[\SensitiveParameter]` propagé bout en bout (`open`, `push`, `converse`), `reason()` qui n'expose que la dernière ligne de stderr (token absent des sorties par construction), URL de push/PR sans credentials.
- **Rendu du contenu agent sûr** : `markdown_to_html` via l'environnement GFM `html_input: strip` + `allow_unsafe_links: false` (services.yaml) — le texte `claude`, non maîtrisé, est assaini sans `|raw`. Message utilisateur échappé + `nl2br`. Liens PR en `rel="noopener noreferrer"`.
- **Fidélité au socle 008** : entité à état à transitions cohésives (`markThinking/markBriefReady/…` façon `markCloned`), enum `InterviewStatus` calqué sur `CloneStatus` (label/badgeTone/icon + actif/terminal/in-flight), port + registry `PullRequestOpener` miroir de `RepositoryReader`, handlers idempotents (garde de statut) non re-propagés, doubles en `services_test.yaml` (aucun `claude`/`git`/réseau réel). Commandes en argv (pas de shell) → pas d'injection.
- **Détection du brief sur le filesystem** (`git status --porcelain`) plutôt que devinée dans le texte : robuste et testée sur repo git réel.
- **Migration propre** : générée, `status` NOT NULL `DEFAULT 'awaiting'`, FK `ON DELETE CASCADE` indexées, `down()` réversible (enfant avant parent).
- **QA verte** : PHPStan L9 ✓, PHP-CS-Fixer ✓ (le `->exclude('private')` de 008 tient), **237 PHPUnit ✓** (enum exhaustif, transitions entité, `ProducedBriefLocator` sur repo git réel, `GitHubPullRequestOpener` en `MockHttpClient` nominal + 401/403/quota/422, parcours fonctionnel complet via doubles, non-contamination inter-interviews, nettoyage à l'abandon, `StoryWorkspaceCleaner`, bouton/page).

## Verdict

- Bloquants restants : 0 / 0
- Importants restants : 0 / 2 (les 2 corrigés)
- Statut : **READY TO COMMIT**

Les 2 importants (contamination inter-interviews, re-tentative de dépôt) sont corrigés et vérifiés : nettoyage post-terminal via `StoryWorkspaceCleaner` + push `--force` idempotent, couverts par 4 nouveaux tests. QA re-passée verte (PHPStan L9 ✓, 237 PHPUnit ✓, CS-Fixer ✓). Dette POC assumée restante à consigner au `/report` : `Bash` dans la liste blanche d'outils (sandbox déférée), fichiers MCP `mate/` hors périmètre à isoler, 422 « PR déjà ouverte » non resurfacée. Prochaine étape : `/commit` pour commit et push.

## Hors review (à vérifier en environnement réel)

- **Interview `claude` réelle bout en bout** : non couverte par les tests (doubles). À valider avec un vrai repo cloné, worker `messenger:consume async` actif, session `claude` ambiante — vérifier `--session-id`/`--resume`, production du `brief.md`, coût.
- **Push + PR draft GitHub réels** : token à droit d'écriture, branche `forge/<slug>` créée, PR draft ouverte, lien affiché. Vérifier le comportement token lecture seule → `Failed` lisible sans fuite.
- **Bit exécutable `bin/git-askpass.sh`** (100755) — déjà noté en 008, réutilisé ici pour le push.
