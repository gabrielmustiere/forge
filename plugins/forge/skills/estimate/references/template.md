# Estimation — <Titre de la story>

> _Estimation de temps « tout compris » produite par `/estimate`. **Du temps, pas un montant** : la conversion en euros (taux horaire, remise) reste à la charge du lecteur._
> Story : `docs/story/<NNN>-<f|r|t>-<slug>/`
> Base d'estimation : <brief.md | pitch.md | plan.md — celui qui a servi> — fiabilité : <grossière | fonctionnelle | affinée>

<!--
guide: Estimation du temps facturable d'une story, toutes phases comprises (pas seulement le code).
RÈGLE D'OR — tout compris : compter cadrage, implem, tests, review, doc, release. Voir method.md. (Workflow solo : pas de phase intégration ni coordination ; release = forfait fixe 30 min.)
DEUX COLONNES : temps de référence (sans IA) et temps réel avec assistant IA — facteur d'accélération PAR PHASE (method.md §2). L'IA n'accélère pas la release (forfait fixe 0,5 h) ; review et conception peu accélérées.
UNITÉ : heures (fractions 0,5 / 0,25). Pas de montant en euros.
BASE = médiane réaliste (le plus probable), pas une borne haute. L'aléa est porté UNE SEULE FOIS, par la marge — padder la base en plus, c'est ~30 % de trop (method.md §4).
Une estimation sur brief ou pitch seul est à RECONFIRMER après le plan — le signaler dans l'en-tête.
Retirer ce bloc et tous les `> _Skill : ..._` avant commit.
-->

## Synthèse

> _Skill : le résultat en deux lignes, lisible d'un coup d'œil. Somme des phases (le plus probable, hors aléa — médiane, pas borne haute) puis total avec marge. Pour une incertitude élevée, donner une fourchette basse/haute plutôt qu'un point._

- **Somme des phases** — référence (sans IA) : <X> h · avec assistant IA : <Xia> h
- **Marge d'incertitude** : <faible +10 % | moyenne +20 % | élevée +35 %> — <raison en une phrase>
- **Total à retenir** — sans IA : **<Y> h** · avec IA : **<Yia> h** _(fourchette **<bas>–<haut> h** si incertitude élevée)_
- **Écart (marge potentielle)** : <Y − Yia> h — la différence entre les deux totaux avec marge.

## Décomposition par phase

> _Skill : une ligne par phase pertinente du track (voir method.md — toutes ne s'appliquent pas à tous les tracks). Chaque chiffre **justifié par un signal** : jamais une durée nue. Colonne « État » pour distinguer ce qui est déjà fait (pitch/plan écrits) de ce qui reste — l'utilisateur arbitre entre facturer le total ou seulement le restant._

| Phase | Réf. (sans IA) | Avec IA | État | Justification (signal) |
|-------|:--------------:|:-------:|------|------------------------|
| Cadrage fonctionnel | <h> | <h·IA> | <déjà fait / reste> | <ex: pitch écrit ; reste 2 h d'ajustements> |
| Conception technique | <h> | <h·IA> | <déjà fait / reste> | <ex: plan détaillé, 8 fichiers identifiés> |
| Implémentation | <h> | <h·IA> | reste | <ex: 6 fichiers à créer dont 1 migration + backfill, impact multi-channel> |
| Tests (auto + QA) | <h> | <h·IA> | reste | <ex: 3 niveaux de test prévus ; refacto → caractérisation amont> |
| Review & corrections | <h> | <h·IA> | reste | <ex: zone sensible, 1 passe de review + reprises> |
| Documentation de clôture | <h> | <h·IA> | reste | <ex: report + sync> |
| Release & déploiement | 0,5 | 0,5 | reste | forfait fixe 30 min (changelog + mise en prod + vérif) |
| **Somme** | **<X> h** | **<Xia> h** | | |

> _Skill : retirer les lignes des phases non pertinentes pour le track (ex: pas de « cadrage fonctionnel » sur une refacto ou une story tech). Ne jamais retirer une phase juste parce qu'elle paraît gratuite — c'est exactement celles-là qu'on sous-facture. La colonne « Avec IA » applique le facteur d'accélération par phase (method.md §2) : fort sur implem/tests/doc ; la release est un forfait fixe de 30 min, identique dans les deux colonnes._

## Signaux de complexité relevés

> _Skill : les éléments concrets, lus dans le pitch/plan/code, qui ont nourri les chiffres. C'est ce qui rend l'estimation défendable face au client. Classer par ce qui pèse le plus._

- <ex: migration avec backfill des commandes existantes — poste de charge à part entière>.
- <ex: impact multi-channel → coût UI et test multiplié>.
- <ex: zone `OrderProcessor` peu testée → modification prudente>.
- <ex: nouveau mécanisme à apprendre → coût d'exploration>.

## Marge d'incertitude — d'où vient le flou

> _Skill : justifier le niveau de marge retenu (faible/moyenne/élevée — barème dans method.md). Lister les sources d'incertitude réelles : artifact disponible, questions ouvertes, zones inconnues. Si élevée, recommander de cadrer davantage avant de s'engager._

- **Niveau** : <faible / moyenne / élevée> (+<%>).
- **Sources** :
  - <ex: estimation sur pitch seul, pas encore de plan technique>.
  - <ex: question ouverte non tranchée sur la règle de remise>.
  - <ex: dépendance à une API externe au comportement mal connu>.

## Hypothèses et exclusions

> _Skill : ce sur quoi l'estimation repose et ce qu'elle ne couvre PAS. Protège des malentendus de facturation. Une exclusion explicite vaut mieux qu'un litige._

- **Hypothèses** : <ex: environnement de prod et accès déjà en place ; pas de reprise de données historique au-delà du backfill prévu>.
- **Exclusions** : <ex: formation utilisateur, support post-livraison, évolutions hors périmètre du pitch>.

## À reconfirmer

> _Skill : ne garder que si l'estimation a été produite sur brief ou pitch seul. Indique quand et comment l'affiner._

- Estimation produite sur **<brief / pitch>** seul → relancer `/estimate` après `/feature-plan` pour l'affiner avant engagement ferme.
