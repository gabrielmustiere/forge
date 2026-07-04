<?php

namespace DataFixtures;

use App\Entity\Project;
use App\Entity\User;
use App\Enum\Type\Provider;
use App\Service\TokenCipher;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TokenCipher $tokenCipher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_USER']);
        $manager->persist($user);

        $projects = [
            [Provider::GitHub, 'https://github.com/symfony/symfony', 'symfony/symfony'],
            [Provider::GitLab, 'https://gitlab.com/gitlab-org/gitlab', 'gitlab-org/gitlab'],
        ];

        foreach ($projects as [$provider, $url, $name]) {
            $manager->persist(new Project(
                $provider,
                $url,
                $name,
                $this->tokenCipher->encrypt('example-read-only-token'),
            ));
        }

        $manager->flush();
    }
}
