# Review — Afficher le kanban d'un projet

> Date : 2026-07-05
> Stack : symfony
> Périmètre : working tree (7 fichiers modifiés + 17 nouveaux hors doc/lock, ~1650 lignes de code)
> Référence d'intention : `docs/story/005-f-kanban-projet/plan.md` + `pitch.md`

## Bloquants

- [x] **[BUG] Le rendu du board plante (500) sur un document `.md` au nom non conforme à la regex de route** — `templates/project/_card.html.twig` + `src/Service/Board/ProjectBoardBuilder.php` — `documentsFor()` incluait tout fichier terminant par `.md`, mais `_card` génère l'URL de chaque doc via `path('app_project_story_doc', { filename })` dont le `requirements` est `[a-z0-9._-]+\.md`. Avec `strict_requirements: true` (confirmé), un fichier comme **`README.md`** aurait levé `InvalidParameterException` → page cassée (viole règles 9/10). **Corrigé** : `documentsFor()` filtre désormais sur `DOCUMENT_NAME = /^[a-z0-9._-]+\.md$/` (aligné sur la route) — les `.md` non servables sont exclus du drawer. Test de non-régression `testDocumentsExcludeNamesNotServableByTheRoute` (README.md / majuscule / sous-chemin / non-.md).

## Importants

- _(aucun)_

## Mineurs

- [x] **[ROBUSTESSE] Carte sans document → drawer vide** — `assets/controllers/story_drawer_controller.js` — **corrigé** : `renderDocuments([])` affiche « Aucun document lisible. » et n'arme aucun `src` (au lieu d'un drawer muet).
- [x] **[PLAN] `<turbo-frame loading="lazy">`** — `templates/project/_drawer.html.twig` — **ajouté** `loading="lazy"` sur le frame (le `src` reste armé par Stimulus au clic). Écart résiduel mineur (mécanisme d'armement) à consigner au `/sync`.
- [x] **[ROBUSTESSE/SECU] Liens markdown tiers** — `config/services.yaml` — **corrigé** au niveau du converter (plutôt qu'un hack template) : ajout de `ExternalLinkExtension` — les liens externes s'ouvrent en nouvel onglet avec `rel="noopener noreferrer"`. Le converter est reconstruit sur un `Environment` explicite (core + GFM + external-link), sanitization `html_input: strip` préservée. Assertion ajoutée au test Application (`target="_blank"` + `noopener`).

## Points positifs

- **Garde-fou d'échec fidèle au patron `ProjectVerifier`** : `ProjectBoardBuilder` catche `Unreachable`/`AccessDenied`/`InvalidUrl` et les traduit en `BoardResult::failure` — jamais d'exception métier au template (règle 10). Idem `StoryDocumentFetcher`.
- **Sanitization prouvée** : la correction du wiring (`twig.markdown.default` plutôt que l'alias ignoré) est couverte par un test Application qui vérifie que `<script>` est strippé — le bug silencieux a été attrapé.
- **Anti-traversée solide** : double regex stricte (`storyId`, `filename`), chemin borné à `docs/story/{storyId}/{filename}`, token déchiffré au plus près et jamais loggé. Test 404 sur `..%2F` et sur filename sans `.md`.
- **Fake reader dev opt-in bien isolé** : `#[When('dev')]` + flag `APP_FAKE_REPOSITORY_READER` (défaut 0) préserve le dogfooding réel ; catalogue partagé avec le stub de test (zéro duplication/drift).
- **Couverture** : 131 PHPUnit (dont unités VO/enum, builder, reader `readFile`, Application colonnes/compteurs/bandeau/vide/erreur/sanitize/traversal) + 11 E2E verts.

## Verdict

- Bloquants restants : 0 / 1
- Importants restants : 0 / 0
- Mineurs restants : 0 / 3
- Statut : **READY TO COMMIT**

Tous les findings corrigés. QA repassée verte : 132 PHPUnit / 312 assertions + 11 E2E. `/commit` pour commit et push.

## Hors review (à vérifier en environnement réel)

- E2E board joués contre le serveur Symfony CLI avec `APP_FAKE_REPOSITORY_READER=1` (le `php -S` router-only ne sert pas les assets → Stimulus KO ; ne pas s'y fier).
- Rendu du board sur un **vrai** repo forge avec docs transversaux (README.md, etc.) une fois le bloquant corrigé.
