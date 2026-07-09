<?php

declare(strict_types=1);

namespace App\Tests\Double;

use App\Manager\InterviewManager;
use App\Service\Interview\InterviewFailedException;
use App\Service\Interview\InterviewRunnerInterface;
use App\Service\Interview\InterviewTurnResult;

/**
 * Runner déterministe pour les tests : remplace {@see \App\Service\Interview\ClaudeInterviewRunner}
 * en environnement `test` (config/services_test.yaml) afin qu'aucun `claude` réel ne soit lancé
 * et que l'abonnement ne soit jamais consommé.
 *
 * Le scénario est piloté par le contenu du message (comme {@see FakeRepositoryCloner} par le nom
 * du dépôt) :
 *  - un message contenant `INTERVIEW-FAIL` simule un échec de tour ;
 *  - un message contenant `FINALISE`, ou le message de conclusion émis par le bouton « Conclure »
 *    ({@see InterviewManager::CONCLUSION_MESSAGE}), fait « produire » le brief : le fake écrit
 *    réellement un `docs/story/010-f-fake/brief.md` **non suivi** dans le clone, pour que le **vrai**
 *    {@see \App\Service\Interview\ProducedBriefLocator} le détecte (l'intégration reste testée) ;
 *  - sinon, le fake renvoie une question de cadrage (l'interview reste en attente).
 */
final class FakeInterviewRunner implements InterviewRunnerInterface
{
    public const BRIEF_SLUG = '010-f-fake';

    public function converse(string $sessionId, string $workingDir, string $userMessage, bool $isFirstTurn): InterviewTurnResult
    {
        if (str_contains($userMessage, 'INTERVIEW-FAIL')) {
            throw new InterviewFailedException('Le tour d\'interview a échoué (simulé).');
        }

        if (str_contains($userMessage, 'FINALISE') || InterviewManager::CONCLUSION_MESSAGE === $userMessage) {
            $storyDir = $workingDir . '/docs/story/' . self::BRIEF_SLUG;
            if (!is_dir($storyDir)) {
                mkdir($storyDir, 0o777, true);
            }
            file_put_contents($storyDir . '/brief.md', "# Brief simulé\n");
            file_put_contents($storyDir . '/metadata.json', "{\"version\":1}\n");

            return new InterviewTurnResult('Voici le brief de cadrage produit.', 0.01);
        }

        return new InterviewTurnResult('Quelle est la priorité principale de ce besoin ?', 0.02);
    }
}
