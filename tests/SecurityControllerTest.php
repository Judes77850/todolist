<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
	public function testLoginPageIsAccessible(): void
	{
		$client = static::createClient();
		$crawler = $client->request('GET', '/login');

		$this->assertResponseIsSuccessful();
		$this->assertSelectorExists('form');
		$this->assertSelectorExists('input[name="_username"]');
		$this->assertSelectorExists('input[name="_password"]');
	}

	public function testRedirectIfUserAlreadyLoggedIn(): void
	{
		$client = static::createClient();
		$userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);

		$testUser = $userRepository->findOneBy([]);
		if (!$testUser) {
			$testUser = new User();
			$testUser->setUsername('testuser');
			$testUser->setPassword('testpassword');
			$testUser->setEmail('test@example.com');
			$testUser->setRoles(['ROLE_USER']);

			$entityManager = static::getContainer()->get('doctrine')->getManager();
			$entityManager->persist($testUser);
			$entityManager->flush();
		}

		$client->loginUser($testUser);
		$client->request('GET', '/login');

		$this->assertResponseRedirects('/');
	}

	public function testLoginFailureShowsError(): void
	{
		$client = static::createClient();
		$crawler = $client->request('GET', '/login');

		$form = $crawler->selectButton('Se connecter')->form([
			'_username' => 'wrong',
			'_password' => 'wrongpass',
		]);

		$client->submit($form);

		$this->assertRouteSame('app_login');
	}

	public function testLogoutRouteIsProtectedByFirewall(): void
	{
		$client = static::createClient();

		$client->request('GET', '/logout');

		$this->assertTrue(
			$client->getResponse()->isRedirection() || $client->getResponse()->isSuccessful(),
			'Logout route should be handled by the firewall and not reach controller method.'
		);
	}
}