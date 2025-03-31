<?php

namespace App\Tests;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class TaskControllerTest extends WebTestCase
{
	protected function getTaskRepository(): TaskRepository
	{
		$container = static::getContainer();
		/** @var TaskRepository $taskRepository */
		return $container->get(TaskRepository::class);
	}

	protected function getUserRepository(): UserRepository
	{
		$container = static::getContainer();
		/** @var UserRepository $userRepository */
		return $container->get(UserRepository::class);
	}

	public function testShowTaskListRequiresAuthentication(): void
	{
		$client = static::createClient();
		$client->request('GET', '/tasks');

		$this->assertResponseRedirects('/login');
	}

	public function testAuthenticatedUserCanSeeTasks(): void
	{
		$client = static::createClient();
		$client->loginUser($this->getUserRepository()->findOneBy(['username' => 'julien']));
		$client->request('GET', '/tasks');

		$this->assertResponseIsSuccessful();
	}

	public function testCreateTask(): void
	{
		$client = static::createClient();
		$client->loginUser($this->getUserRepository()->findOneBy(['username' => 'julien']));
		$crawler = $client->request('GET', '/task/create');

		$this->assertResponseIsSuccessful();

		$form = $crawler->selectButton('Créer')->form([
			'task[title]' => 'Test Task',
			'task[content]' => 'Test Content',
		]);

		$client->submit($form);
		$this->assertResponseRedirects('/tasks');

		$task = $this->getTaskRepository()->findOneBy(['title' => 'Test Task']);
		$this->assertNotNull($task);
	}

	public function testEditTask(): void
	{
		$client = static::createClient();
		$user = $this->getUserRepository()->findOneBy(['username' => 'julien']);
		$task = $this->getTaskRepository()->findOneBy(['author' => $user]);

		$client->loginUser($user);
		$crawler = $client->request('GET', '/tasks/' . $task->getId() . '/edit');

		$this->assertResponseIsSuccessful();

		$form = $crawler->selectButton('Modifier')->form([
			'task[title]' => 'Updated Title',
		]);

		$client->submit($form);
		$this->assertResponseRedirects('/tasks');
	}

	public function testDeleteTask(): void
	{
		$client = static::createClient();
		$entityManager = static::getContainer()->get('doctrine')->getManager();
		$taskRepository = static::getContainer()->get(TaskRepository::class);
		$userRepository = static::getContainer()->get(UserRepository::class);

		$user = $userRepository->findOneBy(['username' => 'julien']);
		$client->loginUser($user);

		$task = new Task();
		$task->setTitle('Test Task');
		$task->setContent('Test Content');
		$task->setAuthor($user);
		$task->setIsDone(true);
		$task->setIsDeleted(false);
		$task->setCreatedAt(new \DateTime());

		$entityManager->persist($task);
		$entityManager->flush();

		$client->request('POST', '/tasks/' . $task->getId() . '/delete');

		$entityManager->refresh($task);

		$this->assertTrue($task->isDeleted(), 'La tâche devrait être marquée comme supprimée.');
	}

	public function testToggleTaskStatus(): void
	{
		$client = static::createClient();
		$user = $this->getUserRepository()->findOneBy(['username' => 'julien']);
		$task = $this->getTaskRepository()->findOneBy(['author' => $user]);

		$client->loginUser($user);
		$client->request('POST', '/tasks/' . $task->getId() . '/toggle');

		$this->assertResponseRedirects('/tasks');
	}
}
