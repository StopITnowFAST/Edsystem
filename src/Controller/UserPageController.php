<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Service\Study;
use App\Service\Chat;
use App\Entity\Message;
use App\Entity\Test;
use App\Entity\TestUserResult;
use App\Entity\UserCard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class UserPageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em, 
        private LoggerInterface $logger,
        private Study $study,
        private Chat $chat,
    ) {
    }
    
    // Домашняя страница
    #[Route('/', name: 'homepage')]
    public function homepage() {
        return $this->redirectToRoute('account');
    }

    // Отображение самой страницы
    #[Route(path: '/account/{section}', name:'account')]
    public function account($section = 'schedule') {
        $userId = $this->getUser()->getId();
        $globalMessageArray = $this->chat->getAllMessages($userId);
        $globalChatArray = $this->chat->getAvailableChats($userId);

        return $this->render('user/main.html.twig', [
            'userId' => $userId,
            'section' => $section,
            'globalMessageArray' => $globalMessageArray,
            'globalChatArray' => $globalChatArray,
        ]);
    }

    // Получение данных для теста
    #[Route(path: '/request/get/user/tests/{userId}', name:'get_tests')]
    public function getTests($userId) {
        if ($this->getUser()->getId() != $userId) {
            return new Response('Permission Error', 403);
        }
        $tests = $this->study->getTestsForStudent($userId);
        $formattedTests = [];
        foreach ($tests as $key => $test) {
            $bestGrade = $this->em->getRepository(TestUserResult::class)->getBestGrade($userId, $test['id']);
            $formattedTests[$key]['id'] = $test['id'];
            $formattedTests[$key]['title'] = $test['name'];
            $formattedTests[$key]['status'] = "Не разработан";
            $formattedTests[$key]['grade'] = $bestGrade[0]['grade'] ?? '-';
            $formattedTests[$key]['attemptsLeft'] = $this->study->getAttemptsForTest($userId, $test['id']);
            $formattedTests[$key]['questionsCount'] = $this->em->getRepository(Test::class)->getQuestinsCount($test['id']);
            $formattedTests[$key]['timeLimit'] = $test['time'];
        }

        return $this->json([
            'data' => $formattedTests,
        ]);
    }

    // Получение данных для чатов
    #[Route(path: '/request/get/user/chat/{userId}', name:'get_chats')]
    public function getChats($userId) {
        if ($this->getUser()->getId() != $userId) {
            return new Response('Permission Error', 403);
        }
        $chats = $this->chat->getAvailableChats($userId);

        return $this->json([
            'data' => $chats,
        ]);
    }

    // Получение сообщений для чатов
    #[Route(path: '/request/get/user/messages/chat/{userId}', name:'get_chats_messages')]
    public function getChatsMessages($userId) {
        if ($this->getUser()->getId() != $userId) {
            return new Response('Permission Error', 403);
        }
        $messages = $this->chat->getAllMessages($userId);

        return $this->json([
            'data' => $messages,
        ]);
    }

    // Отправление сообщения
    #[Route(path: '/request/send/user/message/chat/{userId}', name:'send_message')]
    public function sendMessage($userId) {
        if ($this->getUser()->getId() != $userId) {
            return new Response('Permission Error', 403);
        }
        $postJsonArray = json_decode(file_get_contents("php://input"), true);

        $message = new Message();
        $message->setFromUserId($userId);
        $message->setToUserId($postJsonArray['to_id']);
        $message->setText($postJsonArray['text']);
        $message->setDate(time());

        $this->em->persist($message);
        $this->em->flush($message);

        return new Response('ok', 200);
    }

    #[Route(path: '/request/get/user/updates/chat/{userId}', name:'get_updates')]
    public function getUpdates($userId) {
        $ids = json_decode(file_get_contents("php://input"), true);        
        $startTime = time();  
        
        while (time() - $startTime < $this->chat::TIMEOUT) {
            $newMessages = $this->chat->getNewMessages($ids, $userId);      
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
    
    // Для тестов
    #[Route('/testss', name: 'test')]
    public function test() {
        $userId = $this->getUser()->getId();
        $ids = [
            2 => 3,
            6 => 4,
        ];
        var_dump($this->chat->getNewMessages($ids, $userId)); die;
    }    
}
