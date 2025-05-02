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
            WHERE q.test_id = :testId AND a.is_correct = 1
        ";
        $resultSet = $conn->executeQuery($sql, ['testId' => $testId]);
        $result = $resultSet->fetchAssociative();
        return $result['total_points'] ?? 0;
    }

    public function getCorrectAnswers($testId) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT ta.id, ta.is_correct, ta.points 
            FROM `test` t
            JOIN `test_question` tq on t.id = tq.test_id
            JOIN `test_answer` ta on tq.id = ta.question_id
            WHERE t.id = $testId
        ";
        $resultSet = $conn->executeQuery($sql);
        $results = $resultSet->fetchAllAssociative();
        $formattedAnswers = [];
        foreach ($results as $answer) {
            $formattedAnswers[$answer['id']] = $answer;
        }
        return $formattedAnswers;
    }

    public function getQuestionsStructure(int $testId): array {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT 
                q.id as question_id, 
                q.text as question_text,
                a.id as answer_id,
                a.text as answer_text,
                a.is_correct as is_correct,
                a.points as points
            FROM test_question q
            JOIN test_answer a ON q.id = a.question_id
            WHERE q.test_id = ?
            ORDER BY q.id ASC, a.id ASC
        ';
        
        $stmt = $conn->executeQuery($sql, [$testId]);
        $rawResults = $stmt->fetchAllAssociative();
        
        $structuredResults = [];
        $currentQuestionId = null;
        
        foreach ($rawResults as $row) {
            if ($currentQuestionId !== $row['question_id']) {
                $structuredResults[] = [
                    'id' => $row['question_id'],
                    'text' => $row['question_text'],
                    'answers' => []
                ];
                $currentQuestionId = $row['question_id'];
            }
            
            $structuredResults[count($structuredResults) - 1]['answers'][] = [
                'id' => $row['answer_id'],
                'text' => $row['answer_text'],
                'isCorrect' => (bool)$row['is_correct'],
                'points' => (int)$row['points']
            ];
        }
        
        return $structuredResults;
    }

    public function getUserAnswerIds($userId, $testId, $attempt) {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = "
            SELECT tua.answer_id FROM test_user_answer tua
            JOIN test_answer ta on ta.id = tua.answer_id
            JOIN test_question tq ON tq.id = ta.question_id
            JOIN test t ON t.id = tq.test_id
            WHERE t.id = $testId AND tua.user_id = $userId AND tua.attempt = $attempt
        ";
        
        $resultSet = $conn->executeQuery($sql);
        $results = $resultSet->fetchFirstColumn();
        return $results;
    }

    public function getLastAnsweredQuestion($userId, $testId, $attempt) {
        $conn = $this->getEntityManager()->getConnection();        
        $sql = "
            SELECT count(*) FROM `test_user_answer` tua
            JOIN `test_question` tq ON tua.question_id = tq.id
            WHERE tq.test_id = $testId AND tua.user_id = $userId AND tua.attempt = $attempt
        ";        
        $resultSet = $conn->executeQuery($sql);
        return $resultSet->fetchOne();
    }
}
