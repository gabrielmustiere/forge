<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Interview;
use App\Entity\Project;
use App\Manager\InterviewManager;
use App\Manager\InterviewNotAllowedException;
use App\Repository\InterviewRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Fil d'interview de cadrage : saisie du besoin, dialogue tour par tour, validation et dépôt
 * du brief (story 009).
 *
 * Le composant ne détient pas l'interview en {@see LiveProp} : il la relit à chaque cycle depuis
 * le repository ({@see getInterview()}). Tant qu'un tour ou le dépôt tourne en tâche de fond
 * (statut {@see \App\Enum\Type\InterviewStatus::isInFlight()}), le template poll et se re-rend →
 * l'état posé par le worker (réponse arrivée, brief prêt, PR ouverte, échec) apparaît sans reload
 * ni Mercure — même mécanique que {@see ProjectCloneStatus}. Les transitions passent par le
 * {@see InterviewManager} (gardes métier), les échecs métier sont rendus lisibles via {@see $error}.
 */
#[AsLiveComponent]
final class ProjectInterview
{
    use DefaultActionTrait;

    #[LiveProp]
    public Project $project;

    /** Message en cours de saisie (besoin initial ou réponse de tour). */
    #[LiveProp(writable: true)]
    public string $draft = '';

    /** Dernière erreur métier à afficher (garde refusée, transition invalide) ; effacée à chaque action. */
    #[LiveProp(writable: true)]
    public ?string $error = null;

    public function __construct(
        private readonly InterviewRepository $interviews,
        private readonly InterviewManager $manager,
    ) {
    }

    public function getInterview(): ?Interview
    {
        return $this->interviews->findLatestForProject($this->project);
    }

    #[LiveAction]
    public function start(): void
    {
        $this->guarded(function (): void {
            $message = $this->takeDraft();
            if (null !== $message) {
                $this->manager->start($this->project, $message);
            }
        });
    }

    #[LiveAction]
    public function send(): void
    {
        $this->guarded(function (): void {
            $interview = $this->activeInterview();
            $message = $this->takeDraft();
            if (null !== $interview && null !== $message) {
                $this->manager->submitMessage($interview, $message);
            }
        });
    }

    #[LiveAction]
    public function conclude(): void
    {
        $this->guarded(function (): void {
            $interview = $this->activeInterview();
            if (null !== $interview) {
                $this->manager->conclude($interview);
            }
        });
    }

    #[LiveAction]
    public function validate(): void
    {
        $this->guarded(function (): void {
            $interview = $this->activeInterview();
            if (null !== $interview) {
                $this->manager->submitBrief($interview);
            }
        });
    }

    #[LiveAction]
    public function retry(): void
    {
        $this->guarded(function (): void {
            $interview = $this->activeInterview();
            if (null !== $interview) {
                $this->manager->retry($interview);
            }
        });
    }

    #[LiveAction]
    public function abandon(): void
    {
        $this->guarded(function (): void {
            $interview = $this->activeInterview();
            if (null !== $interview) {
                $this->manager->abandon($interview);
            }
        });
    }

    /**
     * Exécute une action en traduisant une garde métier refusée en message affichable, plutôt
     * qu'en erreur 500 — le parcours ne doit jamais planter (règle : échec lisible).
     */
    private function guarded(callable $action): void
    {
        $this->error = null;

        try {
            $action();
        } catch (InterviewNotAllowedException $e) {
            $this->error = $e->getMessage();
        }
    }

    private function activeInterview(): ?Interview
    {
        $interview = $this->getInterview();

        return null !== $interview && $interview->getStatus()->isActive() ? $interview : null;
    }

    /** Consomme le brouillon (trim + reset) ; retourne `null` s'il est vide. */
    private function takeDraft(): ?string
    {
        $message = trim($this->draft);
        $this->draft = '';

        return '' !== $message ? $message : null;
    }
}
