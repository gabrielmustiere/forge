---
name: Nova · Midnight
colors:
  primary: "#7c6cf0"
  secondary: "#8b7bf5"
  canvas: "#0a0a0b"
  surface: "#0f0f10"
  raised: "#141416"
  line: "#1b1b1e"
  ink: "#e4e4e7"
  success: "#22c55e"
  warning: "#f59e0b"
  danger: "#ef4444"
typography:
  h1:
    fontFamily: "Hanken Grotesk"
    fontSize: 1.5rem
  body-md:
    fontFamily: "Hanken Grotesk"
    fontSize: 0.875rem
  label-caps:
    fontFamily: "JetBrains Mono"
    fontSize: 0.6875rem
  sourceScale: "11/12/13/14/18/22/26"
  weights: "300, 400, 500, 600, 700"
rounded:
  sm: 6px
  md: 9px
  lg: 12px
  xl: 14px
spacing:
  sm: 4px
  md: 8px
  sourceScale: "4/8/12/16/24/32"
---

## Overview

**Nova · Midnight** est la direction artistique de référence du Forge Board. Thème sombre
quasi-noir inspiré de Linear : dense, compact, accent iris (violet). L'interface s'organise
autour d'une **sidebar** (navigation + projets), d'une **zone kanban** centrale et de
**panneaux latéraux** de détail. Tout le kit consomme les tokens définis dans
`assets/styles/app.css` (bloc `@theme`) — c'est le point d'entrée unique du re-thème.

## Style Foundations

- **Visual style :** sombre, dense, compact, inspiré de Linear
- **Typography scale :** 11/12/13/14/18/22/26 px
- **Typography fonts :** sans = Hanken Grotesk, mono = JetBrains Mono (ids, compteurs, dates, code, labels)
- **Typography weights :** 300, 400, 500, 600, 700
- **Color palette :** surfaces empilées (canvas → surface → raised), encre claire, accent iris, couleurs de statut
- **Spacing scale :** 4/8/12/16/24/32

## Colors

### Surfaces (du plus profond au plus haut)

- **Canvas (`#0a0a0b`)** — fond principal, zone kanban. Classe `bg-canvas`.
- **Surface (`#0f0f10`)** — sidebar, panneaux latéraux, headers. Classe `bg-surface`.
- **Stat (`#111113`)** — cartes de statistiques. Classe `bg-stat`.
- **Raised (`#141416`)** — cards, colonnes. Classe `bg-raised` (survol `bg-raised-hover`).
- **Overlay (`#131315`)** — modales. Classe `bg-overlay`.
- **Input (`#1a1a1c`)** — champs de formulaire. Classe `bg-input`.

### Bordures / séparateurs

- **Line (`#1b1b1e`)** — séparateur de panneaux. `border-line`.
- **Frame (`#1e1e21`)** — cadre écran, stat cards. `border-frame`.
- **Line-strong (`#232327`)** — cards, inputs. `border-line-strong`.
- **Line-hover (`#34343a`)** — survol card. `border-line-hover`.
- **Divider (`#3f3f46`)** — micro-séparateurs, barres de priorité éteintes.

### Encre (texte)

- **Ink-bright (`#f4f4f5`)** — titres forts. `text-ink-bright`.
- **Ink (`#e4e4e7`)** — texte primaire. `text-ink`.
- **Ink-soft (`#c4c4c8`)** — corps markdown. `text-ink-soft`.
- **Ink-dim (`#a1a1aa`)** — texte secondaire. `text-ink-dim`.
- **Ink-muted (`#71717a`)** / **Ink-subtle (`#6b6b75`)** — muted, ids, légendes.
- **Ink-faint (`#52525b`)** — labels de section, très discret.

### Accent iris (violet)

- **Iris (`#7c6cf0`)** — accent principal : boutons, barres de progression, underline actif. `bg-iris` / `text-iris`.
- **Iris-bright (`#8b7bf5`)** — survol, extrémité claire du gradient.
- **Iris-deep (`#5b4bd6`)** — extrémité foncée du gradient.
- **Iris-text (`#a78bfa`)** — liens. `text-iris-text`.
- **Iris-tint (`#c4b5fd`)** — code inline, valeurs. `text-iris-tint`.
- Gradient logo : `bg-gradient-to-br from-iris-bright to-iris-deep`.

### États du pipeline forge

Chaque colonne / statut a une couleur de point dédiée (`bg-st-*` / `text-st-*`) :

- **Brief (`#a1a1aa`)** — gris
- **Pitch (`#f59e0b`)** — ambre
- **Plan (`#3b82f6`)** — bleu
- **Review (`#8b5cf6`)** — violet
- **Report (`#14b8a6`)** — teal
- **Done (`#22c55e`)** — vert

### Sémantique produit

- **Urgent (`#fb923c`)** — badge urgent (carré arrondi, « ! »). `bg-urgent` / `text-urgent`.
- **Positive (`#86efac`)** — valeurs positives. `text-positive`.

## Composants

- **Card** : `bg-raised border border-line-strong rounded-[9px]`, padding 10–11px, survol `border-line-hover`.
- **Pill de statut** : fond translucide + texte clair (ex. `bg-iris/15 text-iris-tint`), `rounded-md` 6px.
- **Bouton primaire** : `bg-iris text-white hover:bg-iris-bright`, `rounded-md` 7–8px, hauteur 28–32px.
- **Bouton secondaire** : `border border-line-strong text-ink-dim hover:bg-raised`.
- **Input** : `bg-input border border-line-strong rounded-[9px]`, texte mono pour les valeurs techniques.
- **Barre de progression** : track `bg-line-strong` 5px, fill `bg-iris`, `rounded-full`.
- **Badge urgent** : carré `bg-urgent` 15px, texte `#1c1917` gras.

## Rendu markdown

Le rendu des fichiers projet (`vision.md`, `stack.md`) et des livrables de story
(`pitch.md`, `plan.md`, `report.md`) utilise la classe utilitaire **`.md-content`**
(définie dans `app.css`) : titres `ink-bright`, corps `ink-soft`, `code` sur fond `input`
en `iris-tint`, `blockquote` bordée d'iris. Largeur de lecture ~64ch.

## Où vit le thème

- **`assets/styles/app.css`** — bloc `@theme` : source de vérité de tous les tokens.
  Les tokens sémantiques du kit Flowbite (`--color-brand`, `--color-heading`,
  `--color-neutral-*`…) sont remappés sur la palette Nova, donc modifier une variable
  re-thème l'ensemble du kit.
- **`.dark` posé sur `<html>`** — garde les variantes `dark:` alignées sur Midnight.
- Midnight est le **thème par défaut** (pas de mode clair).
