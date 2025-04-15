<?php

namespace App\Tests;

use App\Entity\User;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class UserControllerTest extends WebTestCase
{
	protected $client;
	private $passwordHasher;

	protected function setUp(): void
	{
		parent::setUp();
		$this->client = static::createClient();
		$this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
	}


	protected function getUserRepository(): UserRepository
	{
		$container = static::getContainer();
		/** @var UserRepository $userRepository */
		return $container->get(UserRepository::class);
	}

	public function testShowCreateUserForm(): void
	{
		$crawler = $this->client->request('GET', '/create-user');

		$this->assertResponseIsSuccessful();
		$this->assertSelectorExists('form[name="user"]');
	}

	public function testHandleCreateUserWithValidData(): void
	{
		$crawler = $this->client->request('GET', '/create-user');

		$timestamp = time();
		$username = 'julien' . $timestamp;

		$form = $crawler->selectButton('Créer')->form([
			'user[username]' => $username,
			'user[password]' => 'julien1000',
			'user[email]' => 'julien1000' . $timestamp . '@gmail.com',
		]);

		$this->client->submit($form);

		$this->assertResponseRedirects('/');

		$user = $this->getUserRepository()->findOneBy(['username' => $username]);
		$this->assertNotNull($user);
	}

	public function testEditUserAuthorization(): void
	{
		$userRepository = $this->getUserRepository();
		$em = static::getContainer()->get('doctrine')->getManager();

		$userToEdit = new User();
		$userToEdit->setUsername('editable_user' . uniqid());
		$userToEdit->setPassword($this->passwordHasher->hashPassword($userToEdit, 'password'));
		$userToEdit->setEmail('editable_user@example.com');
		$userToEdit->setRoles(['ROLE_USER']);
		$em->persist($userToEdit);

		$adminUser = $userRepository->findOneBy(['username' => 'admin_user']);

		if (!$adminUser) {
			$adminUser = new User();
			$adminUser->setUsername('admin_user');
			$adminUser->setPassword($this->passwordHasher->hashPassword($adminUser, 'password'));
			$adminUser->setEmail('admin_user@example.com');
			$adminUser->setRoles(['ROLE_ADMIN']);
			$em->persist($adminUser);
		}

		$em->flush();

		$this->client->loginUser($adminUser);

		$this->client->request('GET', '/users/' . $userToEdit->getId() . '/edit');
		$this->assertResponseIsSuccessful();
	}

	public function testEditUserRole(): void
	{

		$userToEdit = new User();
		$userToEdit->setUsername('role_edit_user' . uniqid());
		$userToEdit->setPassword($this->passwordHasher->hashPassword($userToEdit, 'password'));
		$userToEdit->setEmail('role_edit_user@example.com');
		$userToEdit->setRoles(['ROLE_USER']);

		$em = static::getContainer()->get('doctrine')->getManager();
		$em->persist($userToEdit);
		$em->flush();

		$admin = $this->getUserRepository()->findOneBy(['username' => 'admin_user']);
		$this->assertNotNull($admin, 'Admin user not found');

		$this->client->loginUser($admin);

		$this->client->request('POST', '/users/' . $userToEdit->getId() . '/edit-role', [
			'role' => 'ROLE_ADMIN',
		]);

		$this->assertResponseRedirects('/admin/users');
		$this->client->followRedirect();

		$updatedUser = $this->getUserRepository()->find($userToEdit->getId());
		$this->assertContains('ROLE_ADMIN', $updatedUser->getRoles());
	}


	public function testAdminCanAccessUserIndex(): void
	{

		$this->client->loginUser($this->getUserRepository()->findOneBy(['username' => 'admin_user']));
		$this->client->request('GET', '/admin/users');

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('h1', 'Liste des utilisateurs');
	}

	public function testEditUserWithValidData(): void
	{
		$userRepository = $this->getUserRepository();

		$timestamp = time();
		$uniqueUsername = 'test-user_' . $timestamp;
		$uniqueEmail = 'test-user_' . $timestamp . '@example.com';

		$userToEdit = new User();
		$userToEdit->setUsername($uniqueUsername);
		$userToEdit->setPassword($this->passwordHasher->hashPassword($userToEdit, 'password'));
		$userToEdit->setEmail($uniqueEmail);
		$userToEdit->setRoles(['ROLE_USER']);

		$entityManager = static::getContainer()->get('doctrine')->getManager();
		$entityManager->persist($userToEdit);
		$entityManager->flush();


		$adminUser = $userRepository->findOneBy(['username' => 'admin_user']);
		$this->assertNotNull($adminUser, 'Admin user not found');

		$this->client->loginUser($adminUser);

		$crawler = $this->client->request('GET', '/users/' . $userToEdit->getId() . '/edit');

		$this->assertResponseIsSuccessful();

		$this->assertSelectorExists('#modifier');

		$this->assertSelectorTextContains('#modifier', 'Modifier');

		$form = $crawler->selectButton('Modifier')->form([
			'user[username]' => 'new-username_' . $timestamp,
			'user[password]' => 'new-password',
			'user[email]' => 'new-email_' . $timestamp . '@example.com',
		]);

		$this->client->submit($form);

		$crawler = $this->client->followRedirect();

		$updatedUser = $userRepository->findOneBy(['username' => 'new-username_' . $timestamp]);
		$this->assertNotNull($updatedUser);
	}

	public function testManageTasks(): void
	{
		$adminUser = $this->getUserRepository()->findOneBy(['username' => 'admin_user']);
		$this->assertNotNull($adminUser, 'Admin user not found');

		$this->client->loginUser($adminUser);

		$crawler = $this->client->request('GET', '/admin/tasks');

		$this->assertResponseIsSuccessful();

		$this->assertSelectorExists('.task-list');
	}

	public function testRestoreTask(): void
	{
		$this->client->loginUser($this->getUserRepository()->findOneBy(['username' => 'admin_user']));

		$taskRepository = static::getContainer()->get(TaskRepository::class);
		$task = $taskRepository->findOneBy(['isDeleted' => true]);

		$this->client->request('POST', '/admin/tasks/' . $task->getId() . '/restore');
		$this->assertResponseRedirects('/admin/tasks');

		$restoredTask = $taskRepository->find($task->getId());
		$this->assertFalse($restoredTask->isDeleted());
	}
}
