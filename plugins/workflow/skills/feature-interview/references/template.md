# <Le besoin reformulé en clair — une phrase, pas un nom de ticket>

> _Matière première produite par `/feature-interview`. **Ce n'est pas un pitch validé** : `/feature-pitch` va le reprendre, le challenger et le structurer. Tout ici est une proposition à confirmer._

<!--
guide: Ce fichier capture un besoin flou dégrossi par interview. Pas de solution technique, pas de cadrage formel (ni user stories rigoureuses, ni règles métier, ni critères d'acceptation — c'est le job de /feature-pitch).
Il est consommé par `/feature-pitch` comme pitch initial riche.
Retirer ce bloc et tous les `> _Skill : ..._` avant commit.
-->

## Le besoin en une phrase

> _Skill : la formulation que l'utilisateur a validée en restitution. Une phrase qui dit ce qui le gêne et ce qu'il aimerait à la place. C'est ce que `/feature-pitch` reprendra en Phase 1 comme pitch initial._

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

## Reconnaissance du code existant

> _Skill : ce que la phase 1 a trouvé. Briques proches déjà présentes, ce qu'elles font, où le besoin se rattacherait. Surtout : faut-il étendre quelque chose d'existant plutôt que réinventer ? Renseigne le plus factuellement possible (fichiers/services repérés)._

- **Stack** : <symfony / sylius / inconnu — et la source : `docs/stack.md`, `composer.json`, etc.>. _Renseigner toujours : `/feature-pitch` le réutilise au lieu de re-détecter._
- **Briques proches repérées** : <entité / service / écran / brique native du framework — fichier si connu>.
- **Piste extension vs réinvention** : <le besoin semble une extension de X / rien d'existant ne couvre>.
- _Mettre `_rien de probant trouvé_` si la reconnaissance n'a rien donné (besoin trop neuf ou trop flou à ce stade)._

## Ce que ce n'est PAS

> _Skill : les « non, ça c'est autre chose » de l'utilisateur. Pré-cadre le hors-scope que le pitch formalisera. Évite que le pitch ou le plan élargissent par erreur._

- <Sujet voisin explicitement écarté par l'utilisateur>.

## Zones de flou à creuser au pitch

> _Skill : ce qui est resté incertain malgré l'interview. Ne pas trancher ici — laisser `/feature-pitch` les challenger. Format question ouverte._

- **<Question restée ouverte>** : <ce qui n'a pas pu être tranché>.

## Verbatim utiles

> _Skill : 1 à 3 citations marquantes de l'utilisateur, mot pour mot. Elles gardent l'intention intacte pour le pitch, mieux qu'une reformulation. Optionnel mais précieux._

- « <citation> »
