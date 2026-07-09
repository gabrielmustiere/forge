<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Interview;
use App\Entity\Project;
use App\Enum\Type\InterviewStatus;
use App\Enum\Type\MessageRole;
use App\Enum\Type\Provider;
use PHPUnit\Framework\TestCase;

final class InterviewTest extends TestCase
{
    public function testStartsAwaitingWithSessionAndEmptyThread(): void
    {
        $interview = $this->interview();

        self::assertSame(InterviewStatus::Awaiting, $interview->getStatus());
        self::assertSame('session-uuid', $interview->getSessionId());
        self::assertCount(0, $interview->getMessages());
        self::assertNull($interview->getUpdatedAt());
        self::assertTrue($interview->isFirstTurn());
    }

    public function testAddUserMessageAppendsToThreadAndTouches(): void
    {
        $interview = $this->interview();

        $interview->addUserMessage('Je veux exporter mes factures.');

        self::assertCount(1, $interview->getMessages());
        $message = $interview->getMessages()->first();
        self::assertNotFalse($message);
        self::assertSame(MessageRole::User, $message->getRole());
        self::assertSame('Je veux exporter mes factures.', $message->getContent());
        self::assertNotNull($interview->getUpdatedAt());
        self::assertSame('Je veux exporter mes factures.', $interview->lastUserMessage());
    }

    public function testIsFirstTurnFlipsAfterAssistantReplies(): void
    {
        $interview = $this->interview();
        $interview->addUserMessage('Un besoin.');
        self::assertTrue($interview->isFirstTurn());

        $interview->addAssistantMessage('Une question de cadrage ?');

        self::assertFalse($interview->isFirstTurn());
    }

    public function testLastUserMessageReturnsTheMostRecentUserTurn(): void
    {
        $interview = $this->interview();
        $interview->addUserMessage('premier');
        $interview->addAssistantMessage('question');
        $interview->addUserMessage('second');

        self::assertSame('second', $interview->lastUserMessage());
    }

    public function testLastUserMessageThrowsWhenNoUserTurn(): void
    {
        $this->expectException(\LogicException::class);

        $this->interview()->lastUserMessage();
    }

    public function testMarkThinkingSetsStatusAndClearsError(): void
    {
        $interview = $this->interview();
        $interview->markFailed('boom');

        $interview->markThinking();

        self::assertSame(InterviewStatus::Thinking, $interview->getStatus());
        self::assertNull($interview->getLastError());
    }

    public function testMarkAwaiting(): void
    {
        $interview = $this->interview();
        $interview->markThinking();

        $interview->markAwaiting();

        self::assertSame(InterviewStatus::Awaiting, $interview->getStatus());
    }

    public function testMarkBriefReadySetsStatusAndSlugTogether(): void
    {
        $interview = $this->interview();

        $interview->markBriefReady('010-f-export-factures');

        self::assertSame(InterviewStatus::BriefReady, $interview->getStatus());
        self::assertSame('010-f-export-factures', $interview->getStorySlug());
    }

    public function testMarkSubmittingClearsError(): void
    {
        $interview = $this->interview();
        $interview->markFailed('previous failure');

        $interview->markSubmitting();

        self::assertSame(InterviewStatus::Submitting, $interview->getStatus());
        self::assertNull($interview->getLastError());
    }

    public function testMarkSubmittedSetsStatusAndPullRequestUrlTogether(): void
    {
        $interview = $this->interview();

        $interview->markSubmitted('https://github.com/acme/repo/pull/42');

        self::assertSame(InterviewStatus::Submitted, $interview->getStatus());
        self::assertSame('https://github.com/acme/repo/pull/42', $interview->getPullRequestUrl());
        self::assertFalse($interview->getStatus()->isActive());
    }

    public function testMarkFailedSetsReasonAndStaysActive(): void
    {
        $interview = $this->interview();

        $interview->markFailed('Token en lecture seule : push refusé.');

        self::assertSame(InterviewStatus::Failed, $interview->getStatus());
        self::assertSame('Token en lecture seule : push refusé.', $interview->getLastError());
        self::assertTrue($interview->getStatus()->isActive());
    }

    public function testMarkAbandonedIsTerminal(): void
    {
        $interview = $this->interview();

        $interview->markAbandoned();

        self::assertSame(InterviewStatus::Abandoned, $interview->getStatus());
        self::assertFalse($interview->getStatus()->isActive());
    }

    private function interview(): Interview
    {
        $project = new Project(Provider::GitHub, 'https://github.com/acme/repo', 'acme/repo', 'cipher');

        return new Interview($project, 'session-uuid');
    }
}
