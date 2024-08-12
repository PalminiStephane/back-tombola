<?php

namespace App\Repository;

use App\Entity\Draws;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Draws>
 *
 * @method Draws|null find($id, $lockMode = null, $lockVersion = null)
 * @method Draws|null findOneBy(array $criteria, array $orderBy = null)
 * @method Draws[]    findAll()
 * @method Draws[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DrawsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Draws::class);
    }

    public function findByQueryBuilder(array $criteria, array $orderBy = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('d');
        $qb->andWhere('d.status = :status')->setParameter('status', $criteria['status']);

        if ($orderBy) {
            foreach ($orderBy as $field => $order) {
                $qb->addOrderBy('d.' . $field, $order);
            }
        }

        return $qb;
    }

    public function add(Draws $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Draws $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return Draws[] Returns an array of Draws objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Draws
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
