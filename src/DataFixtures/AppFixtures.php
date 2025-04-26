<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Utilisateur Admin
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setFirstname('Admin');
        $admin->setName('User');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $manager->persist($admin);

        // Utilisateur Bibliothécaire
        $bibliothecaire = new User();
        $bibliothecaire->setEmail('bibliothecaire@example.com');
        $bibliothecaire->setFirstname('Bibliothécaire');
        $bibliothecaire->setName('User');
        $bibliothecaire->setRoles(['ROLE_LIBRARIAN']);
        $bibliothecaire->setPassword($this->passwordHasher->hashPassword($bibliothecaire, 'password'));
        $manager->persist($bibliothecaire);

        // Utilisateur simple
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setFirstname('Simple');
        $user->setName('User');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $manager->persist($user);

        $manager->flush();
    }
}
