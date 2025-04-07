<?php

namespace App\Security\Voter;

use App\Entity\Task;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TaskVoter extends Voter
{
	public const EDIT = 'TASK_EDIT';
	public const DELETE = 'TASK_DELETE';

	public function __construct(private Security $security) {}

	protected function supports(string $attribute, mixed $subject): bool
	{
		return in_array($attribute, [self::EDIT, self::DELETE]) && $subject instanceof Task;
	}

	protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
	{
		$user = $token->getUser();

		if (!$user) {
			return false;
		}

		/** @var Task $task */
		$task = $subject;

		if ($this->security->isGranted('ROLE_ADMIN')) {
			return true;
		}

		return $task->getAuthor() === $user;
	}
}
