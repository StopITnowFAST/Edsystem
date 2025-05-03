<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Service\Chat;
use App\Service\ApiTg;
use App\Service\UserService;
use App\Entity\Message;
use App\Entity\UserCard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;

class ChatController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em, 
        private Chat $chat,
        private LoggerInterface $logger,
    ) {        
    }

    // Получение обновления для диалогов
    #[Route(path: '/api/poll-messages', name:'poll_messages')]
    public function pollMessages() {
        session_write_close();
        $ids = json_decode(file_get_contents("php://input"), true);        
        $startTime = time();        
        while (time() - $startTime < $this->chat::TIMEOUT) {
            $newMessages = $this->chat->getNewMessages($ids);            
            if (!empty($newMessages)) {
                return $this->json([
                    'status' => 'success',
                    'newMessages' => $newMessages,
                ]);
            }
            sleep(2);
            if (connection_aborted()) {
                break;
            }
        }        
        return $this->json([
            'status' => 'timeout',
            'newMessages' => [],
        ]);
    }

    // // Отправка сообщений пользователям телеграмма
    // #[Route(path: '/api/send-message', name:'send_message')]
    // public function sendMessage() {
    //     $postJsonArray = json_decode(file_get_contents("php://input"), true);
    //     $text = $postJsonArray['text'];
    //     $groupId = $postJsonArray['id'];
              
    //     return new Response('ok');
    // }

    // // Отправка сообщения яндекс гпт
    // #[Route(path: '/api/smart-meter/send-message', name:'send_smart_message')]
    // public function handleSmartMeterMessage() {
    //     $postJsonArray = json_decode(file_get_contents("php://input"), true);
    //     $text = $postJsonArray['text'];

    //     $this->chat->handleSmartMeterMessage($text);

    //     return new Response('ok');
    // }

    // // Функция для получения информации о пользователе по его Id
    // #[Route(path: '/api/get/chats', name:'api_get_chats', methods: ['POST'])]
    // public function getChats() {
    //     // $platform = json_decode(file_get_contents("php://input"), true)['platform'];
    //     // $user = 2;
    //     // return $this->json([
    //     //     'username' => $user[0]['tg_nickname'],
    //     // ]);
    // }

    // // Функция для тестов
    // #[Route(path: '/api/tes', name:'test_api')]
    // public function tests() {
    //     $ids = [
    //         '78' => '197',
    //         '79' => '155',
    //     ];
    //     var_dump($this->chat->getNewMessages($ids));die;
    // }
}
