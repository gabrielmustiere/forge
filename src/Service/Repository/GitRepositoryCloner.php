<?php

declare(strict_types=1);

namespace App\Service\Repository;

use App\Service\RepositoryUrl;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Implémentation `git` du {@see RepositoryClonerInterface} : shell-out via `symfony/process`.
 *
 * L'authentification passe par `GIT_ASKPASS` (script {@see $askpassScript}) + le token en
 * variable d'environnement `GIT_ASKPASS_TOKEN` : jamais en argv, jamais dans `.git/config`.
 * `GIT_TERMINAL_PROMPT=0` empêche git de bloquer sur un prompt interactif. Un timeout borne
 * les clones longs ou un worker coincé. Tout échec est traduit en {@see CloneFailedException}
 * dont le message est lisible et exempt de token/URL crédentialisée.
 */
final readonly class GitRepositoryCloner implements RepositoryClonerInterface
{
    /** Borne haute d'un clone/pull (secondes) : au-delà, échec propre plutôt que blocage. */
    private const TIMEOUT_SECONDS = 600.0;

    public function __construct(
        #[Autowire('%kernel.project_dir%/bin/git-askpass.sh')]
        private string $askpassScript,
    ) {
    }

    public function synchronize(RepositoryUrl $url, #[\SensitiveParameter] string $plainToken, string $destination): void
    {
        // Détection sur le filesystem (présence de `<dest>/.git`), pas sur le statut persisté :
        // robuste si l'utilisateur a supprimé le dossier à la main.
        $command = is_dir($destination . '/.git')
            ? ['git', '-C', $destination, 'pull', '--ff-only']
            : ['git', 'clone', $this->cloneUrl($url), $destination];

        $process = new Process($command, env: [
            'GIT_ASKPASS' => $this->askpassScript,
            'GIT_ASKPASS_TOKEN' => $plainToken,
            'GIT_TERMINAL_PROMPT' => '0',
        ]);
        $process->setTimeout(self::TIMEOUT_SECONDS);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            throw new CloneFailedException('Délai dépassé : le dépôt met trop de temps à répondre.');
        }

        if (!$process->isSuccessful()) {
            throw new CloneFailedException($this->reason($process));
        }
    }

    private function cloneUrl(RepositoryUrl $url): string
    {
        // URL HTTPS sans credentials : le token est fourni hors-bande par GIT_ASKPASS.
        return sprintf('https://%s/%s/%s.git', $url->provider->host(), $url->owner, $url->repo);
    }

    /**
     * Extrait une raison lisible de l'échec, sans token ni URL crédentialisée (le token
     * n'est jamais passé en argv/URL, donc absent des sorties du process).
     */
    private function reason(Process $process): string
    {
        $output = trim($process->getErrorOutput()) ?: trim($process->getOutput());
        $lines = array_values(array_filter(explode("\n", $output), static fn (string $l): bool => '' !== trim($l)));
        $lastLine = trim((string) end($lines));

        if ('' === $lastLine) {
            return sprintf('git a échoué (code %d).', (int) $process->getExitCode());
        }

        return sprintf('git a échoué : %s', $lastLine);
    }
}
