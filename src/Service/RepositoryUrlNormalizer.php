<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\Type\Provider;

/**
 * Parse une URL de dépôt saisie (https, ssh, avec ou sans suffixe `.git`) et en produit
 * une forme canonique unique. Le provider est déduit de l'hôte.
 */
final class RepositoryUrlNormalizer
{
    /**
     * @var array<string, Provider>
     */
    private const HOSTS = [
        'github.com' => Provider::GitHub,
        'gitlab.com' => Provider::GitLab,
    ];

    public function normalize(string $url): RepositoryUrl
    {
        $url = trim($url);

        if (preg_match('#^(?:ssh://)?git@(?<host>[^:/]+)[:/](?<path>.+)$#', $url, $matches)) {
            $host = strtolower($matches['host']);
            $path = $matches['path'];
        } else {
            $parts = parse_url($url);

            if (false === $parts || !isset($parts['host'], $parts['path'])) {
                throw new InvalidRepositoryUrlException($url);
            }

            $host = strtolower($parts['host']);
            $path = $parts['path'];
        }

        $provider = self::HOSTS[$host] ?? throw new InvalidRepositoryUrlException($url);

        $segments = array_values(array_filter(
            explode('/', trim($path, '/')),
            static fn (string $segment): bool => '' !== $segment,
        ));

        if (\count($segments) < 2) {
            throw new InvalidRepositoryUrlException($url);
        }

        $lastIndex = array_key_last($segments);
        if (str_ends_with($segments[$lastIndex], '.git')) {
            $segments[$lastIndex] = substr($segments[$lastIndex], 0, -4);
        }

        foreach ($segments as $segment) {
            if (1 !== preg_match('/^[A-Za-z0-9._-]+$/', $segment)) {
                throw new InvalidRepositoryUrlException($url);
            }
        }

        $owner = $segments[0];
        $repo = implode('/', \array_slice($segments, 1));

        return new RepositoryUrl(
            $provider,
            $owner,
            $repo,
            sprintf('https://%s/%s/%s', $host, $owner, $repo),
        );
    }
}
