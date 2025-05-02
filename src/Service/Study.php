<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Student;
use App\Entity\Test;

class Study
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    // Получить группу студента по ID пользователя
    public function getStudentGroup($userId) {
        $student = $this->em->getRepository(Student::class)->findOneBy(['user_id' => $userId]);
        return $student->getGroupId();
    }

    // Получить доступные тесты для пользователя
    public function getTestsForStudent($userId) {
        $student = $this->em->getRepository(Student::class)->findOneBy(['user_id' => $userId]);
        $groupId = $student->getGroupId();
        $conn = $this->em->getConnection();
        $sql = "
            SELECT t.*
            FROM `test` t
            WHERE t.student_groups LIKE '% $groupId,%' AND t.status = 1
        ";
        $resultSet = $conn->executeQuery($sql);
        $tests = $resultSet->fetchAllAssociative();
        return $tests;
    }

    // Получить количество доступных попыток для теста
    public function getAttemptsForTest($userId, $testId) {
        $totalAttempts = $this->em->getRepository(Test::class)->find($testId)->getAttempts();
        $conn = $this->em->getConnection();
        $sql = "
            SELECT DISTINCT tur.*
            FROM `test_user_result` tur
            WHERE tur.user_id = $userId
        ";
        $resultSet = $conn->executeQuery($sql);
        $attemtps = $resultSet->rowCount();
        return $totalAttempts - $attemtps;
    }
}