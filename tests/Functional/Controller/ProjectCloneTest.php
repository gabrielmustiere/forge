<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Project;
use App\Entity\User;
use App\Enum\Type\CloneStatus;
use App\Enum\Type\Provider;
use App\Message\CloneRepository;
use App\Repository\ProjectRepository;
use App\Service\TokenCipher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Le transport `async` est en mémoire en test (config/packages/test/messenger.yaml) : le job
 * de clone n'est pas consommé au dispatch, ce qui permet d'observer l'état `Cloning` persisté
 * et le message enqueué sans lancer de `git` réel.
 */
final class ProjectCloneTest extends WebTestCase
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
    }

    public function testCloneRequestMarksCloningAndEnqueuesTheJob(): void
    {
        $this->login();
        $project = $this->persistProject('https://github.com/acme/cloneable', 'acme/cloneable');
        $id = $project->getId();
        self::assertSame(CloneStatus::NotCloned, $project->getCloneStatus());

        $crawler = $this->client->request('GET', '/projects/' . $id);
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Cloner')->form());

        self::assertResponseRedirects('/projects/' . $id);

        // État persisté : Cloning, avant toute consommation du worker.
        $this->em->clear();
        $updated = $this->projects->find($id);
        self::assertNotNull($updated);
        self::assertSame(CloneStatus::Cloning, $updated->getCloneStatus());

        // Un et un seul CloneRepository a été enqueué sur le transport async.
        $sent = $this->asyncTransport()->getSent();
        self::assertCount(1, $sent);
        $message = $sent[0]->getMessage();
        self::assertInstanceOf(CloneRepository::class, $message);
        self::assertSame($id, $message->projectId);
    }

    public function testCloneIsRejectedWithoutAValidCsrfToken(): void
    {
        $this->login();
        $project = $this->persistProject('https://github.com/acme/cloneable', 'acme/cloneable');
        $id = $project->getId();

        $this->client->request('POST', '/projects/' . $id . '/clone', ['_token' => 'forged']);

        self::assertResponseRedirects('/projects/' . $id);

        $this->em->clear();
        $reloaded = $this->projects->find($id);
        self::assertNotNull($reloaded);
        // Token CSRF invalide → aucun clone demandé, statut inchangé, aucun message enqueué.
        self::assertSame(CloneStatus::NotCloned, $reloaded->getCloneStatus());
        self::assertCount(0, $this->asyncTransport()->getSent());
    }

    public function testTokenNeverAppearsOnTheShowPage(): void
    {
        // Le composant ProjectCloneStatus reçoit le projet en LiveProp : on verrouille le fait
        // que ni le token en clair ni le chiffré ne fuite dans le HTML (déshydratation à l'id).
        $this->login();
        $plain = 'SUPER_SECRET_CLONE_TOKEN';
        $project = $this->persistProject('https://github.com/acme/secret-clone', 'acme/secret-clone', $plain);
        $cipher = $project->getToken();

        $this->client->request('GET', '/projects/' . $project->getId());
        self::assertResponseIsSuccessful();

        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString($plain, $html);
        self::assertStringNotContainsString($cipher, $html);
    }

    public function testCloneRequiresAuthentication(): void
    {
        $project = $this->persistProject('https://github.com/acme/cloneable', 'acme/cloneable');

        $this->client->request('POST', '/projects/' . $project->getId() . '/clone');

        // Firewall `login` : une action sous /projects redirige l'anonyme vers la connexion.
        self::assertResponseRedirects();
        self::assertStringContainsString('/login', (string) $this->client->getResponse()->headers->get('Location'));
        self::assertCount(0, $this->asyncTransport()->getSent());
    }

    private function asyncTransport(): InMemoryTransport
    {
        $transport = static::getContainer()->get('messenger.transport.async');
        \assert($transport instanceof InMemoryTransport);

        return $transport;
    }

    private function persistProject(string $url, string $name, string $plainToken = 'token'): Project
    {
        $project = new Project(Provider::GitHub, $url, $name, $this->cipher->encrypt($plainToken));
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
