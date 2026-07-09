<?php

declare(strict_types=1);

namespace App\Service\Interview;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Implémentation `claude` du {@see InterviewRunnerInterface} : shell-out `claude -p` via
 * `symfony/process` (ADR-0002, option A retenue).
 *
 * Chaque tour est un process éphémère : le premier crée la session (`--session-id`), les
 * suivants la reprennent (`--resume`) — le contexte du dialogue vit sur disque dans le dossier
 * `claude` ambiant (`~/.claude`), pas dans l'app. Le plugin forge est chargé via `--plugin-dir`
 * pour exécuter le **vrai** skill `feature-interview`. La commande est passée en tableau d'argv
 * (jamais via un shell → pas d'injection), la surface d'exécution est bornée par
 * `--allowedTools` + `--permission-mode acceptEdits`, et un timeout coupe un process coincé.
 *
 * V1 locale mono-utilisateur : **session ambiante** (OAuth), sans `ANTHROPIC_API_KEY` ni
 * `--bare` — le mode clé API est la voie de durcissement serveur (suite ADR-0002). Le service
 * fait de l'I/O externe : non testé unitairement, couvert par {@see \App\Tests\Double\FakeInterviewRunner}.
 */
final readonly class ClaudeInterviewRunner implements InterviewRunnerInterface
{
    /** Borne haute d'un tour (secondes) : au-delà, échec propre plutôt que blocage du worker. */
    private const TIMEOUT_SECONDS = 300.0;

    public function __construct(
        #[Autowire('%env(CLAUDE_BIN)%')]
        private string $claudeBin,
        #[Autowire('%env(CLAUDE_MODEL)%')]
        private string $model,
        // Le plugin forge vit dans ce repo : chargé via --plugin-dir pour exécuter le vrai skill.
        #[Autowire('%kernel.project_dir%/plugins/forge')]
        private string $pluginDir,
        /** Liste blanche des outils du skill (CSV), pour borner la surface d'exécution de l'agent. */
        #[Autowire('%env(CLAUDE_ALLOWED_TOOLS)%')]
        private string $allowedTools,
    ) {
    }

    public function converse(string $sessionId, string $workingDir, string $userMessage, bool $isFirstTurn): InterviewTurnResult
    {
        $process = new Process($this->command($sessionId, $userMessage, $isFirstTurn), cwd: $workingDir);
        $process->setTimeout(self::TIMEOUT_SECONDS);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            throw new InterviewFailedException('Délai dépassé : le tour d\'interview met trop de temps à répondre.');
        }

        if (!$process->isSuccessful()) {
            throw new InterviewFailedException($this->reason($process));
        }

        return $this->parse($process->getOutput());
    }

    /**
     * @return list<string>
     */
    private function command(string $sessionId, string $userMessage, bool $isFirstTurn): array
    {
        $command = [
            $this->claudeBin,
            '--print',
            $this->prompt($userMessage, $isFirstTurn),
            '--model', $this->model,
            '--plugin-dir', $this->pluginDir,
            '--permission-mode', 'acceptEdits',
            '--output-format', 'json',
        ];

        // Session : nouvelle au premier tour (`--session-id`), reprise ensuite (`--resume`).
        array_push($command, ...$isFirstTurn ? ['--session-id', $sessionId] : ['--resume', $sessionId]);

        // Liste blanche d'outils : un argv par outil (`--allowedTools <tools...>` est variadique).
        $tools = array_values(array_filter(array_map('trim', explode(',', $this->allowedTools)), static fn (string $t): bool => '' !== $t));
        if ([] !== $tools) {
            array_push($command, '--allowedTools', ...$tools);
        }

        return $command;
    }

    /**
     * Premier tour : on amorce le skill `feature-interview` sur le besoin exprimé et on lui
     * demande de dialoguer tour par tour. Tours suivants : le message brut suffit, `--resume`
     * rejoue le contexte.
     */
    private function prompt(string $userMessage, bool $isFirstTurn): string
    {
        if (!$isFirstTurn) {
            return $userMessage;
        }

        return sprintf(
            'Utilise le skill « feature-interview » du plugin forge pour faire émerger et cadrer le besoin ci-dessous, '
            . "en t'appuyant sur le code réel de ce dépôt. Pose tes questions une à la fois et rends la main après chaque "
            . "question ; n'écris le brief que lorsque le cadrage est complet.\n\nBesoin exprimé : %s",
            $userMessage,
        );
    }

    private function parse(string $output): InterviewTurnResult
    {
        try {
            $data = json_decode($output, true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new InterviewFailedException('Réponse de « claude » illisible (JSON invalide).');
        }

        if (!\is_array($data)) {
            throw new InterviewFailedException('Réponse de « claude » inattendue.');
        }

        if (true === ($data['is_error'] ?? false)) {
            $detail = \is_string($data['result'] ?? null) && '' !== $data['result'] ? $data['result'] : 'erreur inconnue';

            throw new InterviewFailedException(sprintf('Le tour d\'interview a échoué : %s', $detail));
        }

        $result = $data['result'] ?? null;
        if (!\is_string($result) || '' === trim($result)) {
            throw new InterviewFailedException('Réponse de « claude » vide.');
        }

        $cost = $data['total_cost_usd'] ?? null;

        return new InterviewTurnResult($result, \is_int($cost) || \is_float($cost) ? (float) $cost : 0.0);
    }

    /**
     * Extrait une raison lisible d'un échec process. Le token du projet n'est jamais passé à
     * `claude` (session ambiante), donc absent des sorties — rien à masquer ici.
     */
    private function reason(Process $process): string
    {
        $output = trim($process->getErrorOutput()) ?: trim($process->getOutput());
        $lines = array_values(array_filter(explode("\n", $output), static fn (string $l): bool => '' !== trim($l)));
        $lastLine = trim((string) end($lines));

        if ('' === $lastLine) {
            return sprintf('« claude » a échoué (code %d).', (int) $process->getExitCode());
        }

        return sprintf('« claude » a échoué : %s', $lastLine);
    }
}
