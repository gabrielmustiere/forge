# Stack — Sylius

Delta Sylius au-dessus de `symfony.md`. Charger `symfony.md` d'abord, puis ce fichier. Tout ce qui est valable en Symfony pur s'applique aussi ici — ce fichier ne liste **que** le spécifique e-commerce.

## Détection

`composer.json` contient `sylius/sylius`.

## Règle d'or

**Aucune modification du vendor Sylius.** Tout enrichissement passe par les mécanismes officiels : Resource (héritage d'entité), EventListener/EventSubscriber, Service Decorator, Twig Hook, FormTypeExtension, StateMachine callbacks. Si un besoin semble nécessiter de modifier le vendor, c'est que le mécanisme d'extension adéquat n'a pas été identifié.

## Resources (entités Sylius surchargées)

- Héritage de la classe de base Sylius + implémentation de l'interface.
- Déclaration dans `config/packages/_sylius.yaml` (section `sylius_<bundle>.resources`) pour que Sylius utilise la classe custom partout (admin, shop, API).
- Pour ajouter un champ traduisible : étendre l'entité de traduction + l'entité principale, puis gérer le rendu form (FormTypeExtension + Twig Hooks) et l'affichage côté UI.

## Validation

- Sur les entités Sylius (ou qui en héritent), toute contrainte custom **doit** inclure `groups: ['Default', 'sylius']`. Sans le groupe `sylius`, les forms Sylius n'évaluent jamais la contrainte → la validation est silencieusement ignorée et les données invalides passent.

## Multi-channel

- Cloisonnement des données par channel : entités concernées implémentent `ChannelInterface` ou portent une relation vers `Channel`.
- Repositories filtrent par channel quand la donnée est channel-scopée : `->andWhere('o.channel = :channel')`.
- Fixtures couvrant plusieurs channels (sinon les régressions de cloisonnement ne sont jamais visibles en dev).
- Features activables channel par channel quand pertinent (graceful degradation si un channel n'a pas la feature).
- Grids admin filtrent par channel si la donnée est channel-scopée, sinon l'admin voit tout.

## Multi-thème (front shop uniquement)

- **L'admin n'a pas de variation de thème.** Ne pas chercher d'override admin — c'est du SyliusAdminBundle + template unique (souvent Tabler). La variation multi-thème concerne exclusivement le shop.
- Avant de modifier un template shop de base, chercher les overrides dans `themes/*/templates/bundles/Sylius*Bundle/` via `Glob`. Si un override existe pour un thème, il doit être mis à jour en cohérence — sinon le thème diverge silencieusement.
- Hooks Twig ajoutés : les garder thème-agnostiques quand possible (utilisables par tous les thèmes sans réécriture).

## FormTypeExtension + Twig Hooks (piège fréquent)

- Étendre un FormType Sylius ajoute des champs au modèle, mais le rendu HTML passe par les Twig Hooks. Un hook manquant dans un template concerné = **422 silencieux** à la soumission : le champ n'est pas affiché, donc pas envoyé, donc la contrainte de validation le rejette sans message visible.
- Cas classique à vérifier systématiquement : extension de `ProductVariantType` → rendre dans hooks `product_variant.(create|update)` **et** `product.(create|update)` (le produit simple passe aussi par ProductVariantType sous le capot).

## Composants Twig (Symfony UX)

- Un composant dans un sous-dossier (ex `src/Twig/Components/Media/`) s'invoque avec le namespace préfixé par `:` → `<twig:Media:MonComposant />`, pas `<twig:MonComposant />`. Sans le préfixe, Symfony UX ne résout pas le composant et le tag reste rendu littéralement.

## StateMachine (workflows Sylius)

- Ne jamais changer un état en direct (`$order->setState(...)`). Toujours passer par une transition déclarée dans `config/packages/_sylius.yaml` (section `winzou_state_machine`) — sinon les callbacks attachés ne s'exécutent pas.
- Callbacks : services référencés par identifiant, testables unitairement.

## Admin (grids et forms)

- Grids admin déclarés en YAML (`config/grids/...`) ou en PHP via GridProvider — pas de grids inline dans les contrôleurs.
- Permissions fines via voters Sylius-compatibles ou restrictions dans la config grid.

## API Platform

- Ressources API Sylius : `config/api_platform/` ou attributs sur les entités.
- Serialization groups minimaux et explicites — pas de groupe fourre-tout qui expose tout par défaut.
- Sécurité via `security` dans l'opération (expression language) + voters au besoin pour la logique métier.
- Auth JWT côté client : header `Authorization: Bearer <token>`.

## Traduction (i18n)

- Champs traduisibles via entité `*Translation` + `TranslatableInterface` sur l'entité principale (+ méthode `createTranslation()`).
- Libellés UI via fichiers de traduction (`translations/messages.*.yaml` ou équivalent selon projet).

## Axes de review spécifiques

En plus des axes Symfony, checker systématiquement :

- Feature cloisonnée par channel quand elle manipule des données channel-scopées ?
- Repositories filtrent par channel ?
- Fixtures couvrent plusieurs channels ?
- Template shop modifié : overrides des thèmes mis à jour ?
- FormTypeExtension ajouté : hooks Twig présents dans **tous** les templates concernés ?
- Validation custom : groupe `sylius` présent ?
