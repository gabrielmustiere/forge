# Phases 1 à 5 — modes Création et Pivot

À utiliser **uniquement** en mode Création ou Pivot. En mode Enrichir ou Éditer, ignorer ce fichier et charger `mode-evolution.md` (dans le même dossier que ce fichier).

## Phase 1 — Domaines fonctionnels

Identifier les **3 à 8 grands blocs métier** qui structurent le produit. Un domaine = un ensemble cohérent de capacités liées par un même objet, acteur ou processus métier.

Exemples (à adapter au projet) :
- SaaS facturation : Comptes & rôles · Catalogue clients · Émission de factures · Suivi paiements · Reporting fiscal.
- Marketplace : Catalogue produits · Comptes vendeurs · Panier & checkout · Logistique · Réclamations.
- App mobile sportive : Profil athlète · Programmes d'entraînement · Suivi de séance · Communauté · Coaching.

Pour chaque proposition de domaine, challenge :
- **Cohérence** : un dev lit le nom du domaine, sait-il immédiatement quel type de capacités va s'y rattacher ?
- **Indépendance** : le domaine peut-il évoluer en partie indépendamment des autres ?
- **Couverture** : tous les besoins identifiés dans la vision rentrent-ils dans un de ces domaines ? Aucun débordement ?

Reformule jusqu'à ce que chaque domaine soit nommé en 1-3 mots, immédiatement parlant pour un membre de l'équipe.

## Phase 2 — Capacités par domaine

Pour chaque domaine identifié, lister les **capacités** : 3 à 10 par domaine. Une capacité = une chose que le produit doit savoir faire, exprimée sous forme de **verbe d'action utilisateur**.

Format : `<acteur> peut <verbe> <objet métier> (pour <bénéfice optionnel>)`

Exemples :
- ✅ « Un commercial peut importer un client depuis un CSV »
- ✅ « Le système peut relancer automatiquement une facture impayée à J+15 »
- ✅ « Un admin peut consulter l'historique d'une commande »
- ❌ « Gestion des utilisateurs » (pas un verbe d'action)
- ❌ « Module CRM avancé » (technique + flou)
- ❌ « Permettre la collaboration » (verbe vide, pas d'objet)

Une capacité ≠ une feature. La capacité dit **quoi**, la feature dira plus tard **comment c'est exposé** (un écran ? un email ? une API ? un cron ?). Plusieurs features peuvent livrer une capacité (MVP minimal puis enrichissements).

Challenge sur :
- **Granularité** : trop large (« gérer les commandes ») ou trop fine (« cliquer sur le bouton supprimer ») ?
- **Acteur explicite** : qui peut le faire ? Tous ? Un rôle précis ? Le système lui-même ?
- **Doublon** : la capacité figure-t-elle déjà dans un autre domaine sous un autre nom ?

## Phase 3 — Parcours utilisateurs principaux

Identifier les **3 à 7 parcours bout-en-bout** qui traversent les capacités. Un parcours = une histoire utilisateur complète, déclenchée par un événement, qui produit un état final.

Format pour chaque parcours :
- **Acteur** : un persona de la vision.
- **Déclencheur** : ce qui lance le parcours (action utilisateur, événement externe, planification).
- **Étapes** : suite de capacités utilisées (référencer les capacités de la phase 2).
- **État final** : ce qui a changé pour l'acteur ou le système une fois le parcours terminé.
- **Fréquence estimée** : combien de fois par jour/semaine/mois ce parcours est emprunté ?

Exemple :
> **Parcours « Émission mensuelle de factures »**
> - Acteur : comptable PME (utilisateur principal de la vision).
> - Déclencheur : début du mois (manuel ou automatique).
> - Étapes : consulter clients à facturer → générer brouillons → vérifier les montants → valider → envoyer par email.
> - État final : factures émises, envoyées, en attente de paiement.
> - Fréquence : 1 fois/mois par utilisateur.

Les parcours servent à **prioriser** : si un parcours est central et très fréquent, ses capacités sont MVP. S'il est rare ou marginal, ses capacités peuvent attendre.

## Phase 4 — Règles métier transverses

Lister les **règles applicables à plusieurs capacités ou parcours** (les règles spécifiques à une feature unique restent pour `/feature-pitch`). Catégories :

- **Permissions et rôles** : qui peut faire quoi globalement (rôles, scopes, isolation multi-tenant…).
- **Workflows et états** : transitions d'état applicables à plusieurs entités métier (ex: brouillon → validé → envoyé → archivé pour tous les documents).
- **Contraintes de gestion** : limites métier transverses (ex: un client ne peut être supprimé s'il a des factures non soldées).
- **Exigences réglementaires** : RGPD, archivage légal, normes sectorielles (DSP2, HDS…).
- **Conventions transverses** : format des identifiants, devises, fuseaux horaires, langues supportées.

Une règle transverse doit pouvoir être citée dans plusieurs specs de feature à venir. Si elle ne concerne qu'une capacité unique, elle ne va **pas** dans le backlog — elle ira dans la spec de la feature correspondante.

## Phase 5 — Backlog dérivé

À partir des capacités (phase 2) et des parcours (phase 3), construire un **backlog priorisé de features candidates**.

Pour chaque ligne de backlog :

- **Slug pressenti** : kebab-case court (`import-clients-csv`, `relance-facture-j15`). Sera repris par `/feature-pitch` (qui pourra l'affiner) lors du cadrage détaillé. Numérotation laissée à `feature-pitch` (compteur global).
- **Pitch 1 ligne** : « Permettre à [acteur] de [action] pour [bénéfice] ».
- **Capacités couvertes** : référencer les capacités de la phase 2 (par identifiant ou nom).
- **Parcours servis** : référencer les parcours de la phase 3 où la feature s'inscrit.
- **Priorité / horizon** : `MVP` (indispensable au lancement) · `V2` (post-lancement court terme) · `V3` (long terme), aligné sur les horizons de la vision.
- **Dépendances** : autres lignes du backlog à livrer avant (par slug).
- **Justification vision** : pointeur explicite vers ce que la feature sert dans `docs/vision.md` (problème, audience, principe, North Star).

Règles de priorisation :
- Une capacité MVP **doit** être couverte par au moins une feature MVP.
- Si un parcours principal a des étapes non couvertes par le MVP, il doit être marqué « partiellement supporté » dans le backlog.
- Refuser un MVP qui contient plus de la moitié des features — forcer l'utilisateur à reporter le non-essentiel.
- Refuser une feature qui ne se rattache à aucune capacité ni parcours — soit elle est hors périmètre, soit la phase 2/3 est incomplète (boucle de retour).

Itérer la phase 5 jusqu'à ce que le backlog soit cohérent : pas de capacité orpheline, pas de feature sans rattachement, pas d'incohérence MVP / parcours.
