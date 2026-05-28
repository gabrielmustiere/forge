# Checklist migration Doctrine

S'active dès qu'une sous-tâche touche le modèle (entité, mapping, relation). Doctrine est commun à Symfony et Sylius.

```bash
symfony console make:migration                        # générer (JAMAIS à la main)
symfony console doctrine:migrations:migrate --dry-run # vérifier le SQL généré
symfony console doctrine:migrations:migrate           # appliquer
symfony console doctrine:schema:validate              # cohérence schema/mapping
```

**Règle absolue** : ne JAMAIS modifier manuellement le contenu d'un fichier de migration. Si la migration générée ne convient pas, supprimer le fichier, corriger le mapping/entité, et regénérer avec `make:migration`. Une migration commitée n'est jamais modifiée — on en crée une nouvelle.

## Points de vérification manuels

- **`down()`** réversible ? Sinon, documenter pourquoi dans la migration.
- **Colonnes NOT NULL** sur table non vide : DEFAULT prévu, ou ALTER en deux temps (nullable → backfill → NOT NULL) ?
- **Suppressions** (DROP COLUMN/TABLE) : données en prod ? backup ou migration de données préalable ?
- **Index** sur les colonnes utilisées en WHERE/JOIN/ORDER BY ?
- **Fixtures** à mettre à jour pour le nouveau schéma ?
