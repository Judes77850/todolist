<?php

namespace App\Tests;

use App\Entity\User;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
{
	protected function getUserRepository(): UserRepository
	{
		$container = static::getContainer();
		/** @var UserRepository $userRepository */
		return $container->get(UserRepository::class);
	}
	public function testShowCreateUserForm(): void
	{
		$client = static::createClient();
		$crawler = $client->request('GET', '/create-user');

		$this->assertResponseIsSuccessful();
		$this->assertSelectorExists('form[name="user"]');
	}

	public function testHandleCreateUserWithValidData(): void
	{
		$client = static::createClient();
		$crawler = $client->request('GET', '/create-user');

		$timestamp = time();
		$username = 'julien' . $timestamp;

		$form = $crawler->selectButton('Créer')->form([
			'user[username]' => $username,
			'user[password]' => 'julien1000',
			'user[email]' => 'julien1000' . $timestamp . '@gmail.com',
		]);

		$client->submit($form);

		$this->assertResponseRedirects('/');

		$user = $this->getUserRepository()->findOneBy(['username' => $username]);
		$this->assertNotNull($user);
	}

	public function testEditUserRequiresAdminRole(): void
	{
		$client = static::createClient();
		$client->request('GET', '/users/1/edit');

		$this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
		$this->assertResponseRedirects('/login');

		$client->loginUser($this->getUserRepository()->findOneBy(['username' => 'julien8']));
		$client->request('GET', '/users/1/edit');

		$this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
	}

	public function testEditUserRole(): void
	{
		$client = static::createClient();

		$client->loginUser($this->getUserRepository()->findOneBy(['username' => 'admin_user']));

		$crawler = $client->request('POST', '/users/1/edit-role', [
			'role' => 'ROLE_USER',
		]);

		$this->assertResponseRedirects('/admin/users');

		$client->followRedirect();
	}

	public function testAdminCanAccessUserIndex(): void
	{
		$client = static::createClient();

		$client->loginUser($this->getUserRepository()->findOneBy(['username' => 'admin_user']));
		$client->request('GET', '/admin/users');

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('h1', 'Liste des utilisateurs');
	}

	public function testEditUserWithValidData(): void
	{
		$client = static::createClient();
		$userRepository = $this->getUserRepository();

		$timestamp = time();
		$uniqueUsername = 'test-user_' . $timestamp;
		$uniqueEmail = 'test-user_' . $timestamp . '@example.com';

		$userToEdit = new User();
		$userToEdit->setUsername($uniqueUsername);
		$userToEdit->setPassword('password');
		$userToEdit->setEmail($uniqueEmail);
		$userToEdit->setRoles(['ROLE_USER']);

		$entityManager = static::getContainer()->get('doctrine')->getManager();
		$entityManager->persist($userToEdit);
		$entityManager->flush();


		$adminUser = $userRepository->findOneBy(['username' => 'admin_user']);
		$this->assertNotNull($adminUser, 'Admin user not found');

		$client->loginUser($adminUser);

		$crawler = $client->request('GET', '/users/' . $userToEdit->getId() . '/edit');

		$this->assertResponseIsSuccessful();

		$this->assertSelectorExists('#modifier');

		$this->assertSelectorTextContains('#modifier', 'Modifier');

		$form = $crawler->selectButton('Modifier')->form([
			'user[username]' => 'new-username_' . $timestamp,
			'user[password]' => 'new-password',
			'user[email]' => 'new-email_' . $timestamp . '@example.com',
		]);

		$client->submit($form);

		$crawler = $client->followRedirect();

		$updatedUser = $userRepository->findOneBy(['username' => 'new-username_' . $timestamp]);
		$this->assertNotNull($updatedUser);
	}

	public function testManageTasks(): void
	{
		$client = static::createClient();

		$adminUser = $this->getUserRepository()->findOneBy(['username' => 'admin_user']);
		$this->assertNotNull($adminUser, 'Admin user not found');

		$client->loginUser($adminUser);

		$crawler = $client->request('GET', '/admin/tasks');

		$this->assertResponseIsSuccessful();

		$this->assertSelectorExists('.task-list');
	}

	public function testRestoreTask(): void
	{
		$client = static::createClient();
		$client->loginUser($this->getUserRepository()->findOneBy(['username' => 'admin_user']));

		$taskRepository = static::getContainer()->get(TaskRepository::class);
		$task = $taskRepository->findOneBy(['isDeleted' => true]);

		$client->request('POST', '/admin/tasks/' . $task->getId() . '/restore');
		$this->assertResponseRedirects('/admin/tasks');

		$restoredTask = $taskRepository->find($task->getId());
		$this->assertFalse($restoredTask->isDeleted());
	}
}
