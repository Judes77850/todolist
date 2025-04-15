<?php

namespace App\Repository;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Task::class);
	}

	public function findUserTasks(User $user): array
	{
		return $this->createQueryBuilder('t')
			->where('t.author = :user')
			->andWhere('t.isDeleted = false')
			->setParameter('user', $user)
			->getQuery()
			->getResult();
	}

	public function findNoDeletedTasks(): array
	{
		return $this->createQueryBuilder('t')
			->andWhere('t.isDeleted = false')
			->getQuery()
			->getResult();
	}


	//    /**
	//     * @return Task[] Returns an array of Task objects
	//     */
	//    public function findByExampleField($value): array
	//    {
	//        return $this->createQueryBuilder('t')
	//            ->andWhere('t.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->orderBy('t.id', 'ASC')
	//            ->setMaxResults(10)
	//            ->getQuery()
	//            ->getResult()
	//        ;
	//    }

	//    public function findOneBySomeField($value): ?Task
	//    {
	//        return $this->createQueryBuilder('t')
	//            ->andWhere('t.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->getQuery()
	//            ->getOneOrNullResult()
	//        ;
	//    }
}
