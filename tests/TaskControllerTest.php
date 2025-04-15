<?php

namespace App\Tests;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class TaskControllerTest extends WebTestCase
{
	protected $client;
	protected function setUp(): void
	{
		parent::setUp();
		$this->client = static::createClient();
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

}
