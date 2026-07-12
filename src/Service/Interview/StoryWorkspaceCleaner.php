<?php

declare(strict_types=1);

namespace App\Service\Interview;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Retire du clone maintenu (story 008) le dossier de story **non suivi** produit par une
 * interview, une fois celle-ci en état terminal (proposée ou abandonnée).
 *
 * Sans ce nettoyage, le `brief.md` écrit non suivi persiste (le `git pull --ff-only` de la 008
 * n'y touche pas) et la **prochaine** interview du projet le re-détecterait dès son premier tour
 * ({@see ProducedBriefLocator}), basculant à tort en « brief prêt » sur un slug étranger.
 *
 * Best-effort : c'est de l'hygiène, jamais un point d'échec du parcours — une suppression
 * impossible est silencieusement ignorée (le pire cas retombe sur le comportement d'avant fix).
 */
final readonly class StoryWorkspaceCleaner
{
    public function clean(string $cloneDir, string $storySlug): void
    {
        $storyDir = $cloneDir . '/docs/story/' . $storySlug;

        try {
            new Filesystem()->remove($storyDir);
        } catch (IOException) {
            // Hygiène uniquement : on n'interrompt jamais le parcours pour un échec de suppression.
        }
    }
}
