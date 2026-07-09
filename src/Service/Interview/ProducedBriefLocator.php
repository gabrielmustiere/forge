<?php

declare(strict_types=1);

namespace App\Service\Interview;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Détecte, **sur le filesystem**, le brief que le skill vient d'écrire dans le clone —
 * plutôt que de le deviner dans le texte de la réponse (story 009, approche retenue).
 *
 * Le clone maintenu (story 008) est propre par construction (`git pull --ff-only`) : un
 * `docs/story/NNN-f-<slug>/brief.md` **non suivi** (`git status --porcelain`) est donc, sans
 * ambiguïté, le livrable produit par le tour courant. Git peut replier un dossier entièrement
 * nouveau en une seule entrée (`?? docs/story/NNN-f-<slug>/`) : on confirme alors la présence
 * du `brief.md` sur le disque avant de retourner le slug.
 */
final readonly class ProducedBriefLocator
{
    /** Borne haute d'un `git status` (secondes) : trivial sur un clone sain. */
    private const TIMEOUT_SECONDS = 30.0;

    private const STORY_PATH = '#docs/story/(\d{3}-f-[a-z0-9-]+)/#';

    /**
     * Retourne le slug `NNN-f-<slug>` du brief non suivi présent dans le clone, ou `null`
     * si aucun n'a (encore) été produit.
     *
     * @throws InterviewFailedException `git` absent, dossier illisible, délai dépassé
     */
    public function locate(string $cloneDir): ?string
    {
        $process = new Process(['git', '-C', $cloneDir, 'status', '--porcelain', '--untracked-files=all']);
        $process->setTimeout(self::TIMEOUT_SECONDS);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            throw new InterviewFailedException('Délai dépassé en cherchant le brief produit.');
        }

        if (!$process->isSuccessful()) {
            throw new InterviewFailedException('Impossible de lire l\'état du dépôt local pour détecter le brief.');
        }

        foreach (explode("\n", $process->getOutput()) as $line) {
            // Format porcelain : 2 caractères de statut + espace, puis le chemin.
            $path = ltrim(substr($line, 3));
            if ('' === $path || 1 !== preg_match(self::STORY_PATH, $path, $matches)) {
                continue;
            }

            $slug = $matches[1];
            if (is_file($cloneDir . '/docs/story/' . $slug . '/brief.md')) {
                return $slug;
            }
        }

        return null;
    }
}
