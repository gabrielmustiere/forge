# Review — Connecteur GitHub (lecture) + vérification d'éligibilité forge

> Date : 2026-07-05
> Stack : symfony
> Périmètre : working tree (10 fichiers suivis modifiés + ~15 nouveaux fichiers ; ~660 lignes)
> Référence d'intention : `docs/story/003-f-connecteur-github-lecture/plan.md` + `pitch.md`

## Bloquants

_(aucun)_

## Importants

_(aucun)_

## Mineurs

- [x] **[SECU] Web-profiler `http_client` en dev** — **confirmé factuellement** : le HTML du profiler (onglets masqués inclus) contient le token en clair (`auth_bearer`/`Authorization`/`ghp_`). **Accepté comme limitation dev-only** : profiler désactivé en prod, outil mono-utilisateur local, token appartenant à l'utilisateur ; Symfony n'offre pas de redaction propre par-client du collector `http_client` (désactivation globale ou compiler pass = sur-ingénierie pour un non-risque). Les surfaces exigées par le critère #7 restent propres : **HTML servi** (vérifié, aucun token) et **logs applicatifs** (vérifié : monolog ne journalise que les URLs, sans token). Mitigation possible si multi-utilisateur un jour : désactiver le collector `http_client`.
- [x] **[TEST] Route POST `app_project_verify` désormais couverte** — `tests/Functional/Controller/ProjectControllerTest.php` : `testVerifyButtonUpdatesStatusAndTimestamp` (nominal → Eligible + horodatage) et `testVerifyIsRejectedWithoutAValidCsrfToken` (token forgé → statut inchangé). Chemin fiche + CSRF verrouillés.
- [x] **[ROBUSTESSE] E2E — dépendance réseau réduite** — `tests/e2e/projects.spec.ts` : le test badge autonome (qui ajoutait une 2ᵉ déclaration réseau + risque de pollution) est supprimé ; l'assertion badge est repliée dans « declare then delete ». Le seul appel réseau restant est celui **inhérent à la feature** (la déclaration vérifie), token invalide → 401 rapide, save jamais bloqué. Résiduel documenté : un `webServer` en env test isolerait complètement le réseau, mais contredirait la préférence établie de cibler `forge-board.wip` — non retenu.

## Points positifs

- **Sécurité du token prouvée** : déchiffrement au plus près de l'appel (variable locale), passage en `auth_bearer`, jamais dans l'URL ni les logs — vérifié dans les logs applicatifs réels et par un test unitaire dédié (`GitHubRepositoryReaderTest::testPassesTheTokenAsBearerNeverInTheUrl`).
- **Approche bornée à `docs/story` prouvée en réel** : les logs du projet enao montrent exactement les 3 appels prévus (`/repos`, `/contents/docs?ref=`, `/git/trees/{sha}?recursive=1`), troncature neutralisée par conception.
- **Abstraction propre** : `RepositoryReaderInterface` + `tagged_iterator` + registry ; GitLab retourne `UnsupportedProvider` sans appel réseau (test avec `expects(never)`), V2 GitLab possible sans toucher l'orchestration.
- **Couverture d'erreurs exhaustive** : `MockHttpClient` couvre 401/403, rate-limit (403 + `X-RateLimit-Remaining: 0`), 404 repo vs 404 docs, transport/timeout, arbre tronqué — sans aucun réseau réel.
- **Statut lu en base au rendu** : liste et fiche ne font aucun appel réseau (profiler : 0 `http_client`), critères #7/#8 respectés.
- **Migration réversible** avec `DEFAULT 'unverified'` (backfill des lignes existantes) et `verified_at` nullable ; `schema:validate` en sync.

## Verdict

- Bloquants restants : 0 / 0
- Importants restants : 0 / 0
- Mineurs restants : 0 / 3 (2 corrigés, 1 accepté dev-only documenté)
- Statut : **READY TO COMMIT**

`/forge:commit` pour commit et push.

## Hors review (à vérifier en environnement réel)

- **Chemin nominal réel** : déjà vérifié — projet enao (`github.com/enao-io/ems`, vrai token) → **Éligible** via liste (LiveAction) et fiche (form POST), horodatage persisté.
- **E2E en CI** : rejouer `make playwright` sur un runner avec (ou sans) accès sortant à `api.github.com` pour confirmer le comportement de repli (`Unreachable`/`InvalidToken`) et la latence.
