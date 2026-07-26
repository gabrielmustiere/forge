# Proposer un ADR (référence partagée)

Référence chargée par les skills qui **repèrent** une décision d'architecture sans la graver :
les trois plans, les trois `*-implem`, `review`, `report` et `sync`. Elle répond à une question :
quand faut-il suggérer un ADR, et comment le faire sans devenir du bruit.

L'ADR reste écrit par `adr`, et par lui seul ([`skill-boundaries.md`](skill-boundaries.md) §2).
Ces skills **proposent**, ils ne rédigent pas.

## Pourquoi cette référence existe

Une décision d'architecture ne se perd pas au moment où on la prend — elle se perd au moment où
on passe à la suite. Le plan qui a écarté deux alternatives, l'implem qui a dû trancher un choix
non prévu, la review qui découvre un pattern jamais justifié : à chaque fois, quelqu'un sait
*pourquoi*, et personne ne l'écrit. Six mois plus tard, le code montre le *quoi* et le *pourquoi*
a disparu.

Les skills du pipeline sont les seuls témoins de ces moments. D'où la proposition — au moment où
la décision est encore fraîche et argumentée, pas six mois après.

Le matériau est souvent déjà là : `### Alternatives écartées` est une section **obligatoire des
trois plans** ([`document-format.md`](document-format.md) §2c). Un plan contient donc déjà le
contexte, l'option retenue et les options rejetées — l'essentiel d'un ADR.

## Le test d'ADR-ité

Une décision mérite un ADR si elle coche **au moins 3 des 4** critères :

1. **Structurante** — elle engage plus que le fichier courant : un contrat d'interface, une
   dépendance, un modèle de données, une frontière de module, un protocole d'échange.
2. **Coûteuse à inverser** — revenir en arrière coûterait une migration, une reprise de données
   ou une casse d'API. Pas un `git revert`.
3. **Alternative sérieuse écartée** — au moins une option crédible a été rejetée, pour une raison
   qui ne se déduit pas du code.
4. **Question du futur lecteur** — dans six mois, quelqu'un devant ce code demandera
   « pourquoi comme ça ? » et le code seul ne répondra pas.

En dessous de 3 sur 4, **ne propose rien**. Les cas qui échouent typiquement : un choix de nommage,
l'usage d'un helper du framework, une décision que la convention projet imposait de toute façon,
un correctif de bug — même astucieux.

**Un ADR n'est pas une décision produit.** Une audience, une priorité de backlog, un arbitrage de
périmètre relèvent de `vision` et `product-backlog`. L'ADR grave un choix **technique**.

## Règles anti-bruit

Une proposition ignorée neuf fois sur dix ne sera plus lue la dixième. Cinq règles la gardent
crédible :

1. **Une seule proposition par passe.** Si deux décisions qualifient, propose **la plus
   structurante** et mentionne l'autre en une incise. Jamais de liste à cocher.
2. **En clôture, jamais en cours de route.** La proposition ne coupe pas le travail : elle arrive
   avec le récapitulatif final, une fois l'artifact écrit.
3. **Jamais bloquante.** Pas de question, pas d'`AskUserQuestion`, pas d'attente de réponse. Une
   ligne, et le skill se termine.
4. **Pas de doublon.** Avant de proposer, regarde `docs/adr/` (via `Glob`) : si un ADR couvre
   déjà la décision, ne propose rien. S'il en existe un **contredit** par la décision, dis-le —
   c'est un `supersedes`, et c'est plus intéressant qu'une proposition neuve.
5. **Silence par défaut.** L'absence de proposition est le cas normal. La plupart des passes ne
   produisent aucune décision d'architecture, et c'est très bien.

## Format de la proposition

Une ligne, en fin de clôture, après la « prochaine étape » :

> 💡 Décision candidate à un ADR : **<décision en 5-10 mots>** — <le critère qui la qualifie, en
> une demi-ligne>. Pour la graver : `/forge:adr <slug-story>`

Exemples :

> 💡 Décision candidate à un ADR : **stocker les sessions en Redis plutôt qu'en base** — alternative
> sérieuse écartée (table `sessions`) et retour arrière coûteux. Pour la graver : `/forge:adr 014-t-sessions-redis`

> 💡 Décision candidate à un ADR : **frontière de module entre facturation et catalogue** — engage
> tous les futurs appels entre les deux domaines. Pour la graver : `/forge:adr 021-r-split-billing`

Cas du doublon contredit :

> ⚠️ Cette décision contredit `docs/adr/0004-sessions-en-base.md`. Un ADR qui le remplace serait
> plus juste qu'une correction : `/forge:adr 014-t-sessions-redis`

Ce qu'il ne faut pas écrire : « Voulez-vous rédiger un ADR ? » (question bloquante), « Pensez à
documenter vos décisions » (rappel générique, sans décision nommée), ou une liste de trois
candidats laissée à l'arbitrage.

## Où chaque skill regarde

| Skill | Ce qui fournit la décision candidate |
|---|---|
| `feature-plan` | §Approche retenue et §Alternatives écartées — surtout dépendance nouvelle, modèle de données, frontière de module |
| `refactor-plan` | Le découpage cible : un refacto acte souvent un pattern qui survivra au refacto |
| `tech-plan` | Presque toujours : composant retenu, stratégie de résilience, dispositif de repli. C'est le track le plus dense en ADR |
| `feature-implem` / `refactor-implem` / `tech-implem` | Un choix **non prévu au plan**, tranché en cours d'exécution. Cas le plus précieux : rien d'autre ne le trace |
| `review` | Un finding qui révèle une décision structurante sans justification écrite nulle part |
| `report` | Un écart intention/réel qui est en fait un changement d'approche assumé |
| `sync` | Une divergence durable repérée en réalignant — y compris un `stack.md` enrichi d'une dépendance structurante |

Pour les `*-implem`, le déclencheur est **l'écart au plan**, pas la conformité : une exécution
fidèle à un plan déjà porteur de la décision n'a rien à proposer (le plan l'a déjà fait).
