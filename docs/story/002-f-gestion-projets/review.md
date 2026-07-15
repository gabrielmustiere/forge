# Review — Gérer les projets forge (déclarer, lister, éditer/retirer)

> **But** : juger le diff au regard de l'intention — dire si on commite, et ce qui bloque.
> **Registre** : technique
> **Story** : `docs/story/002-f-gestion-projets/`
> **Amont** : `plan.md` · `pitch.md`
> **Diff examiné** : working tree + index (35 fichiers, ~2120 lignes) — feature complète D2

## Synthèse

- Bloquants restants : 0 / 0
- Importants restants : 0 / 0
- Statut : **PRÊT À COMMITER**

Prochaine étape : `/commit` pour commit et push. Les mineurs peuvent être traités maintenant ou versés à la dette (report/sync).

## Bloquants

_(aucun)_

## Importants

_(aucun)_

## Mineurs

- [ ] **[ROBUSTESSE] Unicité insensible à la casse non couverte** — `src/Service/RepositoryUrlNormalizer.php:60` — la normalisation met l'hôte en minuscules mais **préserve la casse de `owner/repo`**. `github.com/Acme/Repo` et `github.com/acme/repo` produisent deux URLs normalisées distinctes → le même dépôt GitHub (casse-insensible) peut être déclaré deux fois, et l'index unique BDD ne le rattrape pas non plus. Impact faible (mono-utilisateur, doublon cosmétique) ; hors des variations exigées par les critères d'acceptation (https/ssh/`.git`). Le plan l'anticipe déjà comme « normalisation incomplète ». Décision possible : lowercaser le chemin pour GitHub (mais GitLab est sensible à la casse → à arbitrer par provider) ou laisser en dette.
- [ ] **[STYLE] `<label>` imbriqués dans le sélecteur provider** — `templates/project/_form.html.twig:21` — le `<label>` externe enveloppe `form_widget(choice)` qui rend lui-même un `<input>` + un `<label>` (thème Flowbite), masqué en `sr-only`. HTML invalide (labels imbriqués), fonctionne en pratique (clic → radio cochée, vérifié E2E). Nettoyage possible : `form_end(form, { render_rest: false })` + radio brute + rendu CSRF manuel, ou accepter la dette.
- [ ] **[STYLE] Duplication du markup d'erreur** — `templates/project/_form.html.twig` — la boucle d'affichage des erreurs (`<p class="… text-fg-danger-strong">…`) est répétée 3× (provider / url / plainToken). Extraction possible en macro Twig.
- [ ] **[PERF] Requête liste sur chaque page authentifiée** — `src/Twig/Components/SidebarProjects.php:22` — `findAllOrdered()` s'exécute à chaque rendu de `base.html.twig` (toutes les pages), et une 2ᵉ fois sur `/projects` (sidebar + Live Component). Négligeable à l'échelle mono-utilisateur ; à surveiller si la liste grossit (cache léger ou requête partagée).
- [ ] **[PERF] Pas d'index sur `created_at`** — `migrations/Version20260704191620.php` — `findAllOrdered()` trie sur `created_at DESC, id DESC` sans index dédié. Sans conséquence à ce volume ; à noter si le tri devient un point chaud.
- [ ] **[DOC] Ligne hors périmètre dans le diff** — `docs/product-backlog.md` — modification d'une ligne présente avant la story (working tree), sans lien direct avec la feature. À confirmer avant commit (inclure ou dégrouper).

## Points positifs

- **Sécurité du token exemplaire** : chiffrement AEAD (`sodium_crypto_secretbox`), clé dérivée d'`APP_SECRET` (HKDF, zéro variable ajoutée), DTO write-only (`always_empty`, jamais hydraté depuis l'entité), et **test fonctionnel assertant l'absence du clair ET du chiffré dans le HTML** de `new`/`edit`. Le critère le plus sensible du pitch est verrouillé par un test.
- **URLs sûres par construction** : le normalizer ne produit que `https://host/owner/repo` avec segments validés `[A-Za-z0-9._-]` → aucun risque de `javascript:` dans les liens sortants `target="_blank"` (avec `rel="noopener noreferrer"`).
- **Architecture respectée** : Request → Controller → Manager → Repository → Entity ; QueryBuilder confiné au repository ; services purs testables unitairement ; DTO au lieu de bind entité (pas de mass-assignment).
- **Validation consolidée et testée** : une seule contrainte `UniqueRepositoryUrl` couvre validité + cohérence provider↔hôte + unicité, avec exclusion de soi à l'édition — couverte par un test unitaire (`ConstraintValidatorTestCase`, stub conforme PHPUnit 13).
- **Couverture de test large** : 27 unit + 11 functional + 3 E2E, PHPStan niveau 10 vert, `failOnNotice/Warning/Deprecation` respecté, sélecteurs `data-test`.
- **Migration propre** : générée, `down()` réversible (DROP TABLE nouvelle), index unique sur `url`, `schema:validate` OK.

## Hors review (à vérifier en environnement réel)

- Suite E2E Playwright à rejouer après un `make db-reset` (elle vise le serveur dev `forge-board.wip` avec fixtures).
- `APP_SECRET` doit être non vide dans l'environnement cible (dev/test le sont déjà via `.env.dev`/`.env.test`) — sinon `TokenCipher` lève une exception au premier chiffrement.
