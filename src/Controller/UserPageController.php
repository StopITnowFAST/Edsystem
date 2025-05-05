<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Service\Study;
use App\Service\Chat;
use App\Service\File;
use App\Entity\Message;
use App\Entity\Test;
use App\Entity\Group;
use App\Entity\File as FileEntity;
use App\Entity\TestUserResult;
use App\Entity\UserCard;
use App\Entity\Schedule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class UserPageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em, 
        private LoggerInterface $logger,
        private Study $study,
        private Chat $chat,
        private File $fileService,
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
        $accountType = $this->study->getUserType($userId);

        if ($accountType == 'hollow') 
            return $this->render('user/main.html.twig', ['userType' => $accountType]);

        $globalMessageArray = $this->chat->getAllMessages($userId);
        $globalChatArray = $this->chat->getAvailableChats($userId);
        if ($accountType == 'student') {
            $groupId = $this->study->getStudentGroup($userId);
            $group = $this->em->getRepository(Group::class)->find($groupId);
            $startfirst = $group->getEdStartsFirst();
            $startsecond = $group->getEdStartsSecond();
        }         

        return $this->render('user/main.html.twig', [
            'userId' => $userId,
            'section' => $section,
            'globalMessageArray' => $globalMessageArray,
            'globalChatArray' => $globalChatArray,
            'startfirst' => $startfirst ?? date("Y") . '09-01',
            'startsecond' => $startsecond ?? date("Y") . '02-06',
            'userType' => $accountType,
        ]);        
    }

    // Получение данных для теста
    #[Route(path: '/request/get/user/tests/{userId}', name:'get_tests')]
    public function getTests($userId) {
        if ($this->getUser()->getId() != $userId) {
            return new Response('Permission Error', 403);
        }
        $accountType = $this->study->getUserType($userId);
        if ($accountType == 'student') {
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
        }        

        return $this->json([
            'data' => $formattedTests ?? null,
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
    
    // Получение данных для расписания
    #[Route('/request/get/user/schedule/{userId}', name: 'get_schedule')]
    public function getSchedule($userId) {

        // if ($this->getUser()->getId() != $userId) {
        //     return new Response('Permission Error', 403);
        // }

        $accountType = $this->study->getUserType($userId);
        if ($accountType == 'student') {
            $groupId = $this->study->getStudentGroup($userId);    
            $schedule = $this->em->getRepository(Schedule::class)->findScheduleByGroupId($groupId);
        } else if ($accountType == 'teacher') {
            $schedule = $this->em->getRepository(Schedule::class)->findTeacherSchedule($userId);
        }
        
        return $this->json([
            'schedule' => $schedule
        ]);
    }
    
    // Получение данных для предметов
    #[Route('/request/get/user/subjects/{userId}', name: 'get_subjects')]
    public function getSubjects($userId) {

        if ($this->getUser()->getId() != $userId) {
            return new Response('Permission Error', 403);
        }

        $groupId = $this->study->getStudentGroup($userId);
        $schedule = $this->study->getAllSubjectDates($groupId);
        
        return $this->json([
            'data' => $schedule
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
    public function sendMessage(
        $userId,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        // Проверка авторизации
        if ($this->getUser()->getId() != $userId) {
            return new JsonResponse(['error' => 'Permission denied'], 403);
        }
    
        // Обработка multipart/form-data (с файлами)
        if (str_starts_with($request->headers->get('Content-Type'), 'multipart/form-data')) {
            return $this->handleMultipartRequest($request, $userId, $em, $this->fileService);
        }
    
        // Обработка JSON (для обратной совместимости)
        return $this->handleJsonRequest($request, $userId, $em);
    }

    private function handleMultipartRequest(
        Request $request,
        int $userId,
        EntityManagerInterface $em,
    ): Response {
        $message = new Message();
        $message->setFromUserId($userId);
        $message->setToUserId($request->request->get('to_id'));
        $message->setText($request->request->get('text', ''));
        $message->setDate(time());
        $message->setRead(0);
    
        // Обработка файлов
        $uploadedFiles = $request->files->get('files', []);
        if (count($uploadedFiles) > 0) {
            $firstFile = is_array($uploadedFiles) ? $uploadedFiles[0] : $uploadedFiles;
            $fileEntity = $this->fileService->handleUploadedFile($firstFile, $userId);
            $message->setFileId($fileEntity->getId());
        }
    
        $em->persist($message);
        $em->flush();
    
        return new JsonResponse(['status' => 'success', 'message_id' => $message->getId()]);
    }

    private function handleJsonRequest(Request $request, int $userId, EntityManagerInterface $em): Response 
    {
        $data = json_decode($request->getContent(), true);
        
        $message = new Message();
        $message->setFromUserId($userId);
        $message->setToUserId($data['to_id']);
        $message->setText($data['text']);
        $message->setDate(time());
        $message->setRead(0);
    
        $em->persist($message);
        $em->flush();
    
        return new JsonResponse(['status' => 'success', 'message_id' => $message->getId()]);
    }

    // Получение обновлений
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

    // Пометка всех сообщений диалога прочитанными
    #[Route(path: '/request/mark-messages-read/chat/{userId}', name: 'mark_messages_read')]
    public function markMessagesAsRead($userId): Response {
        
        if (!$this->getUser()) {
            return new Response('Unauthorized', 401);
        }

        $this->em->getRepository(Message::class)->markMessagesAsRead(
            $userId,
            $this->getUser()->getId()
        );

        return new Response('OK', 200);
    }

    #[Route(path: '/download/user-file/{realFileName}', name: 'download_user_file')]
    public function downloadUserFile($realFileName) {
        $fileEntity = $this->em->getRepository(FileEntity::class)->findOneBy(['real_file_name' => $realFileName]);

        if (!$fileEntity) {
            throw $this->createNotFoundException('Файл не найден');
        }

        $filePath = $_ENV['USER_FILE_PATH'] . '/' . $realFileName;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Файл не найден на сервере');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileEntity->getFileName().'.'.$fileEntity->getExtension()
        );

        return $response;
    }
    
    // Для тестов
    #[Route('/testss', name: 'test')]
    public function test() {
        $userId = $this->getUser()->getId();
        $groupId = $this->study->getStudentGroup($userId);
        $ids = [
            2 => 3,
            6 => 4,
        ];
        var_dump($this->study->getAllSubjectDates($groupId)); die;
    }
}
