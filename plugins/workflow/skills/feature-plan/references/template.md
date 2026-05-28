# Plan technique — <Titre de la feature>

> Pitch : `docs/story/<NNN>-f-<slug>/pitch.md`
> Stack : symfony

<!--
guide: Plan TECHNIQUE d'une feature (préfixe `-f-`). Consommé par `/workflow:feature` (étape build) et `/workflow:sync`.
Le pitch décrit l'intention métier ; ce fichier décrit le COMMENT. Tout ce qui touche au code, aux entités, aux services, aux migrations.
Retirer ce bloc et tous les `> _Skill : ..._` avant commit.
-->

## Approche retenue

> _Skill : 1 paragraphe qui décrit la solution choisie en termes architecturaux (pas une liste de fichiers). Mentionner les patterns mobilisés (EntityListener, Live Component, AsDecorator, etc.). Puis lister 2–4 alternatives écartées avec la raison du rejet — sans ce bloc, la review reposera la question._

<Solution retenue en 1–2 paragraphes.>

**Alternatives écartées** :

- **<Alternative A>** : <raison du rejet, en 1 phrase>.
- **<Alternative B>** : <…>.

## Entités et modèle de données

> _Skill : section obligatoire si la feature crée/modifie une entité Doctrine. Une sous-section par entité. Préciser : champs (table type/nullable/contrainte), attributs au niveau classe (UniqueConstraint, EntityListeners, validateur custom), implémentation `OrganizationAwareInterface` ou non. Pour les relations bidirectionnelles, préciser inversedBy/mappedBy et cascade. Si la feature ne touche aucune entité, remplacer cette section par « Aucun impact modèle. »._

### Nouvelle entité `<App\Entity\…>` (ou Modification de `<entité>`)

`<chemin du fichier>` :

| Champ           | Type                          | Nullable | Contrainte                                  |
|-----------------|-------------------------------|----------|---------------------------------------------|
| `id`            | int (PK auto)                 | non      |                                             |
| `<champ>`       | <type PHP/Doctrine>           | <oui/non>| <Assert\… ou contrainte BDD>                |
| `<relation>`    | `ManyToOne` (`<Entité>`)      | non      | `JoinColumn(name: '...', onDelete: '...')`  |

Attributs au niveau classe :

```php
#[ORM\Entity(repositoryClass: <…>::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_<TABLE>_<CHAMP>', fields: ['<champ>'])]
#[ORM\EntityListeners([<…>::class])]
#[UniqueEntity(fields: [...], message: '...')]
```

> _Skill : préciser si l'entité implémente `OrganizationAwareInterface` ou non, et pourquoi. Mentionner les contraintes custom (constraint + validator) à créer si la règle métier ne tient pas dans un `Assert\*` standard._

## Mécanismes framework mobilisés

> _Skill : lister les patterns Symfony / Doctrine / EasyAdmin / projet réutilisés (pas inventés). Ex : `#[AsDoctrineListener]`, `EventSubscriber` à priorité X, Live Component, `#[AsDecorator]`, voter, repository custom, contrainte custom. Pour chaque, dire BRIÈVEMENT pourquoi ce mécanisme plutôt qu'un autre._

- **`<Mécanisme>`** : <usage dans la feature et justification courte>.
- **`<Mécanisme>`** : <…>.

## Fichiers à créer

> _Skill : table exhaustive. Chaque ligne = un fichier qui n'existe pas encore. Mettre les tests en bas. Une description courte qui aide à comprendre le rôle sans lire le fichier._

| Fichier                                              | Rôle                                                              |
|------------------------------------------------------|-------------------------------------------------------------------|
| `src/<…>.php`                                        | <rôle en 1 phrase>                                                |
| `src/Factory/<…>Factory.php`                         | Factory Foundry (états : `as<…>()`, `as<…>()`).                   |
| `migrations/Version<YYYYMMDDHHMMSS>.php`             | <description succincte de la migration>                           |
| `tests/Unit/<…>Test.php`                             | <cas couverts en 1 phrase>                                        |
| `tests/Functional/<…>Test.php`                       | <cas couverts en 1 phrase>                                        |

## Fichiers à modifier

> _Skill : table exhaustive. Chaque ligne = un fichier existant. Décrire le diff conceptuel (« remplacer X par Y », « ajouter relation inverse », « drop méthode Z ») — pas le diff ligne à ligne._

| Fichier                                              | Modification                                                      |
|------------------------------------------------------|-------------------------------------------------------------------|
| `src/Entity/<…>.php`                                 | <modification en 1 phrase>                                        |
| `src/Controller/<…>.php`                             | <modification>                                                    |
| `templates/<…>.html.twig`                            | <modification UI>                                                 |
| `config/packages/<…>.yaml`                           | <modification config>                                             |
| `.env`, `.env.example`                               | <variables ajoutées/retirées>                                     |
| `tests/Unit/<…>Test.php`                             | <adaptation>                                                      |

## Impacts transverses

> _Skill : miroir du pitch §Impacts transverses, mais avec les détails techniques. Indiquer comment la feature interagit avec chaque axe systémique. « Non » explicite reste préférable à l'absence d'item._

- **Multi-tenant** : <ex: l'entité est `OrganizationAware` et filtrée par `OrganizationFilter`. OU : entité gérée sous `/admin` uniquement, filtre désactivé.>
- **Multi-thème** : <oui/non>.
- **API REST/GraphQL** : <oui/non + endpoint>.
- **i18n** : <libellés concernés, FR par défaut, structure existante>.
- **Permissions** : <nouveau voter ou firewall existant suffisant>.
- **Emails / notifications** : <oui/non + mailer concerné>.
- **Migration de données** : <création table, backfill SQL ou via `app:init-data` au déploiement>.
- **Comportement par défaut** : <pour les utilisateurs/orgs qui n'activent pas la feature>.

## Ordre d'implémentation

> _Skill : checklist exécutable, des fondations vers l'UI. Modèle → migration → service → contrôleur/composant → template → tests. Numéroter en respectant les dépendances. Les cases cochées au fil de l'exécution servent de fil rouge pour `/workflow:autopilot`._

1. [ ] <Étape 1 : entité + relation inverse + contrainte custom>.
2. [ ] <Étape 2 : EntityListener / EventSubscriber>.
3. [ ] <Étape 3 : repository custom>.
4. [ ] <Étape 4 : service métier>.
5. [ ] <Étape 5 : entités/contraintes drop des champs obsolètes>.
6. [ ] <Étape 6 : adapter contrôleurs / DTOs / guards>.
7. [ ] <Étape 7 : migration Doctrine (`make:migration`) + relue>.
8. [ ] <Étape 8 : seed (`AppInitDataCommand`) et factories Foundry>.
9. [ ] <Étape 9 : CRUD EasyAdmin / menu DashboardController>.
10. [ ] <Étape 10 : templates Twig + LiveComponent>.
11. [ ] <Étape 11 : drop config/yaml obsolète + variables `.env`>.
12. [ ] <Étape 12 : tests unit + adaptations>.
13. [ ] <Étape 13 : QA finale (PHPStan + CS-Fixer + phpunit + smoke E2E)>.

## Stratégie de test

> _Skill : table « code → type de test → ce qu'on vérifie ». Pas le code des tests, le contrat. Mentionner explicitement ce qui est **hors scope tests** (ex: « pas de spec E2E dédiée — MauffreyStory provisionne, les flows existants couvrent »)._

| Code                                                        | Type            | Ce qu'on vérifie                                                  |
|-------------------------------------------------------------|-----------------|-------------------------------------------------------------------|
| `src/<…>.php`                                               | Unit            | <cas nominaux + cas d'erreur>                                     |
| `src/Validator/<…>.php`                                     | Unit            | <violation A, violation B, combinaisons OK>                       |
| `src/Security/Voter/<…>.php`                                | Unit (adapté)   | <règle de décision>                                               |
| `src/Controller/<…>.php`                                    | Functional      | <parcours + assertions HTTP + flash>                              |

**Hors scope tests pour cette story** :

- <ex: pas de functional CRUD `/admin` — couvert par firewall global + smoke runbook>.
- <ex: pas de spec E2E dédiée — fixture déjà en place>.

## Risques et points d'attention

> _Skill : risques techniques (pas métier — ceux-là vont dans le pitch). Format libre, 1 puce par risque, avec mitigation explicite. Couvrir au minimum : sécurité/isolation tenant, performance, migration irréversible, dépendance externe, comportement non couvert par les tests._

- **<Risque 1>** : <description + mitigation>.
- **<Risque 2>** : <description + mitigation>.

## Questions ouvertes

> _Skill : décisions techniques non tranchées au moment du plan. À trancher en `/workflow:feature` build ou à l'implémentation. Annoter `→ tranché : <choix>` après coup. Reproduire la même question dans le pitch si elle a une dimension métier._

- **<Question 1>** : <énoncé + options techniques>.
- **<Question 2>** : <…>.

---

## Changelog

> _Skill : ajouté par `/workflow:sync` après livraison si le plan a divergé du code livré. Une ligne par sync, daté. Décrire les sections modifiées (§Entités, §Fichiers à modifier, §Ordre item N, §Questions ouvertes) avec la justification._

| Date       | Type                      | Description |
|------------|---------------------------|-------------|
| YYYY-MM-DD | Sync post-implémentation  | <sections impactées + raison> |
