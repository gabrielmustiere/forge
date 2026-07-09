<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Project;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Badge d'état de clone + bouton de déclenchement, sur la fiche projet.
 *
 * Tant que le clone est `Cloning`, le composant poll (`data-poll` côté template) et se
 * re-rend : le projet étant une {@see LiveProp} entité, il est réhydraté depuis la base à
 * chaque cycle → le badge reflète la bascule `Cloned`/`Failed` posée par le worker, sans
 * rechargement de page ni Mercure. Le déclenchement lui-même passe par un POST classique
 * vers `app_project_clone` (le worker fait le travail hors requête).
 */
#[AsLiveComponent]
final class ProjectCloneStatus
{
    use DefaultActionTrait;

    #[LiveProp]
    public Project $project;
}
