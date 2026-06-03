# Outillage de mesure et kill switch

## Méthode de mesure

Utilise la **méthode de mesure décrite dans le plan** pour chaque métrique cible. Commandes typiques selon le cas (à adapter au projet) :

```bash
# Bench de latence
k6 run bench/scenario.js
wrk -t4 -c100 -d30s http://...

# Requête Prometheus / observabilité
curl -sG http://prometheus/api/v1/query --data-urlencode 'query=histogram_quantile(0.95, rate(http_request_duration_seconds_bucket{...}[5m]))'

# Script applicatif
symfony console app:bench:pricing
```

Si une métrique ne peut pas être mesurée (outillage absent, env non représentatif), **stop** — remonter à l'utilisateur : "On ne peut pas lire [métrique] aujourd'hui. Le plan demande soit qu'on instrumente, soit qu'on change la cible, soit qu'on change d'env de mesure."

## Test du kill switch

Avant d'activer le nouveau comportement sur un chemin critique :

1. **Flag OFF** → comportement identique à avant (requête, trace, réponse). Le vérifier via un test ciblé ou un appel manuel.
2. **Flag ON** → nouveau chemin emprunté.
3. **Flag OFF à nouveau** → retour au comportement initial, aucun résidu.

Si le kill switch ne fonctionne pas dans les trois sens, **on n'active rien en prod**. On corrige d'abord.

## QA standard après une étape

```bash
# Style / analyse statique (adapter au stack)
vendor/bin/ecs check --fix
vendor/bin/phpstan analyse

# Tests existants — aucune régression sur les autres chemins
vendor/bin/phpunit
npm run test:e2e
```
