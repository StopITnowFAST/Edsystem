<?php

namespace App\Repository;

use App\Entity\Test;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Test>
 */
class TestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Test::class);
    }

    public function getTableData($offset, $limit) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                `id`, 
                `name`, 
                `shuffle`, 
                `time`
            FROM `test`
            LIMIT $limit OFFSET $offset
        ";
        $resultSet = $conn->executeQuery($sql);        
        return $resultSet->fetchAllAssociative();
    }

    public function getTotalPages() {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT `id` FROM `test` 
        ";
        $resultSet = $conn->executeQuery($sql);        
        return $resultSet->rowCount();
    }

    public function getTestData($testId) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT * FROM `test` t
            JOIN `test_question` q on q.test_id = t.id
            JOIN `test_answer` a on a.question_id = q.id
            WHERE t.id = $testId
        ";
        $resultSet = $conn->executeQuery($sql);        
        return $resultSet->fetchAllAssociative();
    }

    public function getQuestinsCount($testId) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT count(q.id) as total_questions FROM `test_question` q WHERE q.test_id = $testId
        ";
        $resultSet = $conn->executeQuery($sql);        
        return $resultSet->fetchOne();
    }

    public function getQuestionIds($testId) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT id FROM `test_question` WHERE test_id = $testId
        ";
        $resultSet = $conn->executeQuery($sql);        
        return $resultSet->fetchAllAssociative();
    }

    public function countTotalPoints($testId) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT SUM(a.points) as total_points
            FROM test_question q
            JOIN test_answer a ON q.id = a.question_id
            WHERE q.test_id = :testId
        ";
        $resultSet = $conn->executeQuery($sql, ['testId' => $testId]);
        $result = $resultSet->fetchAssociative();
        return $result['total_points'] ?? 0;
    }
}
