# Checklists spécifiques Sylius

Si le stack détecté est **sylius**, activer en plus les axes multi-channel et multi-thème documentés dans `references/stacks/sylius.md` :

- **Cloisonnement channel** : entités, repositories, fixtures, grids admin filtrent-ils bien par channel courant ?
- **Overrides de thèmes** : chercher via `Glob` dans `themes/*/templates/` avant de clôturer une sous-tâche qui touche un template shop de base — un override existant doit être mis à jour symétriquement.
- **Piège FormTypeExtension + Twig Hooks** : symétriques obligatoires (422 silencieux si un hook manque — cas classique `ProductVariantType` → hooks product et product_variant).
