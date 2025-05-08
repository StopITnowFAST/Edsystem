<?php

namespace App\Repository;

use App\Entity\SubjectWikiFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SubjectWikiFile>
 */
class SubjectWikiFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubjectWikiFile::class);
    }

    //    /**
    //     * @return SubjectWikiFile[] Returns an array of SubjectWikiFile objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?SubjectWikiFile
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
