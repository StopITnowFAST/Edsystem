<?php

namespace App\Repository;

use App\Entity\Group;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Group>
 */
class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    public function getTableData($offset, $limit) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                `id`, 
                `name`, 
                `code`, 
                `year`, 
                `semester`,
                `course`
            FROM `group`
            LIMIT $limit OFFSET $offset
        ";
        $resultSet = $conn->executeQuery($sql);        
        return $resultSet->fetchAllAssociative();
    }

    public function getTotalPages() {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT `id` FROM `group` 
        ";
        $resultSet = $conn->executeQuery($sql);        
        return $resultSet->rowCount();
    }
}
