<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
	#[Route('/', name: 'app_home')]
	public function index(Security $security): Response
	{
		$user = $security->getUser();
		return $this->render('default/index.html.twig', [
			'username' => $user ? $user->getUserIdentifier() : 'Invité',
		]);
	}

}