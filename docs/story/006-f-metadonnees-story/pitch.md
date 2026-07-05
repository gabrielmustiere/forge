# Enrichir chaque story de métadonnées lisibles par le Board

> Chaque story forge porte désormais un bloc de métadonnées structuré (dates, tags, changelog consolidé, livraison) **écrit par les skills** dans un fichier du dossier de story. Le Board le lit pour afficher des cartes plus riches (vrai titre, âge, dernière activité, tags, badge de livraison) et permettre de **filtrer par tag** et **trier par activité récente** — sans jamais rien saisir ni intégrer git côté app.

## Contexte

Le socle du Board est livré : il scanne `docs/story/` d'un repo, déduit la colonne de chaque story depuis les fichiers présents, et rend un kanban. Mais une carte ne sait presque rien d'elle-même : au chargement, le builder ne lit que les **noms de fichiers** de chaque story (`StoryFolder::files()`), jamais leur contenu — le contenu n'est lu qu'à l'ouverture d'un document dans le drawer. Résultat : une carte affiche un slug technique, sans date, sans étiquette, sans historique, sans lien vers ce qui l'a livrée.

Or l'information *existe déjà*, mais éparpillée et non exploitable : des dates et des tables de changelog dorment en pied de `pitch.md`/`plan.md`, le track se devine du nom de dossier, la release qui a livré une story n'est reliée nulle part. Pour savoir « quand cette story a-t-elle bougé pour la dernière fois ? » ou « dans quelle version est-elle partie ? », il faut rouvrir les fichiers un par un — exactement l'exploration manuelle que le Board est censé supprimer.

Sans métadonnées, le Board reste un affichage de positions : il montre *où* en est une story sur le pipeline, jamais *ce qu'elle est* ni *son histoire*. Le filtrage et le tri, attendus dès l'horizon 1 an de la vision, sont impossibles faute de données à exploiter.

## Alignement vision

- **Problème adressé** : approfondit le problème central (« l'invisibilité de l'avancement des stories ») — on passe de « quelle colonne ? » à « quelle histoire ? » sans rouvrir les dossiers.
- **Audience servie** : l'utilisateur principal (dev / PO solo), directement — se resituer plus vite et plus finement.
- **Principes respectés** : `#1 lecture seule` (l'app ne fait que lire le fichier metadata, jamais l'écrire), `#2 état déduit jamais saisi` (les métadonnées viennent des fichiers produits par les skills ; la colonne reste déduite, indépendante du metadata), `#3 fidélité` (règle de maintenance de `updated` par chaque skill + validation stricte à la lecture, sinon dégradation), `#4 zéro friction` (lecture du metadata en un appel groupé, chargement instantané non-négociable).
- **Principe tendu → résolu** : « intégration git profonde » est un anti-objectif. On le respecte car le lien release/commit est une **chaîne écrite par le skill de livraison** dans le fichier metadata, pas une intégration git côté app.
- **Impact North Star** (« temps pour se resituer ») : le fait bouger vers le bas — la carte répond à plus de questions sans ouvrir un seul document.
- **Portée du standard** : la convention metadata part chez **tous** les utilisateurs forge (le fichier est produit par le plugin distribué). Elle renforce l'unfair advantage « connaissance intime de la convention forge », mais impose de figer le schéma correctement du premier coup.

## Utilisateurs concernés

- **Développeur / PO solo (utilisateur principal du Board)** — lit des cartes enrichies (vrai titre, âge, dernière activité, tags, badge de livraison), filtre par tag, trie par activité récente.
- **Le même, côté skills forge** — au fil du workflow, se voit proposer des tags à valider et voit ses métadonnées maintenues automatiquement (dates, changelog, livraison) sans double-saisie.
- **Futurs utilisateurs de la marketplace forge** — héritent de la convention metadata dans leurs propres stories, qu'ils utilisent le Board ou non (le fichier reste un artefact lisible du dossier).
- **Utilisateurs sans repo migré** — aucun changement bloquant : une story sans metadata s'affiche comme aujourd'hui (dégradation gracieuse).

## User Stories

- En tant que **dev pilotant mon board**, je veux voir sur chaque carte son **vrai titre**, son **âge** et sa **dernière activité** afin de repérer d'un coup d'œil ce qui est récent, ce qui stagne et ce qui est frais.
- En tant que **dev**, je veux **filtrer les cartes par tag** afin d'isoler un thème (ex. `paiement`, `dette`) à travers le pipeline.
- En tant que **dev**, je veux **trier les cartes par date de mise à jour** afin de faire remonter ce qui a bougé récemment.
- En tant que **dev**, je veux voir sur une story livrée **dans quelle release et quel commit** elle est partie afin de relier l'intention au code livré sans fouiller git.
- En tant que **dev**, je veux consulter le **changelog consolidé** d'une story (une seule timeline) afin de comprendre son histoire sans ouvrir quatre fichiers.
- En tant que **dev en cours de cadrage**, je veux que le skill me **propose des tags** que je **valide** afin d'étiqueter sans inventer un vocabulaire incohérent.
- En tant que **dev sur un repo pas encore migré**, je veux que mes stories **sans metadata** s'affichent quand même proprement afin de ne subir aucune régression.

## Règles métier

Le **contrat de métadonnées** (fichier produit par les skills, lu par l'app) :

1. **Source de vérité unique = un fichier de métadonnées par story**, produit par les skills dans le dossier `docs/story/NNN-<f|r|t>-<slug>/`. L'app ne l'écrit jamais, ne le modifie jamais : lecture seule.
2. Le fichier porte au minimum : **`title`** (le H1 réel de la story), **`created`** (date de création, figée), **`updated`** (date de dernière activité), **`tags`** (liste d'étiquettes kebab-case), **`changelog`** (timeline consolidée), **`delivery`** (release + commit de livraison, quand la story est livrée).
3. **La colonne du pipeline n'est JAMAIS dans le metadata.** L'étape reste déduite des fichiers présents par le moteur de mapping (principe #2). Le fichier de métadonnées ne doit **pas** être compté comme un document de pipeline : `StoryStageMapper` l'ignore totalement — sa présence ne change aucune colonne.
4. **`created`** est écrit une seule fois, par le skill qui crée la story (`feature-interview` ou `feature-pitch` pour un track feature ; `refactor-plan` / `tech-plan` pour les tracks `r`/`t`), et n'est plus jamais modifié.
5. **`updated`** est rebougé par **chaque skill qui écrit dans le dossier de la story** (`feature-pitch`, `feature-plan`, `refactor-plan`, `tech-plan`, les trois `*-implem`, `report`, `sync`/`report-and-sync`, `adr`, `estimate`, `review`). La règle est portée par une **référence partagée** du plugin, pas par la mémoire de chaque skill, pour garantir la fidélité. Les skills opérant à la racine `docs/` (`vision`, `product-backlog`, `stack`, `claude-md`, `help`) ne sont pas concernés.
6. **`tags` : proposés par le skill, validés par l'utilisateur.** Le skill de cadrage suggère des tags (déduits du contenu) ; l'utilisateur tranche à la rédaction. Format kebab-case. Objectif anti-dérive : ne pas générer d'étiquettes non validées.
7. **`changelog` = source unique.** La timeline vit dans le fichier metadata ; chaque skill concerné y **append** une entrée (date, type, description). Les tables de changelog en pied de `pitch.md`/`plan.md` sont **abandonnées** au profit de cette source unique.
8. **`delivery` (release + commit)** est écrit par les skills de livraison : **`commit`** enregistre le SHA du commit de clôture, **`release`** enregistre le tag de version (ex. `v4.3.0`). La release peut être renseignée **plus tard** que le commit (le tag arrive parfois après la livraison) : `delivery` tolère un commit présent sans release.
9. **Dégradation gracieuse (app)** : une story **sans** fichier metadata, ou avec un fichier **malformé/invalide**, s'affiche sans erreur — la carte retombe sur le **slug humanisé** (au lieu du `title`), sans tags, sans dates, sans badge de livraison. Mieux vaut pas de donnée qu'une donnée fausse (principe #3).
10. **Chargement instantané non-négociable (app)** : au chargement du board, le metadata de **toutes** les stories est lu en **un seul appel groupé** (ou via cache), jamais un appel par carte. La North Star (vitesse de lecture) prime : la richesse des cartes ne doit pas ralentir le board.
11. **Filtre & tri (app)** : le board permet de filtrer les cartes par **tag** et de trier par **date de mise à jour**. Le filtrage n'affecte que l'affichage, jamais l'état déduit — y compris les **compteurs de colonne**, qui se recalent sur les cartes visibles après filtrage et reviennent au total serveur quand le filtre est retiré.

## Critères d'acceptation

- [ ] Un fichier de métadonnées est produit/maintenu dans le dossier d'une story par les skills concernés, avec `title`, `created`, `updated`, `tags`, `changelog`, `delivery`.
- [ ] `feature-pitch` (et les skills de création de `r`/`t`) écrit `created` + `title` + tags validés + première entrée de changelog à la création.
- [ ] Chaque skill listé en règle 5 rebooge `updated` et append une entrée de changelog quand il écrit dans le dossier.
- [ ] `commit` écrit le SHA de clôture et `release` écrit le tag dans `delivery` ; une story livrée non taguée a un commit sans release, sans erreur.
- [ ] Les tables de changelog en pied de `pitch.md`/`plan.md` ne sont plus produites ; la timeline vit uniquement dans le metadata.
- [ ] Le Board lit le metadata de toutes les stories en **un seul appel groupé** (vérifiable : nombre d'appels réseau indépendant du nombre de stories).
- [ ] La carte affiche le vrai titre (`title`) ; à défaut de metadata, elle retombe sur le slug humanisé.
- [ ] La carte affiche l'âge / dernière activité, les tags, et un badge de livraison si `delivery` présent.
- [ ] Le drawer expose le changelog consolidé de la story.
- [ ] Le board permet de filtrer par tag et de trier par date de mise à jour.
- [ ] Une story sans metadata, ou avec un metadata invalide, s'affiche sans erreur (dégradation gracieuse).
- [ ] `StoryStageMapper` ignore le fichier metadata : sa présence ne modifie aucune colonne déduite.
- [ ] Les 5 stories existantes (`001`→`005`) reçoivent un metadata rétroactif (backfill) pour que le board de référence ne soit pas à moitié vide.
- [ ] La convention est documentée pour tous les utilisateurs forge (référence partagée du plugin).

## Hors scope

- **Édition des métadonnées depuis l'app** : l'app reste viz-only ; toute écriture passe par les skills / le fichier.
- **Intégration git côté app** (lire commits/PR/CI via API) : le lien release/commit est une chaîne écrite par le skill, jamais résolue par l'app.
- **`stage`/`status` dans le metadata** : la colonne reste déduite des fichiers, jamais stockée (préserve le principe #2, évite la double-vérité).
- **Vocabulaire de tags imposé / taxonomie centralisée** : au MVP, pas de liste blanche verrouillée ; la validation utilisateur est le garde-fou anti-dérive. Une taxonomie formelle est une évolution ultérieure.
- **Filtres avancés** (par track, par date arbitraire, multi-critères, recherche plein texte) : au MVP, seulement filtre par tag + tri par `updated`.
- **Métadonnées de sous-tâches / d'estimation détaillée** au-delà de ce qui est listé : `estimate.md` reste un document à part, non fusionné dans le bloc metadata (au-delà d'une éventuelle entrée de changelog).

## Impacts transverses

- **Multi-tenant** : non (outil mono-utilisateur).
- **Multi-thème** : non.
- **i18n / traduction** : marginal — quelques libellés UI (filtre, tri, badge « livré en… »). Les données metadata elles-mêmes ne sont pas traduites.
- **API** : non (pas d'endpoint exposé ; lecture interne du repo distant).
- **Permissions** : inchangé (accès local unique).
- **Emails / notifications** : non.
- **Migration de données** : pas de migration de schéma BDD (les métadonnées vivent dans les fichiers, pas en base). En revanche, **backfill fichier** : générer le metadata des 5 stories existantes.
- **Comportement par défaut** : une story / un repo sans metadata s'affiche comme aujourd'hui (slug, pas de tags/dates) — aucune régression.

## Notes pour le plan technique

_Pistes brutes — à trancher/concevoir en `/forge:feature-plan`, ne pas figer ici._

**Format du fichier (à trancher au plan)** : `metadata.json` (recommandé) vs frontmatter YAML. Arguments pour `metadata.json` : track-agnostique (le frontmatter devrait se poser sur `pitch.md`, absent des tracks `r`/`t`), structuré, un seul objet à lire, zéro parsing markdown. Nom pressenti : `metadata.json` à la racine du dossier de story.

**Schéma pressenti** (indicatif) : `{ title, created, updated, tags: [], changelog: [{ date, type, description }], delivery: { release, commit } }`. Formats de date à trancher (probablement `YYYY-MM-DD`, aligné sur les changelogs existants).

**Côté plugin (skills)** :
- Créer une **référence partagée** (ex. `plugins/forge/references/story-metadata.md`) décrivant le schéma + la procédure d'écriture/mise à jour ; chaque `SKILL.md` concerné l'invoque (comme `_detection.md` est invoqué aujourd'hui).
- Points d'écriture : `create` (feature-pitch / *-plan) → `created`+`title`+tags+1re entrée ; toute passe → `updated`+entrée changelog ; `commit`→`delivery.commit` ; `release`→`delivery.release`.
- Retirer la production des tables de changelog en pied de `pitch.md`/`plan.md` (skill `sync` et rédacteurs).
- Backfill : one-shot pour générer le metadata des stories `001`→`005` (script ou passe manuelle assistée).

**Côté app (Symfony)** :
- **Lecture groupée** : le connecteur (`GitHubRepositoryReader` / `RepositoryReaderInterface`) doit exposer une lecture du metadata de toutes les stories en **un appel** (batch tree+contenu, ou endpoint GraphQL GitHub, ou cache court) — clé de la règle 10.
- Nouveau value object `StoryMetadata` (immuable) + parsing/validation tolérante (JSON tiers → dégrader si absent/malformé).
- Étendre `StoryCard` (title, âge/updated, tags, delivery) ; le builder (`ProjectBoardBuilder`) hydrate depuis le metadata lu.
- `StoryStageMapper` : exclure explicitement le fichier metadata du calcul d'étape.
- Rendu : carte enrichie + drawer (changelog consolidé) ; filtre par tag + tri `updated` (Live Component / Stimulus, côté serveur ou client à trancher).

## Questions ouvertes

- **Format du fichier** : `metadata.json` vs frontmatter. → recommandation forte `metadata.json` (track-agnostique, un seul fetch), à trancher au plan.
- **Mécanisme de lecture groupée** : batch d'appels, GraphQL GitHub, ou cache court côté app — quelle voie garantit « instantané » sans complexité excessive ? À concevoir au plan.
- **Schéma exact & formats** : clés précises, format de date, forme d'une entrée de changelog et d'un `delivery`. À figer au plan (schéma embarqué chez tous les utilisateurs → décision durable).
- **Backfill** : script one-shot vs passe manuelle assistée pour les 5 stories existantes.
- **Release en différé** : comment `release` est renseignée quand le tag arrive après la livraison (relance de `release` qui réédite le metadata ?). À préciser au plan.
- **Vocabulaire de tags** : rester en libre-validé au MVP ; une référence de tags par projet est une évolution possible (hors scope MVP).

<!-- Changelog : la timeline consolidée vit désormais dans `metadata.json` (règle métier 7),
     plus dans une table en pied de fichier. -->

