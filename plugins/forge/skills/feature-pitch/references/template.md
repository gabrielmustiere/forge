# <Titre de la feature — une phrase, pas un nom de ticket>

> <Résumé en une à deux phrases : ce que la feature change pour l'utilisateur et pourquoi.>

<!--
guide: Ce fichier décrit l'INTENTION métier d'une feature. Aucune solution technique.
Il est consommé par `/forge:feature-implem` (étape plan) et par `/forge:sync` (réalignement post-livraison).
Toutes les sections marquées « optionnelle » peuvent être retirées si elles ne s'appliquent pas.
Retirer ce bloc et tous les `> _Skill : ..._` avant commit.
-->

## Contexte

> _Skill : pourquoi cette feature, pourquoi maintenant. Décrire l'état actuel (1–2 paragraphes), la friction concrète, et la conséquence si on ne fait rien. Pas de solution._

## Utilisateurs concernés

> _Skill : lister les rôles applicatifs (ex: ROLE_SUPERVISEUR, ROLE_ADMIN, utilisateur d'organisation, visiteur anonyme) avec ce que cette feature change pour chacun. Préciser les rôles **non impactés** quand c'est utile (ex: « utilisateurs tenant : aucun changement perçu »)._

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

> _Skill : checkbox cochée à la livraison. Chaque critère doit être observable (test ou validation manuelle). Reprise dans `report.md` à l'identique._

- [ ] <Critère observable 1>.
- [ ] <Critère observable 2>.

## Hors scope

> _Skill : ce qui pourrait être confondu avec la feature mais qu'on ne livre PAS. Préserve le périmètre lors de la review et du plan._

- **<Sujet exclu>** : <raison brève>.
- **<Sujet exclu>** : <…>.

## Impacts transverses

> _Skill : passer en revue les axes systémiques du projet. Mettre « non » explicite si non concerné — éclaire le plan._

- **Multi-tenant** : <oui/non + précision si oui (chaîne de filtrage touchée, OrganizationContext, etc.)>.
- **Multi-thème** : <oui/non>.
- **i18n / traduction** : <oui/non + libellés concernés si oui>.
- **API** : <oui/non + endpoint exposé/modifié si oui>.
- **Permissions** : <nouveaux voters / rôles / firewall ? ou « inchangé »>.
- **Emails / notifications** : <oui/non + mailer concerné si oui>.
- **Migration de données** : <oui/non + nature si oui (création table, drop colonne, backfill, etc.)>.
- **Comportement par défaut** : <ce que voient les utilisateurs qui n'activent pas la feature>.

## Notes pour le plan technique

> _Skill : pistes brutes à explorer en `/forge:feature-implem` plan — entités candidates, services à toucher, patterns projet à mobiliser (OrganizationAwareInterface, Live Component, EventSubscriber, etc.). Ne PAS concevoir ici, ne PAS trancher._

- <Piste 1 : entité X candidate avec champs Y et Z, à confirmer>.
- <Piste 2 : service A à modifier pour …>.

## Questions ouvertes

> _Skill : décisions encore non prises au moment du pitch. Format « **Question** : <énoncé>. Options : (a) <…>, (b) <…>. » Tranchées en plan ou à l'implémentation — annoter `→ tranché : <choix>` après coup._

- **<Question 1>** : <énoncé + options>.
- **<Question 2>** : <…>.

---

## Changelog

> _Skill : ajouté par `/forge:sync` après livraison si le code a divergé de l'intention. Une ligne par sync, daté. Décrire ce qui a été réaligné (cases d'acceptation cochées, questions ouvertes tranchées, règles affinées) avec la motivation._

| Date       | Type                      | Description |
|------------|---------------------------|-------------|
| YYYY-MM-DD | Sync post-implémentation  | <ce qui a été réaligné et pourquoi> |
