<?php

declare(strict_types=1);

namespace App\Tests\Functional\Interview;

use App\Entity\Interview;
use App\Entity\InterviewMessage;
use App\Entity\Project;
use App\Enum\Type\InterviewStatus;
use App\Enum\Type\Provider;
use App\Manager\InterviewManager;
use App\Manager\InterviewNotAllowedException;
use App\Message\RunInterviewTurn;
use App\Message\SubmitBrief;
use App\MessageHandler\RunInterviewTurnHandler;
use App\MessageHandler\SubmitBriefHandler;
use App\Repository\InterviewRepository;
use App\Service\TokenCipher;
use App\Tests\Double\FakeInterviewRunner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Parcours complet via le manager + les handlers, avec les doubles (claude / git push / API
 * GitHub neutralisés — services_test.yaml). Le {@see \App\Service\Interview\ProducedBriefLocator}
 * reste réel : le clone est un **vrai** dépôt git temporaire, et le fake runner y écrit un brief
 * non suivi que le locator détecte pour de bon (l'intégration état ↔ filesystem est testée).
 */
final class InterviewFlowTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private TokenCipher $cipher;
    private InterviewRepository $interviews;
    private InterviewManager $manager;
    private RunInterviewTurnHandler $runTurn;
    private SubmitBriefHandler $submitBrief;
    private Filesystem $fs;
    private string $cloneDir;

    protected function setUp(): void
    {
        self::bootKernel();
        $c = static::getContainer();

        $em = $c->get(EntityManagerInterface::class);
        $cipher = $c->get(TokenCipher::class);
        $interviews = $c->get(InterviewRepository::class);
        $manager = $c->get(InterviewManager::class);
        $runTurn = $c->get(RunInterviewTurnHandler::class);
        $submitBrief = $c->get(SubmitBriefHandler::class);
        \assert($em instanceof EntityManagerInterface);
        \assert($cipher instanceof TokenCipher);
        \assert($interviews instanceof InterviewRepository);
        \assert($manager instanceof InterviewManager);
        \assert($runTurn instanceof RunInterviewTurnHandler);
        \assert($submitBrief instanceof SubmitBriefHandler);
        $this->em = $em;
        $this->cipher = $cipher;
        $this->interviews = $interviews;
        $this->manager = $manager;
        $this->runTurn = $runTurn;
        $this->submitBrief = $submitBrief;

        $this->em->createQuery('DELETE FROM ' . InterviewMessage::class)->execute();
        $this->em->createQuery('DELETE FROM ' . Interview::class)->execute();
        $this->em->createQuery('DELETE FROM ' . Project::class)->execute();

        $this->fs = new Filesystem();
        $this->cloneDir = sys_get_temp_dir() . '/interview-clone-' . bin2hex(random_bytes(6));
        $this->initGitClone();
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->cloneDir);
    }

    public function testHappyPathFromNeedToOpenedProposal(): void
    {
        $project = $this->clonedProject();

        // 1. Démarrage : le besoin est enregistré, un tour part en tâche de fond.
        $interview = $this->manager->start($project, 'Je veux exporter mes factures');
        $id = $interview->getId() ?? self::fail('interview non persistée');
        self::assertSame(InterviewStatus::Thinking, $interview->getStatus());
        self::assertCount(1, $interview->getMessages());

        // 2. Tour joué : le fake pose une question, l'interview attend la suite.
        ($this->runTurn)(new RunInterviewTurn($id));
        $interview = $this->reload($id);
        self::assertSame(InterviewStatus::Awaiting, $interview->getStatus());
        self::assertCount(2, $interview->getMessages());

        // 3-4. L'utilisateur finalise : le fake écrit le brief, le locator réel le détecte.
        $this->manager->submitMessage($interview, 'FINALISE le brief');
        self::assertSame(InterviewStatus::Thinking, $this->reload($id)->getStatus());

        ($this->runTurn)(new RunInterviewTurn($id));
        $interview = $this->reload($id);
        self::assertSame(InterviewStatus::BriefReady, $interview->getStatus());
        self::assertSame(FakeInterviewRunner::BRIEF_SLUG, $interview->getStorySlug());

        // 5-6. Validation puis dépôt : PR draft ouverte (fakes), état terminal succès.
        $this->manager->submitBrief($interview);
        self::assertSame(InterviewStatus::Submitting, $this->reload($id)->getStatus());

        ($this->submitBrief)(new SubmitBrief($id));
        $interview = $this->reload($id);
        self::assertSame(InterviewStatus::Submitted, $interview->getStatus());
        self::assertNotNull($interview->getPullRequestUrl());
        self::assertStringContainsString('/pull/', (string) $interview->getPullRequestUrl());

        // Terminal : le dossier de story non suivi est purgé du clone (pas de contamination).
        self::assertDirectoryDoesNotExist($this->cloneDir . '/docs/story/' . FakeInterviewRunner::BRIEF_SLUG);
    }

    public function testTerminalInterviewDoesNotContaminateTheNextOne(): void
    {
        $project = $this->clonedProject();
        $projectId = (int) $project->getId();

        // 1re interview : produit le brief puis est déposée (terminal → nettoyage).
        $first = $this->manager->start($project, 'FINALISE le brief tout de suite');
        $firstId = $first->getId() ?? self::fail('interview non persistée');
        ($this->runTurn)(new RunInterviewTurn($firstId));
        self::assertSame(InterviewStatus::BriefReady, $this->reload($firstId)->getStatus());
        $this->manager->submitBrief($this->reload($firstId));
        ($this->submitBrief)(new SubmitBrief($firstId));
        self::assertSame(InterviewStatus::Submitted, $this->reload($firstId)->getStatus());

        // 2de interview sur le même projet : son 1er tour ne finalise pas → elle doit rester en
        // attente, et surtout NE PAS re-détecter le brief de la précédente (dossier purgé).
        $project = $this->em->find(Project::class, $projectId) ?? self::fail('projet introuvable');
        $second = $this->manager->start($project, 'Un tout autre besoin');
        $secondId = $second->getId() ?? self::fail('interview non persistée');
        ($this->runTurn)(new RunInterviewTurn($secondId));

        $second = $this->reload($secondId);
        self::assertSame(InterviewStatus::Awaiting, $second->getStatus());
        self::assertNull($second->getStorySlug(), 'la 2de interview ne doit pas hériter du brief de la 1re');
    }

    public function testAbandonPurgesAProducedBrief(): void
    {
        $project = $this->clonedProject();
        $interview = $this->manager->start($project, 'FINALISE le brief tout de suite');
        $id = $interview->getId() ?? self::fail('interview non persistée');
        ($this->runTurn)(new RunInterviewTurn($id));
        self::assertSame(InterviewStatus::BriefReady, $this->reload($id)->getStatus());

        $this->manager->abandon($this->reload($id));
        self::assertSame(InterviewStatus::Abandoned, $this->reload($id)->getStatus());
        self::assertDirectoryDoesNotExist($this->cloneDir . '/docs/story/' . FakeInterviewRunner::BRIEF_SLUG);
    }

    public function testConcludeAtUserRequestProducesTheBrief(): void
    {
        $project = $this->clonedProject();
        $interview = $this->manager->start($project, 'Un besoin à cadrer');
        $id = $interview->getId() ?? self::fail('interview non persistée');

        // Premier tour : le skill pose une question, l'interview attend.
        ($this->runTurn)(new RunInterviewTurn($id));
        self::assertSame(InterviewStatus::Awaiting, $this->reload($id)->getStatus());

        // L'utilisateur clique « Conclure » : message de conclusion envoyé, tour relancé.
        $this->manager->conclude($this->reload($id));
        $interview = $this->reload($id);
        self::assertSame(InterviewStatus::Thinking, $interview->getStatus());
        self::assertSame(InterviewManager::CONCLUSION_MESSAGE, $interview->lastUserMessage());

        // Le tour de conclusion fait produire le brief (fake) que le locator réel détecte.
        ($this->runTurn)(new RunInterviewTurn($id));
        $interview = $this->reload($id);
        self::assertSame(InterviewStatus::BriefReady, $interview->getStatus());
        self::assertSame(FakeInterviewRunner::BRIEF_SLUG, $interview->getStorySlug());
    }

    public function testConcludeRequiresAnAwaitingInterview(): void
    {
        $project = $this->clonedProject();
        $interview = $this->manager->start($project, 'Un besoin');
        // L'interview est en Thinking (tour en tâche de fond), pas en attente d'un message.

        $this->expectException(InterviewNotAllowedException::class);
        $this->manager->conclude($interview);
    }

    public function testStartRequiresAClonedProject(): void
    {
        $project = $this->persistProject('https://github.com/acme/repo', 'acme/repo');
        // cloneStatus reste NotCloned.

        $this->expectException(InterviewNotAllowedException::class);
        $this->manager->start($project, 'Un besoin');
    }

    public function testOnlyOneActiveInterviewPerProject(): void
    {
        $project = $this->clonedProject();
        $this->manager->start($project, 'Premier besoin');

        $this->expectException(InterviewNotAllowedException::class);
        $this->manager->start($project, 'Second besoin en parallèle');
    }

    public function testRunnerFailureIsRecoverableAndRetryable(): void
    {
        $project = $this->clonedProject();
        $interview = $this->manager->start($project, 'INTERVIEW-FAIL sur ce tour');
        $id = $interview->getId() ?? self::fail('interview non persistée');

        ($this->runTurn)(new RunInterviewTurn($id));
        $interview = $this->reload($id);
        self::assertSame(InterviewStatus::Failed, $interview->getStatus());
        self::assertNotNull($interview->getLastError());
        self::assertTrue($interview->getStatus()->isActive(), 'un échec reste actif (re-tentable)');

        // Re-tenter (aucun brief encore) relance un tour d'interview.
        $this->manager->retry($interview);
        self::assertSame(InterviewStatus::Thinking, $this->reload($id)->getStatus());
    }

    public function testAbandonFreesTheSlot(): void
    {
        $project = $this->clonedProject();
        $projectId = (int) $project->getId();
        $interview = $this->manager->start($project, 'Un besoin');

        $this->manager->abandon($interview);
        self::assertSame(InterviewStatus::Abandoned, $this->reload((int) $interview->getId())->getStatus());

        // reload() a vidé l'EM : on relit un projet managé (comme le ferait l'EntityValueResolver).
        $project = $this->em->find(Project::class, $projectId) ?? self::fail('projet introuvable');
        self::assertNull($this->interviews->findActiveForProject($project));

        // Le créneau est libre : une nouvelle interview peut démarrer.
        $second = $this->manager->start($project, 'Un autre besoin');
        self::assertSame(InterviewStatus::Thinking, $second->getStatus());
    }

    public function testReadOnlyTokenFailsSubmitButPreservesTheBrief(): void
    {
        // Token en lecture seule : le fake pusher refuse le push.
        $project = $this->clonedProject('ghp_readonly_token');
        $interview = $this->manager->start($project, 'FINALISE le brief tout de suite');
        $id = $interview->getId() ?? self::fail('interview non persistée');

        ($this->runTurn)(new RunInterviewTurn($id));
        self::assertSame(InterviewStatus::BriefReady, $this->reload($id)->getStatus());

        $this->manager->submitBrief($this->reload($id));
        ($this->submitBrief)(new SubmitBrief($id));

        $interview = $this->reload($id);
        self::assertSame(InterviewStatus::Failed, $interview->getStatus());
        self::assertNotNull($interview->getLastError());
        // Le brief produit n'est pas perdu : le slug reste, l'échec est re-tentable.
        self::assertSame(FakeInterviewRunner::BRIEF_SLUG, $interview->getStorySlug());
    }

    private function reload(int $id): Interview
    {
        $this->em->clear();

        return $this->interviews->find($id) ?? self::fail('interview introuvable');
    }

    private function clonedProject(string $plainToken = 'ghp_write_token'): Project
    {
        $project = $this->persistProject('https://github.com/acme/repo', 'acme/repo', $plainToken);
        $project->markCloned($this->cloneDir, new \DateTimeImmutable());
        $this->em->flush();

        return $project;
    }

    private function persistProject(string $url, string $name, string $plainToken = 'token'): Project
    {
        $project = new Project(Provider::GitHub, $url, $name, $this->cipher->encrypt($plainToken));
        $this->em->persist($project);
        $this->em->flush();

        return $project;
    }

    private function initGitClone(): void
    {
        $this->fs->mkdir($this->cloneDir);
        $this->git('init', '-q');
        $this->git('config', 'user.email', 'test@example.com');
        $this->git('config', 'user.name', 'Test');
        $this->fs->dumpFile($this->cloneDir . '/README.md', "# repo\n");
        $this->git('add', '.');
        $this->git('commit', '-q', '-m', 'init');
    }

    private function git(string ...$args): void
    {
        (new Process(['git', '-C', $this->cloneDir, ...$args]))->mustRun();
    }
}
