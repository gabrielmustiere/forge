# Amorçage depuis le template Symfony par défaut

Procédure utilisée par `/stack` dans le **cas greenfield** (mode Création, aucune brique
technique détectée et aucun choix arrêté). Plutôt que de graver un `docs/stack.md` vide,
on amorce le projet depuis le template de référence, puis on le personnalise à son nom.

> Ne joue cette procédure **que** si l'utilisateur a explicitement choisi « Amorcer depuis
> le template Symfony » à la question de la Phase 2bis. Sinon, ne touche à aucun fichier.

## Le template

`gabrielmustiere/symfony-template` — https://github.com/gabrielmustiere/symfony-template

Stack embarquée : **Symfony 8** (PHP ≥ 8.5), **SQLite** (`var/data.db`), **Symfony Messenger**
(transport Doctrine), **Tailwind CSS 4** + Flowbite via AssetMapper, **Stimulus / Symfony UX**,
**PHPUnit 13** + **Playwright** (E2E), **PHPStan** + **PHP-CS-Fixer**, **Mailpit** (docker,
capture d'e-mails), serveur **MCP `symfony-ai-mate`** (dossier `mate/`), design system **« Paper »**
(`DESIGN.md`, tokens `@theme` dans `assets/styles/app.css`). Tout passe par un `Makefile`
(`make init`, `make serve`, `make quality`, `make ci`…).

C'est le socle par défaut d'un projet neuf : si l'utilisateur n'a pas de contrainte de stack,
c'est ce qu'on propose.

## Récupération

Le dépôt cible est **déjà** un dépôt forge (il contient `docs/vision.md`,
`docs/product-backlog.md`, éventuellement `docs/story/`). Il ne faut donc **jamais** cloner
par-dessus ni écraser ces artefacts. Procédure sûre :

1. Cloner le template dans un dossier temporaire, sans historique :
   ```bash
   git clone --depth 1 https://github.com/gabrielmustiere/symfony-template.git /tmp/forge-tpl
   rm -rf /tmp/forge-tpl/.git
   ```
2. Nettoyer ce qui appartient à l'**histoire du template** et non au nouveau projet :
   ```bash
   rm -rf /tmp/forge-tpl/releases /tmp/forge-tpl/CHANGELOG.md
   ```
   (Le nouveau projet repart d'un changelog vierge — le `/forge:release` du projet le remplira.)
3. Copier le socle applicatif dans le dépôt, **sans écraser** les artefacts forge existants
   (`docs/vision.md`, `docs/product-backlog.md`, `docs/story/`, un `docs/stack.md` déjà amorcé).
   Le template n'apporte pas ces fichiers, il n'y a donc pas de collision sur `docs/` — sauf
   `docs/adr/0001-*.md` (un ADR d'exemple du template) : **ne le copie pas** s'il ferait doublon
   avec les ADR du projet, sinon renomme-le pour laisser le compteur ADR du projet libre.
4. `CLAUDE.md`, `README.md`, `DESIGN.md` : le template en fournit. S'ils **existent déjà** dans
   le projet, **demande** avant d'écraser. S'ils n'existent pas, copie ceux du template — ils
   seront personnalisés à l'étape suivante (et `CLAUDE.md` pourra être régénéré plus tard via
   `/forge:claude-md`).

## Personnalisation — carte des variables à transformer

Le template porte l'identité `template` (domaine local `template.wip`). Il faut la remplacer par
le nom du nouveau projet. Demande le **nom du projet** (slug kebab-case, ex. `mon-app`) puis
applique **toutes** les substitutions ci-dessous — c'est le cœur de l'amorçage.

| Fichier | Variable / emplacement | Valeur du template | Remplacer par |
|---|---|---|---|
| `.symfony.local.yaml` | `proxy.domains:` (liste) | `- template` | `- <projet>` → domaine local `<projet>.wip` |
| `.env` | `DEFAULT_URI` | `"http://template.wip"` | `"http://<projet>.wip"` |
| `.env.dev` | `APP_SECRET` | valeur figée du template | **régénérer** (voir ci-dessous) |
| `compose.yaml` | `services.mailpit.container_name` | `template-mailpit-dev` | `<projet>-mailpit-dev` |
| `README.md` | titre H1 + badge CI + occurrences `template.wip` | `# Symfony Template`, `gabrielmustiere/symfony-template` | nom du projet + son dépôt |
| `CLAUDE.md` | ligne de description (haut de fichier) | « Template Symfony 8… » | une phrase décrivant le nouveau projet |

Régénération de `APP_SECRET` (ne jamais réutiliser le secret du template) :
```bash
php -r "echo bin2hex(random_bytes(16)).PHP_EOL;"
```
Écris la valeur obtenue dans `.env.dev` (clé `APP_SECRET`). Laisse `APP_SECRET=` vide dans `.env`.

Notes :
- `composer.json` est un projet sans champ `name` (`"type": "project"`) — rien à renommer là, sauf
  si l'utilisateur veut un `name` explicite (`vendor/projet`).
- Ne modifie **pas** les `container_name`, DSN ou ports au-delà de ce tableau sans raison : le
  template est déjà cohérent.

## Après l'amorçage

1. Rappelle à l'utilisateur les commandes de démarrage (elles ne sont pas jouées par le skill) :
   ```bash
   make init            # deps + DB + fixtures
   docker compose up -d # Mailpit
   make serve           # serveur Symfony → https://<projet>.wip
   ```
2. **Cartographie maintenant réelle** : la stack existe enfin sur disque. Enchaîne sur la Phase 1
   de détection normale (elle prouvera Symfony 8, SQLite, Tailwind, etc. par les fichiers qu'on
   vient d'écrire), puis rédige `docs/stack.md` en mode Création à partir de ces preuves.
   Le changelog du fichier note l'origine : `AAAA-MM-JJ — Création — amorcé depuis
   gabrielmustiere/symfony-template`.
