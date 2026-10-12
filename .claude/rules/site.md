---
paths:
  - "site/**"
---

# Site public (`site/`)

Deux pages HTML statiques publiées sur [forge.mustiere.fr](https://forge.mustiere.fr) : `site/index.html` (vitrine) et `site/docs/index.html` (documentation du plugin). Zéro build, zéro dépendance externe hors Google Fonts.

## Règles

- **CSS et JS sont partagés** (`assets/forge.*`) — ne pas réintroduire de style ou de script inline dans une seule page : les deux pages divergeraient.
- **Charte** : fond `#0a0e12`, accent cyan `#3fd6f2`, Space Grotesk + JetBrains Mono. Les tokens font foi (`:root` dans `forge.css`).
- **Mobile-first** : styles de base pour mobile, `@media (min-width: 861px)` pour le desktop (+ `1024px` pour le layout de la doc).
- **Contraste** : tout texte doit tenir 4.5:1 sur `--surface`. C'est pourquoi `--text-dim` vaut `#7d8f9c` et non le `#5a6b78` d'origine (3.4:1, insuffisant).

La version affichée et les images générées relèvent de la Partie 2 du `CLAUDE.md` racine.
