# Référence stack — Sylius

À charger quand `composer.json` requiert `sylius/sylius` ou un `sylius/*-bundle`.

## Vocabulaire à utiliser

- "ressource" (Sylius Resource) plutôt que "modèle" pour les entités exposées via le ResourceBundle
- "channel" pour le cloisonnement multi-boutique
- "workflow" pour les state machines (Symfony Workflow)
- "grid" pour les listes admin (configurées via Sylius Grid)
- "Twig Hook" pour les points d'extension de template Sylius 2.x
- "thème" pour les overrides Shop multi-front

## Chemins typiques

### Code custom (`src/`)

- Entités et interfaces : `src/Entity/`
- Services, listeners, subscribers : `src/Service/`, `src/EventListener/`, `src/EventSubscriber/`
- Repositories custom : `src/Repository/`
- Form types et extensions : `src/Form/`, `src/Form/Extension/`
- Controllers custom : `src/Controller/`
- Twig Components : `src/Twig/Components/`
- Templates custom : `templates/`, et overrides par thème dans `themes/<Theme>/templates/`
- Config et surcharges : `config/packages/_sylius.yaml`, `config/services.yaml`

### Code vendor Sylius

- Entités de base : `vendor/sylius/sylius/src/Sylius/Component/`
- Bundles et config : `vendor/sylius/sylius/src/Sylius/Bundle/`
- Workflows : `vendor/sylius/sylius/src/Sylius/Bundle/CoreBundle/Resources/config/app/workflow/`
- Grids : `vendor/sylius/sylius/src/Sylius/Bundle/AdminBundle/Resources/config/grids/`
- Templates : `vendor/sylius/sylius/src/Sylius/Bundle/ShopBundle/templates/`, `.../AdminBundle/templates/`
- Twig Hooks : `vendor/sylius/sylius/src/Sylius/Bundle/ShopBundle/Resources/config/app/twig_hooks/`
- Routes et API resources : `vendor/sylius/sylius/src/Sylius/Bundle/ApiBundle/`

Ne plonger dans le vendor que si la feature documentée s'appuie sur un mécanisme natif (ex. checkout state machine, promotion processor) ou si l'utilisateur veut comprendre une mécanique de base avant de la surcharger.

## Sections additionnelles à inclure dans `overview.md`

Ces sections viennent **en plus** du squelette générique. À inclure uniquement si pertinentes pour la feature documentée.

### Interface Admin

L'admin Sylius 2.x utilise le thème **Tabler**. Documenter :

```markdown
## Interface Admin

### Routes

| Route | Controller | Description |
|-------|------------|-------------|

### Grids

| Grid | Fichier de config | Colonnes/filtres clés |
|------|-------------------|-----------------------|

### Formulaires

| Form Type | Fichier | Champs principaux |
|-----------|---------|-------------------|
```

### Interface Shop

```markdown
## Interface Shop

### Templates et Hooks Twig

| Hook | Template | Ce qu'il rend |
|------|----------|---------------|

### Overrides multi-thème

| Thème | Fichier override | Ce qui change vs le base |
|-------|------------------|--------------------------|

(Mettre "Aucun override" si la feature n'est pas surchargée par les thèmes.)

### Controllers

| Route | Controller | Description |
|-------|------------|-------------|
```

Lister explicitement les thèmes du projet (à détecter dans `themes/`). Exemples courants : `ThemeAlpha`, `ThemeBeta`, `TailwindTheme`.

### API

```markdown
## API

### Resources et opérations

| Resource | Opérations | Serialization groups | Auth |
|----------|------------|----------------------|------|
```

L'API Sylius 2.x utilise API Platform. Authentification typique : JWT (LexikJWTAuthenticationBundle) côté shop et admin.

### Cloisonnement et i18n

```markdown
## Cloisonnement et i18n

- **Multi-channel** : la feature est-elle cloisonnée par channel ? Comment (relation `ChannelInterface`, fixtures, listeners) ?
- **Traductions** : champs traduisibles (`*Translation`, `TranslatableTrait`), libellés UI (domaines `messages`, `validators`, `flashes`)
```

### Points d'extension (compléter le squelette générique)

Pour Sylius, lister explicitement :

- **Events à écouter** : `sylius.<resource>.pre_create`, `sylius.<resource>.post_update`, etc.
- **Workflow events** : `workflow.<graph>.completed.<transition>`
- **Services à décorer** : `sylius.<service>` (factory, repository, processor)
- **Templates à overrider** côté shop via `themes/<Theme>/templates/...`, côté admin via `templates/bundles/SyliusAdminBundle/...`
- **FormTypeExtension** sur les forms Sylius natifs
- **Twig Hooks disponibles** (chercher les `twig_hooks.yaml`)
- **Grids à étendre** via `GridDefinitionConverterEvent`

## Présentation interactive — couches Sylius

Adapter la séquence Phase 3 du SKILL.md pour exposer les couches Sylius :

1. Vue d'ensemble — ce que fait la feature, ressources principales
2. Modèle de données — entités, traductions, relations channel
3. Flux métier — workflows (state machine), events, listeners
4. Interface admin — grids, forms, routes (Tabler)
5. Interface shop — templates, hooks, controllers, overrides multi-thème
6. API — resources, opérations, serialization groups, auth JWT
7. Surcharges custom — ce qui a été modifié dans `src/` par rapport au vendor
