# Review — <Titre de la story>

> Date : YYYY-MM-DD
> Stack : symfony
> Périmètre : <ex: working tree (~29 fichiers modifiés + 9 nouveaux, ~660 lignes diff)>
> Référence d'intention : `docs/story/<NNN>-<f|r|t>-<slug>/plan.md` <!-- guide: + `pitch.md` pour `-f-` -->

<!--
guide: Compte rendu de revue du diff par rapport à l'intention. Produit par `/forge:review`.
Trois niveaux de criticité — chacun avec checkbox `[ ]` / `[x]` indiquant si l'item a été corrigé en cours de review.
Le verdict final indique READY TO COMMIT ou non.
Retirer ce bloc et tous les `> _Skill : ..._` avant commit.
-->

## Bloquants

> _Skill : findings qui empêchent le merge — bugs, failles de sécurité, isolation tenant cassée, divergence comportementale du plan, régression mesurable. Format : `[x] **[TAG] <résumé>** — <description courte + correctif appliqué OU action requise>`. Tags : BUG, SECU, PLAN, ARCHI, MIGRATION. Cocher `[x]` quand corrigé pendant la passe de review. Si aucun, mettre « _(aucun)_ »._

- [ ] **[BUG] <description>** — `<chemin>:<ligne>` — <pourquoi c'est bloquant + fix proposé OU correctif appliqué>.
- [ ] **[PLAN] <description>** — <écart majeur avec le plan + résolution>.
- [ ] **[SECU] <description>** — <faille + mitigation>.

## Importants

> _Skill : findings à corriger avant le merge mais qui ne sont pas bloquants si correction acceptée par le user. Souvent : ARCHI/CONV/BUG/PLAN. Mêmes format/tags que bloquants. Idéalement tous cochés avant verdict READY TO COMMIT._

- [ ] **[ARCHI] <description>** — `<chemin>:<plage de lignes>` — <pourquoi important + action ou correctif>.
- [ ] **[CONV] <description>** — `<chemin>:<ligne>` — <…>.

## Mineurs

> _Skill : améliorations utiles mais non bloquantes. Souvent : STYLE, DOC, I18N, PERF, ROBUSTESSE. Peuvent être laissés non cochés et inscrits au backlog/dette technique du report. Garder courts et actionnables._

- [ ] **[STYLE] <description>** — `<chemin>:<ligne>` — <correction suggérée>.
- [ ] **[DOC] <description>** — `<chemin>` — <correction suggérée>.
- [ ] **[I18N] <description>** — <correction suggérée>.
- [ ] **[PERF] <description>** — <correction suggérée>.

## Points positifs

> _Skill : ce qui mérite d'être souligné — pattern bien appliqué, test exhaustif, refacto propre, ADR documenté. Renforce la confiance du reviewer et signale à l'auteur ce qui est à reproduire. 3–6 puces._

- **<Aspect 1>** : <ce qui est bien fait + impact>.
- **<Aspect 2>** : <…>.
- **<Aspect 3>** : <…>.

## Verdict

> _Skill : statut final. Compteurs « restants » = items encore `[ ]` non résolus. Statut possible : **READY TO COMMIT** (0 bloquants), **READY TO COMMIT sous réserve de <action>**, **CHANGES REQUESTED** (bloquants ou importants non résolus). Indiquer la prochaine étape (commit, fix, re-review)._

- Bloquants restants : N / total
- Importants restants : N / total
- Statut : **READY TO COMMIT** <!-- ou : CHANGES REQUESTED -->

<Une phrase sur la prochaine étape : « /commit pour commit et push. » OU « Corriger les bloquants puis relancer la review. »>

## Hors review (à vérifier en environnement réel)

> _Skill : section optionnelle, présente quand le diff dépend d'éléments non observables en code (bascule infra, OAuth callbacks, mailer DKIM, certificats, suite E2E à rejouer après reset DB). Sert de runbook avant déploiement. Supprimer si non applicable._

- <Élément 1 à vérifier en env réel + commande/méthode>.
- <Élément 2 à vérifier>.
- <Régressions préexistantes hors scope à tracker séparément, le cas échéant>.
