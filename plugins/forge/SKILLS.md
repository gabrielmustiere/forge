# Inventaire — plugin `forge`

Pipeline de développement stack-agnostique (24 skills).

> **Convention métadonnées de story** : chaque skill qui écrit dans un dossier `docs/story/NNN-<f\|r\|t>-<slug>/` maintient un fichier `metadata.json` (titre réel, dates, tags, changelog consolidé, livraison) selon la référence partagée [`references/story-metadata.md`](references/story-metadata.md). Les skills de création écrivent `title`/`created`/`tags` + première entrée ; chaque passe rebouge `updated` et append au changelog ; `commit`/`release` renseignent `delivery`. La timeline vit dans ce fichier — plus de table de changelog en pied de `pitch.md`/`plan.md`. Le Forge Board lit ce fichier (jamais ne l'écrit).

| Skill | Rôle |
| --- | --- |
| [`help`](../plugins/forge/skills/help/SKILL.md) | Sommaire du workflow, tracks, skills et artifacts |
| [`vision`](../plugins/forge/skills/vision/SKILL.md) | **Phase 0** — atelier de cadrage de la vision projet (problème, audience, valeur, North Star, principes, anti-objectifs) → `docs/vision.md`. Document vivant, 4 modes (Création / Enrichir / Éditer / Pivot) avec changelog. |
| [`product-backlog`](../plugins/forge/skills/product-backlog/SKILL.md) | **Phase 0.5** — traduit la vision en domaines, capacités, parcours et backlog priorisé MVP/V2/V3 → `docs/product-backlog.md`. Document vivant, 4 modes (Création / Enrichir / Éditer / Pivot) avec changelog. |
| [`stack`](../plugins/forge/skills/stack/SKILL.md) | **Phase 0 technique** — cartographie la stack complète (langages, backend, frontend, données, ops, devops/CI) → `docs/stack.md`. Chaque techno prouvée par un fichier source, trous comblés par questions ou marqués `_non renseigné_`. Document vivant, 4 modes avec changelog, lu en priorité par les tracks technique (`feature-implem`/`refactor-implem`/`tech-implem`/`review`). |
| [`claude-md`](../plugins/forge/skills/claude-md/SKILL.md) | Génère ou met à jour le `CLAUDE.md` à la racine : analyse du codebase (nature, stack, architecture, commandes, conventions) prouvée par fichier — aucune commande inventée — et injection des 4 principes comportementaux Karpathy (réflexion, simplicité, changements chirurgicaux, objectif vérifiable). Réutilise `docs/stack.md` et `docs/vision.md` s'ils existent. Modes Création / Mise à jour, validation avant écriture. |
| [`feature-interview`](../plugins/forge/skills/feature-interview/SKILL.md) | **Amont optionnel du track feature** — interview de découverte d'un besoin flou (irritant, qui, résultat attendu) ancrée sur une reconnaissance ciblée du code existant → `brief.md`. Alimente `feature-pitch`. |
| [`feature-pitch`](../plugins/forge/skills/feature-pitch/SKILL.md) | Atelier de cadrage d'une idée de feature → `pitch.md` (lit le `brief.md` amont s'il existe) |
| [`feature-plan`](../plugins/forge/skills/feature-plan/SKILL.md) | Plan technique d'une feature cadrée → `plan.md` |
| [`feature-implem`](../plugins/forge/skills/feature-implem/SKILL.md) | Implémentation guidée à partir du plan |
| [`refactor-plan`](../plugins/forge/skills/refactor-plan/SKILL.md) | Cadrage refacto + tests de caractérisation → `plan.md` |
| [`refactor-implem`](../plugins/forge/skills/refactor-implem/SKILL.md) | Exécution guidée d'un refacto avec verrou tests |
| [`tech-plan`](../plugins/forge/skills/tech-plan/SKILL.md) | Cadrage évolution technique (perf, résilience, sécu) → `plan.md` |
| [`tech-implem`](../plugins/forge/skills/tech-implem/SKILL.md) | Exécution d'une évolution technique avec baseline/kill switch |
| [`review`](../plugins/forge/skills/review/SKILL.md) | Code review du diff (sécurité, qualité, conformité) |
| [`commit`](../plugins/forge/skills/commit/SKILL.md) | Génère un Conventional Commit FR et push |
| [`report`](../plugins/forge/skills/report/SKILL.md) | Compte rendu intention vs code réel |
| [`sync`](../plugins/forge/skills/sync/SKILL.md) | Réaligne la doc d'intention **et les docs projet** (`vision` / `stack` / `product-backlog`) avec le code livré |
| [`report-and-sync`](../plugins/forge/skills/report-and-sync/SKILL.md) | Enchaîne `report` puis `sync` en une passe dans la session courante (compte rendu d'écarts, puis réalignement de la doc) — court-circuite le sync si conformité totale |
| [`test-scenario`](../plugins/forge/skills/test-scenario/SKILL.md) | Joue un scénario utilisateur via Playwright MCP |
| [`adr`](../plugins/forge/skills/adr/SKILL.md) | Rédige un Architecture Decision Record MADR léger (`docs/adr/NNNN-slug.md`) depuis un artifact (`pitch.md` / `plan.md` / `review.md` / `report.md`) ou un topic libre — atelier interactif (contexte, drivers, options, conséquences), backlinks automatiques dans l'artifact source, index `docs/adr/README.md` et `report.md` de la story |
| [`estimate`](../plugins/forge/skills/estimate/SKILL.md) | **Transversal optionnel** — chiffre le temps **« tout compris »** d'une story (feature, refacto, tech) à facturer : cadrage, implem, tests, review, doc, release (forfait fixe 30 min). Lit `brief.md`/`pitch.md`/`plan.md` selon ce qui existe, méthode réaliste + marge d'incertitude, **en heures**, avec **deux colonnes** (temps de référence sans IA / temps réel avec assistant IA) → `estimate.md`. Du temps, jamais de montant. |
| [`backfill-metadata`](../plugins/forge/skills/backfill-metadata/SKILL.md) | Reconstruit rétroactivement le `metadata.json` des stories antérieures dépourvues : titre depuis le H1, `created`/`updated` depuis l'historique git du dossier, `changelog` depuis l'apparition de chaque artifact, `delivery` (commit/release) depuis les commits et tags — tags proposés puis validés. N'écrit que des valeurs vraies (jamais de date inventée), delivery laissé absent si la livraison n'est pas identifiable avec certitude. Ne touche pas un fichier valide sauf `--force`. |
| [`release`](../plugins/forge/skills/release/SKILL.md) | Tag annoté SemVer + `CHANGELOG.md` Keep a Changelog + release GitHub |
| [`doc-feature`](../plugins/forge/skills/doc-feature/SKILL.md) | Documente une feature existante (stack-agnostique, détection Sylius/Symfony) → `docs/feature-map/NNN-slug/overview.md` |
