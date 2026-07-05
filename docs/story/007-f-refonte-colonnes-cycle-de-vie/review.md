# Review — Aligner les colonnes du board sur le cycle de vie réel d'une story forge

> Date : 2026-07-05
> Stack : symfony
> Périmètre : working tree (13 fichiers modifiés, +351/−138) — story 007 + travail non commité des tours précédents (popover filtre, rétrécissement colonnes vides)
> Référence d'intention : `docs/story/007-f-refonte-colonnes-cycle-de-vie/plan.md` + `pitch.md`

## Bloquants

- _(aucun)_

## Importants

- [ ] **[SCOPE] Le diff mêle trois chantiers distincts** — `assets/controllers/board_filter_controller.js`, `assets/styles/app.css`, `templates/project/_board.html.twig` — Le working tree agrège (1) la refonte des colonnes de cycle de vie (story 007), (2) le popover de filtre recherchable et (3) le rétrécissement des colonnes vides — ces deux derniers étant du travail non commité de tours antérieurs, hors périmètre du plan 007. Aucun défaut de code, mais le `/commit` doit **découper** en commits séparés pour garder un historique lisible et un scope 007 propre.

## Mineurs

- [x] **[A11Y] Popover sans état ARIA** — `templates/project/_board.html.twig` + `assets/controllers/board_filter_controller.js` — **corrigé** : le bouton expose `aria-haspopup`, `aria-controls="board-tag-menu"` et un `aria-expanded` piloté par `openMenu`/`closeMenu`.
- [~] **[DOC] Slugs des stories fake** — `src/Service/Github/FakeRepositoryCatalog.php:93-95` — **retiré après vérification** : les slugs `cadrage`/`planifie`/`review` reflètent le *sujet* de chaque story (titres « Cadrer la connexion », « Planifier le mapping », « Revue du normaliseur »), pas l'ancienne colonne. Le recoupement est fortuit ; renommer casserait 16 références de test (dont les routes doc `/story/005-r-review/doc/…`) pour réduire la clarté. Non-finding.

## Points positifs

- **Point de vérité unique respecté** : la logique métier ne change qu'à un seul endroit (`StoryStageMapper::PRECEDENCE`) ; le reste (Board, template, couleurs) n'est que répercussion mécanique — exactement l'approche du plan.
- **Zéro dette résiduelle sur le renommage** : le grep de contrôle prévu au plan (`Cadrage|Planifie|::Review|'cadrage'|'planifie'`) ne remonte plus aucune référence d'enum — seul un id de fixture (`010-f-planifie`) subsiste, cosmétique.
- **Une seule source de fixtures** : `StubRepositoryReader` (functional) et `DevFakeRepositoryReader` (dev/E2E) délèguent tous deux à `FakeRepositoryCatalog::boardTree()` — l'ajout de `012-f-idee` couvre les deux niveaux sans duplication.
- **Popover robuste** : le piège du `stopPropagation` (bouton détaché par `renderMenu` → faux clic-extérieur) est correctement identifié et documenté ; le listener document est bien retiré en `disconnect()`.
- **Couverture de test alignée** : mapping (`brief→Idee`), `label()`/`isOnPipeline()` des 6 cases, répartition sur 5 colonnes, refacto jamais en Besoin, et E2E ouvrant le popover avant filtrage — critères d'acceptation du pitch tous couverts.

## Verdict

- Bloquants restants : 0 / 0
- Importants restants : 1 / 1 (consigne de commit, pas un défaut de code)
- Mineurs restants : 0 / 2 (A11Y corrigé, DOC retiré)
- Statut : **READY TO COMMIT sous réserve de découper le commit**

L'important [SCOPE] n'est pas un défaut de code mais une consigne pour `/commit` : isoler la story 007 des deux features non commitées. Aucun correctif de code requis.

## Hors review (à vérifier en environnement réel)

- Rendu visuel Nova des 5 colonnes (Idée gris `st-brief`, Besoin ambre, Cadré bleu, Implémenté violet, Livré teal) et du bandeau « À vérifier » recoloré en rose `st-flag` — déjà validé au navigateur sur données enao réelles pendant l'implem (story `029-f-documents-zone` passée de « À vérifier » à « Idée »).
- Rétrécissement dynamique d'une colonne vidée par le filtre (`w-80 → w-44`) — comportement JS non couvert par un test E2E (hors scope 007).
