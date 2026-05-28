---
name: autopilot
description: Pilote autonome bout-en-bout de `/workflow:feature`, `/workflow:refactor` et `/workflow:tech` — délègue chaque sous-tâche à un subagent isolé, trace dans `.autopilot.json` (reprise possible), s'arrête uniquement aux stop-points stratégiques.
user_invocable: true
argument-hint: "[slug-story ou chemin docs/story/NNN-<f|r|t>-<slug>/]"
allowed-tools:
  - Agent
  - AskUserQuestion
---

# /autopilot — Lancer l'autopilote en contexte isolé

Cette skill est un **point d'entrée slash** pour le subagent `workflow:autopilot`. Toute la logique d'autopilotage (phases, stop-points, reprise via `.autopilot.json`) vit dans l'agent. Le rôle de la skill se limite à : capter les arguments utilisateur, déléguer au subagent, relayer le résumé final.

L'intérêt de ce wrapper est double :
- te donner un point d'entrée slash explicite (`/workflow:autopilot`) plutôt qu'un déclenchement implicite par description
- préserver l'isolation de contexte : c'est le subagent qui orchestre, pas la session principale, donc tu ne satures pas ton contexte avec les détails de chaque sous-tâche

## Procédure

1. Récupère les arguments transmis dans `$ARGUMENTS`. Il s'agit d'un slug de story (`042-f-checkout-express`) ou d'un chemin (`docs/story/042-f-checkout-express/`).

2. Si `$ARGUMENTS` est vide, demande à l'utilisateur le slug ou le chemin de la story avant d'aller plus loin. Ne lance pas l'agent sans cible explicite — l'autopilote a besoin de savoir sur quoi piloter.

3. Invoque le tool `Agent` avec :
   - `subagent_type: "workflow:autopilot"`
   - `description: "Autopilote story <slug>"` (10-12 mots max)
   - `prompt` : reformule la demande pour l'agent en incluant le slug/chemin reçu et toute consigne complémentaire transmise par l'utilisateur (mode dry-run, sous-tâches à exclure, etc.). L'agent connaît déjà sa propre procédure — pas besoin de la lui répéter.

4. Quand le subagent rend la main, relaie à l'utilisateur :
   - le statut final (livraison complète, stop-point atteint, écart majeur signalé)
   - les chemins des artifacts produits ou mis à jour
   - les prochaines étapes attendues (typiquement `/workflow:review`, `/workflow:commit`, `/workflow:report-and-sync`)

## Référence

Spécification complète de l'agent (phases, stop-points, reprise) : `plugins/workflow/agents/autopilot.md`.
