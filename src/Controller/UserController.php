<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

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
				dump($e->getMessage());
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

