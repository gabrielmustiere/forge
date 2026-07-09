<?php

declare(strict_types=1);

namespace App\Tests\Double;

use App\Service\Interview\BriefPusherInterface;
use App\Service\Interview\BriefPushFailedException;
use App\Service\RepositoryUrl;

/**
 * Pusher déterministe pour les tests : remplace {@see \App\Service\Interview\GitBriefPusher}
 * en environnement `test` (config/services_test.yaml) — aucun `git`/push réseau réel.
 *
 * Un token contenant `readonly` simule un droit en lecture seule (push refusé) ; sinon le push
 * « réussit » et renvoie le nom de branche dérivé du slug, sans toucher au disque.
 */
final class FakeBriefPusher implements BriefPusherInterface
{
    public function push(string $cloneDir, string $storySlug, #[\SensitiveParameter] string $plainToken, RepositoryUrl $url): string
    {
        if (str_contains($plainToken, 'readonly')) {
            throw new BriefPushFailedException('git a échoué : le token n\'a pas le droit d\'écriture (simulé).');
        }

        return 'forge/' . $storySlug;
    }
}
