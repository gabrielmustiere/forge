<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectFormData;
use App\Form\ProjectType;
use App\Manager\ProjectManager;
use App\Service\Board\ProjectBoardBuilder;
use App\Service\Board\StoryDocumentFetcher;
use App\Service\Board\StoryDocumentUnavailableException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/projects')]
final class ProjectController extends AbstractController
{
    public function __construct(
        private readonly ProjectManager $manager,
        private readonly ProjectBoardBuilder $boardBuilder,
        private readonly StoryDocumentFetcher $documentFetcher,
    ) {
    }

    #[Route('', name: 'app_project_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('project/index.html.twig');
    }

    #[Route('/new', name: 'app_project_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $data = new ProjectFormData();
        $form = $this->createForm(ProjectType::class, $data, ['validation_groups' => ['Default', 'create']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->manager->create($data);
            $this->addFlash('success', 'Projet déclaré.');

            return $this->redirectToRoute('app_project_index');
        }

        return $this->render('project/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'app_project_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Project $project): Response
    {
        // Scan live à chaque ouverture (règle 8) : le board reflète l'état réel du dépôt.
        return $this->render('project/show.html.twig', [
            'project' => $project,
            'result' => $this->boardBuilder->build($project),
        ]);
    }

    /**
     * Fragment (turbo-frame) chargé à la demande dans le drawer : le contenu markdown d'un
     * document de story. `storyId` et `filename` sont contraints par des regex strictes
     * (aucun `/`, aucun `..`) pour interdire toute traversée de chemin.
     */
    #[Route(
        '/{id}/story/{storyId}/doc/{filename}',
        name: 'app_project_story_doc',
        requirements: [
            'id' => '\d+',
            'storyId' => '\d{3}-[frt]-[a-z0-9-]+',
            'filename' => '[a-z0-9._-]+\.md',
        ],
        methods: ['GET'],
    )]
    public function storyDoc(Project $project, string $storyId, string $filename): Response
    {
        try {
            $markdown = $this->documentFetcher->fetch($project, $storyId, $filename);
        } catch (StoryDocumentUnavailableException) {
            $markdown = null;
        }

        return $this->render('project/_doc.html.twig', [
            'filename' => $filename,
            'markdown' => $markdown,
        ]);
    }

    #[Route('/{id}/verify', name: 'app_project_verify', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function verify(Request $request, Project $project): Response
    {
        if ($this->isCsrfTokenValid('verify' . $project->getId(), (string) $request->request->get('_token'))) {
            $this->manager->reverify($project);
            $this->addFlash('success', 'Accès vérifié.');
        }

        return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
    }

    #[Route('/{id}/edit', name: 'app_project_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Project $project): Response
    {
        $data = ProjectFormData::fromProject($project);
        $form = $this->createForm(ProjectType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->manager->update($project, $data);
            $this->addFlash('success', 'Projet mis à jour.');

            return $this->redirectToRoute('app_project_index');
        }

        return $this->render('project/edit.html.twig', [
            'form' => $form,
            'project' => $project,
        ]);
    }
}
