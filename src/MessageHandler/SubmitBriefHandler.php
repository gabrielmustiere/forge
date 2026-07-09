<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Enum\Type\InterviewStatus;
use App\Message\SubmitBrief;
use App\Repository\InterviewRepository;
use App\Service\Github\PullRequestFailedException;
use App\Service\Github\PullRequestOpenerRegistry;
use App\Service\Interview\BriefPusherInterface;
use App\Service\Interview\BriefPushFailedException;
use App\Service\Interview\StoryWorkspaceCleaner;
use App\Service\InvalidRepositoryUrlException;
use App\Service\RepositoryUrlNormalizer;
use App\Service\TokenCipher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Publie le brief validé hors requête HTTP : push sur une copie de travail isolée (branche
 * dédiée) puis ouverture d'une PR draft, avant de poser l'état final (`Submitted` ou `Failed`).
 *
 * La garde de statut (`Submitting` requis) borne la double-livraison (pattern
 * {@see CloneRepositoryHandler}). Tout échec métier — token en lecture seule, provider non
 * supporté, réseau, conflit — est traduit en {@see InterviewStatus::Failed} lisible et non
 * re-propagé ; le brief reste en local, re-tentable.
 */
#[AsMessageHandler]
final readonly class SubmitBriefHandler
{
    public function __construct(
        private InterviewRepository $interviews,
        private BriefPusherInterface $pusher,
        private PullRequestOpenerRegistry $openers,
        private RepositoryUrlNormalizer $normalizer,
        private TokenCipher $cipher,
        private StoryWorkspaceCleaner $cleaner,
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(SubmitBrief $message): void
    {
        $interview = $this->interviews->find($message->interviewId);

        if (null === $interview || InterviewStatus::Submitting !== $interview->getStatus()) {
            // Interview supprimée, ou dépôt déjà traité (double livraison) : rien à faire.
            return;
        }

        try {
            $project = $interview->getProject();
            $cloneDir = $project->getLocalPath()
                ?? throw new PullRequestFailedException('Le projet n\'est plus cloné localement.');
            $storySlug = $interview->getStorySlug()
                ?? throw new PullRequestFailedException('Aucun brief à déposer pour cette interview.');

            $url = $this->normalizer->normalize($project->getUrl());
            $opener = $this->openers->openerFor($url->provider)
                ?? throw new PullRequestFailedException(sprintf('Ouverture de proposition non supportée pour %s (GitHub uniquement).', $url->provider->label()));

            $plainToken = $this->cipher->decrypt($project->getToken());

            $branch = $this->pusher->push($cloneDir, $storySlug, $plainToken, $url);
            $prUrl = $opener->open($url, $plainToken, $branch, $this->title($storySlug), $this->body($cloneDir, $storySlug));

            $interview->markSubmitted($prUrl);

            // Terminal (proposée) : le brief vit désormais sur la PR distante. On purge la copie
            // non suivie du clone maintenu pour ne pas contaminer une interview ultérieure.
            $this->cleaner->clean($cloneDir, $storySlug);
        } catch (BriefPushFailedException|PullRequestFailedException|InvalidRepositoryUrlException $e) {
            $interview->markFailed($e->getMessage());
        }

        $this->em->flush();
    }

    private function title(string $storySlug): string
    {
        $label = trim(str_replace('-', ' ', (string) preg_replace('/^\d{3}-f-/', '', $storySlug)));

        return sprintf('Cadrage : %s', '' !== $label ? $label : $storySlug);
    }

    /**
     * Corps de la proposition : le contenu du brief produit, précédé d'une note d'origine.
     * Si le brief est illisible (cas improbable, le push vient de le committer), un texte de
     * repli suffit — l'ouverture ne doit pas échouer pour un souci de mise en forme.
     */
    private function body(string $cloneDir, string $storySlug): string
    {
        $note = "> Proposition de cadrage générée depuis Forge Board (brouillon — à relire avant merge).\n\n";
        $briefPath = $cloneDir . '/docs/story/' . $storySlug . '/brief.md';
        $brief = is_file($briefPath) ? file_get_contents($briefPath) : false;

        return $note . (\is_string($brief) && '' !== trim($brief) ? $brief : sprintf('Brief de la story `%s`.', $storySlug));
    }
}
