<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SecurityControllerTest extends WebTestCase
{
	public function testLoginPageIsAccessible(): void
	{
		$client = static::createClient();
		$crawler = $client->request('GET', '/login');

		$this->assertResponseIsSuccessful();
		$this->assertSelectorExists('form[class="needs-validation"]');
		$this->assertSelectorExists('input[name="_username"]');
		$this->assertSelectorExists('input[name="_password"]');
		$this->assertSelectorExists('button[type="submit"]');
	}

	public function testLoginFailureShowsErrorMessage(): void
	{
		$client = static::createClient();

		$crawler = $client->request('GET', '/');

		$link = $crawler->selectLink('Se connecter')->link();
		$crawler = $client->click($link);

		$this->assertRouteSame('app_login');

		$form = $crawler->selectButton('Se connecter')->form([
			'_username' => 'wrong-user',
			'_password' => 'wrong-password',
		]);

		$client->submit($form);

		$this->assertRouteSame('app_login');
	}

	public function testLogout(): void
	{
		$client = static::createClient();

		$userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
		$testUser = $userRepository->findOneBy(['username' => 'test-user']);
		$client->loginUser($testUser);

		$client->request('GET', '/');
		$this->assertResponseIsSuccessful();

		$client->request('GET', '/logout');

		$this->assertResponseRedirects('/');
	}
}
