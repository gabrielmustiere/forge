# Review — <Titre de la story>

> **But** : juger le diff au regard de l'intention — dire si on commite, et ce qui bloque.
> **Registre** : technique
> **Story** : `docs/story/<NNN>-<f|r|t>-<slug>/`
> **Amont** : `plan.md` <!-- guide: + `pitch.md` pour une story `-f-` -->
> **Diff examiné** : <ex: working tree, ~29 fichiers modifiés + 9 nouveaux, ~660 lignes>

<!--
guide: Compte rendu de revue du diff par rapport à l'intention. Produit par `/forge:review`, consommé par l'humain et par `/forge:report` (qui reprend les mineurs non traités en dette).
Format commun à tous les documents de story : voir la charte `${CLAUDE_SKILL_DIR}/../../references/document-format.md`.
Document de DÉCISION : il ouvre sur sa conclusion (charte §1). On doit pouvoir ne lire que la §Synthèse.
Trois niveaux de criticité, catalogue de tags fermé et format de finding : charte §7. Verdicts fermés et en français : charte §8.
« Diff examiné » (en-tête) = ce que la review a regardé. À ne pas confondre avec le §Périmètre d'un plan, qui liste les fichiers prévus (charte §4).
L'en-tête ci-dessus RESTE dans le fichier commité. Retirer ce bloc et tous les `> _Skill : ..._` avant commit.
-->

## Synthèse

> _Skill : la conclusion, en premier. Compteurs « restants » = items encore `[ ]` non résolus à la fin de la passe. Statut : valeurs **fermées** (charte §8) — **PRÊT À COMMITER** (0 bloquant, 0 important non résolu), **PRÊT À COMMITER SOUS RÉSERVE** (préciser laquelle), **CORRECTIONS REQUISES** (bloquants ou importants non résolus). Terminer par la prochaine étape, en une phrase._

- **Bloquants restants** : N / total
- **Importants restants** : N / total
- **Mineurs restants** : N / total
- **Statut** : **PRÊT À COMMITER** <!-- guide: ou PRÊT À COMMITER SOUS RÉSERVE <laquelle> / CORRECTIONS REQUISES -->

<Une phrase sur la prochaine étape : « `/forge:commit` pour commiter et pousser. » OU « Corriger les bloquants puis relancer la review. »>

## Bloquants

> _Skill : findings qui empêchent le commit — bug, faille, isolation cassée, divergence comportementale avec le plan, régression mesurable. Tags typiques : BUG, SECU, PLAN, ARCHI, MIGRATION (catalogue fermé, charte §7). Format de finding normatif (charte §7) : la case cochée signifie **corrigé pendant la passe**, pas « lu ». Si aucun, mettre `_(aucun)_` — jamais une section vide._

- [ ] **[BUG] <résumé en une phrase>** — `<chemin>:<ligne>` — <pourquoi c'est bloquant + correctif appliqué OU action requise>.
- [ ] **[PLAN] <résumé>** — `<chemin>:<ligne>` — <écart majeur avec le plan + résolution>.
- [ ] **[SECU] <résumé>** — `<chemin>:<ligne>` — <faille + mitigation>.

## Importants

> _Skill : findings à corriger avant le commit, sauf arbitrage explicite de l'utilisateur. Tags typiques : ARCHI, CONV, TEST, PERF, ROBUSTESSE. Idéalement tous cochés avant un statut PRÊT À COMMITER. Si aucun, mettre `_(aucun)_`._

- [ ] **[ARCHI] <résumé>** — `<chemin>:<plage de lignes>` — <pourquoi important + action ou correctif>.
- [ ] **[TEST] <résumé>** — `<chemin>:<ligne>` — <…>.

## Mineurs

> _Skill : améliorations utiles, non bloquantes. Tags typiques : STYLE, DOC, I18N, PERF. Peuvent rester non cochés : `/forge:report` les reprend alors **à l'identique** en dette technique (charte §7) — d'où l'importance de les garder courts et actionnables. Si aucun, mettre `_(aucun)_`._

- [ ] **[STYLE] <résumé>** — `<chemin>:<ligne>` — <correction suggérée>.
- [ ] **[DOC] <résumé>** — `<chemin>` — <correction suggérée>.
- [ ] **[I18N] <résumé>** — `<chemin>:<ligne>` — <correction suggérée>.

## Points positifs

> _Skill : ce qui mérite d'être souligné — pattern bien appliqué, test exhaustif, refacto propre, décision documentée. Signale à l'auteur ce qui est à reproduire. 3–6 puces, concrètes : « bien fait » sans objet n'apprend rien._

- **<Aspect 1>** : <ce qui est bien fait + impact>.
- **<Aspect 2>** : <…>.
- **<Aspect 3>** : <…>.

## Hors review (à vérifier en environnement réel)

> _Skill : section **optionnelle** — présente quand le diff dépend d'éléments non observables dans le code (bascule d'infra, redirections d'authentification, délivrabilité des emails, certificats, suite E2E à rejouer après réinitialisation des données). Sert de pense-bête avant déploiement, et recoupe les « Hors scope tests » du plan. Supprimer la section si elle ne s'applique pas._

- <Élément 1 à vérifier en env réel + commande/méthode>.
- <Élément 2 à vérifier>.
- <Régressions préexistantes hors scope, à tracker séparément, le cas échéant>.
