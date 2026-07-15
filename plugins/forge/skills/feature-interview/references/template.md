# Brief — <Le besoin reformulé en clair, une phrase, pas un nom de ticket>

> **But** : dégrossir un besoin flou par interview — matière première à challenger, pas un cadrage validé.
> **Registre** : fonctionnel
> **Story** : `docs/story/<NNN>-f-<slug>/`
> **Amont** : aucun

<!--
guide: Ce fichier capture un besoin flou dégrossi par interview. Produit par `/forge:feature-interview`, consommé par `/forge:feature-pitch` comme pitch initial riche.
Format commun à tous les documents de story : voir la charte `${CLAUDE_SKILL_DIR}/../../references/document-format.md`.
Ce n'est PAS un pitch validé : `/forge:feature-pitch` va le reprendre, le challenger et le structurer. Pas de solution technique, pas de cadrage formel (ni user stories rigoureuses, ni règles métier, ni critères d'acceptation — c'est le job du pitch).
RÈGLE D'OR — registre fonctionnel (charte §3) : AUCUN nom d'entité/classe, de service, de fichier, de framework/bundle, de table, ni de stack. Toute trouvaille technique de la reconnaissance du code se traduit en capacité vécue par l'utilisateur avant d'entrer ici (« class Cart » → « le client peut remplir un panier »).
L'en-tête ci-dessus RESTE dans le fichier commité (charte §2). Retirer ce bloc et tous les `> _Skill : ..._` avant commit.
-->

## Le besoin en une phrase

> _Skill : la formulation que l'utilisateur a validée en restitution. Une phrase qui dit ce qui le gêne et ce qu'il aimerait à la place. C'est ce que `/forge:feature-pitch` reprendra en Phase 1 comme pitch initial._

<Besoin en une phrase, à challenger.>

## L'irritant déclencheur

> _Skill : la situation concrète et récente d'où vient le besoin (technique de l'exemple récent). Ce qui se passe, à quelle fréquence, ce que ça coûte (temps, erreurs, frustration, manque à gagner). Du factuel, pas de l'abstrait._

- **Situation** : <ce qui se passe concrètement, exemple récent>.
- **Fréquence** : <combien de fois, dans quel contexte>.
- **Coût** : <conséquence si rien n'est fait>.

## Qui est concerné

> _Skill : distinguer qui vit le problème de qui bénéficierait de la solution (pas toujours les mêmes). Rôles entrevus, sans formalisme — le pitch précisera les rôles applicatifs._

- **<Personne / rôle>** — <vit le problème / en bénéficierait / les deux>.

## Le résultat attendu

> _Skill : la « baguette magique » — à quoi ressemble la situation résolue, vécue. Pas la solution technique, le résultat. Ce que l'utilisateur ne fait plus / obtient._

<Description de la situation résolue, du point de vue de l'utilisateur.>

## Ce que le produit fait déjà

> _Skill : la phase 1 a lu le code, mais on ne note ici que des **capacités vues par l'utilisateur** — ce que le produit permet déjà de faire autour du besoin, en clair. Jamais de nom d'entité, de service, de fichier ni de stack (règle d'or, charte §3). Sert à `/forge:feature-pitch` pour reformuler le besoin en extension de l'existant plutôt qu'en réinvention._

- **Déjà possible aujourd'hui** : <capacité offerte, du point de vue utilisateur — ex: « le client peut remplir un panier et passer commande »>.
- **Pas encore couvert** : <ce qui manque et que le besoin viserait — ex: « aucune relance quand un panier est abandonné »>.
- _Mettre `_rien de probant trouvé_` si la reconnaissance n'a rien donné (besoin trop neuf, ou trop flou à ce stade)._

## Hors scope

> _Skill : les « non, ça c'est autre chose » de l'utilisateur. Pré-cadre le hors-scope que le pitch formalisera — même titre canonique que dans le pitch et les plans (charte §4). Évite que le pitch ou le plan élargissent par erreur. Mettre `_(aucun)_` si l'utilisateur n'a rien écarté : une section vide serait indistinguable d'un oubli (charte §5)._

- <Sujet voisin explicitement écarté par l'utilisateur>.

## Zones de flou à creuser au pitch

> _Skill : ce qui est resté incertain malgré l'interview. Ne pas trancher ici — laisser `/forge:feature-pitch` les challenger. Format question ouverte._

- **<Question restée ouverte>** : <ce qui n'a pas pu être tranché>.

## Verbatim utiles

> _Skill : 1 à 3 citations marquantes de l'utilisateur, mot pour mot. Elles gardent l'intention intacte pour le pitch, mieux qu'une reformulation. Optionnel mais précieux._

- « <citation> »
