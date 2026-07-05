# Afficher le kanban d'un projet — colonnes, cartes, ouverture de document

> L'écran qui projette les stories d'un repo forge en cartes le long du pipeline unifié : quatre colonnes (Cadrage → Planifié → Review → Livré) plus un bandeau « À vérifier », des cartes qui portent leur badge de track et leur titre, et l'ouverture d'un document en un clic. C'est la page qui rend enfin visible « où en est le projet X ».

## Contexte

Tout le socle est livré mais reste invisible. `002-f-gestion-projets` permet de déclarer et lister des projets ; `003-f-connecteur-github-lecture` sait lire à distance l'arborescence **et le contenu** des fichiers de `docs/story/` d'un repo GitHub ; `004-f-mapping-etapes` calcule, pour chaque story, sa colonne sur le pipeline unifié (Cadrage / Planifié / Review / Livré) ou son classement dans la voie « À vérifier », ainsi que son track (`f`/`r`/`t`). Mais rien de tout cela n'est **montré** : l'utilisateur a des projets déclarés et un moteur qui sait les positionner, sans aucun écran pour le lire.

C'est le trou que cette feature comble — et c'est **l'écran du North Star**. Sans lui, la promesse centrale de la vision (« passer de plusieurs minutes d'exploration manuelle à quelques secondes de lecture ») n'est jamais tenue : le produit reste un back-office muet qui déclare des projets sans jamais afficher leur avancement. Cette feature est l'aboutissement du parcours P1 (« se resituer sur un projet ») et la sortie du parcours P2 (« déclarer un projet », dont la dernière étape est C4.1). Sans elle, tout le travail amont ne produit aucune valeur observable.

## Alignement vision

- **Problème adressé** : attaque frontalement le problème central — « l'invisibilité de l'avancement des stories ». C'est le maillon qui rend la projection lisible d'un coup d'œil.
- **Audience servie** : l'utilisateur principal (développeur / product owner solo), pour son usage le plus fréquent (plusieurs fois par jour en phase active).
- **Principes respectés** : « Lecture seule » (l'écran n'affiche, ne modifie rien — ni le repo, ni `docs/story/`) ; « État déduit, jamais saisi » (les positions viennent du moteur `004`, aucune saisie) ; « Sync fidèle avant tout » (le scan est live à l'ouverture pour ne jamais montrer un état périmé) ; « Zéro friction d'ouverture » (on ouvre un projet, on voit son tableau).
- **Hypothèse testée** : #4 (« un pipeline unifié reste-t-il lisible malgré des tracks hétérogènes ? ») — c'est le premier écran où l'on peut réellement en juger, tracks mélangés sur les mêmes colonnes avec le track porté par un badge.
- **Impact North Star** : direct et décisif — c'est l'écran qui matérialise le « temps pour se resituer ». Sans lui, la métrique n'est pas mesurable.

## Utilisateurs concernés

- **Utilisateur local connecté** (l'unique utilisateur — outil mono-utilisateur, cf. anti-objectif vision « backend partagé ») — bénéficiaire direct : c'est le premier écran qui lui rend l'avancement lisible. Toute la feature vit derrière le firewall de `login`.
- **Aucun autre rôle** — pas de nouveau rôle ni voter ; périmètre de permissions inchangé.

## User Stories

- En tant qu'**utilisateur connecté**, je veux **ouvrir un projet déclaré et voir ses stories réparties en colonnes** le long du pipeline unifié, afin de savoir en quelques secondes où en est chaque chantier sans ouvrir les dossiers à la main.
- En tant qu'**utilisateur connecté**, je veux **lire l'identité d'une carte d'un coup d'œil** (badge de track feature/refacto/tech, identifiant `NNN-slug`, titre), afin d'identifier une story sans la déplier.
- En tant qu'**utilisateur connecté**, je veux **voir les stories indécidables regroupées à part** (bandeau « À vérifier » sous les colonnes) plutôt que rangées au hasard, afin de ne jamais être induit en erreur par une position inventée.
- En tant qu'**utilisateur connecté**, je veux **ouvrir un document d'une story depuis sa carte** (pitch, plan, review, report) et le lire dans l'app, afin d'aller au détail sans quitter le tableau ni fouiller le repo.
- En tant qu'**utilisateur connecté**, je veux **que le tableau reflète l'état réel du repo à l'ouverture**, afin de faire confiance à ce que je lis plutôt que de retourner vérifier à la main.

## Règles métier

1. **Pipeline unifié à quatre colonnes ordonnées** : Cadrage → Planifié → Review → Livré, dans cet ordre de gauche à droite, communes aux trois tracks. La colonne de chaque story est celle fournie par le moteur `004` — l'écran ne recalcule aucune position.
2. **Voie « À vérifier » en bandeau** : les stories classées « À vérifier » par `004` (aucun fichier de pipeline reconnu) sont regroupées dans un **bandeau distinct, sous les quatre colonnes**, visuellement séparé du pipeline pour ne jamais se lire comme une étape. Si aucune story n'est « À vérifier », le bandeau n'apparaît pas.
3. **Identité de carte** : chaque carte affiche trois éléments — un **badge de track** (feature / refacto / tech, déduit de la lettre `f`/`r`/`t` de l'identifiant), l'**identifiant** `NNN-slug`, et un **titre**.
4. **Titre de carte** : la carte affiche le **slug humanisé** (`mapping-etapes` → « Mapping etapes »), sans lecture de contenu — le tableau reste rapide et robuste au chargement. Le **titre réel (`# H1`)** de la story n'est pas lu sur la carte mais apparaît naturellement en tête du document rendu **dans le drawer** à son ouverture. Le titre ne modifie jamais la position de la carte (déduite des seuls noms de fichiers par `004`). _(Arbitré au plan : lire le H1 de chaque story au chargement imposerait un appel réseau par carte ; on privilégie la vitesse d'ouverture et on montre le vrai titre au moment du détail.)_
5. **Ordre des cartes dans une colonne** : par identifiant `NNN` **décroissant** (la story la plus récente en haut), afin de servir le réflexe « se resituer sur ce qui bouge ». Ordre déterministe, identique à chaque rendu.
6. **Ouverture d'un document** : au clic sur une carte, ouvrir ses documents **dans l'app**, dans un **drawer latéral** (panneau glissant qui garde le tableau visible en fond), sans quitter la page. Le drawer présente **toujours d'abord la liste des documents présents** (dans l'ordre de précédence `report` > `review` > `plan` > `pitch`, puis transversaux), même si un seul document existe — comportement uniforme ; le contenu markdown du document choisi s'affiche ensuite. Aucune modification possible — lecture seule stricte.
7. **Compteur par colonne** : chaque colonne affiche en tête un **compteur du nombre de cartes** qu'elle contient (ex. « Livré (7) »), pour jauger la répartition d'un coup d'œil. Le bandeau « À vérifier » affiche de même son compte.
8. **Peuplement à l'ouverture** : à chaque ouverture du tableau d'un projet, l'app **rescanne le repo (`003`) et recalcule les positions (`004`) à la volée** — le tableau affiché reflète l'état réel du repo au moment de l'ouverture, jamais un état figé.
9. **Projet sans story** : un projet éligible forge mais sans aucune story affiche un **message d'état vide** explicite (pipeline présent mais vide), jamais une page cassée.
10. **Échec de scan** : si le scan live échoue (repo injoignable, token invalide, erreur réseau), l'écran affiche un **garde-fou minimal** — un message brut signalant que le tableau n'a pas pu être chargé — sans planter. Le signalement d'erreur riche et actionnable (diagnostic, réparation) relève de `sync-manuelle` (C3.4) et n'est **pas** livré ici.
11. **Lecture seule absolue** : l'écran n'écrit jamais dans le repo ni dans `docs/story/` ; il ne propose aucune action d'édition, de déplacement de carte, ni de déclenchement de skill (anti-objectif vision « éditer / agir depuis l'app »).

## Critères d'acceptation

- [ ] Ouvrir un projet déclaré et éligible affiche un tableau à quatre colonnes ordonnées (Cadrage, Planifié, Review, Livré) avec chaque story positionnée dans la colonne calculée par `004`.
- [ ] Chaque carte affiche son badge de track (feature / refacto / tech), son identifiant `NNN-slug` et le slug humanisé en titre.
- [ ] Le titre réel `# H1` de la story apparaît en tête du document rendu dans le drawer (pas sur la carte).
- [ ] Les stories « À vérifier » (aucun fichier de pipeline reconnu) apparaissent dans un bandeau distinct sous les colonnes, et non dans une colonne ; ce bandeau est absent si aucune story n'est concernée.
- [ ] Une carte `r` ou `t` n'apparaît jamais dans la colonne Cadrage (cohérent avec `004`).
- [ ] Au sein d'une colonne, les cartes sont triées par `NNN` décroissant.
- [ ] Chaque colonne (et le bandeau « À vérifier ») affiche en tête le nombre de cartes qu'elle contient.
- [ ] Cliquer sur une carte ouvre un drawer latéral qui liste d'abord les documents présents (même s'il n'y en a qu'un), puis affiche en markdown le document choisi, le tableau restant visible en fond.
- [ ] Rouvrir le tableau après un changement de fichiers dans le repo reflète le nouvel état (scan live), sans action de rafraîchissement explicite.
- [ ] Un projet éligible sans aucune story affiche un état vide explicite, sans erreur.
- [ ] Un scan qui échoue affiche un message de garde-fou et ne casse pas la page.

## Hors scope

- **Filtrer les cartes par track** (C4.4) : relève de `kanban-filtres-track` (V2). Ici tous les tracks sont affichés ensemble, distingués par le badge.
- **Vue consolidée multi-projets** (C4.5) : relève de `vue-multi-projets` (V2). Ici un tableau = un projet.
- **Bouton de rafraîchissement explicite et signalement d'erreurs riche** (C3.3, C3.4) : relèvent de `sync-manuelle`. Ici le peuplement est un scan live à l'ouverture, avec un simple garde-fou en cas d'échec.
- **Calcul de la position d'une story** : déjà livré par `004-f-mapping-etapes` ; l'écran consomme le résultat, il ne le recalcule pas.
- **Lecture distante / éligibilité forge** : déjà livrées par `003-f-connecteur-github-lecture`.
- **Édition de document, déplacement de carte, déclenchement de skill** : anti-objectif vision (lecture seule) — jamais.
- **Direction artistique moderne** : le socle « Paper » (Flowbite/Tailwind) est utilisé tel quel ; une DA propre au Board est un chantier design distinct (cf. `docs/stack.md`).
- **Colonne « En cours d'implémentation »** : écartée en `004` (l'implémentation ne produit pas de fichier) — non réintroduite ici.

## Impacts transverses

- **Multi-tenant** : non (outil mono-utilisateur).
- **Multi-thème** : non.
- **i18n / traduction** : libellés d'écran en français (colonnes Cadrage / Planifié / Review / Livré, bandeau « À vérifier », badges track, états vides et d'erreur). Pas de contenu multilingue.
- **API** : non (aucune ressource exposée ; l'écran est server-rendered).
- **Permissions** : inchangé — firewall `login` existant, ni rôle ni voter nouveau.
- **Emails / notifications** : non.
- **Migration de données** : à confirmer au plan selon la décision de persistance héritée de `004` (si les positions sont recalculées à la volée → aucune migration ; si un état scanné est stocké → migration). Le scan live à l'ouverture (règle 7) penche vers « aucune persistance nouvelle », à trancher au plan.
- **Comportement par défaut** : c'est le premier écran de valeur du produit ; il devient l'écran principal après ouverture d'un projet.

## Notes pour le plan technique

> Pistes brutes — **ne pas concevoir ici**, à trancher en `/forge:feature-plan`.

- **Composant de rendu** : le tableau kanban est un bon candidat Live Component (Symfony UX) ou une simple page Twig server-rendered ; trancher selon le besoin d'interactivité (ouverture du drawer). Rester dans l'esprit server-rendered de la stack (pas de SPA).
- **Consommation du moteur `004`** : réutiliser tel quel le service de mapping (fichiers → colonne + track + « À vérifier »). L'écran orchestre : projet → `003` (arborescence + contenu) → `004` (positions) → rendu. Ne rien re-parser.
- **Extraction du titre (C4.2)** : lire le `# H1` du document le plus avancé via le contenu déjà accessible par `003`. Définir une petite fonction pure « premier H1 du markdown, sinon null » ; le repli slug humanisé est trivial. Attention au coût : une lecture de contenu par carte — voir si `003` permet un fetch groupé/économe.
- **Rendu markdown du drawer** : choisir la voie de rendu markdown (extension Twig, lib PHP, ou composant) ; lecture seule, pas d'exécution de HTML arbitraire — assainir le rendu (le contenu vient d'un repo tiers).
- **Scan live vs coût réseau** : le peuplement à l'ouverture (règle 7) appelle `003` à chaque affichage ; surveiller la latence sur un repo à quelques dizaines de stories. Un cache court de requête est une optimisation possible mais **hors périmètre fonctionnel** — à noter au plan, pas à concevoir ici.
- **Garde-fou d'échec (règle 9)** : capter proprement l'échec de `003` (injoignable / token invalide) et rendre un état d'erreur minimal ; le diagnostic riche viendra avec `sync-manuelle`.
- **Ouverture d'un document** : route/endpoint de lecture d'un document d'une story (chemin `docs/story/NNN-…/fichier.md`) via `003`, rendu en drawer. Vérifier qu'on ne sert que des fichiers du dossier de story (pas de traversée de chemin).

## Questions ouvertes

- **Rendu du document** : drawer latéral, avec liste des documents toujours affichée d'abord. → tranché : drawer + liste systématique.
- **Ordre des cartes** : `NNN` décroissant (plus récent en haut). → tranché.
- **Compteur par colonne** : affiché en tête de chaque colonne et du bandeau. → tranché : oui.
- **Persistance des positions** : héritée de `004` (recalcul à la volée vs stockage). → tranché au plan : **recalcul à la volée, aucune persistance ni migration** (cf. `plan.md`).
- **Titre de carte** : H1 lu par carte vs slug humanisé. → tranché au plan : **slug sur la carte, H1 dans le drawer** (évite un appel réseau par carte ; règle 4 mise à jour ci-dessus, cf. changelog).

---

## Changelog

| Date       | Type                | Description |
|------------|---------------------|-------------|
| 2026-07-05 | Ajustement (plan)   | Règle 4 et critères de titre revus : le **slug humanisé** est affiché sur la carte (0 appel réseau au chargement) et le **titre réel `# H1`** apparaît dans le drawer. Décidé en `/feature-plan` car lire le H1 par carte imposerait un appel GitHub par story à l'ouverture ; on privilégie la vitesse. Questions ouvertes « persistance » et « titre » tranchées. |
