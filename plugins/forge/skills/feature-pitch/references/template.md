# <Titre de la feature — une phrase, pas un nom de ticket>

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `docs/story/<NNN>-f-<slug>/`
> **Amont** : `brief.md` <!-- guide: ou « aucun » si le pitch part d'une idée directe, sans interview -->

<Résumé en une à deux phrases : ce que la feature change pour l'utilisateur et pourquoi.>

<!--
guide: Ce fichier décrit l'INTENTION métier d'une feature (préfixe `-f-`). Produit par `/forge:feature-pitch`.
Il est consommé par `/forge:feature-plan`, `/forge:estimate` et `/forge:sync` (réalignement post-livraison).
Format commun à tous les documents de story : voir la charte `${CLAUDE_SKILL_DIR}/../../references/document-format.md`.
Le H1 du pitch est le titre seul — pas de préfixe de type (exception assumée, charte §2) : le pitch EST l'énoncé de la feature.
RÈGLE D'OR — registre fonctionnel (charte §3) : aucune solution technique, aucun nom de classe/entité/service/table/framework. La SEULE exception est l'annexe §Pistes pour le plan, explicitement non contractuelle.
L'en-tête et le résumé ci-dessus RESTENT dans le fichier commité. Retirer ce bloc et tous les `> _Skill : ..._` avant commit.
Toutes les sections marquées « optionnelle » peuvent être retirées si elles ne s'appliquent pas.
-->

## Contexte

> _Skill : pourquoi cette feature, pourquoi maintenant. Décrire l'état actuel (1–2 paragraphes), la friction concrète, et la conséquence si on ne fait rien. Pas de solution. C'est ici que vit le « pourquoi » de la story : le `plan.md` n'en fera pas une copie, il renverra à ce document par son en-tête (charte §5)._

## Alignement vision

> _Skill : section **conditionnelle** — à inclure **uniquement si `docs/vision.md` existe**, à retirer entièrement sinon (contrairement aux sections canoniques, celle-ci se supprime : son absence signifie « pas de vision projet », pas « pas regardé »). Confronter la feature aux axes de la vision : problème adressé, audience servie, principes respectés ou mis sous tension, hypothèse testée, impact North Star. Registre fonctionnel : on parle valeur et périmètre, pas mécanisme. Une feature qui ne s'aligne sur rien est un signal — le dire plutôt que broder._

- **Problème adressé** : <en quoi la feature attaque le problème central de la vision>.
- **Audience servie** : <quel segment, dans quel usage>.
- **Principes respectés** : <principes de la vision que la feature honore — ou met sous tension, et pourquoi c'est assumé>.
- **Hypothèse testée** : <l'hypothèse de la vision que cette feature permet de valider ou d'invalider, le cas échéant>.
- **Impact North Star** : <direct / indirect / neutre + en quoi>.

## Utilisateurs concernés

> _Skill : lister les rôles applicatifs avec ce que cette feature change pour chacun. Nommer les rôles comme le métier les nomme (« superviseur », « administrateur d'organisation », « visiteur anonyme »), pas comme le code les code. Préciser les rôles **non impactés** quand c'est utile (ex: « utilisateurs d'une organisation cliente : aucun changement perçu »)._

- **<Rôle>** (<précisions : périmètre, contexte d'accès>) — <impact direct ou indirect>
- **<Rôle>** — <…>

## User Stories

> _Skill : format « En tant que <rôle>, je veux <action> afin de <bénéfice métier>. » Une story = un parcours observable. Couvrir aussi les cas négatifs (« je veux NE PAS voir… »)._

- En tant que **<rôle>**, je veux <…> afin de <…>.
- En tant que **<rôle>**, je veux <…> afin de <…>.

## Règles métier

> _Skill : invariants, contraintes, cardinalités, comportements par défaut. Tout ce qu'un dev doit respecter mais qui ne se déduit pas du code. Numéroter si l'ordre compte._

- <Règle 1 — précise, vérifiable, sans ambiguïté>.
- <Règle 2>.

## Critères d'acceptation

> _Skill : checkbox cochée à la livraison. Chaque critère doit être **observable par un utilisateur** (test ou validation manuelle) — c'est ce qui les distingue des « Critères de sortie » techniques du plan (charte §4). Un critère qu'on ne sait pas vérifier va en §Questions ouvertes. `report.md` reprend ces lignes **à l'identique** et les coche : leur formulation doit être stable._

- [ ] <Critère observable 1>.
- [ ] <Critère observable 2>.

## Hors scope

> _Skill : ce qui pourrait être confondu avec la feature mais qu'on ne livre PAS. Préserve le périmètre lors du plan et de la review. Mettre `_(aucun)_` plutôt que de supprimer la section (charte §5)._

- **<Sujet exclu>** : <raison brève>.
- **<Sujet exclu>** : <…>.

## Impacts transverses

> _Skill : passer en revue les axes systémiques **du point de vue de l'utilisateur** — le miroir technique de ces axes vit dans le `plan.md`, pas ici (charte §3). Poser la question en langage métier (« des libellés à traduire ? », « qui a le droit de voir ça ? »), jamais en termes de mécanisme. Mettre « non » explicite si non concerné : c'est ce qui distingue « vérifié » de « pas regardé »._

- **Traduction / langues** : <oui/non + libellés ou contenus concernés si oui>.
- **Droits d'accès** : <qui peut voir/faire quoi ; nouveau niveau d'autorisation ? ou « inchangé »>.
- **Cloisonnement des données** : <la feature touche-t-elle à qui voit les données de qui ? (organisation, client, canal de vente…)>.
- **Apparence / déclinaisons** : <la feature s'affiche-t-elle différemment selon le contexte (thème, canal, marque) ?>.
- **Exposition à des tiers** : <la donnée est-elle consommée hors de l'interface (partenaire, application externe) ?>.
- **Emails / notifications** : <oui/non + à qui, quand>.
- **Données existantes** : <les données déjà en base doivent-elles être reprises/complétées ? Impact perçu par l'utilisateur>.
- **Comportement par défaut** : <ce que voient les utilisateurs qui n'activent pas la feature>.

## Questions ouvertes

> _Skill : décisions encore non prises au moment du pitch. Format « **Question** : <énoncé>. Options : (a) <…>, (b) <…>. » Tranchées au plan ou à l'implémentation — annoter `→ tranché : <choix>` après coup. Dernière section du document : un document d'intention ferme sur ses inconnues (charte §1). L'annexe qui suit n'est pas une section du pitch._

- **<Question 1>** : <énoncé + options>.
- **<Question 2>** : <…>.

---

## Annexe — Pistes pour le plan

> _Skill : **hors registre, non contractuel.** Seule zone du pitch où un nom technique peut apparaître (charte §3) — parce que la reconnaissance de code a produit des indices qu'il serait absurde de perdre. Règles : (1) des pistes **à confirmer**, jamais des décisions ; (2) `/forge:feature-plan` est libre de toutes les ignorer ; (3) `/forge:sync` ne réaligne JAMAIS cette annexe sur le code livré — elle n'engage rien ; (4) rien ici ne conditionne un critère d'acceptation. Si l'interview et le cadrage n'ont produit aucune piste, retirer l'annexe entière._

- <Piste 1 : structure candidate avec les données Y et Z, à confirmer>.
- <Piste 2 : composant existant A probablement à toucher pour …>.
