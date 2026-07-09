<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Page hôte du parcours de cadrage (story 009) : exprimer un besoin, mener l'interview, valider
 * puis déposer le brief. Tout le comportement dynamique vit dans le Live Component
 * {@see \App\Twig\Components\ProjectInterview} ; ce contrôleur ne fait qu'afficher la page.
 */
#[Route('/projects')]
final class InterviewController extends AbstractController
{
    #[Route('/{id}/interview', name: 'app_project_interview', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function interview(Project $project): Response
    {
        return $this->render('interview/show.html.twig', ['project' => $project]);
    }
}
