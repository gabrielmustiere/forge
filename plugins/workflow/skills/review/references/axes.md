# Axes de review

Parcours chaque fichier modifié et analyse selon ces axes, dans cet ordre de priorité.

## Axe 1 — Sécurité (bloquant)

- **Injection SQL** : requêtes construites par concaténation au lieu de paramètres.
- **XSS** : variables non échappées dans les templates (`{{ var|raw }}` sans raison).
- **CSRF** : formulaires sans token CSRF.
- **Mass assignment** : entités exposées directement sans DTO ou serialization groups.
- **Secrets** : credentials, tokens, clés API dans le code ou la config commitée.
- **Permissions** : accès non vérifié (routes admin sans guard, voters manquants, etc.).

## Axe 2 — Conformité à la référence d'intention (bloquant si fournie)

**Cas feature (`f-`)** — référence = `plan.md` :

- Les fichiers créés/modifiés correspondent-ils au plan ?
- L'approche technique est-elle celle prévue ?
- Les entités et relations sont-elles conformes au schéma prévu ?
- Si un écart existe : est-il justifié ou c'est une dérive silencieuse ?
- Les critères d'acceptation du `pitch.md` sont-ils couverts ?

**Cas refacto (`r-`)** — référence = `plan.md`, focus inversé sur la non-régression :

- Le **comportement externe** est-il strictement préservé (pas de signature publique modifiée, pas de réponse changée, pas de side-effect nouveau) ? Toute modification de comportement observable doit être signalée comme **bloquant**.
- Les **tests de caractérisation** prévus dans le plan sont-ils bien présents et passent-ils ? Si la phase "verrou tests" du plan a été sautée, c'est bloquant.
- Le périmètre du diff respecte-t-il le périmètre du plan (pas de scope creep silencieux) ?
- Les étapes incrémentales prévues sont-elles toutes faites, ou seulement une partie ?

**Cas évolution technique (`t-`)** — référence = `plan.md` :

- La brique technique introduite correspond-elle au plan (lib choisie, point d'intégration, config) ?
- Les critères de succès du plan (perf, résilience, observabilité) sont-ils mesurables après le diff ?
- Le rollback prévu (feature flag, env var, kill switch) est-il bien en place ?

## Axe 3 — Migrations (bloquant si migration présente)

Pour les stacks Doctrine (Symfony / Sylius) — voir `symfony.md` :

- La migration a-t-elle été générée (pas écrite à la main) ?
- Le `down()` est-il fonctionnel et réversible (ou l'irréversibilité est-elle documentée) ?
- Les colonnes NOT NULL sur table non vide ont-elles un DEFAULT ou une migration en deux temps ?
- Risque de perte de données (DROP COLUMN, DROP TABLE) ? Backup ou migration de données préalable ?
- Index pertinents pour les requêtes prévues ?
- `schema:validate` passe-t-il ?

## Axe 4 — Conventions framework (bloquant)

Appliquer les règles du stack détecté. Les violations typiques qui cassent silencieusement sont documentées dans les références :

- **Symfony** (référence stack `symfony.md`) : snake_case en BDD pour colonnes camelCase, DQL/SQL dans repositories uniquement, injection par constructeur, décoration plutôt que monkey-patching.
- **Sylius** (référence stack `sylius.md`) : pas de modification vendor, validation `groups: ['Default', 'sylius']`, FormTypeExtension + Twig Hooks symétriques (piège 422 silencieux), composants Twig namespacés (`Media:MonComposant`), transitions StateMachine plutôt que setState direct.

Pour chaque modification, vérifier qu'elle respecte le mécanisme d'extension du framework plutôt qu'un contournement.

## Axe 5 — Impacts transverses (important)

Selon le stack détecté :

- **Sylius — multi-channel** : données cloisonnées par channel ? Repositories filtrent-ils ? Fixtures multi-channel ?
- **Sylius — multi-thème shop** : templates modifiés ont-ils des overrides dans `themes/*/templates/bundles/Sylius*Bundle/` à mettre à jour ? **L'admin Sylius n'a pas de variation de thème** — ne pas chercher d'override admin.
- **Symfony / tous stacks — i18n** : libellés en dur dans les templates au lieu de `|trans` ?
- **Symfony / tous stacks — API** : si ressource API touchée, serialization groups minimaux ? opérations protégées (security, voters) ? auth correcte ?

## Axe 6 — Qualité du code (important)

- **Conventions projet** : injection par constructeur, typage strict, nommage PSR-4 (PHP).
- **Entités** : héritent correctement, interfaces implémentées, déclarations config à jour (Sylius : `_sylius.yaml`).
- **DRY** : duplication de logique détectable ?
- **Couplage** : dépendances circulaires, services qui en savent trop ?
- **Code mort** : `dump()`, `var_dump()`, `dd()`, commentaires `// TODO` sans ticket, code commenté laissé en place.

## Axe 7 — Performance (important)

- **N+1** : boucles qui déclenchent des requêtes lazy-load (relations Doctrine sans `JOIN`).
- **Requêtes dans les boucles** : appels repository/service dans un `foreach`.
- **Cache** : données fréquemment lues cachées quand pertinent ?
- **Pagination** : listes non paginées sur des collections potentiellement grandes ?

## Axe 8 — Tests (mineur sauf régression)

- Les tests couvrent-ils les cas métier principaux ?
- Cas limites testés (null, vide, droits insuffisants) ?
- Tests E2E ciblent-ils des attributs `data-test` plutôt que des sélecteurs fragiles ?
- **Régression** : si la review identifie un risque clair de régression des tests existants, le signaler en bloquant.
