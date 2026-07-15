# Review — Cloner en local le repo d'un projet depuis son kanban

> **But** : juger le diff au regard de l'intention — dire si on commite, et ce qui bloque.
> **Registre** : technique
> **Story** : `docs/story/008-f-clone-repo-local/`
> **Amont** : `plan.md` · `pitch.md`
> **Diff examiné** : working tree + fichiers non suivis (~12 fichiers source, 6 tests/config, 1 migration)

## Synthèse

- Bloquants restants : 0 / 0
- Importants restants : 0 / 1 (corrigé)
- Mineurs restants : 2 / 5 (dette assumée : polling perpétuel, dispatch après flush)
- Statut : **PRÊT À COMMITER**

Correctifs appliqués et vérifiés (PHPStan L9 ✓, 177 PHPUnit ✓, smoke E2E clone + badges ✓). Les 2 mineurs restants sont de la dette POC assumée (à consigner au `/report`). Prochaine étape : `/commit` pour commit et push.

## Bloquants

_(aucun)_

## Importants

- [x] **[BUG] QA locale corrompt les clones** — `.php-cs-fixer.dist.php`. Le finder faisait `->in(__DIR__)` sans exclure `private/` : dès qu'un repo est cloné (la feature elle-même !), `make quality` réécrit ~180 fichiers du dépôt cloné avec **notre** style. Découvert en relançant CS-Fixer (182 fichiers de `private/enao-io-ems/` modifiés). **Corrigé** : `->exclude('private')` ajouté ; fichiers du clone restaurés (`git checkout` dans le sous-repo). PHPStan était déjà sûr (`paths` explicites, `private/` jamais scanné). Invisible en CI (`private/` y est vide) mais destructeur en local — d'où la criticité.

## Mineurs

- [x] **[DRY] Map `tones` dupliquée** — le composant reprenait la map de `_status_badge.html.twig`. **Corrigé** : `_status_badge.html.twig` rendu paramétrable (`testId` + `iconClass`, avec défauts) ; `ProjectCloneStatus.html.twig` l'inclut désormais (`testId: 'project-clone-status'`, `iconClass: animate-spin` pendant le clonage). Les 2 usages existants (show + liste) inchangés via les défauts.
- [x] **[ROBUSTESSE] `catch (\InvalidArgumentException)` large dans le handler** — **Corrigé** : `ClonePathResolver` lève une `InvalidCloneDestinationException` dédiée (étend `\InvalidArgumentException`) ; le handler catch désormais `CloneFailedException|InvalidRepositoryUrlException|InvalidCloneDestinationException`, plus de fourre-tout. Test du resolver mis à jour sur le type précis.
- [x] **[TEST] Non-fuite du token sur `/show` non couverte** — **Corrigé** : `testTokenNeverAppearsOnTheShowPage` ajouté à `ProjectCloneTest` (assert plain + chiffré absents du HTML rendu par le Live Component). 177 tests verts.
- [ ] **[ROBUSTESSE] Polling perpétuel si worker absent** — _laissé (dette assumée)_. Le poll infini tant que `Cloning` est le comportement intrinsèque de l'async au POC (worker `messenger:consume async` déclaré dans `.symfony.local.yaml`). Le « fix » (timeout d'affichage après N polls) est une feature UX spéculative, contraire à « rien de spéculatif » du CLAUDE.md. À reconsidérer si multi-utilisateur.
- [ ] **[ROBUSTESSE] `requestClone` dispatch après flush** — _laissé (dette assumée)_. Transport Doctrine sur la même BDD : un dispatch échouant après un flush réussi est quasi-impossible. Un rollback explicite ajouterait de la complexité pour un edge théorique. Documenté, non corrigé au POC.

## Points positifs

- **Sécurité du token exemplaire** : `GIT_ASKPASS` + `GIT_ASKPASS_TOKEN` en env (jamais en argv ni `.git/config`), `GIT_TERMINAL_PROMPT=0` pour éviter tout blocage TTY, `#[\SensitiveParameter]` sur `$plainToken`, et `reason()` qui n'expose que la dernière ligne de stderr (le token n'étant jamais en argv/URL, il en est absent). URL de clone sans credentials.
- **Cohérence avec l'existant** : le port `RepositoryClonerInterface` calque `RepositoryReaderInterface`, les transitions `markCloning/markCloned/markCloneFailed` suivent `applyVerification()`, l'enum `CloneStatus` réplique `VerificationStatus` (label/badgeTone/icon), le double `FakeRepositoryCloner` reprend le pattern « scénario piloté par le nom du repo » de `StubRepositoryReader`.
- **Idempotence bien pensée** : garde `Cloning` en amont du dispatch (anti double-clic) + `synchronize()` clone-ou-pull détecté sur le filesystem (`.git`), robuste à une double livraison Messenger comme à une suppression manuelle du dossier.
- **Anti-traversée de chemin** : `ClonePathResolver` aplatit les sous-groupes GitLab (`/` → `-`), rejette `..` et tout caractère hors `[A-Za-z0-9._-]`.
- **Migration propre** : générée par `make:migration`, `clone_status` NOT NULL avec `DEFAULT 'not_cloned'` (couvre les lignes existantes sans backfill), `down()` réversible restaurant l'index unique, `schema:validate` en sync.
- **Tests ciblés** : enum exhaustif, resolver (nominal + sous-groupe + traversée), contrôleur (Cloning + enqueue + CSRF + firewall via transport in-memory), handler (succès/échec sans propagation/projet inconnu). 176 PHPUnit verts, PHPStan L9 vert.

## Hors review (à vérifier en environnement réel)

- **Clone git réel GitHub + GitLab** (repo privé et public) : non couvert par les tests (le fake couvre le contrat, pas le shell-out `git`). À valider manuellement avec un vrai token, worker `messenger:consume async` actif.
- **Bit exécutable de `bin/git-askpass.sh`** : vérifié `755` avec `core.fileMode=true` → sera commité en `100755`. Confirmer après `git add` (`git ls-files -s bin/git-askpass.sh` doit afficher `100755`), sans quoi `GIT_ASKPASS` échouerait.
- **Smoke E2E clone** : validé sous `php -S` (équivalent CI, sans worker → état `Clonage…`). La board spec ne régresse que sous `php -S` mono-thread ; verte sous `symfony serve` multi-thread — artefact de serveur, pas de code.
