<?php

namespace App\Service;

use App\Entity\Categories;
use App\Controller\ReportController;
use App\Entity\MessageHistory;
use App\Entity\Message;
use App\Entity\SmartMeterMessage;
use App\Entity\Faq;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\User;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use App\Service\UserService;
use App\Service\Study;

class Chat {
    CONST LAST_MESSAGE_COUNT = 20;
    CONST TIMEOUT = 20;

    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private Study $study,
    ) {        
    }

    // Функция возвращает id которым может написать пользователь (В формате для IN SQL)
    public function getAvailableChatIds($userId, $groupId) {
        $conn = $this->em->getConnection();
        $sql = "
            SELECT st.user_id FROM `student` st INNER JOIN `user` u on st.user_id = u.id WHERE st.group_id = $groupId
            UNION
            SELECT sb.user_id FROM `schedule_subject` sb INNER JOIN `user` u on sb.user_id = u.id WHERE sb.group_id = $groupId
        ";
        $resultSet = $conn->executeQuery($sql);
        $results = $resultSet->fetchFirstColumn();

        
        $inString = '(' . implode(',', $results) . ')';
        return $inString;
    }

    // Функция возвращает доступные чаты для пользователя
    public function getAvailableChats($userId) {
        $groupId = $this->study->getStudentGroup($userId);        
        $conn = $this->em->getConnection();
        $ids = $this->getAvailableChatIds($userId, $groupId);

        // Достаю данные для первичной загрузки 
        $sql = "
            SELECT 
                u.user_id, 
                u.last_name, 
                u.first_name, 
                u.type, 
                last_msg.text AS last_message_text, 
                last_msg.date AS last_message_date,
                CONCAT('/download/user-file/', f.real_file_name) AS filelink,
                f.file_name
            FROM (
                SELECT st.user_id, st.last_name, st.first_name, 'student' as type
                FROM `student` st WHERE st.user_id IN $ids
                UNION
                SELECT sb.user_id, t.last_name, t.first_name, 'teacher' as type
                FROM `schedule_subject` sb
                JOIN `teacher` t ON t.user_id = sb.user_id
                WHERE sb.user_id IN $ids
            ) u
            LEFT JOIN (
                SELECT 
                    partner_id, 
                    text, 
                    date,
                    file_id
                FROM (
                    SELECT 
                        CASE 
                            WHEN from_user_id = $userId THEN to_user_id
                            ELSE from_user_id
                        END AS partner_id,
                        text,
                        date,
                        file_id,
                        ROW_NUMBER() OVER (PARTITION BY 
                            CASE 
                                WHEN from_user_id = $userId THEN to_user_id
                                ELSE from_user_id
                            END 
                            ORDER BY date DESC
                        ) AS rn
                    FROM `message`
                    WHERE (from_user_id = $userId OR to_user_id = $userId)
                ) ranked
                WHERE rn = 1
            ) last_msg ON last_msg.partner_id = u.user_id
            LEFT JOIN `file` f ON f.id = last_msg.file_id
            ORDER BY 
                last_msg.date DESC,
                u.last_name ASC, 
                u.first_name ASC
        ";
        $resultSet = $conn->executeQuery($sql);
        $results = $resultSet->fetchAllAssociative();
        $chats = [];
        foreach ($results as $key => $chat) {
            $chats[$chat['type']][$chat['user_id']] = $chat;
        }
        return $chats;
    }

    public function getAllMessages($userId) {
        $groupId = $this->study->getStudentGroup($userId);        
        $conn = $this->em->getConnection();
        $sql = "
            SELECT m.*, f.real_file_name, f.file_name FROM `message` m 
            LEFT JOIN `file` f on f.id = m.file_id
            WHERE m.from_user_id = $userId OR m.to_user_id = $userId
        ";
        $resultSet = $conn->executeQuery($sql);
        $results = $resultSet->fetchAllAssociative();
        $messages = [];
        foreach ($results as $message) {
            $message['type'] = ($message['to_user_id'] == $userId) ? 'incoming' : 'outcoming';
            if ($message['file_id'] != null) {
                $message['filelink'] = '/download/user-file/' . $message['real_file_name'];
            }
            if($message['from_user_id'] != $userId) {
                $messages[$message['from_user_id']][] = $message;
            } else {
                $messages[$message['to_user_id']][] = $message;
            }
        }
        return $messages;
    }
    
    public function getNewMessages(array $lastMessages, int $userId) 
    {
        $conn = $this->em->getConnection();
        
        // 1. Создаем SQL для условий
        $conditions = [];
        $params = ['user_id' => $userId];
        $types = ['user_id' => \Doctrine\DBAL\ParameterType::INTEGER];
        
        foreach ($lastMessages as $partnerId => $lastMessageId) {
            $conditions[] = "(m.from_user_id = ? AND m.to_user_id = ? AND m.id > ?) OR (m.to_user_id = ? AND m.from_user_id = ? AND m.id > ?)";
            $params[] = $userId;
            $params[] = $partnerId;
            $params[] = $lastMessageId;
            $params[] = $userId;
            $params[] = $partnerId;
            $params[] = $lastMessageId;
            array_push($types, ...array_fill(0, 6, \Doctrine\DBAL\ParameterType::INTEGER));
        }
        
        if (empty($conditions)) {
            return [];
        }
        
        $whereClause = implode(' OR ', $conditions);
        
        // 2. Формируем SQL
        $sql = "
            SELECT m.*, f.real_file_name, f.file_name
            FROM `message` m
            LEFT JOIN `file` f on f.id = m.file_id
            WHERE ($whereClause)
            ORDER BY m.date ASC
        ";
        
        // 3. Выполняем запрос
        $results = $conn->executeQuery($sql, $params, $types)->fetchAllAssociative();

        $messages = [];
        foreach ($results as $message) {
            $message['type'] = ($message['to_user_id'] == $userId) ? 'incoming' : 'outcoming';
            if ($message['file_id'] != null) {
                $message['filelink'] = '/download/user-file/' . $message['real_file_name'];
            }
            if($message['from_user_id'] != $userId) {
                $messages[$message['from_user_id']][] = $message;
            } else {
                $messages[$message['to_user_id']][] = $message;
            }
        }
        return $messages;
    }    
}