<?php

declare(strict_types=1);

namespace App\Service\Interview;

/**
 * Exécute un tour de dialogue de l'interview de cadrage (story 009).
 *
 * Une seule implémentation ({@see ClaudeInterviewRunner}) : shell-out `claude -p` /
 * `--resume` dans le clone local (ADR-0002). Le port isole le shell-out → substituable par
 * un double en test, sans lancer `claude` ni consommer l'abonnement.
 */
interface InterviewRunnerInterface
{
    /**
     * Joue un tour : envoie `$userMessage` au skill `feature-interview` et retourne sa réponse.
     *
     * Au **premier tour** ({@see $isFirstTurn} vrai), une nouvelle session `claude` est créée
     * avec `$sessionId` ; aux tours suivants, la session est reprise via `--resume $sessionId`
     * (le contexte du dialogue est ainsi conservé). Le process s'exécute avec `$workingDir`
     * comme répertoire courant (le clone local du projet), pour que le skill lise le vrai code.
     *
     * @param string $sessionId   UUID de session `claude` (stable pour toute l'interview)
     * @param string $workingDir  chemin absolu du clone local (cwd du process)
     * @param string $userMessage message brut saisi par l'utilisateur
     * @param bool   $isFirstTurn true si aucun tour n'a encore été joué (démarre la session)
     *
     * @throws InterviewFailedException binaire absent, délai dépassé, session/sortie illisible
     */
    public function converse(string $sessionId, string $workingDir, string $userMessage, bool $isFirstTurn): InterviewTurnResult;
}
