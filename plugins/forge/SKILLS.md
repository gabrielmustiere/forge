# Inventaire — plugin `forge`

Pipeline de développement stack-agnostique (26 skills).

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
| [`sync`](../plugins/forge/skills/sync/SKILL.md) | Réaligne la doc d'intention avec le code livré |
| [`report-and-sync`](../plugins/forge/skills/report-and-sync/SKILL.md) | Point d'entrée slash qui délègue au subagent `forge:report-and-sync` (enchaîne `report` puis `sync` en contexte isolé) |
| [`autopilot`](../plugins/forge/skills/autopilot/SKILL.md) | Point d'entrée slash qui délègue au subagent `forge:autopilot` (pilotage autonome bout-en-bout d'une story avec stop-points stratégiques et reprise via `.autopilot.json`) |
| [`test-scenario`](../plugins/forge/skills/test-scenario/SKILL.md) | Joue un scénario utilisateur via Playwright MCP |
| [`adr`](../plugins/forge/skills/adr/SKILL.md) | Rédige un Architecture Decision Record MADR léger (`docs/adr/NNNN-slug.md`) depuis un artifact (`pitch.md` / `plan.md` / `review.md` / `report.md`) ou un topic libre — atelier interactif (contexte, drivers, options, conséquences), backlinks automatiques dans l'artifact source, index `docs/adr/README.md` et `report.md` de la story |
| [`estimate`](../plugins/forge/skills/estimate/SKILL.md) | **Transversal optionnel** — chiffre le temps **« tout compris »** d'une story (feature, refacto, tech) à facturer : cadrage, implem, tests, review, doc, release (forfait fixe 30 min). Lit `brief.md`/`pitch.md`/`plan.md` selon ce qui existe, méthode réaliste + marge d'incertitude, **en heures**, avec **deux colonnes** (temps de référence sans IA / temps réel avec assistant IA) → `estimate.md`. Du temps, jamais de montant. |
| [`migrate-legacy`](../plugins/forge/skills/migrate-legacy/SKILL.md) | Migre les anciens formats workflow — dossiers `<f\|r\|t>-NNN-<slug>/` → `NNN-<f\|r\|t>-<slug>/`, et artifacts `feature.md`/`design.md` → `pitch.md`/`plan.md` + `feature.md` → `overview.md` dans `feature-map/`, via `git mv` |
| [`import-external`](../plugins/forge/skills/import-external/SKILL.md) | Importe une doc Spec Kit / BMAD-METHOD / GSD vers le format `docs/story/NNN-<f\|r\|t>-<slug>/` |
| [`release`](../plugins/forge/skills/release/SKILL.md) | Tag annoté SemVer + `CHANGELOG.md` Keep a Changelog + release GitHub |
| [`doc-feature`](../plugins/forge/skills/doc-feature/SKILL.md) | Documente une feature existante (stack-agnostique, détection Sylius/Symfony) → `docs/feature-map/NNN-slug/overview.md` |

## Agents

Deux subagents sont fournis par le plugin pour les opérations qui doivent tourner en **contexte isolé** (saturation contexte évitée, reprise possible après interruption). Ils sont invocables directement par l'orchestrateur via le tool `Agent`, ou via leur skill wrapper slash (`/forge:autopilot`, `/forge:report-and-sync`).

| Agent | Rôle |
| --- | --- |
| [`autopilot`](../plugins/forge/agents/autopilot.md) | Pilote autonome bout-en-bout d'une story (feature, refacto, évolution technique). Délègue **chaque sous-tâche à un subagent dédié** pour préserver l'isolation, trace l'avancement dans `.autopilot.json` (reprise propre après crash ou pause), et n'arrête la boucle qu'aux stop-points stratégiques : verrou caractérisation (refacto), baseline mesurée (tech), écart majeur détecté, échec QA/tests irrécupérable, avant tests finaux. Ne fait ni `/review`, ni `/commit`, ni `/report`, ni `/sync`. |
| [`report-and-sync`](../plugins/forge/agents/report-and-sync.md) | Clôture documentaire d'une story livrée en une passe. **Architecture inline** (aucun appel au tool `Skill`) : produit lui-même `report.md` (constat des écarts intention vs code livré) avec vérification post-écriture, puis applique le sync sur `pitch.md` / `plan.md` avec changelog. Court-circuite le sync si conformité totale détectée. |
