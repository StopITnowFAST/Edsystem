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
            SELECT st.user_id FROM `student` st WHERE st.group_id = $groupId
            UNION
            SELECT sb.user_id FROM `subject` sb WHERE sb.group_id = $groupId
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
            SELECT u.user_id, u.last_name, u.first_name, u.type,last_msg.text AS last_message_text,last_msg.date AS last_message_date
            FROM (
                SELECT st.user_id, st.last_name, st.first_name, 'student' as type
                FROM `student` st WHERE st.user_id IN $ids
                UNION
                SELECT sb.user_id, t.last_name, t.first_name, 'teacher' as type
                FROM `subject` sb
                JOIN `teacher` t ON t.user_id = sb.user_id
                WHERE sb.user_id IN $ids
            ) u
            LEFT JOIN (
                SELECT 
                    CASE 
                        WHEN m1.from_user_id = $userId THEN m1.to_user_id
                        ELSE m1.from_user_id
                    END AS partner_id,
                    m1.text,
                    m1.date
                FROM `message` m1
                WHERE (m1.from_user_id = $userId OR m1.to_user_id = $userId)
                AND m1.date = (
                    SELECT MAX(m2.date)
                    FROM `message` m2
                    WHERE (m2.from_user_id = m1.from_user_id AND m2.to_user_id = m1.to_user_id)
                    OR (m2.from_user_id = m1.to_user_id AND m2.to_user_id = m1.from_user_id)
                )
            ) last_msg ON last_msg.partner_id = u.user_id
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
        $ids = $this->getAvailableChatIds($userId, $groupId);
        $sql = "
            SELECT m.* FROM `message` m WHERE m.from_user_id IN $ids OR m.to_user_id IN $ids
        ";
        $resultSet = $conn->executeQuery($sql);
        $results = $resultSet->fetchAllAssociative();
        $messages = [];
        foreach ($results as $message) {
            $message['type'] = ($message['to_user_id'] == $userId) ? 'incoming' : 'outcoming';
            if($message['from_user_id'] != $userId) {
                $messages[$message['from_user_id']][] = $message;
            } else {
                $messages[$message['to_user_id']][] = $message;
            }
        }
        return $messages;
    }


    public function getTotalHistory() {
        $messages = $this->em->getRepository(Message::class)->getLastHistory(self::LAST_MESSAGE_COUNT);
        $redactedMessages = [];
        foreach (array_reverse($messages) as $message) {
            $redactedMessages[$message['user_id']][] = $message;
        }
        return $redactedMessages;
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
            SELECT m.*
            FROM `message` m
            WHERE ($whereClause)
            ORDER BY m.date ASC
        ";
        
        // 3. Выполняем запрос
        $results = $conn->executeQuery($sql, $params, $types)->fetchAllAssociative();

        $messages = [];
        foreach ($results as $message) {
            $message['type'] = ($message['to_user_id'] == $userId) ? 'incoming' : 'outcoming';
            if($message['from_user_id'] != $userId) {
                $messages[$message['from_user_id']][] = $message;
            } else {
                $messages[$message['to_user_id']][] = $message;
            }
        }
        return $messages;
    }

    public function handleSmartMeterMessage($text) {
        // $this->addPureMessage('outcoming', 80,  $text, 'gpt');
        // if (self::TEST) {
        //     // $this->addPureMessage('incoming', 80,  'Шмяк', 'gpt');
        //     return 1;
        // }
        try {
            $this->logger->info("Запрос к яндекс гпт для текста {text}", ['text' => $text]);
            $prompt = $this->getPrompt($text);
            $ch = curl_init($_ENV['YANDEX_GPT_API_DOMEN']);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type:application/json',
                    'Authorization: Api-Key ' . $_ENV['YANDEX_API_KEY']  
                ],
                CURLOPT_POSTFIELDS => $prompt,
                CURLOPT_VERBOSE => false, 
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
            $json = json_decode($response, true);
            if (isset($json['result'])) {
                // $this->addPureMessage('incoming', 80,  $json['result']['alternatives'][0]['message']['text'], 'gpt');
            }
        } catch (\Throwable $e) {            
            $this->logger->info("Ошибка во время генерации ответа GPT - {error}", ['error' => $e]);
            // $this->addPureMessage('incoming', 80,  'Ошибка во время генерации ответа', 'gpt');
        }
    }

    public function getPrompt($text): bool|string {
        $prompt = [
            'modelUri' => $_ENV['YANDEX_GPT_MODEL_DOMEN'],
            'completionOptions' => [
                'stream' => false,
                'temperature' => 1,
                'maxTokens' => '2000',
                'reasoningOptions' => [
                    'mode' => 'DISABLED'
                ]
            ],
            'messages' => [
                [
                    'role' => 'system',
                    'text' => 'Ответь на сообщение как чат бот'
                ],
                [
                    'role' => 'user',
                    'text' => $text
                ]
            ]
        ];
        return json_encode($prompt, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    public function getLastMessages($groupId, $amount) {
        $messages = $this->em->getRepository(Message::class)->getLastMessages($groupId, $amount);
        return $messages;
    }    

    public function getSynthesizeSpeech(string $text) {
        try {
            $this->logger->info("Запрос к яндекс озвучки для текста {text}", ['text' => $text]);
            $ch = curl_init($_ENV['YANDEX_VOICE_DOMEN']);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type:text/plain'],
                CURLOPT_POSTFIELDS => json_encode(['text' => $text]),
                CURLOPT_VERBOSE => true, 
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
            $json = json_decode($response, true);
            $this->logger->info("Получены данные от озвучки - {json}", ['json' => $json]);
            $audioData = '';
            if (is_array($json['data'])) {
                $this->logger->info("массив");
                foreach ($json['data'] as $chunk) {
                    $audioData .= base64_decode($chunk);
                }
            } else {
                $this->logger->info("не массив");
                $audioData = base64_decode($json['data']);
            }
            $this->logger->info("ААААА, деньги уходят");
            return base64_encode($audioData);
        } catch(\Throwable $e)  {
            $this->logger->info("Ошибка во время озвучки - {error}", ['error' => $e]);
            return null;
        }
    }
}