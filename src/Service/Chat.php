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

class Chat {
    CONST LAST_MESSAGE_COUNT = 20;
    CONST TIMEOUT = 20;
    CONST TEST = true;

    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {        
    }

    public function getTotalHistory() {
        $messages = $this->em->getRepository(Message::class)->getLastHistory(self::LAST_MESSAGE_COUNT);
        $redactedMessages = [];
        foreach (array_reverse($messages) as $message) {
            $redactedMessages[$message['user_id']][] = $message;
        }
        return $redactedMessages;
    }

    public function getNewMessages($ids) {
        // $this->logger->info('Сделан запрос для пользователя {group}', ['group' => $userId]);
        $messages = $this->em->getRepository(Message::class)->findNewMessages($ids);
        // $messagesArray = [];
        // if (!empty($messages)) {
        //     foreach ($messages as $key => $message) {
        //         $messagesArray[$key]['id'] = $message['id'];
        //         $messagesArray[$key]['type'] = $message['type'];
        //         $messagesArray[$key]['text'] = $message['text']; 
        //         $messagesArray[$key]['user_id'] = $message['user_id'];
        //         if ($message['type'] == 'incoming') {                
        //             $audioContent = (self::TEST) ? base64_encode(file_get_contents("/www/wwwroot/energomera_linkityan/botsmaker/public/test.mp3")) : $this->getSynthesizeSpeech($message['text']);
        //             $messagesArray[$key]['voice'] = [
        //                 'content' => $audioContent,
        //                 'content_type' => 'audio/mpeg'
        //             ];
        //         }
        //     }
        // }
        return $messages;
    }

    public function handleSmartMeterMessage($text) {
        // $this->addPureMessage('outcoming', 80,  $text, 'gpt');
        if (self::TEST) {
            // $this->addPureMessage('incoming', 80,  'Шмяк', 'gpt');
            return 1;
        }
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