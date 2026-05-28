# Référence stack — Symfony

À charger quand `composer.json` requiert `symfony/framework-bundle` **sans** Sylius (cas Sylius traité dans `sylius.md`).

## Vocabulaire à utiliser

- "controller" pour le point d'entrée HTTP
- "service" pour les classes câblées dans le container
- "event listener" / "event subscriber" / "kernel event"
- "form type" pour les formulaires Symfony
- "voter" pour les règles d'autorisation
- "message" / "handler" pour Symfony Messenger
- "workflow" / "state machine" pour Symfony Workflow

## Chemins typiques

### Code applicatif (`src/`)

- Entités Doctrine : `src/Entity/`
- Repositories : `src/Repository/`
- Controllers : `src/Controller/`
- Services applicatifs : `src/Service/`
- Form Types : `src/Form/`
- Event listeners/subscribers : `src/EventListener/`, `src/EventSubscriber/`
- Voters de sécurité : `src/Security/Voter/`
- Message handlers : `src/Message/`, `src/MessageHandler/`
- Commandes CLI : `src/Command/`
- Twig extensions et components : `src/Twig/`

### Configuration

- Bundles : `config/bundles.php`
- Packages : `config/packages/*.yaml` (et `config/packages/<env>/*.yaml`)
- Routes : `config/routes.yaml` + attributs `#[Route]` sur les controllers
- Services : `config/services.yaml`
- Sécurité : `config/packages/security.yaml` (firewalls, providers, access_control)
- Doctrine : `config/packages/doctrine.yaml`, mapping XML/YAML éventuel sous `config/doctrine/`
- Templates : `templates/`
- Migrations : `migrations/`

## Sections additionnelles à inclure dans `overview.md`

Ces sections viennent **en plus** du squelette générique. À inclure uniquement si pertinentes pour la feature documentée.

### Routes

```markdown
## Routes

| Route (name) | Path | Méthodes | Controller | Description |
|--------------|------|----------|------------|-------------|
```

Privilégier les attributs PHP (`#[Route]`) à la lecture des YAML.

### Sécurité

À inclure si la feature touche à l'authentification, l'autorisation, ou des données sensibles :

```markdown
## Sécurité

### Firewall et accès

- Firewall concerné : (extrait de `security.yaml`)
- `access_control` pertinents

### Voters

| Voter | Subject | Attributs supportés | Logique |
|-------|---------|---------------------|---------|
```

### Asynchrone (Messenger)

Si la feature enqueue ou consomme des messages :

```markdown
## Asynchrone (Messenger)

### Messages

| Message | Handler | Transport | Retry | Description |
|---------|---------|-----------|-------|-------------|
```

### Doctrine et migrations

```markdown
## Persistance

### Mapping Doctrine

Type de mapping (attributs PHP, XML, YAML), particularités (héritage, embeddables, types custom).

### Migrations

| Migration | Date | Effet |
|-----------|------|-------|
```

### Workflow / State machine

Si la feature utilise `symfony/workflow` :

```markdown
## Workflow

- Nom : `<workflow_name>` (config dans `config/packages/workflow.yaml`)
- Type : `state_machine` ou `workflow`
- États : ...
- Transitions : ...
- Guards : (event listeners sur `workflow.<name>.guard.<transition>`)
- Actions post-transition : (`workflow.<name>.completed.<transition>`)
```

### API (si API Platform ou contrôleurs JSON)

```markdown
## API

### Resources / endpoints

| Resource ou endpoint | Opérations / méthodes | Auth | Serialization groups |
|----------------------|------------------------|------|----------------------|
```

### Points d'extension (compléter le squelette générique)

Pour Symfony, lister explicitement :

- **Kernel events** à écouter (`kernel.request`, `kernel.controller`, `kernel.response`, `kernel.exception`)
- **Doctrine events** (`prePersist`, `postUpdate`, etc.) ou subscribers ORM
- **Services à décorer** via `#[AsDecorator]`
- **Form type extensions** via `AbstractTypeExtension`
- **Compiler passes** si présents dans `src/Kernel.php` ou `src/DependencyInjection/`
- **Twig blocks/extensions** disponibles

## Présentation interactive — couches Symfony

Adapter la séquence Phase 3 du SKILL.md :

1. Vue d'ensemble — ce que fait la feature, controllers/commandes/handlers principaux
2. Modèle de données — entités Doctrine, relations, repositories
3. Flux métier — services orchestrateurs, événements, workflow
4. Routes et controllers — endpoints HTTP, mappings de payload
5. Sécurité — firewall, voters, access_control si applicable
6. Asynchrone — messages et handlers Messenger si applicable
7. Couche présentation — templates Twig, components, JSON
