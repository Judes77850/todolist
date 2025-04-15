<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, User::class);
	}

	public function loadUserByIdentifier(string $identifier): ?UserInterface
	{
		return $this->createQueryBuilder('u')
			->andWhere('u.username = :identifier')
			->setParameter('identifier', $identifier)
			->getQuery()
			->getOneOrNullResult();
	}

	//    /**
	//     * @return User[] Returns an array of User objects
	//     */
	//    public function findByExampleField($value): array
	//    {
	//        return $this->createQueryBuilder('u')
	//            ->andWhere('u.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->orderBy('u.id', 'ASC')
	//            ->setMaxResults(10)
	//            ->getQuery()
	//            ->getResult()
	//        ;
	//    }

	//    public function findOneBySomeField($value): ?User
	//    {
	//        return $this->createQueryBuilder('u')
	//            ->andWhere('u.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->getQuery()
	//            ->getOneOrNullResult()
	//        ;
	//    }
}
