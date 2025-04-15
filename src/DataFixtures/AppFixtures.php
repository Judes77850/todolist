<?php

namespace App\DataFixtures;

use App\Entity\Task;
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
		$adminUser = new User();
		$adminUser->setUsername('admin_user');
		$adminUser->setPassword($this->passwordHasher->hashPassword($adminUser, 'admin_password'));
		$adminUser->setRoles(['ROLE_ADMIN']);
		$adminUser->setEmail('admin@example.com');
		$manager->persist($adminUser);

		$nonAdminUser = new User();
		$nonAdminUser->setUsername('non_admin_user');
		$nonAdminUser->setPassword($this->passwordHasher->hashPassword($nonAdminUser, 'user_password'));
		$nonAdminUser->setRoles(['ROLE_USER']);
		$nonAdminUser->setEmail('user@example.com');
		$manager->persist($nonAdminUser);

		for ($i = 1; $i <= 5; $i++) {
			$task = new Task();
			$task->setTitle("Tâche $i");
			$task->setContent("Contenu de la tâche $i.");
			$task->setCreatedAt(new \DateTime());
			$task->setAuthor($adminUser);
			$manager->persist($task);
		}

		$manager->flush();
	}

}

