# Évolution tech — <Titre — ce qui change techniquement / infra>

> Date : YYYY-MM-DD
> Stack : symfony

<!--
guide: Plan d'une évolution technique (préfixe `-t-`). Consommé par `/forge:tech-implem` (étape build) et `/forge:sync`.
Une évolution tech change l'infrastructure ou un composant technique transverse sans impact métier visible
(montée de version, paramétrage env-driven, bibliothèque remplacée, déploiement). Si du métier change, c'est une feature ;
si la structure du code seule change, c'est un refacto.
Le pitch n'existe pas pour une évolution tech : ce plan porte motivation et détail.
Retirer ce bloc et tous les `> _Skill : ..._` avant commit.
-->

## Problème adressé

> _Skill : pourquoi cette évolution maintenant. Décrire l'état actuel, le déclencheur (déploiement bloqué, dépendance obsolète, scaling, sécurité, prochain projet incompatible). Quantifier si possible (occurrences, durée, taille). Conséquences si on ne fait rien — souvent un blocker de date._

<État actuel + friction concrète + déclencheur.>

**Pourquoi maintenant** : <facteur temporel précis>.

## Brique retenue

> _Skill : décrire la solution technique en termes de patterns + composants. Pas de fichier ici, juste la brique. Préciser la lib/le composant choisi et indiquer s'il introduit une nouvelle dépendance. Lister les alternatives écartées avec raison._

- **Pattern** : <ex: configuration-driven host parsing, factory dynamique, decorator middleware…>.
- **Lib / composant** : <existant projet OU nouvelle dépendance (composer/npm)>. <Préciser si vendor ou maison.>
- **Alternatives écartées** :

| Alternative                                         | Pourquoi écartée                                       |
|-----------------------------------------------------|--------------------------------------------------------|
| <Alternative A>                                     | <raison concise>                                       |
| <Alternative B>                                     | <…>                                                    |

## Point d'intégration

> _Skill : où s'ancre la nouvelle brique dans le code existant. Lister les fichiers (à créer / modifier) avec ce qui change. Documenter le **mécanisme d'extension** mobilisé (services Symfony standards, env injection, compiler pass, etc.). Conclure par **Impact sur les clients existants** : ce qui change ou non pour les consommateurs._

- **`src/<…>.php`** : <description du changement / création>.
- **`src/<…>.php`** : <…>.
- **Mécanisme d'extension** : <ex: services Symfony standards (autowire de variable d'env), aucun touche-à-vendor>.

**Impact sur les clients existants** :

- <ex: aucune signature publique modifiée — les services gardent leurs constructeurs>.
- <ex: format de host attendu change — tous les appelants qui forgeaient des URLs doivent migrer>.

## Critères de succès mesurables

> _Skill : table « métrique → baseline → cible → méthode de mesure ». Quantifier — c'est la différence majeure avec un plan de feature/refacto. Pour une évolution sans métrique chiffrable (« éradication d'une convention »), le dire explicitement et donner un critère binaire vérifiable._

| Métrique                                | Baseline actuelle    | Cible              | Méthode de mesure                         |
|-----------------------------------------|----------------------|--------------------|-------------------------------------------|
| <ex: occurrences résiduelles de X>      | <chiffre + commit>   | **0**              | `grep -rn '<pattern>' …`                  |
| <ex: suite Unit + Functional>           | passante             | **0 régression**   | `make test`                               |
| <ex: accès local sur les hosts cibles>  | n/a                  | **HTTP 200 / 302** | `curl -kI`                                |
| <ex: bascule preprod>                   | n/a                  | **login OK**       | navigation manuelle + envoi login link    |
| <ex: bascule prod>                      | n/a                  | **login OK**       | navigation manuelle                       |

> _Skill : si l'évolution n'est pas un changement de perf, dire « ce n'est pas un changement de perf — la cible est binaire et vérifiable par <…> ». Cf. story 019._

## Rollback et compatibilité

> _Skill : section obligatoire pour un `-t-` car l'impact peut toucher de la prod. Décrire le kill switch (variable d'env, alias service, feature flag, DNS), le comportement en panne de dépendance, la cohabitation entre ancienne et nouvelle version, et les effets sur sessions/cookies si applicable._

- **Kill switch** : <variable / alias / mécanisme de rollback sans redéploiement applicatif>.
- **Comportement en panne de dépendance** : <ex: aucune nouvelle dépendance d'infra — logique 100 % in-process>.
- **Cohabitation** : <oui/non + justification>.
- **Sessions / cookies** : <impact bascule, action utilisateur requise>.

## Impacts transverses

> _Skill : effets sur les zones non directement touchées par la brique. Modules clients, migration de données, impacts prod/infra (DNS, OAuth, mailer, certificats, secrets), sécurité (nouveau vecteur ? vecteur supprimé ? tests à ajouter). Toujours indiquer migration de données — soit « aucune », soit la nature._

- **Modules clients impactés** : <liste>.
- **Migration de données** : <aucune / nature>.
- **Impacts prod / infra** : <DNS, OAuth, mailer, certificats, secrets — préciser ce qui doit être configuré côté infra avant déploiement>.
- **Sécurité** : <nouveau vecteur ou non, tests à ajouter explicitement>.

## Plan d'exécution incrémental

> _Skill : séquence d'étapes commitables, incluant les actions infra (DNS, OAuth, configs de provider). Chaque étape doit dire si elle inclut une bascule prod/preprod ou si elle est purement applicative. Pour une bascule infra, lister les pré-requis externes._

1. [ ] **Étape 1 — <Refacto code + tests unit>** (no-op fonctionnel)
   - <description + fichiers + vérification>.
2. [ ] **Étape 2 — <Bascule dev local + tests Functional + E2E>**
   - <description>.
3. [ ] **Étape 3 — <Bascule preprod>**
   - **Côté infra** : <DNS, OAuth, mailer — pré-requis>.
   - **Vérifier** : <navigation, mails, …>.
4. [ ] **Étape 4 — <Bascule prod>**
   - **Côté infra** : <…>.
   - **Vérifier** : <…>.
5. [ ] **Étape 5 — <Nettoyage documentation>**
   - <CLAUDE.md, docs/ressources/, ADR éventuelle>.

## Critères de sortie

> _Skill : checkboxes vérifiables avant clôture. Suite verte, grep clean, accès cible OK, bascule validée, kill switch testé, doc alignée. Plus exigeant qu'un plan de feature car l'impact peut être prod._

- [ ] Suite Unit verte.
- [ ] Suite Functional verte.
- [ ] Suite E2E verte.
- [ ] `grep -rn '<pattern obsolète>' …` retourne 0 hit.
- [ ] Accès local validé : <commande curl + statut attendu>.
- [ ] Bascule preprod validée : <login + service annexes accessibles>.
- [ ] Bascule prod validée : <login + certificat + Sentry sans erreur>.
- [ ] Kill switch testé : rollback symbolique <documenté dans le compte rendu>.
- [ ] PHPStan level 5 : 0 erreur.
- [ ] PHP-CS-Fixer : aucun diff.
- [ ] Documentation projet alignée (CLAUDE.md + `docs/ressources/`).

## Risques et mitigations

> _Skill : table risque/probabilité/mitigation. Couvrir : cookies/sessions perdus, certificats non délivrés, OAuth callbacks non enregistrés, mailer DKIM/SPF, test oublié encore sur ancien pattern, perte d'accès admin, dépendance externe down._

| Risque                                              | Probabilité       | Mitigation                                     |
|-----------------------------------------------------|-------------------|------------------------------------------------|
| <Risque 1>                                          | faible/moyen/élevé| <mitigation>                                   |
| <Risque 2>                                          | <…>               | <…>                                            |

## Questions ouvertes

> _Skill : à clarifier avant ou pendant l'exécution. Souvent : accès aux consoles externes (Gandi, Azure, Google Cloud, Mailgun), ADR à créer ou non, tests de host hostile à ajouter. Annoter `→ tranché : <choix>` après coup._

- **<Question 1>** : <énoncé + options>.
- **<Question 2>** : <…>.

---

## Changelog

> _Skill : ajouté par `/forge:sync` après livraison si le plan a divergé du code livré. Une ligne par sync, daté. Pour un `-t-`, signaler aussi les étapes infra (preprod/prod) restées à exécuter — c'est de la dette traçable._

| Date       | Type                      | Description |
|------------|---------------------------|-------------|
| YYYY-MM-DD | Sync post-implémentation  | <sections impactées + raison + étapes infra encore à exécuter le cas échéant> |
