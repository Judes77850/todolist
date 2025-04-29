<?php

namespace App\Tests;

use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class TaskControllerTest extends WebTestCase
{
	private $client;
	private $entityManager;

	protected function setUp(): void
	{
		parent::setUp();
		$this->client = static::createClient();
		$this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
	}

	protected function getTaskRepository(): TaskRepository
	{
		return static::getContainer()->get(TaskRepository::class);
	}

	protected function getUserRepository(): UserRepository
	{
		return static::getContainer()->get(UserRepository::class);
	}

	public function testShowTaskListRequiresAuthentication(): void
	{
		$this->client->request('GET', '/tasks');

		$this->assertResponseRedirects('/login');
	}

	public function testAuthenticatedUserCanSeeTasks(): void
	{
		$this->client->loginUser($this->getUserRepository()->findOneBy(['username' => 'test-user']));
		$this->client->request('GET', '/tasks');

		$this->assertResponseIsSuccessful();
	}

	public function testCreateTask(): void
	{
		$this->client->loginUser($this->getUserRepository()->findOneBy(['username' => 'test-user']));
		$crawler = $this->client->request('GET', '/task/create');

		$this->assertResponseIsSuccessful();

		$form = $crawler->selectButton('Créer')->form([
			'task[title]' => 'Test Task',
			'task[content]' => 'Test Content',
		]);

		$this->client->submit($form);
		$this->assertResponseRedirects('/tasks');

		$task = $this->getTaskRepository()->findOneBy(['title' => 'Test Task']);
		$this->assertNotNull($task);
	}

	public function testUserCannotEditOthersTask(): void
	{
		$user = $this->getUserRepository()->findOneBy(['username' => 'test-user']);
		$otherUser = $this->getUserRepository()->findOneBy(['username' => 'non_admin_user']);

		$this->client->loginUser($otherUser);
		$task = $this->getTaskRepository()->findOneBy(['author' => $user]);

		$this->client->request('GET', '/tasks/' . $task->getId() . '/edit');
		$this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
	}

	public function testAdminCanEditAnyTask(): void
	{
		$admin = $this->getUserRepository()->findOneBy(['username' => 'admin_user']);
		$user = $this->getUserRepository()->findOneBy(['username' => 'test-user']);
		$task = $this->getTaskRepository()->findOneBy(['author' => $user]);

		$this->client->loginUser($admin);
		$this->client->request('GET', '/tasks/' . $task->getId() . '/edit');
		$this->assertResponseIsSuccessful();
	}

	public function testDeleteAsAuthor(): void
	{
		$user = $this->createUser('user_' . time());
		$user->setEmail('user_' . time() . '@example.com');
		$task = $this->createTask($user);
		$this->client->loginUser($user);
		$this->client->request('POST', '/tasks/' . $task->getId() . '/delete');

		$this->assertResponseRedirects('/tasks');
		$this->assertTrue($this->isTaskDeleted($task));
	}

	public function testDeleteAsAdminForAnonymousTask(): void
	{
		$adminUser = $this->createAdminUser();

		$this->client->loginUser($adminUser);

		$anonymousUser = $this->createAnonymousUser();

		$task = $this->createAnonymousTask($anonymousUser);

		$this->client->request('POST', '/tasks/' . $task->getId() . '/delete');

		$this->assertResponseRedirects('/tasks');
		$this->assertTrue($this->isTaskDeleted($task));

		$this->entityManager->remove($anonymousUser);
		$this->entityManager->remove($task);
		$this->entityManager->flush();
	}

	public function testDeleteUnauthorizedUser(): void
	{
		$author = $this->createUser('author_' . time());
		$otherUser = $this->createUser('other_' . time());
		$task = $this->createTask($author);

		$this->client->loginUser($otherUser);

		$this->client->request('POST', '/tasks/' . $task->getId() . '/delete');

		$this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
		$this->assertFalse($this->isTaskDeleted($task));
	}

	public function testDeleteNotLoggedIn(): void
	{
		$user = $this->createUser('user_' . time());
		$task = $this->createTask($user);

		$this->client->request('POST', '/tasks/' . $task->getId() . '/delete');

		$this->assertResponseRedirects('/login');
	}

	public function testDeleteTask(): void
	{
		$entityManager = static::getContainer()->get('doctrine')->getManager();
		$user = $this->getUserRepository()->findOneBy(['username' => 'test-user']);
		$this->client->loginUser($user);

		$task = new Task();
		$task->setTitle('Test Task');
		$task->setContent('Test Content');
		$task->setAuthor($user);
		$task->setIsDone(true);
		$task->setIsDeleted(false);
		$task->setCreatedAt(new \DateTime());

		$entityManager->persist($task);
		$entityManager->flush();

		$this->client->request('POST', '/tasks/' . $task->getId() . '/delete');

		$entityManager->refresh($task);

		$this->assertTrue($task->isDeleted(), 'La tâche devrait être marquée comme supprimée.');
	}

	public function testToggleTaskStatus(): void
	{
		$user = $this->getUserRepository()->findOneBy(['username' => 'test-user']);
		$task = $this->getTaskRepository()->findOneBy(['author' => $user]);

		$this->client->loginUser($user);
		$this->client->request('POST', '/tasks/' . $task->getId() . '/toggle');

		$this->assertResponseRedirects('/tasks');
	}

	private function createUser(string $username): User
	{
		$user = new User();
		$user->setUsername($username);
		$user->setPassword(password_hash('password', PASSWORD_BCRYPT));
		$user->setEmail('user' . time() . '@example.com');
		$user->setRoles(['ROLE_USER']);
		$this->entityManager->persist($user);
		$this->entityManager->flush();
		return $user;
	}

	private function createAdminUser(): User
	{
		$user = new User();
		$user->setUsername('admin_' . time());
		$user->setPassword(password_hash('password', PASSWORD_BCRYPT));
		$user->setEmail('admin' . time() . '@example.com');
		$user->setRoles(['ROLE_ADMIN']);

		$this->entityManager->persist($user);
		$this->entityManager->flush();

		echo 'Admin user created with ID: ' . $user->getId();

		return $user;
	}

	private function createAnonymousUser(): User
	{
		$user = new User();
		$user->setUsername('anonyme');
		$user->setPassword(password_hash('password', PASSWORD_BCRYPT));
		$user->setEmail('anonymous_' . time() . '@example.com');
		$user->setRoles(['ROLE_ANONYMOUS']);

		$this->entityManager->persist($user);
		$this->entityManager->flush();

		return $user;
	}

	private function createAnonymousTask(User $user): Task
	{
		$task = new Task();
		$task->setTitle('Tâche anonyme');
		$task->setContent('Ceci est une tâche liée à un utilisateur anonyme');
		$task->setAuthor($user);
		$task->setIsDeleted(false);
		$task->setCreatedAt(new \DateTimeImmutable());


		$this->entityManager->persist($task);
		$this->entityManager->flush();

		return $task;
	}

	private function createTask(User $author): Task
	{
		$task = new Task();
		$task->setTitle('Task title');
		$task->setContent('Task content');
		$task->setAuthor($author);
		$task->setIsDeleted(false);
		$task->setCreatedAt(new \DateTimeImmutable());

		$this->entityManager->persist($task);
		$this->entityManager->flush();

		return $task;
	}

	private function isTaskDeleted(Task $task): bool
	{
		$task = $this->entityManager->getRepository(Task::class)->find($task->getId());
		return $task ? $task->isDeleted() : false;
	}

}
