# Inventaire — plugin `workflow`

Pipeline de développement stack-agnostique (25 skills).

| Skill | Rôle |
| --- | --- |
| [`help`](../plugins/workflow/skills/help/SKILL.md) | Sommaire du workflow, tracks, skills et artifacts |
| [`vision`](../plugins/workflow/skills/vision/SKILL.md) | **Phase 0** — atelier de cadrage de la vision projet (problème, audience, valeur, North Star, principes, anti-objectifs) → `docs/vision.md`. Document vivant, 4 modes (Création / Enrichir / Éditer / Pivot) avec changelog. |
| [`product-backlog`](../plugins/workflow/skills/product-backlog/SKILL.md) | **Phase 0.5** — traduit la vision en domaines, capacités, parcours et backlog priorisé MVP/V2/V3 → `docs/product-backlog.md`. Document vivant, 4 modes (Création / Enrichir / Éditer / Pivot) avec changelog. |
| [`stack`](../plugins/workflow/skills/stack/SKILL.md) | **Phase 0 technique** — cartographie la stack complète (langages, backend, frontend, données, ops, devops/CI) → `docs/stack.md`. Chaque techno prouvée par un fichier source, trous comblés par questions ou marqués `_non renseigné_`. Document vivant, 4 modes avec changelog, lu en priorité par les tracks technique (`feature-implem`/`refactor-implem`/`tech-implem`/`review`). |
| [`claude-md`](../plugins/workflow/skills/claude-md/SKILL.md) | Génère ou met à jour le `CLAUDE.md` à la racine : analyse du codebase (nature, stack, architecture, commandes, conventions) prouvée par fichier — aucune commande inventée — et injection des 4 principes comportementaux Karpathy (réflexion, simplicité, changements chirurgicaux, objectif vérifiable). Réutilise `docs/stack.md` et `docs/vision.md` s'ils existent. Modes Création / Mise à jour, validation avant écriture. |
| [`feature-interview`](../plugins/workflow/skills/feature-interview/SKILL.md) | **Amont optionnel du track feature** — interview de découverte d'un besoin flou (irritant, qui, résultat attendu) ancrée sur une reconnaissance ciblée du code existant → `brief.md`. Alimente `feature-pitch`. |
| [`feature-pitch`](../plugins/workflow/skills/feature-pitch/SKILL.md) | Atelier de cadrage d'une idée de feature → `pitch.md` (lit le `brief.md` amont s'il existe) |
| [`feature-plan`](../plugins/workflow/skills/feature-plan/SKILL.md) | Plan technique d'une feature cadrée → `plan.md` |
| [`feature-implem`](../plugins/workflow/skills/feature-implem/SKILL.md) | Implémentation guidée à partir du plan |
| [`refactor-plan`](../plugins/workflow/skills/refactor-plan/SKILL.md) | Cadrage refacto + tests de caractérisation → `plan.md` |
| [`refactor-implem`](../plugins/workflow/skills/refactor-implem/SKILL.md) | Exécution guidée d'un refacto avec verrou tests |
| [`tech-plan`](../plugins/workflow/skills/tech-plan/SKILL.md) | Cadrage évolution technique (perf, résilience, sécu) → `plan.md` |
| [`tech-implem`](../plugins/workflow/skills/tech-implem/SKILL.md) | Exécution d'une évolution technique avec baseline/kill switch |
| [`review`](../plugins/workflow/skills/review/SKILL.md) | Code review du diff (sécurité, qualité, conformité) |
| [`commit`](../plugins/workflow/skills/commit/SKILL.md) | Génère un Conventional Commit FR et push |
| [`report`](../plugins/workflow/skills/report/SKILL.md) | Compte rendu intention vs code réel |
| [`sync`](../plugins/workflow/skills/sync/SKILL.md) | Réaligne la doc d'intention avec le code livré |
| [`report-and-sync`](../plugins/workflow/skills/report-and-sync/SKILL.md) | Point d'entrée slash qui délègue au subagent `workflow:report-and-sync` (enchaîne `report` puis `sync` en contexte isolé) |
| [`autopilot`](../plugins/workflow/skills/autopilot/SKILL.md) | Point d'entrée slash qui délègue au subagent `workflow:autopilot` (pilotage autonome bout-en-bout d'une story avec stop-points stratégiques et reprise via `.autopilot.json`) |
| [`test-scenario`](../plugins/workflow/skills/test-scenario/SKILL.md) | Joue un scénario utilisateur via Playwright MCP |
| [`adr`](../plugins/workflow/skills/adr/SKILL.md) | Rédige un Architecture Decision Record MADR léger (`docs/adr/NNNN-slug.md`) depuis un artifact (`pitch.md` / `plan.md` / `review.md` / `report.md`) ou un topic libre — atelier interactif (contexte, drivers, options, conséquences), backlinks automatiques dans l'artifact source, index `docs/adr/README.md` et `report.md` de la story |
| [`migrate-legacy`](../plugins/workflow/skills/migrate-legacy/SKILL.md) | Migre les anciens formats workflow — dossiers `<f\|r\|t>-NNN-<slug>/` → `NNN-<f\|r\|t>-<slug>/`, et artifacts `feature.md`/`design.md` → `pitch.md`/`plan.md` + `feature.md` → `overview.md` dans `feature-map/`, via `git mv` |
| [`import-external`](../plugins/workflow/skills/import-external/SKILL.md) | Importe une doc Spec Kit / BMAD-METHOD / GSD vers le format `docs/story/NNN-<f\|r\|t>-<slug>/` |
| [`release`](../plugins/workflow/skills/release/SKILL.md) | Tag annoté SemVer + `CHANGELOG.md` Keep a Changelog + release GitHub |
| [`doc-feature`](../plugins/workflow/skills/doc-feature/SKILL.md) | Documente une feature existante (stack-agnostique, détection Sylius/Symfony) → `docs/feature-map/NNN-slug/overview.md` |

## Agents

Deux subagents sont fournis par le plugin pour les opérations qui doivent tourner en **contexte isolé** (saturation contexte évitée, reprise possible après interruption). Ils sont invocables directement par l'orchestrateur via le tool `Agent`, ou via leur skill wrapper slash (`/workflow:autopilot`, `/workflow:report-and-sync`).

| Agent | Rôle |
| --- | --- |
| [`autopilot`](../plugins/workflow/agents/autopilot.md) | Pilote autonome bout-en-bout d'une story (feature, refacto, évolution technique). Délègue **chaque sous-tâche à un subagent dédié** pour préserver l'isolation, trace l'avancement dans `.autopilot.json` (reprise propre après crash ou pause), et n'arrête la boucle qu'aux stop-points stratégiques : verrou caractérisation (refacto), baseline mesurée (tech), écart majeur détecté, échec QA/tests irrécupérable, avant tests finaux. Ne fait ni `/review`, ni `/commit`, ni `/report`, ni `/sync`. |
| [`report-and-sync`](../plugins/workflow/agents/report-and-sync.md) | Clôture documentaire d'une story livrée en une passe. **Architecture inline** (aucun appel au tool `Skill`) : produit lui-même `report.md` (constat des écarts intention vs code livré) avec vérification post-écriture, puis applique le sync sur `pitch.md` / `plan.md` avec changelog. Court-circuite le sync si conformité totale détectée. |
