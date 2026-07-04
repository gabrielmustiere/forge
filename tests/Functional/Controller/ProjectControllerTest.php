<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Project;
use App\Entity\User;
use App\Enum\Type\Provider;
use App\Repository\ProjectRepository;
use App\Service\TokenCipher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ProjectControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private TokenCipher $cipher;
    private ProjectRepository $projects;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        $cipher = $container->get(TokenCipher::class);
        $projects = $container->get(ProjectRepository::class);
        \assert($em instanceof EntityManagerInterface);
        \assert($cipher instanceof TokenCipher);
        \assert($projects instanceof ProjectRepository);
        $this->em = $em;
        $this->cipher = $cipher;
        $this->projects = $projects;

        $this->em->createQuery('DELETE FROM ' . Project::class)->execute();

        $this->client->loginUser($this->ensureUser());
    }

    public function testDeclareProjectAddsItToTheList(): void
    {
        $crawler = $this->client->request('GET', '/projects/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[data-test="project-form"]')->form();
        $this->client->submit($form, [
            'project[provider]' => 'github',
            'project[url]' => 'https://github.com/acme/widget',
            'project[plainToken]' => 'ghp_declaredtoken',
        ]);

        self::assertResponseRedirects('/projects');
        $this->client->followRedirect();
        self::assertSelectorTextContains('[data-test="project-list"]', 'acme/widget');

        $project = $this->projects->findOneByNormalizedUrl('https://github.com/acme/widget');
        self::assertNotNull($project);
        self::assertSame('acme/widget', $project->getName());
        self::assertSame('ghp_declaredtoken', $this->cipher->decrypt($project->getToken()));
    }

    public function testDuplicateUrlIsRejected(): void
    {
        $this->persistProject('https://github.com/acme/dup', 'acme/dup');

        $crawler = $this->client->request('GET', '/projects/new');
        $form = $crawler->filter('form[data-test="project-form"]')->form();
        $this->client->submit($form, [
            'project[provider]' => 'github',
            'project[url]' => 'git@github.com:acme/dup.git',
            'project[plainToken]' => 'ghp_token',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'Ce dépôt est déjà suivi.');
    }

    public function testProviderMismatchIsRejected(): void
    {
        $crawler = $this->client->request('GET', '/projects/new');
        $form = $crawler->filter('form[data-test="project-form"]')->form();
        $this->client->submit($form, [
            'project[provider]' => 'github',
            'project[url]' => 'https://gitlab.com/acme/widget',
            'project[plainToken]' => 'ghp_token',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'ne correspond pas au provider');
    }

    public function testEditRenewsUrlAndToken(): void
    {
        $project = $this->persistProject('https://github.com/acme/widget', 'acme/widget', 'old-token');
        $id = $project->getId();
        $previousCipher = $project->getToken();

        $crawler = $this->client->request('GET', '/projects/' . $id . '/edit');
        $form = $crawler->filter('form[data-test="project-form"]')->form();
        $this->client->submit($form, [
            'project[url]' => 'https://github.com/acme/renamed',
            'project[plainToken]' => 'ghp_newtoken',
        ]);

        self::assertResponseRedirects('/projects');

        $this->em->clear();
        $updated = $this->projects->find($id);
        self::assertNotNull($updated);
        self::assertSame('https://github.com/acme/renamed', $updated->getUrl());
        self::assertNotSame($previousCipher, $updated->getToken());
        self::assertSame('ghp_newtoken', $this->cipher->decrypt($updated->getToken()));
    }

    public function testEditWithoutTokenKeepsTheExistingOne(): void
    {
        $project = $this->persistProject('https://github.com/acme/widget', 'acme/widget', 'kept-token');
        $id = $project->getId();

        $crawler = $this->client->request('GET', '/projects/' . $id . '/edit');
        $form = $crawler->filter('form[data-test="project-form"]')->form();
        $this->client->submit($form, [
            'project[name]' => 'acme/renamed-only',
            // plainToken laissé vide
        ]);

        self::assertResponseRedirects('/projects');

        $this->em->clear();
        $updated = $this->projects->find($id);
        self::assertNotNull($updated);
        self::assertSame('acme/renamed-only', $updated->getName());
        self::assertSame('kept-token', $this->cipher->decrypt($updated->getToken()));
    }

    public function testTokenNeverAppearsInHtml(): void
    {
        $plain = 'SUPER_SECRET_TOKEN_VALUE';
        $project = $this->persistProject('https://github.com/acme/secret', 'acme/secret', $plain);
        $cipher = $project->getToken();

        $this->client->request('GET', '/projects/' . $project->getId() . '/edit');
        $editHtml = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString($plain, $editHtml);
        self::assertStringNotContainsString($cipher, $editHtml);

        $this->client->request('GET', '/projects/new');
        $newHtml = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString($plain, $newHtml);
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
