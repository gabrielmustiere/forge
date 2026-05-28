---
name: report-and-sync
description: Enchaîne `/workflow:report` puis `/workflow:sync` en une passe après livraison d'une story (feature, refacto ou évolution technique) — compte rendu d'écarts intention vs code livré, puis réalignement de la doc. Délègue à un subagent isolé.
user_invocable: true
argument-hint: "[slug-story ou chemin docs/story/NNN-<f|r|t>-<slug>/]"
allowed-tools:
  - Agent
  - AskUserQuestion
---

# /report-and-sync — Clôture documentaire en contexte isolé

Cette skill est un **point d'entrée slash** pour le subagent `workflow:report-and-sync`. Le subagent enchaîne deux phases :

1. **REPORT** — invoque la skill `/workflow:report` pour produire `report.md` (constat des écarts entre intention et code livré)
2. **SYNC** — invoque la skill `/workflow:sync` pour appliquer les écarts validés à la doc d'intention (`pitch.md` + `plan.md` pour une feature, `plan.md` pour un refacto ou une évolution tech) avec changelog interne

Le wrapper te donne un point d'entrée slash explicite et préserve l'isolation de contexte : c'est le subagent qui pilote l'enchaînement, pas la session principale.

## Procédure

1. Récupère les arguments transmis dans `$ARGUMENTS` (slug de story ou chemin `docs/story/NNN-<f|r|t>-<slug>/`).

2. Si `$ARGUMENTS` est vide, demande à l'utilisateur le slug ou le chemin de la story. La clôture documentaire a besoin d'une story livrée explicitement nommée — ne devine pas.

3. Invoque le tool `Agent` avec :
   - `subagent_type: "workflow:report-and-sync"`
   - `description: "Report+Sync story <slug>"`
   - `prompt` : transmets le slug/chemin et toute consigne complémentaire de l'utilisateur (par exemple, contraintes particulières sur le sync, sections à ne pas réaligner, etc.). L'agent connaît sa propre procédure REPORT → SYNC.

4. Quand le subagent rend la main, relaie à l'utilisateur :
   - le chemin du `report.md` produit
   - les fichiers d'intention modifiés par le sync (avec rappel du changelog ajouté)
   - les écarts éventuellement laissés non-réalignés (et pourquoi)

## Référence

Spécification complète de l'agent : `plugins/workflow/agents/report-and-sync.md`.
