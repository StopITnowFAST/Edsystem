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
use App\Entity\Teacher;
use App\Entity\SubjectWiki;
use App\Entity\Group;
use App\Entity\File as FileEntity;
use App\Entity\TestUserResult;
use App\Entity\UserCard;
use App\Entity\SubjectWikiFile;
use App\Entity\ScheduleSubject;
use App\Entity\Schedule;
use App\Entity\Student;
use App\Entity\ScheduleLessonType;
use App\Entity\ScheduleTime;
use App\Entity\Grade;
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

        if ($accountType == 'hollow') {
            return $this->render('user/main.html.twig', ['userType' => $accountType]);
        } else if ($accountType == 'student') {
            $group = $this->study->getStudentGroup($userId);
        }

        if (isset($group) && $group == 0)
            return $this->render('user/main.html.twig', [
                'userType' => $accountType,
                'break' => true,
            ]);

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

        if ($this->getUser()->getId() != $userId) {
            return new Response('Permission Error', 403);
        }

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

        $accountType = $this->study->getUserType($userId);
        if ($accountType == 'student') {
            $groupId = $this->study->getStudentGroup($userId);
            $subjects = $this->study->getAllSubjectDates($groupId, $userId);
        } else if ($accountType == 'teacher') {
            $subjects = $this->study->getTeacherSubjectsDates($userId);
            
            foreach ($subjects as &$subjectData) {
                foreach ($subjectData as &$lesson) {

                    $conn = $this->em->getConnection();
                    $sql = "
                        SELECT EXISTS(
                            SELECT 1 FROM grade 
                            WHERE date = :date 
                            AND type = :type 
                            AND time = :time
                            LIMIT 1
                        ) AS has_grades
                    ";
                    $stmt = $conn->prepare($sql);
                    $result = $stmt->executeQuery([
                        'date' => $lesson['date'],
                        'type' => $this->em->getRepository(ScheduleLessonType::class)->findOneBy(['name' => $lesson['type']])->getId(),
                        'time' => mb_substr($lesson['time'], 0, 1)
                    ]);
                    $hasGrades = (bool) $result->fetchOne();
                    
                    $lesson['hasGrades'] = $hasGrades > 0;
                }
            }
        }
        
        return $this->json([
            'data' => $subjects
        ]);
    }

    #[Route('/request/create/wiki/entry', name: 'create_wiki')]
    public function createWikiEntry(Request $request): Response {
        $user = $this->getUser();
        $userId = $user->getId();
        $accountType = $this->study->getUserType($userId);
        
        // Проверяем, что пользователь авторизован и является преподавателем
        if ($accountType != 'teacher') {
            return $this->json([
                'status' => 'error',
                'message' => 'Только преподаватели могут создавать записи'
            ], Response::HTTP_FORBIDDEN);
        }

        // Получаем и валидируем данные
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['text']) || empty(trim($data['text']))) {
            return $this->json([
                'status' => 'error',
                'message' => 'Текст записи не может быть пустым'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['subject_id']) || !is_numeric($data['subject_id'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Неверный идентификатор предмета'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Находим предмет
        $subject = $this->em->getRepository(ScheduleSubject::class)->find($data['subject_id']);

        if (!$subject) {
            return $this->json([
                'status' => 'error',
                'message' => 'Предмет не найден'
            ], Response::HTTP_NOT_FOUND);
        }

        // Создаем новую запись
        $wikiEntry = new SubjectWiki();
        $wikiEntry->setText(trim($data['text']));
        $wikiEntry->setCanUploadFile($data['can_upload'] ?? false);
        $wikiEntry->setSubjectId($subject->getId());
        
        // Сохраняем в базу
        $this->em->persist($wikiEntry);
        $this->em->flush();

        // Возвращаем успешный ответ с ID созданной записи
        return $this->json([
            'status' => 'success',
            'message' => 'Запись успешно создана',
            'entry_id' => $wikiEntry->getId()
        ]);
    }

    
    // Получение данных для вики
    #[Route('/request/get/user/wiki/{userId}', name: 'get_wiki')]
    public function getWiki($userId) {

        // Получаем все предметы, к которым у пользователя есть доступ
        $subjects = $this->study->getSubjectsForUser($userId);
        $accountType = $this->study->getUserType($userId);
        
        $data = [];
        
        foreach ($subjects as $subject) {
            $wikiEntries = $this->em
                ->getRepository(SubjectWiki::class)
                ->findBy(['subject_id' => $subject['id']], ['created_at' => 'DESC']);
            
            $subjectData = [];
            foreach ($wikiEntries as $entry) {
                $teacherFiles = $this->em->getRepository(SubjectWikiFile::class)->findBy([
                    'wiki_id' => $entry->getId(),
                    'file_type' => 'teacher',
                ]);
                
                $studentFiles = $this->em->getRepository(SubjectWikiFile::class)->getStudentFiles($userId, $entry->getId());

                $studentAnswers = ($accountType == 'teacher') 
                    ? $this->em->getRepository(SubjectWikiFile::class)->findBy([
                        'wiki_id' => $entry->getId(),
                        'file_type' => 'student',
                    ])
                    : [];
                
                $entryData = [
                    'id' => $entry->getId(),
                    'text' => $entry->getText(),
                    'can_upload_file' => $entry->isCanUploadFile(),
                    'created_at' => gmdate("Y.m.d", $entry->getCreatedAt()),
                    'teacher_files' => [],
                    'student_files' => []
                ];
                
                foreach ($teacherFiles as $file) {
                    $fileEntity = $this->em->getRepository(FileEntity::class)->find($file->getFileId());
                    $entryData['teacher_files'][] = [
                        'id' => $fileEntity->getId(),
                        'name' => $fileEntity->getFileName(),
                        'url' => '/download/user-file/' . $fileEntity->getRealFileName(),
                    ];
                }
                
                foreach ($studentFiles as $file) {
                    $fileEntity = $this->em->getRepository(FileEntity::class)->find($file['file_id']);
                    $student = $this->em->getRepository(Student::class)->findOneBy(['user_id' => $fileEntity->getCreatedBy()]);
                    $studentName = $student->getLastName() . ' ' . $student->getFirstName();
                    $entryData['student_files'][] = [
                        'id' => $fileEntity->getId(),
                        'name' => $fileEntity->getFileName(),
                        'url' => '/download/user-file/' . $fileEntity->getRealFileName(),
                        'student_name' => $studentName,
                    ];
                }

                foreach ($studentAnswers as $file) {
                    $fileEntity = $this->em->getRepository(FileEntity::class)->find($file->getFileId());
                    $student = $this->em->getRepository(Student::class)->findOneBy(['user_id' => $fileEntity->getCreatedBy()]);
                    $studentName = $student->getLastName() . ' ' . $student->getFirstName();
                    $entryData['student_answers'][] = [
                        'id' => $fileEntity->getId(),
                        'name' => $fileEntity->getFileName(),
                        'url' => '/download/user-file/' . $fileEntity->getRealFileName(),
                        'student_name' => $studentName,
                    ];
                }
                
                $subjectData[] = $entryData;
            }
            
            $data[$subject['name']] = $subjectData;
        }
        
        // Добавляем ID предметов в ответ
        $subjectIds = [];
        foreach ($subjects as $subject) {
            $subjectIds[$subject['name']] = $subject['id'];
        }
        
        return $this->json([
            'status' => 'success', 
            'data' => $data,
            'subjects' => $subjectIds
        ]);
    }


    #[Route('/request/delete/wiki/entry', name: 'delete_wiki')]
    public function deleteWIki(Request $request) {
        $data = json_decode($request->getContent(), true);
        $entryId = $data['entry_id'];
        $entry = $this->em->getRepository(SubjectWiki::class)->find($entryId);
        $this->em->remove($entry);
        $this->em->flush();
        return $this->json([
            'status' => 'success',
        ]);
    }


    #[Route('/request/upload/wiki/file', name: 'upload_file')]
    public function uploadFile(Request $request) {
        $userId = $this->getUser()->getId();
        $data = json_decode($request->getContent(), true);
        $uploadedFiles = $request->files->get('files', []);
        foreach ($uploadedFiles as $file) {
            $filedata = var_export($file, true);
            $this->logger->info($filedata);
            $fileEntity = $this->fileService->handleUploadedFile($file, $userId);
            $wikiFile = new SubjectWikiFile;
            $wikiFile->setFileId($fileEntity->getId());
            $wikiFile->setFileType($request->request->get('file_type'));
            $wikiFile->setWikiId($request->request->get('entry_id'));
            $this->em->persist($wikiFile);
        }
        $this->em->flush();
        return new Response('ok');
    }

    #[Route('/request/delete/wiki/file', name: 'delete_file')]
    public function deleteAnswerFile(Request $request) {
        $data = json_decode($request->getContent(), true);
        $wikiFile = $this->em->getRepository(SubjectWikiFile::class)->findOneBy(['file_id' => $data['file_id']]);
        $this->em->remove($wikiFile);
        $this->em->flush();
        return $this->json([
            'status' => 'success',
        ]);
    }

    // Получение данных для оценок
    #[Route('/request/get/user/students', name: 'get_subjects_students')]
    public function getStudentsForLesson(Request $request) {
        $data = json_decode($request->getContent(), true);
        $userId = $this->getUser()->getId();
        
        // Валидация
        if (!isset($data['subject'], $data['date'], $data['type'], $data['time'])) {
            return new JsonResponse(['error' => 'Недостаточно данных'], 400);
        }
                
        try {
            // 1. Находим предмет по имени
            $subject = $this->em->getRepository(ScheduleSubject::class)
                ->findOneBy(['name' => $data['subject']]);
            
            if (!$subject) {
                return new JsonResponse(['error' => 'Предмет не найден'], 404);
            }
            
            // 2. Получаем группу из расписания преподавателя
            $schedule = $this->em->getRepository(Schedule::class)
                ->findOneBy([
                    'user_id' => $userId,
                    'schedule_subject_id' => $subject->getId(),
                ]);
            
            if (!$schedule) {
                return new JsonResponse(['error' => 'Расписание не найдено'], 404);
            }
            
            $groupId = $schedule->getScheduleGroupId();
            
            // 3. Получаем всех студентов группы
            $students = $this->em->getRepository(Student::class)->findBy(['group_id' => $groupId]);
            
            // 4. Формируем ответ с оценками и посещаемостью
            $result = [];

            foreach ($students as $student) {
                $grade = $this->em->getRepository(Grade::class)
                    ->findOneBy([
                        'user_id' => $student->getUserId(),
                        'subject_id' => $subject->getId(),
                        'date' => $data['date'],
                        'time' => mb_substr($data['time'], 0, 1),
                        'type' => $this->em->getRepository(ScheduleLessonType::class)->findOneBy(['name' => $data['type']])->getId(),
                    ]);
                
                $result[] = [
                    'id' => $student->getId(),
                    'full_name' => $student->getLastName() . " " . $student->getFirstName(),
                    'grade' => $grade ? $grade->getGrade() : null,
                    'attendance' => $grade && $grade->isAbsent() ? 'absent' : 'present',
                    'group_id' => $groupId
                ];
            }
            
            return new JsonResponse($result);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Ошибка сервера: ' . $e->getMessage()], 500);
        }
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


    // Сохранение оценки    
    #[Route(path: '/request/set/user/grade', name:'set_grade')]
    public function saveGrades(Request $request) {

        $data = json_decode($request->getContent(), true);
        $user = $this->getUser()->getId();        
        
        try {
            foreach ($data as $gradeData) {
                $userId = $this->em->getRepository(Student::class)->find($gradeData['studentId'])->getUserId();
                $subjectId = $this->em->getRepository(ScheduleSubject::class)
                    ->findOneBy(['name' => $gradeData['subject']])->getId();
                $typeId = $this->em->getRepository(ScheduleLessonType::class)->findOneBy(['name' => $gradeData['type']])->getId();
                $timeId = $this->em->getRepository(ScheduleTime::class)->findOneBy(['lesson_number' => mb_substr($gradeData['time'], 0, 1)])->getId();
                
                $grade = $this->em->getRepository(Grade::class)
                    ->findOneBy([
                        'user_id' => $userId,
                        'subject_id' => $subjectId,
                        'date' => $gradeData['date'],
                        'time' => $timeId,
                        'type' => $typeId,
                    ]) ?? new Grade();
                
                $grade->setUserId($userId);
                $grade->setSubjectId($subjectId);
                $grade->setGrade(($gradeData['grade'] == '') ? null : $gradeData['grade']);
                $grade->setAbsent($gradeData['attendance'] === 'absent');
                $grade->setDate($gradeData['date']);
                $grade->setType($typeId);
                $grade->setTime($timeId);
                $this->em->persist($grade);
            }
            
            $this->em->flush();
            
            return new JsonResponse(['status' => 'success']);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
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
    }

    #[Route('/request/get/user/profile/{userId}', name: 'get_user_profile')]
    public function getUserProfile(int $userId): JsonResponse {
        $userId = $this->getUser()->getId();

        $profileData = $this->getProfileData($userId);
        
        return $this->json([
            'status' => 'success',
            'data' => $profileData
        ]);
    }

    private function getProfileData($userId): array {
        $accountType = $this->study->getUserType($userId);
        $user = ($accountType == 'student') ? $this->em->getRepository(Student::class)->findOneBy(['user_id' => $userId])
            : $this->em->getRepository(Teacher::class)->findOneBy(['user_id' => $userId]);
        $fullName = $user->getLastName() . ' ' . $user->getFirstName() . ' ' . $user->getMiddleName();

        $profileData = [
            'full_name' => $fullName,
            'type' => $accountType,
        ];

        if ($accountType == 'teacher') {
            $profileData['subjects'] = $this->getTeacherSubjects($userId);
        } else {
            $profileData['group'] = $this->getStudentGroupInfo($userId);
            $profileData['subjects'] = $this->getStudentSubjectsWithGrades($userId);
        }

        return $profileData;
    }

    private function getTeacherSubjects($userId): array {
        $subjects = [];
        $teacherSubjects = $this->study->getSubjectsForUser($userId);

        foreach ($teacherSubjects as $subject) {
            $groups = [];
            $allGroupsForSubject = $this->study->getGroups($subject['id']);
            foreach ($allGroupsForSubject as $group) {
                $groups[] = $group['code'];
            }

            $subjects[] = [
                'name' => $subject['name'],
                'groups' => $groups
            ];
        }

        return $subjects;
    }

    private function getStudentGroupInfo($userId): array
    {
        $groupId = $this->study->getStudentGroup($userId);
        $group = $this->em->getRepository(Group::class)->find($groupId);

        return [
            'code' => $group->getCode(),
            'course' => $group->getCourse(),
            'semester' => $group->getSemester(),
            'admission_year' => $group->getYear()
        ];
    }

    private function getStudentSubjectsWithGrades($userId): array
    {
        $subjects = [];
        $gradesData = $this->study->getGrades($userId);
        
        // Группируем оценки по предметам
        $groupedGrades = [];
        foreach ($gradesData as $grade) {
            $subjectId = $grade['subject_id'];
            if (!isset($groupedGrades[$subjectId])) {
                $groupedGrades[$subjectId] = [];
            }
            $groupedGrades[$subjectId][] = $grade;
        }
        
        // Получаем информацию о предметах
        $subjectIds = array_keys($groupedGrades);
        $subjectsInfo = $this->getSubjectsInfo($subjectIds);
        
        // Формируем итоговый массив
        foreach ($groupedGrades as $subjectId => $grades) {
            $total = 0;
            $count = 0;
            $gradesList = [];
            
            foreach ($grades as $grade) {
                $total += $grade['grade'];
                $count++;
                
                $gradesList[] = [
                    'date' => $grade['date'],
                    'lesson_type' => $this->em->getRepository(ScheduleLessonType::class)->find($grade['type'])->getName(),
                    'value' => $grade['grade'],
                ];
            }
            
            $subjectName = $subjectsInfo[$subjectId] ?? 'Неизвестный предмет';
            
            $subjects[] = [
                'id' => $subjectId,
                'name' => $subjectName,
                'grades' => $gradesList,
                'average_grade' => $count > 0 ? round($total / $count, 2) : 0
            ];
        }
        
        return $subjects;
    }

    private function getSubjectsInfo(array $subjectIds): array
    {
        if (empty($subjectIds)) {
            return [];
        }
        
        $conn = $this->em->getConnection();
        $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
        $sql = "SELECT id, name FROM schedule_subject WHERE id IN ($placeholders)";
        
        $stmt = $conn->executeQuery($sql, $subjectIds);
        $result = $stmt->fetchAllAssociative();
        
        $subjects = [];
        foreach ($result as $row) {
            $subjects[$row['id']] = $row['name'];
        }
        
        return $subjects;
    }
}
