# Diataxis — règles d'écriture appliquées à la documentation fonctionnelle

> Adapté en français du skill [`diataxis`](https://github.com/rlespinasse/agent-skills/tree/main/skills/diataxis)
> de Romain Lespinasse (© 2025, licence MIT), lui-même extrait de [diataxis.fr](https://diataxis.fr/)
> (Daniele Procida). Les règles propres à forge (cas numérotés, captures, registre fonctionnel)
> sont ajoutées dans les sections qui les signalent.

## Sommaire

- [Les quatre types](#les-quatre-types)
- [Boussole](#boussole)
- [Tutoriel](#tutoriel)
- [Guide pratique](#guide-pratique)
- [Référence](#référence)
- [Explication](#explication)
- [Cas limites](#cas-limites)
- [Anti-motifs](#anti-motifs)
- [Contrôle par type](#contrôle-par-type)
- [Qualité](#qualité)

## Les quatre types

Diataxis range la documentation selon le **besoin du lecteur**, pas selon le sujet. Une page sert
un seul besoin.

| Type | Orientation | Le lecteur veut… | Forme | Dossier |
|---|---|---|---|---|
| **Tutoriel** | apprentissage | apprendre en faisant, guidé | une leçon | `tutorials/` |
| **Guide pratique** | tâche | atteindre un objectif précis | une suite d'étapes | `how-to/` |
| **Référence** | information | vérifier un fait exact | une description sèche, structurée | `reference/` |
| **Explication** | compréhension | comprendre le pourquoi | un texte de réflexion | `explanation/` |

Les noms de dossiers restent en anglais (convention Diataxis, stable quelle que soit la langue du
projet). Les titres et le contenu sont dans la langue du pitch.

## Boussole

Deux questions suffisent à classer un contenu, qu'il s'agisse d'une page, d'une section ou d'une
phrase :

1. **Action ou connaissance ?** Le lecteur fait-il quelque chose, ou cherche-t-il à savoir ?
2. **Acquisition ou application ?** Le lecteur apprend-il, ou est-il en train de travailler ?

| | Acquisition (il apprend) | Application (il travaille) |
|---|---|---|
| **Action** | Tutoriel | Guide pratique |
| **Connaissance** | Explication | Référence |

La boussole sert aussi de correcteur en cours d'écriture : quand une section est difficile à
écrire ou « sonne faux », c'est souvent qu'elle a changé de type en route. Il faut la déplacer
dans la page qui lui correspond.

**Application aux cas de la matrice (forge)** :

| Le cas répond à… | Il va dans… |
|---|---|
| « que se passe-t-il exactement si… ? » (tous les cas, sans exception) | la **Référence**, table « Cas et comportements » |
| « comment j'atteins tel résultat ? » (parcours nominaux, variantes, récupération d'une erreur) | un **Guide pratique** |
| « pourquoi ça se comporte ainsi ? » (règle métier contre-intuitive, limite assumée, hors périmètre) | une **Explication** |
| le premier parcours complet, pour quelqu'un qui découvre | le **Tutoriel** |

Un même cas peut figurer dans plusieurs pages, sous des formes différentes : une ligne de table en
référence, une étape de récupération dans un guide.

## Tutoriel

Une leçon, sous la responsabilité de l'auteur : le lecteur suit, et il doit réussir.

**Fait** :

- annonce la destination dès la première phrase : « Dans ce tutoriel, nous allons importer notre
  premier fichier de clients et vérifier le résultat. » (et non « vous allez apprendre… ») ;
- avance par étapes concrètes, chacune produisant un **résultat visible**, montré par une capture ;
- rassure en continu : « Vous devez voir… », « Remarquez que… » ;
- suit un seul chemin, entièrement fiable, avec un jeu de données fourni ou décrit ;
- se termine par ce qui a été accompli et des liens vers les guides.

**Ne fait pas** : expliquer (un lien suffit), proposer des options ou des alternatives,
généraliser, lister des paramètres.

**Langue** : la première personne du pluriel (« nous »), et des impératifs simples (« Cliquez
sur… »).

**Coût** : un tutoriel est la page la plus chère à maintenir, car chaque étape dépend de la
précédente. C'est pourquoi le skill n'en produit qu'à bon escient.

## Guide pratique

Une recette pour quelqu'un qui sait déjà ce qu'il veut faire.

**Fait** :

- un titre qui énonce l'objectif, du point de vue de l'utilisateur : « Importer des clients depuis
  un fichier CSV », « Corriger un import rejeté ». Jamais le nom de l'outil seul (« Import CSV ») ;
- se présente comme un contrat : « Si vous voulez X, suivez ces étapes » ;
- donne les prérequis (droits, données préparées), puis des étapes ordonnées, sans détour ;
- commence et s'arrête à des points raisonnables : le lecteur l'intègre à son propre travail ;
- reste ouvert aux variantes réelles (« si votre fichier contient des en-têtes, … ») ;
- traite les échecs probables dans une section finale, chacun avec un lien vers la référence.

**Ne fait pas** : enseigner les bases (tutoriel), expliquer le pourquoi (explication), recopier
une table exhaustive (référence), couvrir un objectif trop large (« Gérer ses clients »).

**Langue** : « Ce guide montre comment… », « Si vous voulez…, faites… », « Pour la liste complète
des messages, voir la référence. »

## Référence

Une description, consultée et non lue d'un bout à l'autre. Elle doit être sûre.

**Fait** :

- décrit, et seulement décrit, sur un ton neutre, factuel et sans ambiguïté ;
- suit la structure de ce qu'elle décrit : un écran, puis ses champs, ses actions, ses états ;
- garde un format identique d'une feature à l'autre, pour que le lecteur trouve où il s'attend à
  trouver ;
- énonce les contraintes au conditionnel strict : « Le fichier doit faire moins de 5 Mo. »,
  « L'import est refusé si… » ;
- cite les messages **mot pour mot** ;
- donne des exemples courts, qui montrent sans enseigner.

**Forge** : la référence d'une feature contient la table **« Cas et comportements »**, qui liste
tous les cas de la matrice avec leur ID. C'est la seule page exhaustive par construction ; les
autres pages y renvoient.

**Ne fait pas** : donner des instructions (« commencez par… »), argumenter (« c'est mieux
parce que… »), raconter.

## Explication

Une réflexion sur un sujet, qu'on peut lire loin du clavier.

**Fait** :

- porte sur un **sujet**, pas sur une tâche ni sur un écran ; le titre se lit naturellement après
  « À propos de… » : « La détection des doublons », « Pourquoi l'import est asynchrone » ;
- donne du contexte : le problème d'origine (le §Problème du pitch), les choix et leurs
  compromis, les alternatives écartées quand elles éclairent l'usage ;
- fait des liens avec le reste du produit ;
- assume un point de vue, et l'argumente.

**Ne fait pas** : absorber des étapes ou des tables. C'est le piège principal de l'explication,
qui tend à devenir un fourre-tout. Elle doit rester bornée.

**Forge** : l'explication reste en registre fonctionnel. Elle parle de règles métier et
d'arbitrages produit, jamais d'architecture. Une décision d'architecture a sa place dans un ADR
(`/forge:adr`), pas dans la documentation utilisateur.

## Cas limites

Le format décide du type, pas le sujet. La question à se poser : comment le lecteur va-t-il se
servir de cette page ?

| Contenu | Consulté (table symptôme → cause → solution) | Suivi pas à pas |
|---|---|---|
| Dépannage | Référence (une section de la page de référence) | Guide (« Diagnostiquer un import qui échoue ») |
| FAQ | Référence, si les entrées sont indépendantes | Explication, si l'ensemble construit une compréhension |
| Migration (changement de comportement d'une feature) | — | Guide pour les étapes ; Explication pour ce qui a changé et pourquoi |

## Anti-motifs

| Symptôme | Correction |
|---|---|
| Un tutoriel qui ouvre une parenthèse « en détail, le système… » | une phrase et un lien vers l'explication |
| Un guide qui se termine par la table de tous les champs | lien vers la référence |
| Une référence qui dit « commencez par ouvrir l'écran X » | la référence décrit l'écran, le guide dit d'y aller |
| Une explication avec des étapes numérotées | les étapes partent dans un guide |
| Un guide intitulé d'après l'outil (« Le module d'import ») | titre reformulé en objectif |
| Une page « Import de clients » qui mélange tout | scindée en guide, référence et, si besoin, explication |
| Une capture qui montre trois cas à la fois | une capture par cas |
| Un message d'erreur paraphrasé | recopié mot pour mot |

## Contrôle par type

À passer sur chaque page en phase 5 :

| Type | Test |
|---|---|
| Tutoriel | La destination est annoncée dans la première phrase. Chaque étape a un résultat visible. Aucune option, aucun paramètre listé. |
| Guide pratique | Le titre énonce un objectif. Les prérequis sont listés. Les étapes sont ordonnées. Aucune table exhaustive, aucun pourquoi développé. |
| Référence | Aucune instruction ni aucun argument. La table « Cas et comportements » contient tous les cas non exclus. Les messages sont cités mot pour mot. |
| Explication | « À propos de <titre> » se lit naturellement. Aucune étape numérotée. Elle reste en registre fonctionnel. |

## Qualité

Diataxis rend les manques visibles, mais il ne garantit pas la qualité. Deux niveaux :

**Qualité fonctionnelle**, mesurable : exactitude (vérifiée dans le code et à l'écran),
complétude (la matrice), cohérence (mêmes termes que l'interface, mêmes formats d'une page à
l'autre), utilité, précision, redondance maîtrisée (un fait vit dans la référence, les autres
pages y renvoient).

**Qualité profonde**, jugée : la page se lit sans à-coups, elle colle à la façon dont les gens
travaillent, et elle anticipe la question suivante du lecteur. Elle découle de la qualité
fonctionnelle et du respect des types, mais ne s'y réduit pas.

Le cadre ne doit pas être forcé : une feature minuscule peut tenir en un guide et une référence
courte. Mieux vaut deux pages justes que quatre pages creuses.
