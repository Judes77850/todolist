<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
	#[Route('/create-user', name: 'create_user', methods: ['GET'])]
	public function showCreateUserForm(): Response
	{
		$user = new User();
		$form = $this->createForm(UserType::class, $user);

		return $this->render('user/create.html.twig', [
			'form' => $form->createView(),
			'errors' => [],
		]);
	}

	#[Route('/users/{id}/edit', name: 'user_edit', methods: ['GET', 'POST'])]
	#[IsGranted('ROLE_ADMIN')]
	public function editUser(
		User $user,
		Request $request,
		UserPasswordHasherInterface $passwordHasher,
		EntityManagerInterface $entityManager
	): Response {
		$form = $this->createForm(UserType::class, $user);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$plainPassword = $form->get('password')->getData();

			if (!empty($plainPassword)) {
				$hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
				$user->setPassword($hashedPassword);
			}

			try {
				$entityManager->flush();
				$this->addFlash('success', 'Utilisateur mis à jour avec succès.');
				return $this->redirectToRoute('user_index');
			} catch (\Exception $e) {
				$this->addFlash('danger', 'Erreur lors de la mise à jour de l\'utilisateur.');
			}
		}

		return $this->render('user/edit.html.twig', [
			'form' => $form->createView(),
			'user' => $user,
		]);
	}

	#[Route('/admin/users', name: 'user_index', methods: ['GET'])]
	#[IsGranted('ROLE_ADMIN')]
	public function index(UserRepository $userRepository): Response
	{
		return $this->render('user/index.html.twig', [
			'users' => $userRepository->findAll(),
		]);
	}

	#[Route('/users/{id}/edit-role', name: 'user_edit_role', methods: ['POST'])]
	#[IsGranted('ROLE_ADMIN')]
	public function editUserRole(User $user, Request $request, EntityManagerInterface $entityManager): Response
	{
		$this->denyAccessUnlessGranted('ROLE_ADMIN');

		$newRole = $request->request->get('role');

		if (!in_array($newRole, ['ROLE_USER', 'ROLE_ADMIN'])) {
			$this->addFlash('danger', 'Rôle invalide.');
			return $this->redirectToRoute('user_index');
		}

		$user->setRoles([$newRole]);
		$entityManager->flush();

		$this->addFlash('alert-success', 'Rôle mis à jour avec succès.');
		return $this->redirectToRoute('user_index');
	}

	#[Route('/admin/tasks', name: 'task_manage', methods: ['GET'])]
	#[IsGranted('ROLE_ADMIN')]
	public function manageTasks(Request $request, TaskRepository $taskRepository): Response
	{
		$showDeleted = $request->query->get('showDeleted') === 'on';

		if ($showDeleted) {
			$tasks = $taskRepository->findAll();
		} else {
			$tasks = $taskRepository->findNoDeletedTasks();
		}

		return $this->render('task/manage.html.twig', [
			'tasks' => $tasks,
			'showDeleted' => $showDeleted,
		]);
	}
	#[Route('/admin/tasks/{id}/restore', name: 'task_restore', methods: ['POST'])]
	#[IsGranted('ROLE_ADMIN')]
	public function restoreTask(int $id, TaskRepository $taskRepository, EntityManagerInterface $entityManager): Response
	{
		$task = $taskRepository->find($id);

		if (!$task) {
			throw $this->createNotFoundException('La tâche n\'existe pas.');
		}

		$task->setIsDeleted(false);
		$entityManager->persist($task);
		$entityManager->flush();

		$this->addFlash('success', 'Tâche restaurée avec succès.');
		return $this->redirectToRoute('task_manage');
	}


	#[Route('/create-user', name: 'handle_create_user', methods: ['POST'])]
	public function handleCreateUser(
		Request $request,
		UserPasswordHasherInterface $passwordHasher,
		EntityManagerInterface $entityManager
	): Response {
		$user = new User();
		$form = $this->createForm(UserType::class, $user);
		$form->handleRequest($request);

		if ($form->isSubmitted() && !$form->isValid()) {
			$errors = [];
			foreach ($form->getErrors(true) as $error) {
				$errors[] = $error->getMessage();
			}
			return $this->render('user/create.html.twig', [
				'form' => $form->createView(),
				'errors' => $errors,
			]);
		}

		if ($form->isSubmitted() && $form->isValid()) {
			$plainPassword = $form->get('password')->getData();

			if (!empty($plainPassword)) {
				$hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
				$user->setPassword($hashedPassword);
			} else {
				return new Response('Le mot de passe ne peut pas être vide', Response::HTTP_BAD_REQUEST);
			}

			try {
				$entityManager->persist($user);
				$entityManager->flush();
			} catch (\Exception $e) {
				return new Response('Erreur lors de la création de l\'utilisateur', Response::HTTP_INTERNAL_SERVER_ERROR);
			}

			return $this->redirectToRoute('app_home');
		}

		return $this->render('user/create.html.twig', [
			'form' => $form->createView(),
			'errors' => [],
		]);
	}
}

