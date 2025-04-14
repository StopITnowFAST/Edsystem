<?php

namespace App\Repository;

use App\Entity\Student;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Student>
 */
class StudentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Student::class);
    }

    public function getTableData($offset, $limit) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
            `student`.`id`, 
            `student`.`first_name`, 
            `student`.`middle_name`, 
            `student`.`last_name`,
            `group`.`code`
            FROM `student`
            LEFT JOIN `group` ON `student`.`group_id` = `group`.`id`
            LIMIT $limit OFFSET $offset
        ";
        $resultSet = $conn->executeQuery($sql);        
        return $resultSet->fetchAllAssociative();
    }

    public function getTotalPages() {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT `id` FROM `student` 
        ";
        $resultSet = $conn->executeQuery($sql);        
        return $resultSet->rowCount();
    }
}
