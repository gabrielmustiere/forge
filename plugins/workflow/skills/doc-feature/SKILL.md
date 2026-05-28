---
name: doc-feature
description: Documente une feature implémentée en lisant le code — entités, flux, routes, services, templates, points d'extension. Stack-agnostique (Sylius, Symfony). Produit `docs/feature-map/NNN-slug/overview.md`. Utile pour onboarding ou cartographie.
user_invocable: true
disable-model-invocation: true
argument-hint: "[sujet, chemin fichier ou slug]"
allowed-tools:
  - Read
  - Grep
  - Glob
  - Write
  - Edit
  - Bash(ls:*)
  - Bash(find:*)
  - Bash(git log:*)
---

# /doc-feature — Documentation d'une feature existante

Tu analyses une fonctionnalité **déjà implémentée** dans le code (custom ou tiers) et tu produis une documentation claire et exploitable, pour qu'un dev qui débarque puisse comprendre comment ça marche en lisant un seul fichier.

## Périmètre du skill

Ce skill est **hors pipeline** (pas de spec → design → implem). Son rôle est de **constater l'existant** et de produire une carte du code. Il ne propose pas d'amélioration, ne refactore rien, et ne cadre pas une nouvelle feature (pour ça : `/feature-pitch`). Il est utile :

- avant de modifier une feature qu'on ne connaît pas
- pour onboarder un nouveau dev
- pour identifier les points d'extension d'une mécanique tierce

## Règles

1. **Toujours lire le code avant de documenter** — ne jamais inventer ou supposer. Cite les fichiers et lignes (`path/to/file.ext:42`).
2. **Privilégier `AskUserQuestion`** pour clarifier le périmètre. Si l'outil n'est pas chargé, le récupérer via `ToolSearch`.
3. **Tracer le chemin complet** — point d'entrée (route, CLI, event, cron…) → orchestration (controller, handler, service) → métier (entités, règles, side effects) → sortie (template, response, message).
4. **Documenter ce qui existe, pas ce qui devrait exister** — pas de suggestions d'amélioration sauf si demandé.
5. **Adapter le niveau de détail au stack détecté** — charger la référence stack appropriée (voir Phase 0).

## Déroulement

### Phase 0 — Détection du stack

Avant toute exploration, identifie le stack du projet pour adapter le vocabulaire et les sections du document. Lis dans cet ordre :

1. `composer.json` à la racine — clés `require` :
   - `sylius/sylius` ou `sylius/*-bundle` → **stack Sylius**, charger `references/sylius.md`
   - `symfony/framework-bundle` (sans Sylius) → **stack Symfony**, charger `references/symfony.md`
   - Autre PHP → **stack PHP générique**
2. `package.json` à la racine — clés `dependencies` / `devDependencies` :
   - `next`, `react`, `vue`, `@nestjs/*`, `express`, etc. → noter le framework JS
3. À défaut : examiner l'arborescence (`src/`, `app/`, `lib/`, `pkg/`, `cmd/`…) et le `README` pour situer.

Si plusieurs stacks coexistent (monorepo, front + back), demande à l'utilisateur quel périmètre documenter en priorité.

Charge la référence stack avant d'explorer — elle contient les chemins typiques, les conventions et les sections additionnelles à inclure dans le document final.

### Phase 1 — Identification de la feature

- Si l'utilisateur fournit un sujet (`/doc-feature gestion des promotions`) → commence l'exploration directement.
- Si l'utilisateur fournit un fichier (`/doc-feature src/Entity/Order/Order.php`) → part de ce fichier et remonte le fil.
- Sinon, demande quelle feature documenter.

Clarifie le périmètre :

- Couche concernée (back-office, front public, API, batch, tout) ?
- Profondeur attendue (vue d'ensemble rapide vs exhaustif) ?
- Code custom uniquement, ou inclure le tiers (vendor, node_modules) ?

### Phase 2 — Exploration en profondeur

Explore systématiquement avec `Glob`, `Grep`, `Read`. Adapte les patterns au stack détecté en Phase 0.

**Code applicatif (générique)** — chemins typiques selon stack :

- PHP : `src/`, `app/`, `lib/`, `tests/`
- Node : `src/`, `app/`, `lib/`, `packages/*/src/`
- Go : `cmd/`, `internal/`, `pkg/`
- Python : `<package>/`, `src/<package>/`

À chercher dans tous les cas :

- **Modèle de données** : entités, value objects, schémas, types
- **Points d'entrée** : routes HTTP, commandes CLI, handlers de message, listeners d'événement, jobs cron
- **Orchestration** : controllers, handlers, use cases, services applicatifs
- **Métier** : règles, état, transitions, side effects
- **Persistance** : repositories, requêtes, mappings
- **Présentation** : templates, composants, layouts
- **Configuration** : fichiers de config, variables d'environnement, feature flags
- **Tests** : ils révèlent les comportements attendus et les edge cases

**Code tiers** — si pertinent et que la référence stack le précise (ex. `vendor/sylius/` pour une feature native Sylius). À défaut, ne plonge dans les dépendances que si l'utilisateur le demande.

### Phase 3 — Présentation interactive

Présente tes découvertes par couche, en demandant à l'utilisateur s'il veut approfondir certains aspects. Structure générique :

1. **Vue d'ensemble** — ce que fait la feature, les concepts principaux
2. **Modèle de données** — entités/types, relations, champs clés
3. **Flux métier** — séquence d'événements, états, règles
4. **Points d'entrée** — routes, commandes, handlers
5. **Couche présentation** — templates, composants, vues (si applicable)
6. **API exposée** — endpoints, contrats, auth (si applicable)
7. **Configuration et extension** — comment c'est paramétré, comment l'étendre

La référence stack peut ajouter ou affiner certaines sections (ex. workflows + grids + thèmes pour Sylius, security + messenger pour Symfony).

À chaque étape, demande : "Tu veux que j'approfondisse [aspect X] ou on passe à la suite ?"

### Phase 4 — Rédaction

Quand l'utilisateur valide, écris le fichier de documentation.

**Choix du dossier** :

- Format : `docs/feature-map/NNN-slug-de-la-feature/` (NNN = prochain numéro sur 3 chiffres, slug en kebab-case). Exemples : `001-promotions/`, `002-cart-lifecycle/`.
- Scanner les dossiers existants via `Glob` (pattern `docs/feature-map/[0-9]*/`), extraire le préfixe numérique le plus élevé et incrémenter de 1.
- **Collision de slug** : si le slug existe déjà sous un autre numéro, demande à l'utilisateur s'il veut **étendre** la doc existante ou choisir un slug distinct. Ne jamais écraser sans validation.

**Nom du fichier** : `overview.md` dans ce dossier.

**Format de base** (sections génériques — la référence stack peut en ajouter) :

```markdown
# [Nom de la feature]

> Résumé en une phrase.
> Stack : [détecté]
> Périmètre exploré : [back-office / front / API / tout]

## Vue d'ensemble

Ce que fait cette feature, à qui elle sert, où elle intervient dans le parcours.

## Modèle de données

### Entités principales

| Entité | Fichier | Rôle |
|--------|---------|------|

### Relations

Description des relations entre entités/types.

### Surcharges custom (si applicable)

| Élément | Fichier | Ce qui est ajouté/modifié |
|---------|---------|---------------------------|

## Flux métier

### Séquence

Description du déroulé : déclencheur → étapes → résultat.

### Événements et listeners (si applicable)

| Événement | Listener/Handler | Fichier | Effet |
|-----------|------------------|---------|-------|

### États et transitions (si applicable)

États, transitions, callbacks. Citer le fichier de config.

## Points d'entrée

### Routes / commandes / handlers

| Entrée | Cible | Description |
|--------|-------|-------------|

## Couche présentation (si applicable)

| Élément | Fichier | Ce qu'il rend |
|---------|---------|---------------|

## API (si applicable)

| Endpoint | Méthode | Auth | Payload | Réponse |
|----------|---------|------|---------|---------|

## Configuration

Fichiers de config clés, paramètres, variables d'environnement.

## Points d'extension

Comment surcharger ou étendre cette feature :

- Événements à écouter
- Services à décorer / interfaces à implémenter
- Templates à overrider
- Hooks et extension points spécifiques au framework
```

La référence stack peut compléter ce squelette par des sections additionnelles (ex. "Interface Admin", "Overrides multi-thème", "Cloisonnement multi-channel" pour Sylius). Inclure uniquement les sections pertinentes pour la feature documentée.

### Phase 5 — Clôture

Affiche le chemin du fichier produit :

> Documentation prête : `docs/feature-map/NNN-slug/overview.md`

## Argument optionnel

- `/doc-feature promotions` — commence l'exploration sur le sujet donné.
- `/doc-feature src/Entity/Order/Order.php` — part d'un fichier spécifique et remonte le fil.
- `/doc-feature` sans argument — demande quelle feature documenter.
