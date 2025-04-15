<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class UserFixtures extends Fixture
{
	private $passwordHasher;

	public function __construct(UserPasswordHasherInterface $passwordHasher)
	{
		$this->passwordHasher = $passwordHasher;
	}
	public function load(ObjectManager $manager): void
	{
		$user = new User();
		$user->setUsername('test-user');
		$user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
		$user->setEmail('test-user@example.com');
		$user->setRoles(['ROLE_USER']);

		$manager->persist($user);
		$manager->flush();
	}
}
