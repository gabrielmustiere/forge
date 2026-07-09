<?php

declare(strict_types=1);

namespace App\Service\Interview;

use App\Service\RepositoryUrl;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Implémentation `git` du {@see BriefPusherInterface} : push sur une **copie de travail isolée**
 * du clone maintenu (story 009, approche retenue).
 *
 * Le clone maintenu (story 008) est gardé propre par `git pull --ff-only` : le muter (créer une
 * branche, committer) casserait cet invariant. On travaille donc sur une copie jetable obtenue
 * par `git clone --local` (partage d'objets, rapide), dans laquelle on recopie le dossier de
 * story **non suivi**, puis on crée la branche, on committe et on pousse **directement vers le
 * distant HTTPS**. La copie est supprimée à la fin (succès comme échec).
 *
 * L'authentification réutilise le socle de la 008 : `GIT_ASKPASS` ({@see $askpassScript}) + le
 * token en `GIT_ASKPASS_TOKEN` — jamais en argv, jamais dans un `.git/config`. `GIT_TERMINAL_PROMPT=0`
 * empêche tout blocage sur un prompt. Un timeout borne un push lent.
 */
final readonly class GitBriefPusher implements BriefPusherInterface
{
    /** Borne haute d'une opération git (secondes). */
    private const TIMEOUT_SECONDS = 300.0;

    public function __construct(
        #[Autowire('%kernel.project_dir%/bin/git-askpass.sh')]
        private string $askpassScript,
        #[Autowire('%kernel.project_dir%/var/brief-push')]
        private string $workBaseDir,
    ) {
    }

    public function push(string $cloneDir, string $storySlug, #[\SensitiveParameter] string $plainToken, RepositoryUrl $url): string
    {
        $storyRelPath = 'docs/story/' . $storySlug;
        if (!is_dir($cloneDir . '/' . $storyRelPath)) {
            throw new BriefPushFailedException(sprintf('Dossier de story « %s » introuvable dans le clone.', $storySlug));
        }

        $fs = new Filesystem();
        $workDir = $this->workBaseDir . '/' . $storySlug . '-' . bin2hex(random_bytes(6));
        $branch = 'forge/' . $storySlug;

        try {
            // Copie de travail isolée : le clone maintenu reste intact.
            $this->git(['git', 'clone', '--local', '--quiet', $cloneDir, $workDir]);

            // Le dossier de story est non suivi dans le clone → absent de la copie : on l'y recopie.
            $fs->mirror($cloneDir . '/' . $storyRelPath, $workDir . '/' . $storyRelPath);

            $this->git(['git', '-C', $workDir, 'checkout', '-q', '-b', $branch]);
            $this->git(['git', '-C', $workDir, 'add', '--', $storyRelPath]);
            $this->git([
                'git', '-C', $workDir,
                '-c', 'user.email=forge-board@localhost',
                '-c', 'user.name=Forge Board',
                'commit', '-q', '-m', sprintf('docs(story): %s — brief de cadrage', $storySlug),
            ]);

            // Push direct vers le distant HTTPS (l'origin de la copie pointe sur le clone local).
            // `--force` rend le dépôt idempotent : la branche `forge/<slug>` est **entièrement
            // possédée par Forge Board** (jamais mergée par l'app), donc réécrasable sans risque.
            // Sans lui, une re-tentative après un push réussi mais une PR échouée recommiterait un
            // SHA différent → rejet non-fast-forward → échec définitif (brief non re-déposable).
            $this->git(
                ['git', '-C', $workDir, 'push', '--force', $this->remoteUrl($url), $branch . ':' . $branch],
                [
                    'GIT_ASKPASS' => $this->askpassScript,
                    'GIT_ASKPASS_TOKEN' => $plainToken,
                    'GIT_TERMINAL_PROMPT' => '0',
                ],
            );

            return $branch;
        } finally {
            $fs->remove($workDir);
        }
    }

    /**
     * @param list<string>               $command
     * @param array<string, string>|null $env
     *
     * @throws BriefPushFailedException délai dépassé ou commande git en échec
     */
    private function git(array $command, ?array $env = null): void
    {
        $process = new Process($command, env: $env);
        $process->setTimeout(self::TIMEOUT_SECONDS);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            throw new BriefPushFailedException('Délai dépassé pendant le dépôt du brief.');
        }

        if (!$process->isSuccessful()) {
            throw new BriefPushFailedException($this->reason($process));
        }
    }

    private function remoteUrl(RepositoryUrl $url): string
    {
        // URL HTTPS sans credentials : le token est fourni hors-bande par GIT_ASKPASS.
        return sprintf('https://%s/%s/%s.git', $url->provider->host(), $url->owner, $url->repo);
    }

    /**
     * Raison lisible d'un échec git, sans token ni URL crédentialisée (le token transite par
     * l'environnement, jamais par l'argv/URL → absent des sorties du process).
     */
    private function reason(Process $process): string
    {
        $output = trim($process->getErrorOutput()) ?: trim($process->getOutput());
        $lines = array_values(array_filter(explode("\n", $output), static fn (string $l): bool => '' !== trim($l)));
        $lastLine = trim((string) end($lines));

        if ('' === $lastLine) {
            return sprintf('git a échoué (code %d) pendant le dépôt du brief.', (int) $process->getExitCode());
        }

        return sprintf('git a échoué : %s', $lastLine);
    }
}
