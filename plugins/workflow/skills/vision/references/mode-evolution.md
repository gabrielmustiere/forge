# Phase 1bis — Cibler l'évolution (modes Enrichir et Éditer)

À utiliser **uniquement** en mode Enrichir ou Éditer. En mode Création ou Pivot, **ignorer ce fichier** et passer aux axes de challenge (`axes-challenge.md`, dans le même dossier que ce fichier).

L'utilisateur ne re-déroule pas tout l'atelier : on cible l'axe (ou les axes) concerné(s).

## Étape 1 — Identifier les axes concernés

Demande explicitement, via `AskUserQuestion` :

**Quel(s) axe(s) sont concernés ?** Propose ces choix (multi-sélection) :

- Problème
- Audience (principale, secondaire, hors-cible)
- Proposition de valeur
- Métriques (North Star, secondaires, seuils, signal d'arrêt)
- Principes produit
- Anti-objectifs
- Hypothèses critiques
- Risques externes
- Horizons

## Étape 2 — Préciser la nature de l'évolution

Pour chaque axe ciblé, demande la nature précise :

- En **Enrichir** : « Quel nouvel élément veux-tu ajouter à cet axe ? » (et reformule comme un ajout cohérent, pas comme une réécriture).
- En **Éditer** : « Quel élément existant veux-tu corriger / reformuler / retirer, et pourquoi ? »

## Étape 3 — Contrôle de cohérence

Avant d'écrire, challenge systématiquement :

- L'ajout contredit-il un anti-objectif déjà énoncé ? Un principe ?
- L'ajout reste-t-il aligné sur le problème central et l'audience principale ? Si non, est-ce qu'on est en train de faire un Pivot déguisé ? (Si oui, repropose le mode Pivot.)
- L'élément retiré laisse-t-il un trou (un anti-objectif retiré était-il invoqué par un principe ?) ?

## Sortie

Quand l'évolution ciblée est claire et cohérente, **saute la Phase 2** (challenge complet inutile) et passe directement à la Phase 3 pour mettre à jour le doc.

Si en cours de discussion l'utilisateur veut en fait revisiter plusieurs axes en profondeur, propose-lui de basculer en mode Pivot pour faire les choses proprement plutôt que d'empiler des enrichissements jusqu'à perdre la cohérence.
