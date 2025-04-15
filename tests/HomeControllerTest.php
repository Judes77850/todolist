<?php

namespace App\Tests;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
	protected function getUserRepository(): UserRepository
	{
		$container = static::getContainer();
		/** @var UserRepository $userRepository */
		return $container->get(UserRepository::class);
	}

	public function testHomePageAsGuest(): void
	{
		$client = static::createClient();

		$crawler = $client->request('GET', '/');

		$this->assertResponseIsSuccessful();

		$this->assertSelectorTextContains('a#connexion', 'Se connecter');

	}

	public function testHomePageAsAuthenticatedUser(): void
	{

		$client = static::createClient();

		$user = $this->createTestUserInDatabase();

		$client->loginUser($user);

		$crawler = $client->request('GET', '/');

		$this->assertResponseIsSuccessful();

		$this->assertSelectorTextContains('a#deconnexion', 'Se déconnecter');
	}

	private function createTestUserInDatabase(): User
	{
		$timestamp = time();

		$user = new User();
		$user->setUsername('testuser_' . $timestamp);
		$user->setPassword(password_hash('testpassword', PASSWORD_BCRYPT));
		$user->setEmail('testuser_' . $timestamp . '@example.com');

		$entityManager = static::getContainer()->get('doctrine')->getManager();
		$entityManager->persist($user);
		$entityManager->flush();

		return $user;
	}
}


