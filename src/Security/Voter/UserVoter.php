<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
	public const EDIT = 'USER_EDIT';

	public function __construct(private Security $security)
	{
	}

	protected function supports(string $attribute, mixed $subject): bool
	{
		return $attribute === self::EDIT && $subject instanceof User;
	}

	protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
	{
		$currentUser = $token->getUser();

		if (!$currentUser instanceof User) {
			return false;
		}

		/** @var User $user */
		$user = $subject;

		if ($this->security->isGranted('ROLE_ADMIN')) {
			return true;
		}

		return $currentUser === $user;
	}
}
