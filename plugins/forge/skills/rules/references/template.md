# Format d'un fichier de règle

Squelette d'un fichier `.claude/rules/<zone>.md`. À charger au moment de la rédaction (Phase 4).

## Squelette

```markdown
---
paths:
  - "<glob>"
  - "<glob>"
---

# <Zone>

- <règle actionnable>
- <règle actionnable>
```

C'est tout. Pas de titre de projet, pas de date, pas de changelog, pas de « dernière mise à jour »,
pas de section « contexte ». Chaque ligne est injectée en contexte à chaque session qui touche la
zone : tout ce qui n'aide pas à écrire du code est du poids mort payé en boucle.

## Le frontmatter

`paths` est le seul champ. Une liste de globs, entre guillemets.

| Modèle | Matche |
| --- | --- |
| `**/*.ts` | tous les fichiers TypeScript, n'importe où |
| `src/**/*` | tout sous `src/` |
| `*.md` | les Markdown à la racine seulement |
| `src/**/*.{ts,tsx}` | expansion d'accolades pour plusieurs extensions |
| `src/components/*.tsx` | un seul niveau, pas les sous-dossiers |

Trois erreurs qui rendent une règle morte en silence, et que la Phase 5 est là pour attraper :

- **`src/*.php` au lieu de `src/**/*.php`** — un seul `*` ne descend pas dans les sous-dossiers.
- **Un chemin qui n'existe pas** — `app/` quand le projet dit `src/`. Zéro match, zéro déclenchement,
  aucune erreur.
- **Les dossiers en point** — `**` ne fabrique jamais un composant commençant par `.`. Donc
  `plugins/**/*` ramène tout `plugins/` **sauf** `plugins/forge/.claude-plugin/plugin.json`. C'est le
  plus vicieux des trois : le glob n'est pas vide, il a l'air juste, et il rate exactement le fichier
  que la règle visait. Le dossier en point doit apparaître **littéralement** dans le motif —
  `plugins/**/.claude-plugin/*` matche, `.github/workflows/**` aussi. Un compte qui a l'air rond
  (61 au lieu de 62) ne prouve rien : vérifie que les fichiers nommés par la règle sont dans la liste.

Un fichier **sans** `paths` est chargé au lancement comme `.claude/CLAUDE.md`. Ce n'est presque jamais
ce qu'on veut ici : si la règle doit être vraie partout, sa place est dans le `CLAUDE.md`.

## Le corps

Des puces impératives. Une ligne = une décision que Claude peut prendre différemment selon qu'il l'a
lue ou non.

Bon :

```markdown
---
paths:
  - "src/Repository/**/*.php"
---

# Repositories

- Tout QueryBuilder vit ici — jamais dans un contrôleur ni un service.
- Une méthode de repository retourne une entité ou un tableau d'entités, pas un tableau associatif.
- Les jointures fetch sont explicites (`addSelect`) : on ne compte pas sur le lazy loading.
```

Mauvais, et voici pourquoi :

```markdown
# Repositories

- Ce dossier contient les repositories Doctrine du projet.   ← constat, pas règle (→ docs/stack.md)
- Écrire des requêtes performantes.                          ← invérifiable, ne décide rien
- Doctrine 3.2 est installé depuis mars 2024.                ← historique (→ git log)
- Toujours lancer `symfony console` et pas `php bin/console`. ← outillage : aucun paths ne le
                                                                déclenche au bon moment (→ CLAUDE.md)
```

## Taille

Une dizaine de lignes est un bon fichier. Au-delà de trente, tu mélanges deux zones : découpe, et
donne à chaque moitié son `paths`. Un fichier trop gros est aussi le signe qu'on y a mis du contexte
explicatif — coupe-le, la règle se suffit.

## Nommage

`kebab-case.md`, d'après la zone : `doctrine.md`, `twig.md`, `migrations.md`, `marketplace.md`.

Les sous-dossiers (`.claude/rules/frontend/stimulus.md`) sont découverts récursivement — utilise-les
quand le nombre de fichiers le justifie, pas par anticipation.
