#!/usr/bin/env python3
"""Vérifie que la version affichée par site/ correspond à celle du plugin.

Le site est statique : sa version est écrite en dur à plusieurs endroits, et
/forge:release ne touche que CHANGELOG.md. Sans ce garde-fou, le site continue
d'annoncer l'ancienne version après chaque release — c'est déjà arrivé.

Chaque occurrence est marquée dans la source pour être trouvée ici ; le compte
attendu est figé pour qu'une occurrence supprimée ou ajoutée sans mise à jour de
ce script fasse échouer la CI plutôt que de passer inaperçue.
"""

import json
import pathlib
import re
import sys

ROOT = pathlib.Path(__file__).resolve().parent.parent
PLUGIN_MANIFEST = ROOT / "plugins" / "forge" / ".claude-plugin" / "plugin.json"

# fichier -> (regex capturant la version, nombre d'occurrences attendues)
SITE_SOURCES = {
    "site/index.html": [
        (r'data-forge-version>v(\d+\.\d+\.\d+)<', 1),
        (r'"softwareVersion": "(\d+\.\d+\.\d+)"', 1),
    ],
    "site/docs/index.html": [
        (r'data-forge-version>v(\d+\.\d+\.\d+)<', 1),
        (r'"softwareVersion": "(\d+\.\d+\.\d+)"', 1),
    ],
    "site/llms.txt": [
        (r'^Version : (\d+\.\d+\.\d+)', 1),
    ],
}


def main() -> int:
    expected = json.loads(PLUGIN_MANIFEST.read_text(encoding="utf-8"))["version"]
    errors = []

    for relative_path, patterns in SITE_SOURCES.items():
        path = ROOT / relative_path
        if not path.is_file():
            errors.append(f"{relative_path} — fichier introuvable")
            continue

        content = path.read_text(encoding="utf-8")
        for pattern, expected_count in patterns:
            found = re.findall(pattern, content, re.MULTILINE)

            if len(found) != expected_count:
                errors.append(
                    f"{relative_path} — {len(found)} occurrence(s) pour /{pattern}/, "
                    f"{expected_count} attendue(s). Mettre à jour ce script si la page a changé."
                )

            for version in found:
                if version != expected:
                    errors.append(
                        f"{relative_path} — version {version} affichée, {expected} attendue "
                        f"(source : plugins/forge/.claude-plugin/plugin.json)"
                    )

    if errors:
        print(f"Version du plugin : {expected}\n", file=sys.stderr)
        for error in errors:
            print(f"  ✗ {error}", file=sys.stderr)
        print(
            "\nLe site annonce une version qui n'est pas celle du plugin.",
            file=sys.stderr,
        )
        return 1

    print(f"✓ site/ annonce bien la version {expected}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
