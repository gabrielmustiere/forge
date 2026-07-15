# Report — Gérer les projets forge (déclarer, lister, éditer/retirer)

> **But** : constater l'écart entre l'intention et le code livré — écarts, dette, suites.
> **Registre** : factuel
> **Story** : `docs/story/002-f-gestion-projets/`
> **Amont** : `pitch.md` · `plan.md` · `review.md`

## Synthèse

Feature D2 livrée intégralement et conforme à ~90 % au plan : les 8 critères d'acceptation du pitch sont satisfaits, la review est **PRÊT À COMMITER** (0 bloquant / 0 important, 6 mineurs versés en dette). Les écarts structurants sont tous volontaires et justifiés : (1) la validité d'URL + la cohérence provider↔hôte (règle 7) + l'unicité (règle 4) sont **consolidées dans une seule contrainte `UniqueRepositoryUrl`** au lieu des 3 mécanismes séparés esquissés au plan ; (2) la confirmation de suppression passe par un **overlay piloté par l'état Live** (`confirmingId`) au lieu du composant `Modal` existant ; (3) `.env` n'est pas modifié — un `.env.example` + un garde-fou dans `TokenCipher` remplacent la doc inline. Périmètre : 27 tests unit + 11 functional + 3 E2E, PHPStan niveau 10 vert, `make ci` OK.

## Périmètre livré

### Fichiers créés

| Fichier                                                     | Rôle                                                                          | Prévu dans le plan |
|-------------------------------------------------------------|-------------------------------------------------------------------------------|--------------------|
| `src/Entity/Project.php`                                    | Entité projet déclaré (provider, url normalisée, name, token chiffré, createdAt). | Oui                |
| `src/Enum/Type/Provider.php`                                | Enum backed string github/gitlab + `host()`, `label()`, `icon()`.             | Oui                |
| `src/Repository/ProjectRepository.php`                      | `findAllOrdered()` (+ tie-breaker `id DESC`), `existsByNormalizedUrl(exclude?)`. | Oui                |
| `src/Service/RepositoryUrl.php`                             | Value object immuable (provider dérivé, owner, repo, normalizedUrl).           | Oui                |
| `src/Service/RepositoryUrlNormalizer.php`                   | Parse/normalise l'URL, **dérive le provider depuis l'hôte**.                   | Oui                |
| `src/Service/TokenCipher.php`                               | `encrypt()`/`decrypt()` sodium ; clé hkdf d'`APP_SECRET` ; exception si secret vide. | Oui                |
| `src/Service/InvalidRepositoryUrlException.php`             | Exception dédiée du chemin d'erreur de normalisation.                         | **Non (ajout — cf. §Ajouts non prévus)** |
| `src/Manager/ProjectManager.php`                            | Orchestration create/update/delete. `final readonly class`.                   | Oui                |
| `src/Form/ProjectFormData.php`                              | DTO de saisie (provider, url, name, plainToken).                              | Oui                |
| `src/Form/ProjectType.php`                                  | FormType sur DTO (sélecteur provider, token jamais pré-rempli).               | Oui                |
| `src/Validator/UniqueRepositoryUrl.php`                     | Contrainte consolidée : validité + cohérence provider↔hôte + unicité.        | Oui (périmètre élargi — cf. §Écarts) |
| `src/Validator/UniqueRepositoryUrlValidator.php`            | Validateur (normalise, vérifie hôte↔provider, exclut l'entité courante).      | Oui                |
| `src/Controller/ProjectController.php`                      | `index`, `new`, `edit`, `show` (routes `app_project_*`, `/projects`).         | Oui                |
| `src/Twig/Components/ProjectList.php`                       | Live Component liste + `confirmDelete`/`cancelDelete`/`delete` via `confirmingId`. | Oui                |
| `src/Twig/Components/SidebarProjects.php`                   | `#[AsTwigComponent]` alimentant la liste de la sidebar.                        | **Non (ajout — mécanisme non spécifié)** |
| `templates/project/index.html.twig`                        | Page liste (hôte du Live Component + bouton « déclarer »).                    | Oui                |
| `templates/project/new.html.twig`                          | Formulaire de déclaration (inclut `_form.html.twig`).                         | Oui                |
| `templates/project/edit.html.twig`                         | Formulaire d'édition (inclut `_form.html.twig`).                              | Oui                |
| `templates/project/_form.html.twig`                        | Partial mutualisé new/edit (sélecteur provider aux couleurs de marque).       | **Non (ajout — cf. §Ajouts non prévus)** |
| `templates/project/show.html.twig`                         | Page détail placeholder (métadonnées + « kanban à venir » + lien repo).       | Oui                |
| `templates/components/ProjectList.html.twig`               | Template Live Component (table + overlay de confirmation).                     | Oui                |
| `templates/components/SidebarProjects.html.twig`           | Template du composant sidebar.                                                | **Non (ajout)**    |
| `assets/controllers/url_name_suggest_controller.js`        | Pré-remplissage live du nom `owner/repo` sans écraser une saisie manuelle.     | Oui                |
| `.env.example`                                              | Documente `APP_SECRET` requis pour le chiffrement des tokens.                  | Oui (en remplacement de la modif `.env` — cf. §Écarts) |
| `migrations/Version20260704191620.php`                     | Création table `project` (index unique `url`).                                | Oui                |
| `tests/Unit/Service/RepositoryUrlNormalizerTest.php`       | Formes https/ssh/`.git`, owner/repo, rejet hôte↔provider incohérent.          | Oui                |
| `tests/Unit/Service/TokenCipherTest.php`                   | Round-trip, non-déterminisme (nonce), rejet altération.                       | Oui                |
| `tests/Unit/Enum/ProviderTest.php`                         | `host()`/`label()` par cas.                                                    | Oui                |
| `tests/Unit/Validator/UniqueRepositoryUrlValidatorTest.php`| Doublon rejeté, édition de soi-même OK.                                        | Oui                |
| `tests/Functional/Repository/ProjectRepositoryTest.php`    | Méthodes repository sur BDD de test.                                          | Oui                |
| `tests/Functional/Controller/ProjectControllerTest.php`    | CRUD complet + token absent du HTML + confirmation suppression.               | Oui                |
| `tests/Functional/Twig/ProjectListComponentTest.php`       | Live Component testé par **instanciation directe**.                           | Oui (variante autorisée — cf. §Écarts) |
| `tests/e2e/projects.spec.ts`                               | Déclaration puis suppression via l'UI (sélecteurs `data-test`).               | Oui                |

### Fichiers modifiés

| Fichier                        | Modification                                                                 | Prévu dans le plan |
|--------------------------------|------------------------------------------------------------------------------|--------------------|
| `templates/base.html.twig`     | Sidebar : placeholder remplacé par le composant `SidebarProjects` + nav « Projets ». | Oui                |
| `fixtures/AppFixtures.php`      | Ajout de projets d'exemple (token chiffré via `TokenCipher`).                | Oui                |
| `composer.json`                | Ajout de `ext-sodium: *` à `require`.                                        | Oui                |
| `docs/product-backlog.md`      | Modification d'une ligne (hors périmètre feature — à trancher au commit).    | **Non (hors scope — cf. §Dette)** |
| `.env`                         | **Non modifié** — remplacé par `.env.example` + garde-fou `TokenCipher`.     | Écart volontaire (cf. §) |

## Écarts avec le plan

### Écarts volontaires

| Prévu                                                                                   | Réalisé                                                                                                 | Raison                                                                                                       |
|-----------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------|
| 3 mécanismes séparés : contrainte d'unicité + Callback/contrainte de cohérence provider↔hôte + validation d'URL ; provider reçu en paramètre. | Une seule contrainte `UniqueRepositoryUrl` couvre validité d'URL + cohérence provider↔hôte (règle 7) + unicité (règle 4) ; le normalizer **dérive le provider de l'hôte**. | Les trois contrôles nécessitent tous la normalisation préalable ; les regrouper évite de la refaire 3× et réduit le nombre de fichiers. Validé en review (§Points positifs). |
| Confirmation de suppression via le composant `Modal` (`<dialog>`) existant.             | Overlay piloté par l'état Live (`confirmingId` sur `ProjectList`, actions `confirmDelete`/`cancelDelete`/`delete`). | Le `<dialog>` s'accorde mal avec le re-render Live ; l'état porté par le composant est plus robuste.        |
| « Câbler la sidebar » dans `base.html.twig` (mécanisme non spécifié).                   | Rendu via un `#[AsTwigComponent] SidebarProjects` dédié (+ son template).                              | Isole la requête et le rendu de la liste hors du layout ; réutilisable et testable.                         |
| Modifier `.env` pour documenter `APP_SECRET` (valeur laissée vide).                     | `.env` intouché ; création de `.env.example` + garde-fou : `TokenCipher` lève une exception si le secret est vide. `APP_SECRET` déjà présent en `.env.dev`/`.env.test`. | Évite de toucher un fichier d'env versionné ; le garde-fou rend l'échec explicite plutôt que silencieusement faible. Aucun manque fonctionnel en dev/test. |
| `findAllOrdered()` : tri sur `createdAt` seul.                                           | Tri `createdAt DESC` **+ tie-breaker `id DESC`**.                                                       | `created_at` SQLite est à la précision seconde → sans tie-breaker, l'ordre de deux projets créés dans la même seconde est indéterminé. |
| QA finale sous **PHPStan niveau 9** (plan §Ordre 12 + CLAUDE.md).                        | Projet analysé et vert en **PHPStan niveau 10**.                                                       | Le projet tourne en niveau 10 ; conformité supérieure au niveau demandé.                                    |
| Test Live Component « via test du composant OU WebTestCase sur l'endpoint live ».       | Test par **instanciation directe** du composant.                                                       | L'endpoint `/_components` est derrière le firewall `main` ; l'instanciation directe teste la logique sans traverser la sécurité. Variante explicitement autorisée par le plan. |

**Questions ouvertes du plan — tranchées à l'implémentation** (conformes aux défauts proposés) :

- Garde-fou `APP_SECRET` vide → `TokenCipher` lève une exception (préférence du plan retenue).
- Périmètre `url-name-suggest` → pré-remplissage **live** (à la saisie), sans écraser une saisie manuelle.
- Suppression depuis `show` → le bouton vit **uniquement** dans le Live Component ; `show` renvoie vers la liste.

### Non implémenté

| Élément prévu | Raison | Action requise |
|---------------|--------|----------------|
| Aucun          | La feature D2 est livrée intégralement (les 8 critères d'acceptation satisfaits). | —              |

### Ajouts non prévus

| Élément ajouté                                                                          | Raison                                                                                                    |
|-----------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------|
| `src/Service/InvalidRepositoryUrlException.php`                                         | Chemin d'erreur propre pour la normalisation, consommé par le validateur — découvert nécessaire en cours d'implémentation. |
| `src/Twig/Components/SidebarProjects.php` (+ template)                                  | Concrétisation du « câblage sidebar » resté abstrait au plan (cf. §Écarts).                              |
| `templates/project/_form.html.twig` (partial mutualisé)                                 | Factorise le markup commun aux formulaires `new` et `edit` (DRY).                                        |
| Couleurs de marque du bouton provider sélectionné (GitHub blanc/clair, GitLab orange `#fc6d26`) au lieu de l'accent iris. | **Demande utilisateur post-livraison.** Implémenté via `group-has-[:checked]:` sur le span + widget en `sr-only` (contournement : le thème Flowbite enveloppe la radio dans un `<div>`, cassant `peer-checked`). Non prévu au pitch/plan d'origine. |

## Tests

| Code                                             | Type prévu        | Type réalisé                                            | Statut                                   |
|--------------------------------------------------|-------------------|--------------------------------------------------------|------------------------------------------|
| `RepositoryUrlNormalizer`                        | Unit              | Unit (https/ssh/`.git`, owner/repo, rejet hôte↔provider) | Fait                                     |
| `TokenCipher`                                    | Unit              | Unit (round-trip, non-déterminisme, altération)        | Fait                                     |
| `Provider` enum                                  | Unit              | Unit (`host()`/`label()`)                              | Fait                                     |
| `UniqueRepositoryUrlValidator`                   | Unit              | Unit (doublon, édition de soi, cohérence provider↔hôte) | Fait — couverture étendue (contrainte consolidée) |
| `ProjectRepository`                              | Functional        | Functional (`findAllOrdered`, `existsByNormalizedUrl`) | Fait                                     |
| `ProjectController` + `ProjectManager`           | Functional        | Functional (CRUD, doublon, provider↔hôte, token absent du HTML, confirmation) | Fait                                     |
| `ProjectList` (Live Component)                   | Functional (composant OU endpoint live) | Functional par **instanciation directe** | Fait (variante autorisée — endpoint live derrière firewall) |
| Parcours déclaration + suppression               | E2E (Playwright)  | E2E (3 specs, sélecteurs `data-test`)                  | Fait                                     |

Total : 27 unit + 11 functional + 3 E2E, verts. PHPStan niveau 10 vert, `make ci` OK.

## Dette technique identifiée

Issus de la review (mineurs non traités, versés en dette assumée) :

1. **[ROBUSTESSE] Unicité insensible à la casse non couverte** — `src/Service/RepositoryUrlNormalizer.php:60` — l'hôte est minusculé mais la casse de `owner/repo` est préservée → `github.com/Acme/Repo` et `github.com/acme/repo` sont deux URLs distinctes. Impact faible (mono-utilisateur, doublon cosmétique) ; GitHub est casse-insensible mais GitLab non → arbitrage par provider à faire si repris.
2. **[STYLE] `<label>` imbriqués dans le sélecteur provider** — `templates/project/_form.html.twig:21` — HTML invalide (labels imbriqués), fonctionne en pratique (vérifié E2E). Nettoyage : rendu radio brute + CSRF manuel.
3. **[STYLE] Duplication du markup d'erreur** — `templates/project/_form.html.twig` — boucle d'erreurs répétée 3× ; extraction possible en macro Twig.
4. **[PERF] Requête liste sur chaque page authentifiée** — `src/Twig/Components/SidebarProjects.php:22` — `findAllOrdered()` s'exécute à chaque rendu de `base.html.twig`, + une 2ᵉ fois sur `/projects`. Négligeable en mono-utilisateur ; cache léger si la liste grossit.
5. **[PERF] Pas d'index sur `created_at`** — `migrations/Version20260704191620.php` — tri `created_at DESC, id DESC` sans index dédié. Sans conséquence à ce volume.
6. **[DOC] Ligne hors périmètre dans le diff** — `docs/product-backlog.md` — modification présente avant la story, sans lien direct avec la feature. À confirmer avant commit (inclure ou dégrouper).

Au-delà de la review :

7. **Rotation d'`APP_SECRET`** — tradeoff assumé (choix produit) : une rotation rend les tokens existants illisibles (re-saisie via l'édition). À re-arbitrer vers une `PROJECT_TOKEN_KEY` dédiée si le besoin devient réel.
8. **Scannabilité GitLab** — déclarable dès maintenant mais non scannable tant que `connecteur-gitlab-lecture` (V2) n'est pas livré. Limite assumée du pitch.

## Critères d'acceptation

Reprise des critères du `pitch.md` :

- [x] Un utilisateur connecté peut déclarer un projet (provider GitHub/GitLab, URL, token) ; le projet apparaît dans sa liste.
- [x] Le nom est pré-rempli en `owner/repo` à partir de l'URL (live, sans écraser une saisie manuelle) et reste éditable.
- [x] Déclarer une URL déjà suivie (même forme normalisée) est refusé avec « ce repo est déjà suivi ».
- [x] Une URL ne correspondant pas au provider sélectionné est refusée avec un message clair.
- [x] La liste affiche nom, provider, URL et date d'ajout, et permet d'ouvrir un projet.
- [x] Depuis l'édition, l'utilisateur peut modifier l'URL et renouveler le token ; laisser le champ token masqué intact conserve le token existant.
- [x] Le token n'est jamais présent dans le HTML servi (clair **et** chiffré, vérifié par test functional) ni dans les logs.
- [x] Retirer un projet demande une confirmation, puis le fait disparaître définitivement de la liste.

## Leçons apprises

- **Consolider les contrôles qui partagent une normalisation** : dès que validité, cohérence et unicité reposent sur la même URL normalisée, une contrainte unique évite de refaire le parsing 3× et réduit la surface de fichiers. À anticiper au plan plutôt que de découper par principe.
- **`<dialog>` et Live Components font mauvais ménage** : pour une confirmation dans un composant Live, préférer d'emblée un état porté par le composant (`LiveProp`) à un composant modal externe — le re-render casse l'accord avec l'élément natif.
- **SQLite trie à la seconde** : tout tri temporel destiné à être déterministe a besoin d'un tie-breaker sur la clé primaire. À poser par défaut dans les méthodes `findAll…Ordered`.
- **Le thème Flowbite enveloppe les radios** : `peer-checked` ne traverse pas le `<div>` intercalé ; `group-has-[:checked]:` sur un conteneur parent est le contournement fiable pour styler un button-group radio.
- **Endpoint Live derrière firewall = test par instanciation** : quand `/_components` est protégé, tester le composant Live par instanciation directe reste la voie la plus simple pour couvrir sa logique sans monter tout un scénario authentifié.
