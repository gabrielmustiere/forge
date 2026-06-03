# Refacto — <Titre du refacto — ce qui change structurellement>

> Date : YYYY-MM-DD
> Stack : symfony
> ADR : `docs/adr/<NNNN>-<slug>.md` <!-- guide: optionnel, supprimer la ligne si pas d'ADR associée -->

<!--
guide: Plan d'un refacto (préfixe `-r-`). Consommé par `/workflow:refactor-implem` (étape build) et `/workflow:sync`.
Un refacto ne change PAS le comportement externe. Si du métier change, c'est une feature.
Le pitch n'existe pas pour un refacto : ce plan porte à la fois la motivation et le détail.
Retirer ce bloc et tous les `> _Skill : ..._` avant commit.
-->

## Motivation

> _Skill : pourquoi refactorer maintenant. État actuel chiffré quand possible (lignes, durée profile, hits grep, complexité cyclomatique). Lister les conséquences si on ne fait rien — la dette s'aggrave-t-elle ? Y a-t-il un déclencheur (perf, sécurité, prochaine feature bloquée) ?_

<État actuel + friction concrète. Au moins 1 chiffre vérifiable si possible (ex : 1 807 ms cumulés, 80 occurrences résiduelles, etc.).>

<Conséquences si on ne fait rien.>

## Périmètre

### Code visé

> _Skill : table « fichier → lignes → ce qu'on touche ». Liste exhaustive (un reviewer doit pouvoir vérifier qu'aucun fichier n'a été oublié)._

| Fichier                            | Lignes | Action                                          |
|------------------------------------|-------:|-------------------------------------------------|
| `src/<…>.php`                      |    XXX | <verbe d'action : extraire / éclater / drop…>   |
| `src/<…>.php`                      |    XXX | <…>                                             |

### Clients identifiés

> _Skill : tout ce qui consomme le code visé. Les patterns de référence (Twig `path('…')`, redirectToRoute, tests, configs sécurité, E2E). Indiquer si oui ou non chaque catégorie de client est affectée par le refacto. Si « aucun client n'est affecté », le dire — c'est un gage de sécurité._

- `templates/**/*.html.twig` — <impacté ou non + raison>.
- Tests Functional — <impacté ou non>.
- Tests E2E Playwright — <impacté ou non>.
- `config/packages/<…>.yaml` — <impacté ou non>.

### Hors scope

> _Skill : ce qui pourrait être absorbé dans ce refacto mais ne l'est pas. Préserve le périmètre face à la review._

- **<Sujet>** : <raison brève (ex: refacto séparé, métier hors structurel, etc.)>.

## Cible

### Forme attendue après refacto

> _Skill : décrire la structure CIBLE. Schéma ASCII de l'arborescence si nouvelle couche. Interface(s) clé(s). Contrat. Comment les anciens callers consomment la nouvelle forme._

<Description de la forme cible. Pour un refacto qui introduit une nouvelle couche, ajouter une arborescence et l'interface principale.>

```
<arborescence cible>
```

```php
interface <…>
{
    public function <…>(<…>): <…>;
}
```

### Pattern de refacto

> _Skill : nommer le pattern (Strangler Fig, Extraction de classe, Move Method, Introduce Parameter Object…). Justifier pourquoi ce pattern convient à la situation. Si pas de pattern formel (« simple renommage + extraction »), le dire._

**<Nom du pattern>**. <Justification 1–2 phrases.>

### Alternatives écartées

> _Skill : table ou liste. Chaque alternative avec sa raison de rejet. Sans ce bloc, la review reposera ces questions._

| Alternative                                         | Pourquoi écartée                                       |
|-----------------------------------------------------|--------------------------------------------------------|
| <Alternative A>                                     | <raison concise>                                       |
| <Alternative B>                                     | <…>                                                    |

## Comportement externe à préserver

> _Skill : invariants observables que le refacto ne doit PAS changer. URLs, noms de routes, signatures publiques, statut HTTP, content-type, side-effects (mails, dispatch event, audit log), templates rendus, flash messages, chaîne tenant. Pour chaque, dire explicitement « inchangé ». Liste consultée à chaque étape du refacto._

- **URLs** : <tous les paths préservés / liste des changements si non>.
- **Noms de routes** : <tous les `name:` préservés / liste sinon>.
- **Signatures publiques** : <…>.
- **Sécurité** : <`#[IsGranted]` répliqués / firewall inchangé / voters inchangés>.
- **Multi-tenancy** : <chaîne `OrganizationContext` inchangée>.
- **Side-effects** : <envois mail, audit logs, dispatch workflow — déclenchés aux mêmes endroits>.
- **Templates rendus** : <mêmes chemins, mêmes variables>.
- **Flash messages** : <mêmes clés, mêmes contenus>.

## Stratégie de caractérisation

> _Skill : section critique pour un refacto. Décrire AVANT le code applicatif comment on verrouille le comportement actuel. Deux blocs : (1) ce qui existe déjà et sert de filet ; (2) ce qu'il faut écrire de nouveau AVANT de toucher au code de production. Si pas de tests de caractérisation supplémentaires (cas du refacto purement structurel à risque faible), l'expliciter avec la raison._

### Tests existants utilisés comme filet

| Test                                                       | Ce qu'il couvre                              | Niveau     |
|------------------------------------------------------------|----------------------------------------------|------------|
| `tests/Functional/<…>Test.php`                             | <comportement couvert>                       | functional |
| `tests/Unit/<…>Test.php`                                   | <…>                                          | unit       |

### Tests de caractérisation à écrire AVANT le refacto

> _Skill : règle absolue — aucun code de production touché tant que ces tests ne sont pas verts et committés. Si la décision est « pas de caractérisation supplémentaire », sauter ce sous-bloc et le déclarer explicitement avec le risque accepté._

| Test à créer                                               | Comportement à verrouiller                   | Niveau     |
|------------------------------------------------------------|----------------------------------------------|------------|
| `tests/<…>/<…>Test.php`                                    | <invariant>                                  | functional |
| `tests/fixtures/<…>.json`                                  | <capture payload>                            | fixture    |

**Règle absolue** : aucun code de production touché tant que ces tests ne sont pas écrits, verts, et committés.

## Stratégie d'exécution incrémentale

> _Skill : décomposer en étapes commitables. Chaque étape doit être déployable seule (revert atomique). Numérotation, fichiers créés/modifiés par étape, vérification à effectuer (caractérisation verte, suite Unit verte, perf mesurée). Statuer explicitement sur Strangler Fig vs feature flag._

### Étapes

1. [ ] **Étape 0 — <nom>**
   - Objectif : <résultat attendu>.
   - Fichiers créés : <…>.
   - Vérification : <suite verte + critère mesurable>.
   - Commit isolé : oui/non.

2. [ ] **Étape 1 — <nom>**
   - <…>

### Strangler Fig / feature flag

> _Skill : statuer explicitement. « Strangler Fig actif : ancien et nouveau coexistent aux étapes X et Y derrière l'interface I. » OU « Pas de feature flag : chaque étape est elle-même un toggle (alias service / commit revert). »_

<Décision + justification.>

## Critères de réussite

> _Skill : checkbox cochée à la livraison. Comportement préservé, perf mesurée si applicable, tests verts, qualité (PHPStan + CS-Fixer). Mesurable, pas qualitatif._

- [ ] Tous les tests de caractérisation passent **avant ET après** chaque étape.
- [ ] Suite complète (`symfony php bin/phpunit` + `npm run test:e2e`) sans nouvelle régression.
- [ ] <Critère structurel : ex « 0 occurrence résiduelle de `<pattern>` mesurée par `grep …` »>.
- [ ] <Critère perf le cas échéant : ex « panel serializer < 200 ms (vs 1 807 ms aujourd'hui) »>.
- [ ] Chaque étape committée est déployable seule (revert atomique possible).
- [ ] PHPStan level 5 : 0 nouvelle erreur. CS-Fixer clean.

## Risques et mitigations

> _Skill : table « risque → probabilité → mitigation ». Couvrir au minimum : isolation tenant si le code touche au filtrage, casse d'API publique, divergence comportementale subtile (valeur par défaut, conversion de type, null), perf inattendue, dépendance externe non vérifiée._

| Risque                                              | Probabilité     | Mitigation                                     |
|-----------------------------------------------------|-----------------|------------------------------------------------|
| <Risque 1>                                          | faible/moyen/élevé | <mitigation concrète>                       |
| <Risque 2>                                          | <…>             | <…>                                            |

## Questions ouvertes

> _Skill : décisions encore non prises. À trancher en design détaillé ou en cours d'exécution. Annoter `→ tranché : <choix>` après coup._

- **<Question 1>** : <énoncé + options>.
- **<Question 2>** : <…>.

---

## Changelog

> _Skill : ajouté par `/workflow:sync` après livraison si le plan a divergé du code. Une ligne par sync, daté._

| Date       | Type                      | Description |
|------------|---------------------------|-------------|
| YYYY-MM-DD | Sync post-implémentation  | <sections impactées + raison> |
