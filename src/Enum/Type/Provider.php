<?php

declare(strict_types=1);

namespace App\Enum\Type;

enum Provider: string
{
    case GitHub = 'github';
    case GitLab = 'gitlab';

    public function host(): string
    {
        return match ($this) {
            self::GitHub => 'github.com',
            self::GitLab => 'gitlab.com',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::GitHub => 'GitHub',
            self::GitLab => 'GitLab',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::GitHub => 'tabler:brand-github',
            self::GitLab => 'tabler:brand-gitlab',
        };
    }
}
