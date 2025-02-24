<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TaskController extends AbstractController
{
	#[Route(path: '/tasks', name: 'task_index')]
	public function index(TaskRepository $taskRepository, Security $security): Response
	{
		$user = $security->getUser();
		if (!$user) {
			return $this->redirectToRoute('app_login');
		}
		$tasks = $taskRepository->findUserTasks($user);

		return $this->render('task/index.html.twig', [
			'tasks' => $tasks,
			'user' => $user,
		]);
	}


	#[Route(path: '/task/create', name: 'task_create')]
	public function create(Request $request, EntityManagerInterface $entityManager, Security $security): Response
	{
		$task = new Task();
		$form = $this->createForm(TaskType::class, $task);

		$user = $security->getUser();
		dump($user);

		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$task->setAuthor($user);
			$task->setCreatedAt(new \DateTime());

			$entityManager->persist($task);
			$entityManager->flush();

			return $this->redirectToRoute('task_index');
		}

		return $this->render('task/create.html.twig', [
			'form' => $form->createView(),
		]);
	}

	#[Route(path: '/tasks/{id}/delete', name: 'task_delete', methods: ['POST'])]
	public function delete(Task $task, EntityManagerInterface $entityManager): Response
	{
		if ($task->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
			throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette tâche');
		}

		$task->setIsDeleted(true);
		$entityManager->flush();
		$this->addFlash('success', 'La tâche a bien été supprimée.');

		return $this->redirectToRoute('task_index');
	}

	#[Route(path: '/tasks/{id}/edit', name: 'task_edit', methods: ['GET', 'POST'])]
	public function edit(Task $task, Request $request, EntityManagerInterface $entityManager): Response
	{
		if ($task->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
			throw $this->createAccessDeniedException('You cannot edit this task');
		}

		$form = $this->createForm(TaskType::class, $task);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->flush();
			$this->addFlash('success', 'La tâche a bien été modifiée.');
			return $this->redirectToRoute('task_index');
		}

		return $this->render('task/edit.html.twig', [
			'form' => $form->createView(),
			'task' => $task,
		]);
	}

	#[Route(path: '/tasks/done', name: 'task_done_index')]
	public function deletedIndex(TaskRepository $taskRepository, Security $security): Response
	{
		$user = $security->getUser();
		if (!$user) {
			return $this->redirectToRoute('app_login');
		}

		$doneTasks = $taskRepository->findBy(['author' => $user, 'isDone' => true]);

		return $this->render('task/done_index.html.twig', [
			'tasks' => $doneTasks,
			'user' => $user,
		]);
	}

	#[Route(path: '/tasks/{id}/toggle', name: 'task_toggle', methods: ['POST'])]
	public function toggle(Task $task, EntityManagerInterface $entityManager): Response
	{
		$task->setIsDone(!$task->isDone());
		$entityManager->flush();
		$this->addFlash('success', sprintf('La tâche %s a bien été marquée comme faite.', $task->getTitle()));

		return $this->redirectToRoute('task_index');
	}


}
