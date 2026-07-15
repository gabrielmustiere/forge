# Gérer les projets forge à suivre : les déclarer, les retrouver, maintenir leurs accès

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `docs/story/002-f-gestion-projets/`
> **Amont** : aucun

> L'utilisateur déclare un repo forge (provider + URL + token de lecture), retrouve la liste de ses projets déclarés et en ouvre un, et peut corriger l'URL, renouveler le token ou retirer un projet. C'est la brique qui donne à l'app quelque chose à projeter en kanban.

## Contexte

`login` est livré : l'application est protégée derrière une connexion locale. Mais une fois connecté, l'utilisateur arrive devant une app vide — il n'existe encore aucun moyen de **déclarer un repo à suivre**. Sans projet déclaré, il n'y a rien à scanner, rien à mapper, rien à afficher : toute la chaîne de valeur (connecteur → mapping → kanban) est en aval de cette capacité.

La friction concrète : aujourd'hui, pour savoir où en est un chantier, l'utilisateur ouvre `docs/story/` à la main, repo par repo (cf. `docs/vision.md`, « Le problème »). L'app existe précisément pour supprimer ça — mais elle a besoin d'un point d'entrée où l'utilisateur enregistre **quels repos** il veut voir, avec **quel accès en lecture**. Sans cette gestion des projets, l'app ne dépasse pas l'écran de login.

Cette feature couvre le domaine fonctionnel **D2 — Projets** dans son intégralité : déclaration (C2.1, C2.2), consultation de la liste (C2.4), édition et retrait (C2.5). Elle reste volontairement **autonome** : l'app ne contacte jamais le repo distant et n'en lit rien. La vérification d'éligibilité forge et le scan appartiennent au domaine D3 (connecteur), livrés séparément.

## Alignement vision

- **Problème adressé** : pose le point d'entrée sans lequel « où en est le projet X ? » reste sans réponse dans l'app — condition nécessaire (pas suffisante) de la North Star.
- **Audience servie** : l'utilisateur principal (développeur / product owner solo) qui jongle avec plusieurs repos forge.
- **Principes respectés** : « Lecture seule — la vérité vit dans les fichiers » (la déclaration ne touche jamais le repo, elle stocke une donnée locale à l'app) ; « Zéro friction d'ouverture » (déclarer un projet doit rester une opération de quelques secondes).
- **Exigence réglementaire honorée** : sécurité des tokens d'accès (stockés chiffrés, jamais réaffichés, jamais consignés dans les traces techniques de l'app).
- **Impact North Star** : indirect mais bloquant — sans projets déclarés, aucun kanban ne peut exister.

## Utilisateurs concernés

- **Utilisateur local connecté** (l'unique utilisateur de l'app, mono-utilisateur — cf. anti-objectif vision « backend partagé / multi-utilisateur ») — c'est lui qui déclare, consulte, édite et retire ses projets. Toute la feature n'est accessible qu'une fois connecté (`login`).

## User Stories

- En tant qu'**utilisateur connecté**, je veux **déclarer un projet** en choisissant son provider (GitHub / GitLab), en collant l'URL de son repo et en fournissant un token de lecture, afin que l'app sache quel repo je veux suivre et avec quel accès.
- En tant qu'**utilisateur connecté**, je veux que le **nom du projet soit pré-rempli** à partir de l'URL (`owner/repo`) tout en restant corrigeable, afin de ne pas ressaisir une information déjà contenue dans l'URL, sans être bloqué si l'URL est atypique.
- En tant qu'**utilisateur connecté**, je veux **consulter la liste de mes projets déclarés** (nom, provider, URL, date d'ajout) et en **ouvrir un**, afin de retrouver mes chantiers en un coup d'œil.
- En tant qu'**utilisateur connecté**, je veux **corriger l'URL d'un projet ou renouveler son token** sans avoir à le supprimer et le recréer, afin de réparer un accès cassé (token expiré, repo déplacé) rapidement.
- En tant qu'**utilisateur connecté**, je veux **retirer un projet** que je ne suis plus, derrière une confirmation, afin de nettoyer ma liste sans crainte d'un clic accidentel.
- En tant qu'**utilisateur connecté**, je veux **NE JAMAIS revoir mon token en clair** après l'avoir saisi, afin que ce secret ne fuite pas à l'écran ni dans le code de la page reçue par mon navigateur.

## Règles métier

1. Un projet est défini par un **provider** (GitHub ou GitLab), une **URL de repo** et un **token de lecture**. Les trois sont requis à la déclaration (le nom est déduit, pas requis en saisie).
2. Le **nom est déduit de l'URL** (`owner/repo`) et pré-rempli ; l'utilisateur peut le corriger avant d'enregistrer (saisie manuelle de repli pour les URL atypiques). Le nom n'est jamais lu dans le contenu du repo à ce stade.
3. Les différentes écritures d'une même URL de repo (https ou ssh, avec ou sans suffixe `.git`) désignent **le même repo** : l'app les ramène à une écriture unique de référence, qui sert aussi bien à l'enregistrer qu'à la comparer aux autres.
4. **Unicité** : deux projets ne peuvent pas pointer vers le même repo, quelle que soit l'écriture de son URL. Une déclaration en doublon est refusée avec un message explicite (« ce repo est déjà suivi »).
5. Le **token est conservé chiffré**, n'est **jamais réaffiché en clair** après saisie, n'est **jamais renvoyé au navigateur** et **n'apparaît jamais dans les traces techniques de l'app**.
6. À l'**édition**, le champ token affiche un marqueur masqué (`••••`) signalant qu'un token est présent : laissé tel quel (non modifié) → le token existant est conservé ; une nouvelle valeur saisie → le token est remplacé.
7. Le **provider choisi contraint l'URL acceptée** : une URL doit correspondre à l'hôte du provider sélectionné (github.com pour GitHub, gitlab.com pour GitLab), sinon la déclaration est refusée.
8. La **suppression est définitive** et passe par une confirmation explicite. Rien de scanné n'étant conservé en D2, aucune donnée dérivée n'est à préserver.
9. Le **statut d'un projet est minimal** : « déclaré ». Aucun état de synchronisation, de vérification ou d'erreur d'accès n'est calculé dans cette feature (cela relève de D3).

## Critères d'acceptation

- [ ] Un utilisateur connecté peut déclarer un projet en choisissant un provider (GitHub/GitLab via un sélecteur), en saisissant une URL de repo et un token ; le projet apparaît ensuite dans sa liste.
- [ ] Le nom est pré-rempli en `owner/repo` à partir de l'URL et reste éditable avant enregistrement.
- [ ] Déclarer un repo déjà suivi est refusé avec un message « ce repo est déjà suivi », y compris quand son URL est écrite autrement (https/ssh, avec/sans `.git`).
- [ ] Une URL ne correspondant pas au provider sélectionné est refusée avec un message clair.
- [ ] La liste des projets affiche, pour chacun, nom, provider, URL et date d'ajout, et permet d'ouvrir un projet.
- [ ] Depuis l'édition d'un projet, l'utilisateur peut modifier l'URL et renouveler le token ; laisser le champ token masqué intact conserve le token existant.
- [ ] Le token n'est jamais visible dans la page reçue par le navigateur — y compris en affichant le code source de la page d'édition — ni dans les traces techniques conservées par l'app.
- [ ] Retirer un projet demande une confirmation, puis le fait disparaître définitivement de la liste.

## Hors scope

- **Vérification d'éligibilité forge (C2.3)** : contrôler la présence de `docs/story/` et refuser un repo non-forge suppose de lire le repo distant → feature `verification-projet` livrée avec le connecteur D3. En D2, on déclare sans vérifier.
- **Lecture / scan du repo distant (D3)** : l'app ne contacte jamais GitHub/GitLab. La déclaration est une donnée purement locale à l'app.
- **Statut de synchronisation, de vérification ou d'erreur d'accès** : aucun badge « vérifié / token expiré / injoignable ». Le statut se limite à « déclaré » (les statuts riches arrivent avec la sync, D3).
- **Scannabilité GitLab** : un projet GitLab est **déclarable dès maintenant** (le sélecteur l'accepte), mais **ne sera pas scannable** tant que `connecteur-gitlab-lecture` (V2) n'est pas livré. Limite assumée pour préparer l'affordance multi-provider sans créer d'attente de sync.
- **Multi-utilisateur / partage de projets** : anti-objectif vision. Les projets appartiennent à l'unique utilisateur local.

## Impacts transverses

- **Traduction / langues** : libellés en français (écran de déclaration/édition, liste, messages d'erreur, confirmation de suppression) — pas de contenu multilingue.
- **Droits d'accès** : inchangé — toute la feature n'est accessible qu'une fois connecté, sans nouveau rôle ni niveau d'autorisation supplémentaire.
- **Cloisonnement des données** : non (outil mono-utilisateur : tous les projets déclarés appartiennent au seul utilisateur local).
- **Apparence / déclinaisons** : non.
- **Exposition à des tiers** : non — les projets déclarés ne sont consultables que depuis l'interface de l'app.
- **Emails / notifications** : non.
- **Données existantes** : aucune reprise attendue — les projets déclarés sont une donnée entièrement nouvelle, que l'app se met à conserver (provider, URL, nom, token chiffré, date d'ajout).
- **Comportement par défaut** : sans objet — la gestion des projets n'est pas une option qu'on active : c'est ce que l'utilisateur trouve en arrivant, une fois connecté.

## Questions ouvertes

- **Secret qui protège les tokens** : (a) un secret déjà présent dans l'app, (b) un secret dédié à ce seul usage. → à trancher en plan (impacte la possibilité de renouveler ce secret et de déplacer les données de l'app d'une machine à l'autre).
- **Fluidité de la liste** : l'édition et la suppression se font-elles directement depuis la liste, sans recharger la page ? → à trancher en plan selon l'ambition UX.
- **Que faire à l'« ouverture » d'un projet en D2** : la capacité C2.4 dit « ouvrir un projet », mais le kanban (D4) n'existe pas encore. Options : (a) page détail minimale du projet (ses seules informations déclarées), (b) écran d'attente « kanban à venir ». → à trancher en plan.
- **Ouverture de l'URL du repo** : la carte/liste propose-t-elle un lien sortant vers le repo (GitHub/GitLab) ? → cosmétique, à trancher en plan.

---

## Annexe — Pistes pour le plan

> Pistes brutes — **ne pas concevoir ici**, à trancher en `/forge:feature-implem` (plan).

- **Entité `Project`** candidate : champs `provider` (enum backed string `github`/`gitlab`, cf. convention `src/Enum/Type/`), `url` (forme normalisée, contrainte d'unicité), `name`, `readToken` (chiffré), `createdAt`. À confirmer.
- **Chiffrement du token** : décider du mécanisme (clé dérivée d'`APP_SECRET` ou clé dédiée en variable d'env ; type Doctrine custom `EncryptedString` vs chiffrement dans un manager). Le token ne doit jamais transiter en clair vers le front → attention au binding de formulaire (ne pas hydrater le champ token à l'édition).
- **Normalisation d'URL** : un petit service/value-object dédié (parse provider + `owner/repo`, canonicalise https/ssh/`.git`) réutilisable par la validation, la déduction du nom et le contrôle d'unicité.
- **Validation** : contrainte d'unicité sur l'URL normalisée (`UniqueEntity` ou vérif repository) ; contrainte de cohérence provider ↔ hôte (Callback ou contrainte custom). S'appuyer sur les skills `symfony:validation-*`.
- **Formulaire** : `ProjectType` avec sélecteur provider (button-group radio, cf. DA) ; à l'édition, champ token vide/masqué avec logique « inchangé si non touché » (piste : `FormEvents`/`DataMapper` pour ne pas écraser le token existant). Voir `symfony:form-type` / `symfony:form-advanced`.
- **Liste + ouverture** : contrôleur + repository (pas de QueryBuilder hors repository) ; rendu Twig/Live Component selon la DA de référence (cf. [[design-system-nova]] et `DESIGN.md`).
- **Suppression confirmée** : modale de confirmation (Flowbite) ; hard delete.
