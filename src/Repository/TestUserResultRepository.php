<?php

namespace App\Repository;

use App\Entity\TestUserResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TestUserResult>
 */
class TestUserResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestUserResult::class);
    }

    // Получить лучшие результаты прохождения теста
    public function getBestGrade($userId, $testId) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT * FROM `test_user_result` WHERE grade = (SELECT MAX(grade) FROM test_user_result WHERE user_id = $userId and test_id = $testId);
        ";
        $resultSet = $conn->executeQuery($sql);
        $grade = $resultSet->fetchAllAssociative();
        return $grade;
    }
}
