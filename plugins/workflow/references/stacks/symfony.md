# Stack — Symfony

Référence chargée par `/feature-plan`, `/feature`, `/refactor-plan`, `/refactor`, `/tech-plan`, `/tech`, `/review`, `/help` quand le projet est détecté comme Symfony (ou Sylius, qui étend Symfony). C'est un **radar de points de vigilance** pour concevoir et reviewer — pas un tutoriel procédural (les procédures étape par étape sont l'affaire de skills dédiées).

## Détection

`composer.json` contient `symfony/framework-bundle`. Si `sylius/sylius` est également présent, charger ce fichier **puis** `sylius.md` par-dessus (Sylius est un sur-ensemble).

## Modèle et persistance (Doctrine)

- **Snake_case en BDD** : tout champ ou relation en camelCase côté PHP doit avoir `#[ORM\Column(name: 'mon_champ')]` ou `#[ORM\JoinColumn(name: 'ma_relation_id')]`. Sans cette annotation explicite, la colonne est créée en camelCase en base, ce qui crée un décalage entre nomenclature projet et schéma réel.
- **Migrations générées** : `symfony console make:migration`, jamais écrites à la main. Une migration déjà commitée ne se modifie pas — on en crée une nouvelle. Si la migration générée ne convient pas, corriger le mapping/entité et regénérer.
- **Reversibility du `down()`** : si la migration est volontairement irréversible (drop de donnée par exemple), le documenter dans le fichier de migration en commentaire.
- **NOT NULL sur table non vide** : ALTER en deux temps (nullable → backfill → NOT NULL), ou DEFAULT prévu. Sinon la migration casse en prod sur les lignes existantes.
- **Index** : toute colonne utilisée en WHERE/JOIN/ORDER BY doit avoir un index. L'absence se paie cher sur les grosses tables.
- **Tout accès aux données passe par une méthode nommée d'un repository.** Aucun appel direct à `$em->find()`, `$em->getRepository()->findBy()`, DQL ou QueryBuilder hors `src/Repository/` — les services, listeners, contrôleurs appellent `OrderRepository::findPendingForUser($user)`, pas `findBy(['status' => ...])`. La méthode expose l'intention métier (pas la mécanique SQL), reste testable unitairement, et concentre l'optimisation à un seul endroit. Seule exception : `EntityValueResolver` dans un contrôleur (le resolver résout l'entité via le repository pour nous).

> **Skills dédiés** : pour dérouler précisément la procédure d'un de ces trois domaines, rediriger vers le plugin `symfony` plutôt que de tout détailler ici — `/symfony:doctrine-entity` (entités, relations, validation), `/symfony:doctrine-query` (DQL/QueryBuilder/N+1), `/symfony:doctrine-migration` (génération, dry-run, réversibilité). Les règles ci-dessus restent le radar de vigilance pour la review et le cadrage.

## Services

- Injection par constructeur, auto-wiring, auto-configuration.
- `declare(strict_types=1)` en tête de fichier.
- Nommage PSR-4, espaces de noms cohérents avec l'arborescence.
- Décoration via `#[AsDecorator]` pour étendre un service existant — jamais de modification in-place du service vendor.

## Forms

- Validation par contraintes (attributs ou YAML), pas par checks manuels dans le contrôleur.
- CSRF actif par défaut, à ne désactiver qu'avec une raison documentée.
- DTO plutôt que bind direct sur l'entité pour les forms complexes (évite le mass-assignment et découple la représentation du modèle).

## Templates Twig

- Libellés via `|trans`, jamais en dur — i18n-ready par défaut.
- Échappement par défaut : un `|raw` doit avoir une raison explicite et la donnée doit être prouvée safe en amont.
- Logique métier hors du template. Si un calcul fait plus d'une ligne, passer par un service ou une Twig extension.

## Tests

- **PHPUnit** (`vendor/bin/phpunit`) — unit pour les services/handlers (mocks), functional pour les repositories (BDD de test), application tests (`WebTestCase`) pour les contrôleurs.
- Fixtures reproductibles : chaque test part d'un état connu, pas de dépendance à l'ordre d'exécution.
- Sélecteurs stables (`data-test` attributes) pour les tests E2E plutôt que des sélecteurs CSS fragiles.

## QA et outillage

Commandes à lancer après chaque sous-tâche d'implémentation (adapter au runner du projet — `symfony`, `docker compose exec`, ou direct) :

```bash
vendor/bin/ecs check --fix                   # style
vendor/bin/phpstan analyse                   # analyse statique
symfony console doctrine:schema:validate     # si schéma touché
npm run build                                # assets (si front)
```

Si le projet utilise un autre outillage (Rector, PHP CS Fixer, Psalm), le découvrir dans `composer.json` / `Makefile` / `CLAUDE.md` du projet et s'y conformer — ne pas imposer un outillage absent.

## Sécurité

- **Voters** pour les autorisations fines, `ROLE_*` pour les gros contours.
- `access_control` dans `security.yaml` comme garde-fou grossier ; la règle métier vit dans un voter.
- Pas de secrets en dur : `.env` / `.env.local` jamais commités. Prod = variables d'environnement ou Vault.
- Jamais de `dump()`, `var_dump()`, `dd()` en commit — code de debug éphémère uniquement.

## Performance

- **N+1** : JOIN explicites dans les DQL quand on charge une relation qu'on va itérer. Le lazy-loading Doctrine est piégeur en boucle.
- Pas d'appel repository/service dans un `foreach` — charger en batch en amont.
- Pagination sur toute liste potentiellement grande (admin, API, exports).
- Cache (`cache.app`, HTTP cache) pour les données fréquemment lues et peu variables.
