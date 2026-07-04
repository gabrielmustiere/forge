# Phase 0bis — Cibler l'évolution (modes Enrichir et Éditer)

À utiliser **uniquement** en mode Enrichir ou Éditer. En mode Création ou Pivot, ignorer ce fichier et charger `phases-creation.md` (dans le même dossier que ce fichier).

L'utilisateur ne re-déroule pas tout l'atelier : on cible l'élément concerné.

## Étape 1 — Identifier les éléments concernés

Via `AskUserQuestion`, demande :

**Quel(s) élément(s) sont concernés ?** Propose ces choix (multi-sélection) :

- Domaine (nouveau bloc métier, ou renommage/retrait d'un domaine)
- Capacité (dans un domaine existant ou nouveau)
- Parcours utilisateur
- Règle métier transverse (permissions, workflow d'état, contrainte, conformité, convention)
- Ligne de backlog (nouvelle feature, repriorisation, retrait)
- Couverture / dépendances (réorganisation des liens entre éléments)

## Étape 2 — Préciser la nature

Pour chaque élément ciblé, demande la nature précise :

- En **Enrichir** : « Quel nouvel élément ajouter ? À quel domaine / parcours / capacité se rattache-t-il ? »
- En **Éditer** : « Quel élément existant veux-tu corriger / reformuler / retirer, et pourquoi ? »

## Étape 3 — Contrôle de cohérence

Systématique avant rédaction :

- **Alignement vision** : l'ajout pointe-t-il vers un problème, une audience, un principe ou une North Star de `docs/vision.md` ? Sinon refus ou retour au mode Pivot (signal qu'on dérive).
- **Conflit anti-objectifs** : l'ajout contredit-il un anti-objectif de la vision ?
- **Rattachement** : une nouvelle feature s'accroche-t-elle à au moins une capacité existante (ou à une capacité elle-même ajoutée dans la même session) ? Une nouvelle capacité se rattache-t-elle à un domaine ? Un nouveau parcours référence-t-il des capacités identifiées ?
- **Doublon** : l'élément existe-t-il déjà sous un autre nom ?
- **Trou laissé par un retrait** : si on retire une capacité, vérifier qu'elle ne casse pas un parcours ou ne laisse pas une feature orpheline (proposer alors un retrait en cascade ou un renommage).
- **Priorisation cohérente** : une feature MVP qui dépend d'une capacité ajoutée en V2 = incohérence ; signaler.
- **Cohérence avec features livrées** : si une capacité a déjà été livrée (vérifier `docs/story/`), un Éditer doit refléter la réalité du code, pas la réécrire en silence.

## Sortie

Quand l'évolution ciblée est claire et cohérente, **saute les Phases 1 → 5** (atelier complet inutile) et passe directement à la Phase 6 pour mettre à jour le doc.

Si l'utilisateur cumule trop d'évolutions au fil de la discussion (plusieurs domaines retouchés, MVP/V2 réorganisé en profondeur), propose de basculer en mode Pivot plutôt que d'empiler des enrichissements jusqu'à perdre la cohérence.
