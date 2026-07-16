---
name: report-and-sync
description: Enchaîne `/forge:report` puis `/forge:sync` en une passe après livraison d'une story (feature, refacto ou évolution technique) — compte rendu d'écarts intention vs code livré, puis réalignement de la doc d'intention. Court-circuite le sync si conformité totale.
user_invocable: true
disable-model-invocation: true
argument-hint: "[slug-story ou chemin docs/story/NNN-<f|r|t>-<slug>/]"
allowed-tools:
  - Read
  - Grep
  - Glob
  - Write
  - Edit
  - Bash(git status:*)
  - Bash(git log:*)
  - Bash(git diff:*)
  - Bash(git show:*)
  - Bash(ls:*)
---

> _Orchestrateur : ces `allowed-tools` sont l'**union exacte** de ceux de `/forge:report` et `/forge:sync`, jamais plus (contrat `references/skill-boundaries.md` §4). Si l'un des deux skills gagne une capacité, la répercuter ici ; sinon, ne rien ajouter._

# /report-and-sync — Clôture documentaire en une passe

Cette skill enchaîne les deux procédures de clôture documentaire **dans la session courante**, en s'appuyant sur les deux skills canoniques comme **unique source de vérité** :

1. **REPORT** — `/forge:report` produit `report.md` (constat des écarts entre intention et code livré)
2. **SYNC** — `/forge:sync` applique les écarts validés à la doc d'intention (`pitch.md` + `plan.md` pour une feature, `plan.md` pour un refacto ou une évolution tech), puis **propage aux documents projet** (`vision.md` / `stack.md` / `product-backlog.md`) via leurs modes Enrichir/Éditer (Phase 5 de `/forge:sync`)

> **Pas de subagent.** Report et sync sont des procédures **interactives** : elles te font valider chaque écart et écrivent des fichiers. Elles doivent tourner dans la session principale, où l'écriture peut t'être autorisée et où tu peux répondre aux questions. Un subagent délégué ne peut ni demander la permission d'écrire (`report.md` ne s'écrit pas) ni mener une revue interactive fluide — c'est ce qui cassait la clôture par le passé.

> **Pas d'outil `Skill` non plus.** `/forge:report` et `/forge:sync` sont marquées `disable-model-invocation: true` : elles sont réservées à l'invocation humaine et l'outil `Skill` les refuse. Charge leur procédure avec `Read`, puis **déroule-la toi-même dans cette session**. C'est le même mécanisme que les références du plugin (`references/stacks/_detection.md`) : lire le fichier _est_ le chargement.
>
> ⚠️ Dans le texte lu, `${CLAUDE_SKILL_DIR}` désigne le dossier de **la skill que tu viens de lire** (`.../skills/report/` ou `.../skills/sync/`), **pas** celui de `report-and-sync`. `Read` renvoie le Markdown brut sans substituer la variable : résous-la toi-même à partir du chemin que tu as lu. Ainsi `${CLAUDE_SKILL_DIR}/references/template.md` dans `report/SKILL.md` pointe vers `.../skills/report/references/template.md`.

## Procédure

1. Récupère `$ARGUMENTS` (slug de story ou chemin `docs/story/NNN-<f|r|t>-<slug>/`). Si vide, demande le slug ou le chemin — ne devine pas.

2. **Phase REPORT** — lis `${CLAUDE_SKILL_DIR}/../report/SKILL.md` et déroule **intégralement** sa procédure sur le slug/chemin retenu (chargement de l'intention, analyse du code, revue interactive, écriture de `report.md`, mise à jour du `metadata.json`). Ne passe à l'étape suivante qu'une fois `report.md` écrit et confirmé.

3. **Court-circuit conformité** — si le report conclut à une conformité totale (aucun écart : ni écart volontaire, ni manque, ni ajout), le sync est inutile. Annonce-le et arrête-toi là.

4. **Phase SYNC** — sinon, lis `${CLAUDE_SKILL_DIR}/../sync/SKILL.md` et déroule sa procédure sur la même story. Elle repart du `report.md` fraîchement écrit comme source des écarts, propose chaque réalignement à validation, applique les `Edit` et met à jour le `metadata.json`.

5. **Bilan** — récapitule :
   - le chemin du `report.md` produit
   - les fichiers d'intention modifiés par le sync
   - les documents projet propagés (`stack.md` / `product-backlog.md`) et tout signalement `vision.md`
   - les écarts éventuellement laissés non-réalignés (et pourquoi)
   - prochaine étape suggérée : `/forge:commit` pour committer la clôture documentaire

## Référence

Les procédures complètes vivent dans `${CLAUDE_SKILL_DIR}/../report/SKILL.md` et `${CLAUDE_SKILL_DIR}/../sync/SKILL.md` — **seule source de vérité**. Cette skill ne fait que les enchaîner ; elle ne réimplémente rien.

⚠️ Ne lis jamais ces fichiers via un chemin relatif au projet (`plugins/forge/skills/report/SKILL.md`) : ce chemin n'existe que dans le repo source de la marketplace. Chez l'utilisateur, le plugin est installé **hors du projet** (`~/.claude/plugins/...`) — seul le chemin dérivé de `${CLAUDE_SKILL_DIR}` est correct.

## Argument optionnel

`/report-and-sync ma-feature` — enchaîne report puis sync sur la story trouvée par slug.

`/report-and-sync docs/story/015-f-checkout-express/` — cible directement le dossier.

`/report-and-sync` sans argument — demande la story à traiter.
