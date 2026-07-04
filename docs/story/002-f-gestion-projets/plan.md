# Plan technique — Gérer les projets forge (déclarer, lister, éditer/retirer)

> Pitch : `docs/story/002-f-gestion-projets/pitch.md`
> Stack : symfony

## Approche retenue

CRUD server-rendered classique posé sur l'architecture imposée du projet (Request → Controller → Manager → Repository → Entity). Une entité `Project` porte les métadonnées (`provider`, `url` normalisée + unique, `name`, `token` **chiffré**, `createdAt`). Toute la logique métier — normalisation d'URL, déduction du nom, chiffrement du token, contrôle d'unicité — vit dans un `ProjectManager` et deux services purs (`RepositoryUrlNormalizer`, `TokenCipher`) ; le contrôleur reste mince. Le **token est write-only en D2** : `TokenCipher` expose `encrypt()`/`decrypt()` mais seul `encrypt()` est appelé ici (le déchiffrement n'arrivera qu'avec le connecteur D3). Le token stocké est le **chiffré** ; le clair ne transite jamais vers le front grâce à un **DTO de formulaire** (`ProjectFormData`) dont le champ token n'est jamais hydraté depuis l'entité.

Rendu en deux registres : le **formulaire déclaration/édition est une page classique** (contrôleur + `FormType` sur DTO, markup mutualisé dans un partial `_form.html.twig`) — c'est ce qui rend propre la règle « token inchangé si non touché » et la garantie « jamais dans le HTML ». La **liste est un Live Component** (`ProjectList`) avec suppression via `#[LiveAction]` derrière un **overlay de confirmation piloté par l'état Live** (`LiveProp confirmingId`), re-render sans rechargement. L'**ouverture d'un projet** mène à une page détail placeholder (métadonnées + encart « kanban à venir » + lien sortant vers le repo), prête à héberger le board en D3/D4.

**Alternatives écartées** :

- **Type Doctrine `EncryptedStringType`** (chiffrement transparent au mapping) : injecter la clé dans un type Doctrine (non-service) est un point de douleur connu, et l'entité porterait le clair en mémoire (risque de sérialisation accidentelle). Le chiffrement au niveau `ProjectManager` garde l'entité porteuse du seul chiffré.
- **Clé de chiffrement dédiée (`PROJECT_TOKEN_KEY`)** : écartée au profit d'une clé dérivée d'`APP_SECRET` (hkdf) — zéro nouvelle variable. Tradeoff assumé : rotation d'`APP_SECRET` = tokens illisibles.
- **Liste en Twig statique + Turbo** : écartée au profit d'un Live Component pour la suppression sans reload (choix produit).
- **Bind direct du form sur l'entité `Project`** : écarté au profit d'un DTO — sans lui, gérer « token inchangé si non touché » et empêcher le chiffré de repartir au front serait fragile.
- **Déduction du nom 100 % serveur** : conservée comme garantie (nom vide au submit → dérivé), mais complétée par un contrôleur Stimulus de pré-remplissage pour honorer le « pré-rempli » du pitch.

## Entités et modèle de données

### Nouvelle entité `App\Entity\Project`

`src/Entity/Project.php` :

| Champ        | Type                                   | Nullable | Contrainte                                                        |
|--------------|----------------------------------------|----------|-------------------------------------------------------------------|
| `id`         | int (PK auto)                          | non      |                                                                   |
| `provider`   | `Provider` (enum backed string)        | non      | `#[ORM\Column(enumType: Provider::class)]`                        |
| `url`        | string(255) — forme **normalisée**     | non      | unique (voir UniqueConstraint) ; `Assert\NotBlank`                 |
| `name`       | string(255) — `owner/repo` par défaut  | non      | `Assert\NotBlank` (rempli par déduction si vide)                   |
| `token`      | text — **chiffré** (nonce+cipher b64)  | non      | jamais réaffiché ; jamais loggé                                    |
| `createdAt`  | `DateTimeImmutable`                     | non      | `#[ORM\Column(name: 'created_at')]`, set au `new` (constructeur)  |

Attributs au niveau classe :

```php
#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_PROJECT_URL', fields: ['url'])]
```

Notes de mapping :
- Champs camelCase → colonnes snake_case explicites (`created_at`) conformément à la convention Symfony du projet.
- `token` en type `text` (le chiffré base64 dépasse volontiers 255 caractères selon la longueur du token source).
- L'entité **ne porte aucun service** et **n'implémente pas** `OrganizationAwareInterface` (app mono-utilisateur, pas de multi-tenant).
- Contrainte d'unicité applicative : contrainte custom `UniqueRepositoryUrl` (validation) **en plus** de l'index unique BDD (défense en profondeur + message métier « ce repo est déjà suivi »). On ne peut pas utiliser `UniqueEntity` tel quel car la comparaison doit porter sur l'URL **normalisée** (calculée avant persistance), pas sur la saisie brute.

### Nouvel enum `App\Enum\Type\Provider`

`src/Enum/Type/Provider.php` — backed string (`github`, `gitlab`), avec `host(): string` (github.com / gitlab.com), `label(): string`, `icon(): string` (nom d'icône tabler pour l'UI). Premier occupant de `src/Enum/Type/` (convention CLAUDE.md à instaurer).

## Mécanismes framework mobilisés

- **Service `TokenCipher`** (`sodium_crypto_secretbox`) : chiffrement authentifié du token. Clé dérivée `hash_hkdf('sha256', <APP_SECRET brut>, 32, 'project-read-token')`, nonce aléatoire par chiffrement (`random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES)`), sortie `base64(nonce || cipher)`. Injecté avec `#[Autowire('%kernel.secret%')]`. Justification : natif PHP (`ext-sodium` vérifié présent), zéro dépendance, AEAD.
- **Service `RepositoryUrlNormalizer` + value object `RepositoryUrl`** : parsing/normalisation d'URL pur (https, ssh `git@host:owner/repo.git`, avec/sans `.git`), extraction `owner/repo`, **dérivation du provider depuis l'hôte** (le provider n'est pas un paramètre d'entrée). Une URL d'hôte inconnu ou malformée lève `InvalidRepositoryUrlException` (exception dédiée, `src/Service/`). Service sans état, testable unitairement, réutilisé par le manager ET la validation.
- **`ProjectManager`** (service métier) : orchestration create/update/delete. Concentre la logique (normaliser → dédupliquer → chiffrer si token fourni → déduire le nom si vide → persister via repository/EM). Justification : archi projet (logique hors contrôleur/entité).
- **Repository custom `ProjectRepository`** (`ServiceEntityRepository`) : méthodes nommées, QueryBuilder confiné ici.
- **Contrainte de validation custom `UniqueRepositoryUrl`** (`Constraint` + `ConstraintValidator`) : **contrainte consolidée** qui regroupe validité de l'URL, cohérence provider↔hôte (règle métier 7) et unicité sur URL normalisée via `ProjectRepository` (en excluant l'entité courante à l'édition). Les trois contrôles reposant tous sur la même normalisation, les mécanismes séparés esquissés initialement (Callback de cohérence + contrainte d'unicité distincte) sont abandonnés au profit de ce point d'entrée unique — moins de fichiers, normalisation faite une seule fois.
- **Form `ProjectType` sur DTO `ProjectFormData`** : découple la représentation de saisie du modèle. Sélecteur `provider` en `ChoiceType` (`expanded: true` → radios) stylé façon button-group ; le bouton du provider sélectionné adopte les **couleurs de la marque** (GitHub blanc/clair, GitLab orange `#fc6d26`) via `group-has-[:checked]:` sur le span, le widget radio étant masqué en `sr-only` — contournement nécessaire car le thème Flowbite enveloppe la radio dans un `<div>`, ce qui casse `peer-checked`. Champ `plainToken` en `PasswordType` `mapped` côté DTO mais **jamais pré-rempli** ; `always_empty: true` pour ne jamais réémettre de valeur.
- **Live Component `ProjectList`** (`#[AsLiveComponent]`) : liste + `#[LiveAction] public function delete(int $id)` (CSRF géré par le composant), délègue à `ProjectManager::delete()`, re-render sans reload. Confirmation via un **overlay piloté par l'état Live** (`LiveProp confirmingId` + actions `confirmDelete`/`cancelDelete`/`delete`) plutôt que le composant `Modal` (`<dialog>`), qui s'accorde mal avec le re-render Live.
- **Composant `#[AsTwigComponent] SidebarProjects`** : alimente la liste des projets de la sidebar (`base.html.twig`) hors du layout, isolant la requête et le rendu — testable et réutilisable.
- **Contrôleur Stimulus `url-name-suggest`** : pré-remplit le champ nom (`owner/repo`) au `input`/`blur` de l'URL, sans écraser une saisie manuelle.
- **`EntityValueResolver`** : résolution de `Project` par `{id}` dans `show`/`edit` (seule exception tolérée à « tout passe par une méthode de repository »).

## Fichiers à créer

| Fichier                                                          | Rôle                                                                                     |
|------------------------------------------------------------------|------------------------------------------------------------------------------------------|
| `src/Entity/Project.php`                                         | Entité projet déclaré (provider, url normalisée, name, token chiffré, createdAt).        |
| `src/Enum/Type/Provider.php`                                     | Enum backed string github/gitlab + `host()`, `label()`, `icon()`.                        |
| `src/Repository/ProjectRepository.php`                          | `findAllOrdered()` (tri `createdAt DESC` + tie-breaker `id DESC`), `existsByNormalizedUrl(exclude?)`. |
| `src/Service/RepositoryUrl.php`                                  | Value object immuable : `provider` (dérivé de l'hôte), `owner`, `repo`, `normalizedUrl`. |
| `src/Service/RepositoryUrlNormalizer.php`                        | Parse/normalise une URL saisie, dérive le provider de l'hôte, produit un `RepositoryUrl`. |
| `src/Service/InvalidRepositoryUrlException.php`                  | Exception dédiée au chemin d'erreur de normalisation (hôte inconnu / URL malformée).     |
| `src/Service/TokenCipher.php`                                    | `encrypt()`/`decrypt()` sodium ; clé dérivée d'`APP_SECRET` (hkdf).                       |
| `src/Manager/ProjectManager.php`                                 | Orchestration create/update/delete (normalise, chiffre si fourni, déduit le nom).        |
| `src/Form/ProjectFormData.php`                                   | DTO de saisie (provider, url, name, plainToken) porteur des contraintes.                 |
| `src/Form/ProjectType.php`                                       | FormType sur le DTO (sélecteur provider, champ token jamais pré-rempli).                  |
| `src/Validator/UniqueRepositoryUrl.php`                          | Contrainte : unicité de l'URL normalisée.                                                 |
| `src/Validator/UniqueRepositoryUrlValidator.php`                | Validateur associé (interroge `ProjectRepository`, exclut l'entité courante).            |
| `src/Controller/ProjectController.php`                           | `index`, `new`, `edit`, `show` (routes `app_project_*`, paths `/projects`).              |
| `src/Twig/Components/ProjectList.php`                            | Live Component liste + `#[LiveAction] confirmDelete()/cancelDelete()/delete()`.           |
| `src/Twig/Components/SidebarProjects.php`                        | `#[AsTwigComponent]` alimentant la liste de la sidebar (`base.html.twig`).                |
| `templates/project/index.html.twig`                             | Page liste (hôte du Live Component + bouton « déclarer »).                                |
| `templates/project/new.html.twig`                               | Formulaire de déclaration (inclut `_form.html.twig`).                                     |
| `templates/project/edit.html.twig`                              | Formulaire d'édition (inclut `_form.html.twig` ; token masqué, « laisser vide pour conserver »). |
| `templates/project/_form.html.twig`                             | Partial mutualisé new/edit (sélecteur provider aux couleurs de marque).                   |
| `templates/project/show.html.twig`                              | Page détail placeholder (métadonnées + encart « kanban à venir » + lien repo).           |
| `templates/components/ProjectList.html.twig`                    | Template du Live Component (table + overlay de confirmation de suppression).              |
| `templates/components/SidebarProjects.html.twig`                | Template du composant sidebar.                                                            |
| `assets/controllers/url_name_suggest_controller.js`             | Pré-remplissage du nom `owner/repo` au saisie de l'URL.                                   |
| `migrations/Version20260704XXXXXX.php`                          | Création table `project` (générée par `make:migration`).                                 |
| `tests/Unit/Service/RepositoryUrlNormalizerTest.php`            | Formes https/ssh/`.git`, extraction owner/repo, rejet hôte↔provider incohérent.          |
| `tests/Unit/Service/TokenCipherTest.php`                        | Round-trip encrypt→decrypt, non-déterminisme (nonce), rejet altération.                  |
| `tests/Unit/Enum/ProviderTest.php`                              | `host()`/`label()` par cas.                                                               |
| `tests/Unit/Validator/UniqueRepositoryUrlValidatorTest.php`     | Doublon rejeté, édition de soi-même OK.                                                   |
| `tests/Functional/Repository/ProjectRepositoryTest.php`         | Méthodes repository sur BDD de test.                                                      |
| `tests/Functional/Controller/ProjectControllerTest.php`         | CRUD complet + **token absent du HTML** + confirmation suppression.                       |
| `tests/Functional/Twig/ProjectListComponentTest.php`            | Live Component `ProjectList` testé par **instanciation directe** (endpoint live derrière firewall). |
| `tests/e2e/projects.spec.ts`                                    | Déclaration puis suppression via l'UI (sélecteurs `data-test`).                           |

## Fichiers à modifier

| Fichier                                              | Modification                                                                                  |
|------------------------------------------------------|-----------------------------------------------------------------------------------------------|
| `templates/base.html.twig`                           | Remplacer le placeholder sidebar « docs/story/ — scan à venir » par le composant `SidebarProjects` (liste + lien « déclarer ») ; ajouter l'entrée nav « Projets » (`app_project_index`). |
| `fixtures/AppFixtures.php`                            | Ajouter 1-2 `Project` d'exemple (token chiffré via `TokenCipher`).                            |
| `.env`                                               | **Non modifié** (décision de livraison) — `APP_SECRET` est documenté via `.env.example` + garde-fou `TokenCipher` (exception si secret vide) ; il est déjà renseigné en `.env.dev`/`.env.test`. |
| `.env.example` (créer si absent) / `README`          | Mentionner `APP_SECRET` requis pour le chiffrement des tokens.                                |
| `composer.json`                                      | Ajouter `ext-sodium: *` à `require` (dépendance rendue explicite).                            |
| `config/packages/security.yaml`                      | Aucun changement fonctionnel requis (`^/` déjà `ROLE_USER`) — vérifier que `/projects` est bien couvert. |

## Impacts transverses

- **Multi-tenant** : non — entité non `OrganizationAware`, app mono-utilisateur derrière le firewall `main`.
- **Multi-thème** : non.
- **API REST/GraphQL** : non — server-rendered uniquement.
- **i18n** : libellés UI en français, en dur dans les templates (comme l'existant `login.html.twig`) ; pas de catalogue multilingue introduit. Messages de contrainte en français.
- **Permissions** : firewall existant suffisant (`access_control ^/ → ROLE_USER`). Aucun voter, aucun rôle nouveau. Pas de cloisonnement par utilisateur (mono-utilisateur).
- **Emails / notifications** : non.
- **Migration de données** : création de la table `project` (nouvelle, aucun backfill). `down()` = `DROP TABLE project`.
- **Comportement par défaut** : la section « Projets » de la sidebar passe du placeholder à la liste réelle (vide au départ → CTA « déclarer un projet »).

## Ordre d'implémentation

1. [ ] `Provider` enum (`src/Enum/Type/`) + `RepositoryUrl` value object + `RepositoryUrlNormalizer` + tests unit.
2. [ ] `TokenCipher` (sodium, clé hkdf d'`APP_SECRET`) + test unit round-trip/altération ; ajouter `ext-sodium` à `composer.json`.
3. [ ] Entité `Project` + `ProjectRepository` (méthodes nommées) ; `make:migration` + relecture (index unique `url`, snake_case).
4. [ ] `ProjectManager` (create/update/delete : normalise, dédup, chiffre si token fourni, déduit le nom si vide).
5. [ ] Contrainte `UniqueRepositoryUrl` + validateur + test unit.
6. [ ] DTO `ProjectFormData` + `ProjectType` (sélecteur provider, token `always_empty`).
7. [ ] `ProjectController` (`index`, `new`, `edit`, `show`) + templates `new`/`edit`/`show` + contrôleur Stimulus `url-name-suggest`.
8. [ ] Live Component `ProjectList` + template (table, suppression via overlay de confirmation `LiveProp confirmingId` + `#[LiveAction]`).
9. [ ] Câblage sidebar via composant `#[AsTwigComponent] SidebarProjects` (`base.html.twig`) : nav « Projets » + liste + CTA déclarer.
10. [ ] Fixtures : 1-2 projets d'exemple ; renseigner `APP_SECRET` en `.env.local` ; `.env.example`.
11. [ ] Tests functional (repository + contrôleur, dont **assertion token absent du HTML**) + E2E Playwright.
12. [ ] QA finale : `make quality` (PHP-CS-Fixer + PHPStan niveau 10 + build), `make phpunit`, `make playwright`, `symfony console doctrine:schema:validate`.

## Stratégie de test

| Code                                             | Type            | Ce qu'on vérifie                                                                     |
|--------------------------------------------------|-----------------|--------------------------------------------------------------------------------------|
| `RepositoryUrlNormalizer`                        | Unit            | https/ssh, avec/sans `.git`, extraction `owner/repo`, casse d'hôte, rejet provider↔hôte incohérent, URL invalide. |
| `TokenCipher`                                    | Unit            | Round-trip encrypt→decrypt ; deux chiffrés du même clair diffèrent (nonce) ; déchiffrement d'un chiffré altéré échoue. |
| `Provider` enum                                  | Unit            | `host()`, `label()`, `icon()` par cas.                                               |
| `UniqueRepositoryUrlValidator`                   | Unit            | Doublon (URL normalisée équivalente) → violation ; édition de la même entité → pas de violation. |
| `ProjectRepository`                              | Functional      | `findAllOrdered()` (ordre createdAt), `findOneByNormalizedUrl()`.                    |
| `ProjectController` + `ProjectManager`           | Functional      | Déclaration OK → projet en liste ; doublon refusé ; provider↔hôte incohérent refusé ; édition (URL + renouvellement token) ; **token jamais présent dans le HTML de `new`/`edit`** ; suppression après confirmation. |
| `ProjectList` (Live Component)                   | Functional      | `delete()` retire le projet ; test du composant par **instanciation directe** (l'endpoint live `/_components` est derrière le firewall). |
| Parcours déclaration + suppression               | E2E (Playwright)| Flux UI complet, sélecteurs `data-test`.                                             |

**Hors scope tests pour cette story** :

- Pas de test de lecture/scan distant (D3 — aucun appel réseau ici).
- Pas de test de déchiffrement en usage réel (`decrypt()` existe mais n'est consommé qu'en D3 ; couvert par le round-trip unit).
- Pas de test multi-utilisateur/permission fine (mono-utilisateur, firewall global).

## Risques et points d'attention

- **`APP_SECRET` vide** (`.env` l.actuelle `APP_SECRET=`) : bloquant pour la dérivation de clé — un secret vide produit une clé faible/déterministe. Mitigation : renseigner `APP_SECRET` en `.env.local` (étape 10) ; `TokenCipher` lève une exception explicite si le secret est vide, pour éviter un chiffrement silencieusement faible.
- **Rotation d'`APP_SECRET`** : change la clé dérivée → tokens existants illisibles. Mitigation : documenté comme tradeoff assumé (choix produit) ; en pratique un token illisible se re-saisit via l'édition (P4). À re-arbitrer si une rotation devient un besoin réel (bascule vers `PROJECT_TOKEN_KEY`).
- **`ext-sodium`** : requis. Vérifié présent (`ext-sodium: OK`) ; rendu explicite dans `composer.json` pour cadrer l'environnement.
- **Fuite du token vers le front** : risque central du pitch. Mitigation : le DTO n'expose jamais le chiffré ; champ `PasswordType` en `always_empty`/non pré-rempli ; test functional assertant l'absence du token (clair **et** chiffré) dans le HTML de `new`/`edit`.
- **Token dans les logs** : ne jamais logger le DTO ni l'entité brute ; pas de `dump()`/`dd()` (règle projet). Point de vigilance en review.
- **CSRF des `#[LiveAction]`** : la suppression modifie l'état — s'assurer que la protection CSRF LiveComponent est active (défaut) et que la modale de confirmation précède l'action.
- **Normalisation incomplète** : une URL exotique mal normalisée pourrait contourner l'unicité (doublon perçu comme distinct). Mitigation : batterie de cas unit sur le normalizer ; index unique BDD en filet de sécurité (viole → contrainte, pas de doublon silencieux).

## Questions ouvertes

> _Tranchées à l'implémentation (cf. `report.md`)._

- **Garde-fou `APP_SECRET` vide** : ~~exception ou minimum de longueur ?~~ → **Tranché** : `TokenCipher` lève une exception explicite à la construction si le secret est vide.
- **Périmètre du contrôleur Stimulus `url-name-suggest`** : ~~`blur` seul ou live ?~~ → **Tranché** : pré-remplissage **live** (à la saisie), sans écraser une saisie manuelle.
- **Suppression depuis la page `show`** : ~~Live Component seul ou aussi route delete sur `show` ?~~ → **Tranché** : uniquement dans le Live Component ; `show` renvoie vers la liste.

---

## Changelog

| Date       | Type                      | Description |
|------------|---------------------------|-------------|
| 2026-07-04 | Sync post-implémentation  | Réalignement sur le code livré (cf. `report.md`) : validation consolidée en une seule contrainte `UniqueRepositoryUrl` (validité + cohérence provider↔hôte + unicité) et provider dérivé de l'hôte (§Approche, §Entités, §Mécanismes, §Fichiers) ; confirmation de suppression via overlay Live `confirmingId` au lieu du composant `Modal` (§Approche, §Mécanismes, §Ordre 8) ; sidebar via `#[AsTwigComponent] SidebarProjects` (§Mécanismes, §Fichiers, §Ordre 9) ; `.env` non modifié → `.env.example` + garde-fou `TokenCipher` (§Fichiers à modifier) ; tie-breaker `id DESC` sur `findAllOrdered` (§Fichiers) ; PHPStan niveau 10 (§Ordre 12) ; test Live Component par instanciation directe (§Stratégie de test, §Fichiers) ; ajouts `InvalidRepositoryUrlException`, partial `_form.html.twig`, couleurs de marque du bouton provider (§Fichiers, §Mécanismes) ; questions ouvertes tranchées. Limites connues versées en dette (unicité insensible à la casse, index `created_at`, requête sidebar par page) — cf. `report.md` §Dette, non promues en engagement. |
