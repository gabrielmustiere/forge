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
 * Bouton « Exprimer un besoin » (précondition de clone, règle 1) et page hôte de l'interview.
 */
final class ProjectInterviewTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private TokenCipher $cipher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $c = static::getContainer();

        $em = $c->get(EntityManagerInterface::class);
        $cipher = $c->get(TokenCipher::class);
        \assert($em instanceof EntityManagerInterface);
        \assert($cipher instanceof TokenCipher);
        $this->em = $em;
        $this->cipher = $cipher;

        $this->em->createQuery('DELETE FROM ' . Project::class)->execute();
    }

    public function testButtonIsAnActiveLinkOnAClonedProject(): void
    {
        $this->login();
        $project = $this->persistProject(cloned: true);

        $crawler = $this->client->request('GET', '/projects/' . $project->getId());
        self::assertResponseIsSuccessful();

        $link = $crawler->filter('a[data-test="project-interview"]');
        self::assertCount(1, $link, 'un lien actif « Exprimer un besoin » est attendu');
        self::assertStringContainsString('/interview', (string) $link->attr('href'));
    }

    public function testButtonIsDisabledOnANonClonedProject(): void
    {
        $this->login();
        $project = $this->persistProject(cloned: false);

        $crawler = $this->client->request('GET', '/projects/' . $project->getId());
        self::assertResponseIsSuccessful();

        self::assertCount(0, $crawler->filter('a[data-test="project-interview"]'));
        $button = $crawler->filter('button[data-test="project-interview"]');
        self::assertCount(1, $button, 'un bouton désactivé est attendu tant que le projet n\'est pas cloné');
        self::assertNotNull($button->attr('disabled'));
    }

    public function testInterviewPageRendersTheStartForm(): void
    {
        $this->login();
        $project = $this->persistProject(cloned: true);

        $crawler = $this->client->request('GET', '/projects/' . $project->getId() . '/interview');
        self::assertResponseIsSuccessful();

        self::assertCount(1, $crawler->filter('[data-test="interview-start"]'));
        self::assertCount(1, $crawler->filter('[data-test="interview-message-input"]'));
    }

    public function testInterviewPageRequiresAuthentication(): void
    {
        $project = $this->persistProject(cloned: true);

        $this->client->request('GET', '/projects/' . $project->getId() . '/interview');

        self::assertResponseRedirects();
        self::assertStringContainsString('/login', (string) $this->client->getResponse()->headers->get('Location'));
    }

    private function persistProject(bool $cloned): Project
    {
        $project = new Project(Provider::GitHub, 'https://github.com/acme/repo', 'acme/repo', $this->cipher->encrypt('token'));
        if ($cloned) {
            $project->markCloned('/tmp/acme-repo', new \DateTimeImmutable());
        }
        $this->em->persist($project);
        $this->em->flush();

        return $project;
    }

    private function login(): void
    {
        $this->client->loginUser($this->ensureUser());
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
