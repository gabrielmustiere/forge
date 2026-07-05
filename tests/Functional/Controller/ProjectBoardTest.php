<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Project;
use App\Entity\User;
use App\Enum\Type\Provider;
use App\Service\TokenCipher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Le reader réseau est neutralisé en environnement test ({@see \App\Tests\Double\StubRepositoryReader}) :
 * le nom du dépôt pilote l'arbre de stories renvoyé — `*board*` produit un pipeline complet,
 * `*offline*` un échec, un dépôt neutre un arbre vide. Aucun appel réseau réel n'est émis.
 */
final class ProjectBoardTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private TokenCipher $cipher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        $cipher = $container->get(TokenCipher::class);
        \assert($em instanceof EntityManagerInterface);
        \assert($cipher instanceof TokenCipher);
        $this->em = $em;
        $this->cipher = $cipher;

        $this->em->createQuery('DELETE FROM ' . Project::class)->execute();

        $this->client->loginUser($this->ensureUser());
    }

    public function testBoardRendersColumnsCountsAndBanner(): void
    {
        $project = $this->persistProject('https://github.com/acme/board-app', 'acme/board-app');

        $crawler = $this->client->request('GET', '/projects/' . $project->getId());
        self::assertResponseIsSuccessful();

        // Les quatre colonnes ordonnées du pipeline.
        self::assertCount(4, $crawler->filter('[data-test="board-column"]'));

        // Compteurs par colonne : Cadrage 1, Planifié 1, Review 1, Livré 2.
        self::assertSame('1', trim($crawler->filter('[data-stage="cadrage"] [data-test="column-count"]')->text()));
        self::assertSame('1', trim($crawler->filter('[data-stage="planifie"] [data-test="column-count"]')->text()));
        self::assertSame('1', trim($crawler->filter('[data-stage="review"] [data-test="column-count"]')->text()));
        self::assertSame('2', trim($crawler->filter('[data-stage="livre"] [data-test="column-count"]')->text()));

        // Bandeau « À vérifier » présent avec son compte.
        self::assertCount(1, $crawler->filter('[data-test="board-banner"]'));
        self::assertSame('1', trim($crawler->filter('[data-test="banner-count"]')->text()));
    }

    public function testRefactoCardIsNeverInCadrageColumn(): void
    {
        $project = $this->persistProject('https://github.com/acme/board-app', 'acme/board-app');

        $crawler = $this->client->request('GET', '/projects/' . $project->getId());

        $cadrage = $crawler->filter('[data-stage="cadrage"]')->text();
        self::assertStringNotContainsString('005-r-review', $cadrage);

        $review = $crawler->filter('[data-stage="review"]')->text();
        self::assertStringContainsString('005-r-review', $review);
    }

    public function testCardsWithinAColumnAreSortedByNumberDescending(): void
    {
        $project = $this->persistProject('https://github.com/acme/board-app', 'acme/board-app');

        $crawler = $this->client->request('GET', '/projects/' . $project->getId());

        $ids = $crawler->filter('[data-stage="livre"] [data-test="story-card"]')->each(
            static fn ($node): string => (string) $node->attr('data-story-id'),
        );
        self::assertSame(['007-t-livre', '003-f-livre-complet'], $ids);
    }

    public function testEmptyEligibleProjectShowsEmptyState(): void
    {
        $project = $this->persistProject('https://github.com/acme/quiet-repo', 'acme/quiet-repo');

        $this->client->request('GET', '/projects/' . $project->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-test="board-empty"]');
        self::assertSelectorNotExists('[data-test="board"]');
    }

    public function testFailedScanShowsGuardrail(): void
    {
        $project = $this->persistProject('https://github.com/acme/offline-app', 'acme/offline-app');

        $this->client->request('GET', '/projects/' . $project->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-test="board-error"]');
    }

    public function testStoryDocRendersSanitizedMarkdown(): void
    {
        $project = $this->persistProject('https://github.com/acme/board-app', 'acme/board-app');

        $this->client->request('GET', '/projects/' . $project->getId() . '/story/005-r-review/doc/plan.md');
        self::assertResponseIsSuccessful();

        $html = (string) $this->client->getResponse()->getContent();
        // Le vrai titre `# H1` du document est rendu dans le drawer.
        self::assertStringContainsString('<h1>Titre réel de la story</h1>', $html);
        // Le HTML brut du contenu tiers est neutralisé (converter en mode strip).
        self::assertStringNotContainsString('<script>', $html);
        // Un lien externe du contenu tiers s'ouvre en nouvel onglet, sans fuite de contexte.
        self::assertStringContainsString('target="_blank"', $html);
        self::assertStringContainsString('noopener', $html);
        self::assertSelectorExists('[data-test="doc-content"]');
    }

    public function testStoryDocRejectsFilenameWithoutMarkdownExtension(): void
    {
        $project = $this->persistProject('https://github.com/acme/board-app', 'acme/board-app');

        $this->client->request('GET', '/projects/' . $project->getId() . '/story/005-r-review/doc/passwd');
        self::assertResponseStatusCodeSame(404);
    }

    public function testStoryDocRejectsPathTraversalAttempt(): void
    {
        $project = $this->persistProject('https://github.com/acme/board-app', 'acme/board-app');

        // `storyId` avec des segments de traversée : ne matche pas la regex stricte → 404.
        $this->client->request('GET', '/projects/' . $project->getId() . '/story/..%2F..%2Fetc/doc/pitch.md');
        self::assertResponseStatusCodeSame(404);
    }

    public function testBoardRequiresAuthentication(): void
    {
        $project = $this->persistProject('https://github.com/acme/board-app', 'acme/board-app');
        $id = $project->getId();

        self::ensureKernelShutdown();
        $anonymous = static::createClient();
        $anonymous->request('GET', '/projects/' . $id);

        self::assertResponseRedirects();
        self::assertStringContainsString('/login', (string) $anonymous->getResponse()->headers->get('Location'));
    }

    private function persistProject(string $url, string $name, string $plainToken = 'token'): Project
    {
        $project = new Project(Provider::GitHub, $url, $name, $this->cipher->encrypt($plainToken));
        $this->em->persist($project);
        $this->em->flush();

        return $project;
    }

    private function ensureUser(): User
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        if (null === $user) {
            $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
            \assert($hasher instanceof UserPasswordHasherInterface);

            $user = new User();
            $user->setEmail('admin@example.com');
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($hasher->hashPassword($user, 'password'));
            $this->em->persist($user);
            $this->em->flush();
        }

        return $user;
    }
}
