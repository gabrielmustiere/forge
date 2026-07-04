# ADR-0001 — Stack front de Forge Board : Paper + Tailwind 4 + Flowbite 4 + UX Toolkit

- **Statut** : accepted
- **Date** : 2026-07-04
- **Déciders** : @gabrielmustiere
- **Story liée** : —
- **Origine** : décision héritée du socle Symfony de démarrage (choix d'origine 2026-05-26), réactée pour l'application Forge Board.

## Contexte

Forge Board est une application Symfony 8. Son front doit livrer "out of the box" un look pro, des composants interactifs (drawer, toast, datepicker, dropdown) et un theming centralisé, sans repartir d'une feuille blanche CSS pour chaque écran.

La contrainte forte est de rester **aligné avec la philosophie Symfony UX** (Twig + Stimulus + Turbo, hotwire-style) — pas de framework JS lourd (React/Vue) qui doublerait la couche de rendu et romprait le modèle mental Symfony. En parallèle, un design system documenté ("Paper", cf. `DESIGN.md`) est câblé au CSS et aux composants.

Le socle apporte un mécanisme de composants Twig réutilisables avec variants (`html_cva`) et fusion intelligente de classes Tailwind (`tailwind_merge`), via UX Toolkit + `tales-from-a-dev/twig-tailwind-extra` + un composant de référence `templates/components/Button.html.twig`. L'ADR grave le choix global pour Forge Board.

## Decision drivers

- **Productivité immédiate** — construire un écran complet en assemblant des composants prêts, sans phase "je refais une CSS".
- **Alignement Symfony UX / Twig** — pas de framework JS, tout en Twig + Stimulus, pour préserver la cohérence avec la stack et le modèle mental Symfony.
- **Theming centralisé** — les tokens (couleurs, typo, radius) vivent à un seul endroit (`@theme` dans `assets/styles/app.css`) pour repeindre l'identité sans toucher aux composants.

## Options considérées

### Option A — Paper + Tailwind 4 + Flowbite 4 + UX Toolkit + Twig Tailwind Extra (retenue)

Empilement de quatre briques complémentaires :

- **Tailwind CSS 4** pour les utilitaires + theming par variables CSS (`@theme`, `@source`, `@custom-variant dark`).
- **Flowbite 4** pour les composants JS interactifs (drawer sidebar, dropdown, toast, datepicker) via `importmap`, sans framework JS.
- **Symfony UX Toolkit** (`symfony/ux-toolkit`) pour la syntaxe `<twig:Button>` / `<twig:ux:icon>` et l'écosystème de composants Twig réutilisables.
- **`tales-from-a-dev/twig-tailwind-extra`** pour `html_cva` (variants typés) et `tailwind_merge` (fusion de classes utilisateur sans collisions).
- **Design system Paper** documenté dans `DESIGN.md` (Roboto / Montserrat / PT Mono, palette monochrome + accent violet).

- Aligne avec **Productivité immédiate** : oui — drawer/toast/datepicker prêts, composants Twig versionnés (`<twig:Button variant="brand">`).
- Aligne avec **Alignement Symfony UX** : oui — Flowbite est piloté côté HTML par data-attributes (compatible Turbo), UX Toolkit est officiel Symfony, aucun framework JS.
- Aligne avec **Theming centralisé** : oui — toutes les couleurs/typo passent par les variables `@theme`, les composants consomment des tokens (`bg-brand`, `text-heading`), changer d'identité = remplacer un bloc de variables.
- Coût / trade-off : empilement de 4 dépendances front à maintenir + courbe d'apprentissage de `html_cva`.

### Option B — Tailwind Plus + Stimulus pur

Utiliser **Tailwind Plus** (ex Tailwind UI, composants HTML/JS officiels Tailwind Labs, payants) et piloter les interactions avec Stimulus seul, sans Flowbite ni UX Toolkit.

- Aligne avec **Productivité immédiate** : partiellement — les composants Tailwind Plus sont fournis en HTML brut à copier/coller, pas en composants Twig packagés. Chaque écran doit re-coller et re-styler.
- Aligne avec **Alignement Symfony UX** : oui — Stimulus fait déjà partie de la stack.
- Aligne avec **Theming centralisé** : partiel — Tailwind Plus suit Tailwind, mais les composants livrés ont leurs propres conventions de classes, ce qui frotte avec un design system custom comme Paper.
- Coût / trade-off : licence payante par développeur, pas de versioning npm/composer des composants (copier/coller), réécriture manuelle des comportements JS que Flowbite donne gratuitement (datepicker notamment).

## Décision

**Option A retenue.**

Tailwind Plus + Stimulus pur (Option B) répond à *Alignement Symfony UX* mais échoue sur *Productivité immédiate* : ce qui compte n'est pas de *pouvoir* construire les composants, mais de *partir avec* des composants packagés et versionnés (composer/importmap), réutilisables d'un écran à l'autre. Le modèle copier/coller de Tailwind Plus est antinomique avec ce besoin.

Le coût accepté — empilement de 4 dépendances + dépendance à Flowbite (projet indépendant non aligné sur le cycle Symfony) — est jugé proportionné parce que (a) chaque brique remplit un rôle distinct sans chevauchement, (b) Flowbite est piloté en HTML/data-attributes, donc remplaçable composant par composant si Forge Board devait diverger.

## Conséquences

**Positives**

- L'application part avec un écran d'accueil professionnel, une sidebar mobile, des toasts de flash et un thème sombre fonctionnel sans écriture de CSS.
- Les composants Twig (`<twig:Button>`, à venir : Badge, Card, Input, Alert) sont versionnés dans le repo.
- Le theming Paper est centralisé dans `assets/styles/app.css` — repeindre l'identité = remplacer un bloc de variables `@theme`.
- `html_cva` apporte une syntaxe typée pour les variants des composants Twig, contrôlée par les `@prop` du fichier.

**Négatives / coûts assumés**

- **Empilement de 4 dépendances front** (Tailwind, Flowbite, UX Toolkit, Twig Tailwind Extra) : surface de maintenance augmentée et risque de divergence entre les couches (ex: un upgrade Tailwind majeur peut casser Flowbite ou les classes générées par `html_cva`).
- **Dépendance à Flowbite** : projet indépendant, son cycle de release ne suit pas Symfony. Risque d'incompatibilité sur les majeures futures de Tailwind ou de Symfony UX — à surveiller à chaque upgrade.
- Courbe d'apprentissage : comprendre le pattern `html_cva` (variants / sizes / shapes) pour créer de nouveaux composants Twig sans dupliquer la logique.

**Note DA** — Une direction artistique plus moderne, propre à Forge Board, est un chantier à venir : elle se posera **par-dessus** ce socle (retravail des tokens `@theme` et des composants), sans nécessairement remettre en cause le choix des briques techniques.

## Links

- Design system : `DESIGN.md` (front-matter Paper)
- Theming : `assets/styles/app.css` (variables `@theme` + mode sombre via `.dark`)
- Composant de référence : `templates/components/Button.html.twig` (pattern `html_cva` + `tailwind_merge`)
- Références externes :
  - Flowbite — https://flowbite.com/
  - Symfony UX Toolkit — https://symfony.com/bundles/ux-toolkit/current/index.html
  - `tales-from-a-dev/twig-tailwind-extra` — https://github.com/tales-from-a-dev/twig-tailwind-extra
