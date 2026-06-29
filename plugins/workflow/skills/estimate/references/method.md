# Méthode de chiffrage « tout compris »

Référence chargée par `/estimate` au moment de chiffrer. Trois blocs : les **phases facturables** (par track), les **signaux de complexité** (ce qui justifie chaque chiffre), le **barème de marge** (l'incertitude assumée). Plus une liste des **pièges** qui font sous-chiffrer.

## Sommaire

- [1. Les phases facturables, par track](#1-les-phases-facturables-par-track)
- [2. Accélération par un assistant IA](#2-accélération-par-un-assistant-ia)
- [3. Signaux de complexité](#3-signaux-de-complexité)
- [4. Barème de marge d'incertitude](#4-barème-de-marge-dincertitude)
- [5. Pièges du sous-chiffrage](#5-pièges-du-sous-chiffrage)
- [6. Comment poser le total](#6-comment-poser-le-total)

## 1. Les phases facturables, par track

Le principe « tout compris » : on facture la chaîne de livraison entière, pas la seule implémentation. Toutes les phases ne s'appliquent pas à tous les tracks — voici lesquelles retenir selon le préfixe de la story.

| Phase | Feature (`f-`) | Refacto (`r-`) | Tech (`t-`) | Ce qu'elle couvre |
|-------|:---:|:---:|:---:|---|
| **Cadrage fonctionnel** | ✅ | — | — | Interview + pitch : faire émerger et challenger le besoin. |
| **Conception technique** | ✅ | ✅ | ✅ | Plan : architecture, découpage, stratégie de test. |
| **Implémentation** | ✅ | ✅ | ✅ | Code, migrations, config. Pour tech : baseline + kill switch. |
| **Tests** | ✅ | ✅ | ✅ | Tests automatisés + QA manuelle. Refacto : **tests de caractérisation en amont** (poste lourd, souvent oublié). |
| **Review & corrections** | ✅ | ✅ | ✅ | La review **et** la reprise des remarques. |
| **Documentation de clôture** | ✅ | ✅ | ✅ | Report + sync, mise à jour de doc. |
| **Release & déploiement** | ✅ | ✅ | ✅ | **Forfait fixe : 0,5 h (30 min)**, quel que soit le track — changelog, tag, mise en prod, vérif. Ne pas ré-estimer. |

**Contexte solo** : ce workflow est mono-développeur. Pas de phase « Intégration » dédiée (pas de merge multi-contributeurs ni de coordination de CI partagée à facturer comme poste distinct) ni de phase « Coordination & échanges » (réunions/recette ne sont pas suivies comme un poste séparé). La phase **Release & déploiement** est un **forfait fixe de 30 min** : c'est une routine de durée constante, on ne la ré-estime pas et l'IA ne l'accélère pas.

**Spécificités par track à ne pas rater :**

- **Refacto** : la phase Tests inclut l'écriture des **tests de caractérisation AVANT** de toucher au code (le verrou de non-régression). C'est souvent la moitié de l'effort d'un refacto et le premier poste qu'on oublie de chiffrer.
- **Tech** : l'implémentation inclut la **mesure de baseline** avant modif et la pose d'un **kill switch** ; la phase Tests inclut la **re-mesure** après chaque étape pour prouver le gain. Compter ce temps de mesure.
- **Feature** : si un `brief.md` et/ou `pitch.md` existent déjà, leur temps est **déjà consommé** (sunk). Le noter dans l'estimate : l'utilisateur décidera de le facturer (tout compris) ou de ne chiffrer que le reste à faire.

**Phases déjà réalisées** : quand un artifact existe déjà (pitch écrit, plan écrit), la phase correspondante est en partie derrière soi. Indique-le clairement (`déjà fait` / `reste à faire`) pour que l'utilisateur arbitre entre facturer le total ou seulement le restant.

## 2. Accélération par un assistant IA

Le chiffrage produit **deux colonnes** : le temps de référence (réalisation classique, à la main) et le temps réel quand un assistant de code (type Claude Code) fait le gros de la production. Le second est toujours inférieur ou égal au premier.

Principe directeur : **l'IA accélère ce qui se produit, pas ce qui est humain incompressible.** Le code, les tests, la doc et l'exploration de codebase sont massivement accélérés ; les arbitrages métier, les validations et la mise en production (forfait fixe) sont incompressibles. Un facteur d'accélération s'applique donc **par phase**, jamais en bloc sur le total.

Barème indicatif — le facteur est le temps *avec IA* rapporté au temps de référence (0,3× = trois fois plus rapide, 1× = aucun gain) :

| Phase | Facteur IA | Pourquoi |
|-------|:---:|---|
| Cadrage fonctionnel | ~0,6× | L'IA structure et challenge, mais le besoin vient de l'humain et du client. |
| Conception technique | ~0,5× | Exploration de code et propositions d'archi accélérées ; les arbitrages restent humains. |
| Implémentation | ~0,3–0,4× | Génération de code massive, l'humain pilote et valide. Le plus gros gain. |
| Tests (auto + QA) | ~0,3–0,4× | Tests générés vite ; la QA exploratoire manuelle reste partiellement humaine. |
| Review & corrections | ~0,6× | Pré-revue automatique utile, mais la décision et les reprises restent pilotées. |
| Documentation de clôture | ~0,3× | Report et sync quasi automatisables. |
| Release & déploiement | forfait fixe | **0,5 h dans les deux colonnes** — durée constante, aucune accélération. |

Conséquences à garder en tête :

- **Le ratio global n'est jamais celui de l'implémentation seule.** Les phases peu accélérables (review, conception) et le forfait release fixe plafonnent le gain sur le total.
- Le facteur dépend de la **maîtrise de l'outil** par l'utilisateur : un usage rodé tire plus de gain. Demander et ajuster si besoin (surtout sur implem / tests / doc).
- Ces deux estimations **éclairent une décision de facturation**, elles ne la prennent pas : l'utilisateur choisit la base, et l'écart représente sa marge (ou sa capacité à prendre plus de travail).

## 3. Signaux de complexité

Un chiffre ne vaut que par le signal qui le justifie. Recense-les selon l'artifact disponible — plus on descend, plus l'estimation est fiable.

### Depuis le `pitch.md` (signaux fonctionnels)

- **Nombre de user stories / parcours** distincts à livrer.
- **Rôles & permissions** : combien d'acteurs, faut-il de nouveaux droits/voters ?
- **Règles métier** : leur nombre et leur subtilité (une règle conditionnelle multi-cas coûte plus qu'un CRUD).
- **Cas limites listés** : chacun est du code + un test.
- **Richesse UI** : nombre d'écrans, de formulaires, d'états d'interface.
- **Impacts transverses cochés** — chacun est un **poste de charge à part entière**, pas un détail : migration de données, i18n (champs traduisibles), API à exposer, emails/notifications, multi-channel / multi-tenant, multi-thème.

### Depuis le `plan.md` (signaux techniques)

- **Nombre de fichiers à créer** et **à modifier** (les tables du plan les listent — compte-les).
- **Migrations** : présence, et surtout **backfill** de données existantes (un backfill est toujours plus long et plus risqué qu'un simple `ALTER`).
- **Nouvelles entités** et relations (bidirectionnelles = plus de soin).
- **Mécanismes framework** : distinguer ceux **réutilisés** (connus, rapides) de ceux **nouveaux** (à apprendre → ajouter un coût d'apprentissage).
- **Niveaux de test prévus** (unit / functional / E2E) : plus de niveaux = plus de temps de test.
- **Risques listés** : chaque risque technique identifié est une source de dépassement potentiel → nourrit la marge.

### Depuis le code (quand le plan désigne des fichiers existants)

- **Taille et couplage** des fichiers à modifier : un service de 800 lignes fortement couplé se touche lentement.
- **Présence de tests** sur la zone : du code testé se modifie avec un filet (rapide et sûr) ; une zone sans test impose de la prudence (ou d'écrire des tests d'abord) → ralentit.
- **Cohérence avec les conventions** : du code legacy non conforme aux conventions actuelles demande un effort d'adaptation.

### Quand il n'y a que le `brief.md`

Peu de signaux exploitables : on chiffre à la louche, sur la surface fonctionnelle perçue, et on **assume une marge élevée**. L'estimate doit dire explicitement qu'elle est à reconfirmer après le pitch puis le plan.

## 4. Barème de marge d'incertitude

**Principe directeur — ne jamais compter l'incertitude deux fois.** La durée de base de chaque phase est la **médiane réaliste** : le temps le plus probable si le déroulé est normal, pas la durée « au cas où ». L'aléa, l'imprévu, ce qu'on ne sait pas encore — tout ça est porté **uniquement par la marge**. Si on padde la base (« je mets un peu plus pour être tranquille ») *et* qu'on ajoute une marge par-dessus, l'incertitude est comptée deux fois : c'est la cause n°1 d'un devis ~30 % trop haut. La base se cale au plus probable ; la marge, et elle seule, encaisse le reste.

On applique ensuite **un seul pourcentage global** au total, choisi selon le niveau de flou réel.

| Niveau | Marge indicative | Quand l'appliquer |
|--------|:---:|---|
| **Faible** | **+15 %** | `plan.md` détaillé, stack et domaine maîtrisés, peu ou pas de transverse, zone de code couverte par des tests, aucune question ouverte majeure. |
| **Moyenne** | **+30 %** | Pitch clair mais pas encore de plan, **ou** plan avec quelques inconnues ; transverse modéré ; une ou deux questions ouvertes ; zone partiellement testée. |
| **Élevée** | **+50 % et plus** | `brief.md` seul, zones de flou non tranchées, domaine ou stack peu connus, fort impact transverse, legacy non testé, dépendance externe non maîtrisée. À ce niveau, présenter plutôt une **fourchette** (basse / haute) qu'un point unique. |

Règles d'usage :

- La marge **n'est pas un coussin de confort** caché : elle est affichée, nommée et justifiée. Le client (et l'utilisateur) doit voir d'où vient l'incertitude.
- Le niveau de marge **suit l'artifact** : on ne peut pas prétendre à une marge faible sur un brief seul. Plus la matière est riche, plus la marge peut baisser.
- Quand l'incertitude est élevée, le bon réflexe n'est pas de gonfler la marge à l'infini : c'est de **proposer de cadrer davantage** (lancer le pitch ou le plan) pour réduire le flou avant de s'engager.

## 5. Pièges du sous-chiffrage

Les erreurs qui transforment un devis en perte. Les avoir en tête, c'est la moitié du travail.

- **Ne chiffrer que le code.** L'implémentation est souvent moins de la moitié du temps total une fois cadrage, tests, review, doc et release comptés. C'est l'erreur n°1.
- **Estimer optimiste.** Le « si tout va bien » n'arrive jamais pour toutes les phases à la fois. On chiffre le réaliste (la médiane), pas le meilleur cas.
- **Doubler le matelas (sur-chiffrer par réflexe défensif).** Le miroir du piège optimiste, et tout aussi coûteux. Mettre un coussin dans la base *puis* ajouter une marge compte l'incertitude deux fois — le devis sort ~30 % trop haut, se perd à l'appel d'offres ou érode la confiance quand le réalisé tombe loin en dessous. La base = médiane réaliste ; la marge = le seul matelas. **Test du miroir** : si chaque ligne de base te paraît déjà « safe », elle est trop haute — le safe vit dans la marge, pas dans la base.
- **Ignorer le coût d'apprentissage.** Un mécanisme/lib/zone inconnu se paie en temps d'exploration avant même la première ligne utile.
- **Confondre estimation et engagement.** Une estimation sans marge devient, aux yeux du client, une promesse. La marge protège la relation autant que la trésorerie.

## 6. Comment poser le total

Le total se calcule **pour chacune des deux colonnes** (référence sans IA, puis temps réel avec IA), et chacune se lit en deux temps, jamais en un seul chiffre nu :

1. **Somme des phases** (le plus probable, hors aléa — la médiane, pas la borne haute ; l'aléa vit dans la marge, pas ici) — la décomposition rend l'estimation auditable et négociable phase par phase.
2. **Total avec marge** = somme × (1 + marge) — le chiffre à retenir pour s'engager.

La marge d'incertitude s'applique aux deux colonnes ; le facteur IA joue, lui, **avant** la marge (sur la somme des phases). On obtient donc quatre repères : référence (somme / avec marge) et avec IA (somme / avec marge). L'écart entre les deux totaux avec marge, c'est la marge de l'utilisateur.

Unité : l'**heure** (et fractions : 0,5 h ; 0,25 h pour les petites phases). Rester cohérent sur toute l'estimate — une seule unité dans un même tableau. Sur une grosse story, on peut rappeler l'équivalent en jours entre parenthèses pour la lisibilité, mais l'heure reste l'unité de référence (c'est ce qui se facture).

Pour une incertitude élevée, présenter **basse / haute** plutôt qu'un point : `total bas` = somme × (1 + marge basse), `total haut` = somme × (1 + marge haute). C'est plus honnête qu'un faux chiffre précis.

Rappel : tout est en **temps**. La conversion en montant (taux horaire, remise) appartient à l'utilisateur — l'estimate ne porte aucun euro.
