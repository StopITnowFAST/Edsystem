<?php

namespace App\Repository;

use App\Entity\SubjectWiki;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SubjectWiki>
 */
class SubjectWikiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubjectWiki::class);
    }
    
    public function findAllWiki($userId) {        
        $conn = $this->em->getConnection();
        $sql = "
            
        ";
        $resultSet = $conn->executeQuery($sql);
        return  $resultSet->fetchAllAssociative();
    }
}
