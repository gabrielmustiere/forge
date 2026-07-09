<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\Interview;
use App\Entity\Project;
use App\Enum\Type\InterviewStatus;
use App\Message\RunInterviewTurn;
use App\Message\SubmitBrief;
use App\Repository\InterviewRepository;
use App\Service\Interview\StoryWorkspaceCleaner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Orchestration du parcours de cadrage (story 009) : démarrage, tours de dialogue, validation
 * et dépôt du brief, abandon. Applique les gardes métier (projet cloné — règle 1 ; une seule
 * interview active par projet — règle 2) et transpose chaque action en transition d'état
 * **synchrone et persistée avant dispatch** (borne le double-clic et sert de garde d'idempotence
 * aux handlers, pattern {@see ProjectManager::requestClone()}).
 */
final readonly class InterviewManager
{
    /**
     * Message injecté quand l'utilisateur clique « Conclure le cadrage » : donne la main à
     * l'utilisateur sur la fin du dialogue (le skill décidait seul jusqu'ici). Instruit le skill
     * de produire le brief sur-le-champ avec les éléments déjà recueillis.
     */
    public const string CONCLUSION_MESSAGE = 'J\'ai terminé de répondre. Le cadrage est complet de mon côté : produis maintenant le brief avec les éléments recueillis, sans poser de nouvelle question.';

    public function __construct(
        private EntityManagerInterface $em,
        private InterviewRepository $interviews,
        private MessageBusInterface $bus,
        private StoryWorkspaceCleaner $cleaner,
    ) {
    }

    /**
     * Démarre une interview sur un projet cloné avec le premier besoin exprimé, et lance le
     * premier tour en tâche de fond.
     *
     * @throws InterviewNotAllowedException projet non cloné, ou interview déjà active
     */
    public function start(Project $project, string $firstMessage): Interview
    {
        if (!$project->isCloned()) {
            throw new InterviewNotAllowedException('Le projet doit être cloné avant d\'exprimer un besoin.');
        }

        if (null !== $this->interviews->findActiveForProject($project)) {
            throw new InterviewNotAllowedException('Une interview est déjà en cours sur ce projet.');
        }

        $interview = new Interview($project, Uuid::v4()->toRfc4122());
        $interview->addUserMessage($firstMessage);
        $interview->markThinking();

        $this->em->persist($interview);
        $this->em->flush();

        $this->dispatchTurn($interview);

        return $interview;
    }

    /**
     * Envoie un nouveau message dans une interview en attente et relance un tour.
     *
     * @throws InterviewNotAllowedException l'interview n'attend pas de message
     */
    public function submitMessage(Interview $interview, string $message): void
    {
        if (InterviewStatus::Awaiting !== $interview->getStatus()) {
            throw new InterviewNotAllowedException('Cette interview n\'attend pas de message pour l\'instant.');
        }

        $interview->addUserMessage($message);
        $interview->markThinking();
        $this->em->flush();

        $this->dispatchTurn($interview);
    }

    /**
     * Conclut le dialogue à la demande de l'utilisateur : envoie un message de conclusion qui
     * instruit le skill de produire le brief maintenant. C'est un {@see submitMessage()} au contenu
     * fixe — mêmes gardes (l'interview doit attendre un message) et même relance de tour.
     *
     * @throws InterviewNotAllowedException l'interview n'attend pas de message
     */
    public function conclude(Interview $interview): void
    {
        $this->submitMessage($interview, self::CONCLUSION_MESSAGE);
    }

    /**
     * Valide le brief présenté et lance son dépôt en proposition de revue.
     *
     * @throws InterviewNotAllowedException aucun brief n'est en attente de validation
     */
    public function submitBrief(Interview $interview): void
    {
        if (InterviewStatus::BriefReady !== $interview->getStatus()) {
            throw new InterviewNotAllowedException('Aucun brief à valider pour cette interview.');
        }

        $interview->markSubmitting();
        $this->em->flush();

        $this->dispatchSubmit($interview);
    }

    /**
     * Re-tente l'opération après un échec récupérable : le dépôt seul si le brief était prêt
     * (l'interview n'est pas rejouée), sinon le dernier tour d'interview.
     *
     * @throws InterviewNotAllowedException l'interview n'est pas en échec
     */
    public function retry(Interview $interview): void
    {
        if (InterviewStatus::Failed !== $interview->getStatus()) {
            throw new InterviewNotAllowedException('Rien à re-tenter : l\'interview n\'est pas en échec.');
        }

        if (null !== $interview->getStorySlug()) {
            $interview->markSubmitting();
            $this->em->flush();
            $this->dispatchSubmit($interview);

            return;
        }

        $interview->markThinking();
        $this->em->flush();
        $this->dispatchTurn($interview);
    }

    /**
     * Abandonne une interview active : terminal, libère le créneau du projet (aucun effet distant).
     *
     * @throws InterviewNotAllowedException l'interview est déjà terminée
     */
    public function abandon(Interview $interview): void
    {
        if (!$interview->getStatus()->isActive()) {
            throw new InterviewNotAllowedException('Cette interview est déjà terminée.');
        }

        $interview->markAbandoned();
        $this->em->flush();

        // Terminal sans dépôt : purge le brief non suivi éventuellement produit, pour qu'il ne
        // soit pas re-détecté par la prochaine interview du projet.
        $storySlug = $interview->getStorySlug();
        $cloneDir = $interview->getProject()->getLocalPath();
        if (null !== $storySlug && null !== $cloneDir) {
            $this->cleaner->clean($cloneDir, $storySlug);
        }
    }

    private function dispatchTurn(Interview $interview): void
    {
        $this->bus->dispatch(new RunInterviewTurn($this->id($interview)));
    }

    private function dispatchSubmit(Interview $interview): void
    {
        $this->bus->dispatch(new SubmitBrief($this->id($interview)));
    }

    private function id(Interview $interview): int
    {
        return $interview->getId() ?? throw new \LogicException('Interview non persistée.');
    }
}
