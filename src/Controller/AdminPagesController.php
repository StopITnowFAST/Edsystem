<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Status;
use App\Entity\Student;
use App\Service\File;
use App\Service\Help;
use App\Entity\File as FileEntity;
use App\Entity\Group;
use App\Entity\Teacher;
use App\Service\TableWidget;
use App\Entity\Redirect;
use App\Entity\HeaderMenu;
use App\Entity\ScheduleClassroom;
use App\Entity\ScheduleLessonType;
use App\Entity\ScheduleTime;
use App\Entity\ScheduleSubject;
use App\Entity\Test;
use App\Entity\Schedule;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Service\BreadcrumbsGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AdminPagesController extends AbstractController {

    function __construct(
        private EntityManagerInterface $em, 
        private TableWidget $table,
        private File $file,
        private BreadcrumbsGenerator $breadcrumbs,
        private UrlGeneratorInterface $router,
        private Help $helper,
    ) {
    }
    
    // Таблица модераторы
    #[Route(path: '/admin/moderators/{page}', name: 'admin_moderators')] 
    function adminModerators($page = 1) {
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Модераторы' => 'admin_moderators',
        ], $this->router);

        $pagination = $this->table->createPagination($page, $this->em->getRepository(User::class));
        return $this->render('admin/moderators.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'notes' => $pagination['data'],
            'totalNotes' => $pagination['totalNotes'],
            'pagRow' => $pagination['row'],
            'currentPage' => $page,
            'paginationSize' => $pagination['size'],
            'formName' => 'admin_moderators',
        ]);
    }

    // Таблица группы
    #[Route('/admin/groups/{page}', 'admin_groups')] 
    function adminGroups($page = 1) {
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Группы' => 'admin_groups',
        ], $this->router);
        $pagination = $this->table->createPagination($page, $this->em->getRepository(Group::class));
        return $this->render('admin/groups.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'notes' => $pagination['data'],
            'totalNotes' => $pagination['totalNotes'],
            'pagRow' => $pagination['row'],
            'currentPage' => $page,
            'paginationSize' => $pagination['size'],
            'formName' => 'admin_groups',
        ]);
    }

    // Таблица студенты группы
    #[Route('/admin/update/groups/{groupId}/students', 'admin_groups_students')] 
    function adminGroupStudents($groupId) {
        $group = $this->em->getRepository(Group::class)->find($groupId);
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Группы' => 'admin_groups',
            'Добавить группу' => ['admin_update_note', ['type' => 'groups', 'id' => $groupId]],
            'Студенты группы' => ['admin_groups_students', ['groupId' => $groupId]],
        ], $this->router);
        $notes = $this->em->getRepository(Student::class)->findBy(['group_id' => $groupId]);
        $link = $_ENV['HOSTNAME'] . "connect-with-group/" .  $group->getGroupToken();
        return $this->render('admin/group_students.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'notes' => $notes,
            'link' => ($group->isFull()) ? null : $link,
        ]);
    }

    // Таблица чаты
    #[Route('admin/chats/{page}', 'admin_chats')]
    function adminChats($page = 1) {
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Чаты' => 'admin_chats',
        ], $this->router);
        $teachers = $this->em->getRepository(Teacher::class)->findAll();
        return $this->render('admin/chats.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'tableData' => $teachers,
        ]);
    }

    // Таблица загруженные файлы
    #[Route('/admin/files/{page}', name: 'admin_files')]
    function adminFiles($page = 1) {
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Файлы' => 'admin_files',
        ], $this->router);
        $pagination = $this->table->createPagination($page, $this->em->getRepository(FileEntity::class));
        return $this->render('admin/files.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'notes' => $pagination['data'],
            'totalNotes' => $pagination['totalNotes'],
            'pagRow' => $pagination['row'],
            'currentPage' => $page,
            'paginationSize' => $pagination['size'],
            'formName' => 'admin_files',
        ]);
    }
    
    // Таблица студенты
    #[Route('/admin/students/{page}', name: 'admin_students')]
    function adminStudents($page = 1) {
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Студенты' => 'admin_students',
        ], $this->router);
        $pagination = $this->table->createPagination($page, $this->em->getRepository(Student::class));
        return $this->render('admin/students.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'notes' => $pagination['data'],
            'totalNotes' => $pagination['totalNotes'],
            'pagRow' => $pagination['row'],
            'currentPage' => $page,
            'paginationSize' => $pagination['size'],
            'formName' => 'admin_students',
        ]);
    }

    // Таблица преподаватели
    #[Route(path: '/admin/teachers/{page}', name: 'admin_teachers')] 
    function adminTeachers($page = 1) {
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Преподаватели' => 'admin_teachers',
        ], $this->router);
        $pagination = $this->table->createPagination($page, $this->em->getRepository(Teacher::class));
        return $this->render('admin/teachers.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'notes' => $pagination['data'],
            'totalNotes' => $pagination['totalNotes'],
            'pagRow' => $pagination['row'],
            'currentPage' => $page,
            'paginationSize' => $pagination['size'],
            'formName' => 'admin_teachers',
        ]);
    }

    // Таблица тесты
    #[Route(path: '/admin/tests/{page}', name: 'admin_tests')] 
    function adminTests($page = 1) {
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Тесты' => 'admin_tests',
        ], $this->router);
        $pagination = $this->table->createPagination($page, $this->em->getRepository(Test::class));
        return $this->render('admin/tests.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'notes' => $pagination['data'],
            'totalNotes' => $pagination['totalNotes'],
            'pagRow' => $pagination['row'],
            'currentPage' => $page,
            'paginationSize' => $pagination['size'],
            'formName' => 'admin_tests',
        ]);
    }

    // Таблица редиректы
    #[Route('/admin/redirects', name: 'admin_redirects')]
    function adminRedirects() {
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Редиректы' => 'admin_redirects',
        ], $this->router);
        $redirects = $this->em->getRepository(Redirect::class)->findAll();
        return $this->render('admin/redirects.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'notes' => $redirects,
        ]);
    }

    // Таблица меню header
    #[Route('/admin/header-menu', name: 'admin_header-menu')]
    function adminHeaderMenu() {
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Меню' => 'admin_header-menu',
        ], $this->router);
        $menu = $this->em->getRepository(HeaderMenu::class)->findAllItems();
        return $this->render('admin/header_menu.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'notes' => $menu,
        ]);
    }

    // Таблица аудитории
    #[Route('/admin/schedule/classrooms/{page}', 'admin_schedule_classrooms')] 
    function adminScheduleClassrooms($page = 1) {
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Аудитории' => 'admin_schedule_classrooms',
        ], $this->router);
        $pagination = $this->table->createPagination($page, $this->em->getRepository(ScheduleClassroom::class));
        return $this->render('admin/schedule_classrooms.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'notes' => $pagination['data'],
            'totalNotes' => $pagination['totalNotes'],
            'pagRow' => $pagination['row'],
            'currentPage' => $page,
            'paginationSize' => $pagination['size'],
            'formName' => 'admin_schedule_classrooms',
        ]);
    }

    // Таблица типы занятий
    #[Route('/admin/schedule/lesson-types/{page}', 'admin_schedule_lesson_types')] 
    function adminScheduleLessonTypes($page = 1) {
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Типы занятий' => 'admin_schedule_lesson_types',
        ], $this->router);
        $pagination = $this->table->createPagination($page, $this->em->getRepository(ScheduleLessonType::class));
        return $this->render('admin/schedule_lesson_types.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'notes' => $pagination['data'],
            'totalNotes' => $pagination['totalNotes'],
            'pagRow' => $pagination['row'],
            'currentPage' => $page,
            'paginationSize' => $pagination['size'],
            'formName' => 'admin_schedule_lesson_types',
        ]);
    }

    // Таблица предметы
    #[Route('/admin/schedule/subjects/{page}', 'admin_schedule_subjects')] 
    function adminScheduleSubjects($page = 1) {
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Предметы' => 'admin_schedule_subjects',
        ], $this->router);
        $groups = $this->em->getRepository(Group::class)->findBy(['status' => 1]);
        $teachers = $this->em->getRepository(Teacher::class)->findBy(['status' => 1]);
        $pagination = $this->table->createPagination($page, $this->em->getRepository(ScheduleSubject::class));
        return $this->render('admin/schedule_subjects.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'notes' => $pagination['data'],
            'totalNotes' => $pagination['totalNotes'],
            'pagRow' => $pagination['row'],
            'currentPage' => $page,
            'paginationSize' => $pagination['size'],
            'formName' => 'admin_schedule_subjects',
            'groups' => $groups,
            'teachers' => $teachers,
        ]);
    }

    // Таблица время занятий
    #[Route('/admin/schedule/lesson-time/{page}', 'admin_schedule_lesson_times')] 
    function adminLessonTime($page = 1) {
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Время занятий' => 'admin_schedule_lesson_times',
        ], $this->router);
        $pagination = $this->table->createPagination($page, $this->em->getRepository(ScheduleTime::class));
        return $this->render('admin/schedule_lesson_times.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'notes' => $pagination['data'],
            'totalNotes' => $pagination['totalNotes'],
            'pagRow' => $pagination['row'],
            'currentPage' => $page,
            'paginationSize' => $pagination['size'],
            'formName' => 'admin_schedule_lesson_times',
        ]);
    }

    // Далее идут пути для создания записей

    // Создание студента
    #[Route(path: '/admin/create/student', name: 'admin_create_student')] 
    function adminCreateStudent(Request $request, $element = null) {
        if ($request->isMethod('POST')) {
            $student = (isset($_POST['isUpdate'])) ? $this->em->getRepository(Student::class)->find($_POST['updateId']) : new Student;                
            $student->setFirstName($_POST['firstName']);
            $student->setMiddlename($_POST['middleName']);
            $student->setLastName($_POST['lastName']);
            $student->setGroupid($_POST['group']);
            $student->setBirthdayDate(strtotime($_POST['birthdayDate']));
            $student->setStatus($_POST['status']);
            $this->em->persist($student);
            $this->em->flush();
            return $this->redirectToRoute('admin_students');
        }
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Студенты' => 'admin_students',
            'Добавить студента' => 'admin_create_student',
        ], $this->router);
        if ($element) {
            $element->setBirthdayDate(gmdate("Y-m-d",$element->getBirthdayDate()+100));
        }        
        $groups = $this->em->getRepository(Group::class)->findAll();
        $statuses = $this->em->getRepository(Status::class)->findAll();
        return $this->render('admin/redact/student.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'groups' => $groups,
            'statuses' => $statuses,
            'updating_element' => $element,
        ]); 
    }

    // Создание преподавателя
    #[Route(path: '/admin/create/teacher', name: 'admin_create_teacher')] 
    function adminCreateTeacher(Request $request, $element = null) {
        if ($request->isMethod('POST')) {
            $teacher = (isset($_POST['isUpdate'])) ? $this->em->getRepository(Teacher::class)->find($_POST['updateId']) : new Teacher;
            $teacher->setDescription($_POST['description']);
            $teacher->setFirstName($_POST['firstName']);
            $teacher->setMiddleName($_POST['middleName']);
            $teacher->setLastName($_POST['lastName']);
            $teacher->setGrade($_POST['grade']);
            $teacher->setPosition($_POST['position']);
            $teacher->setInstitut($_POST['institut']);
            $teacher->setDivision($_POST['division']);
            $teacher->setDescription($_POST['description']);
            $teacher->setStatus($_POST['status']);
            $this->em->persist($teacher);
            $this->em->flush();
            return $this->redirectToRoute('admin_teachers');
        }
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Преподаватели' => 'admin_teachers',
            'Добавить преподавателя' => 'admin_create_teacher',
        ], $this->router);
        $statuses = $this->em->getRepository(Status::class)->findAll();
        return $this->render('admin/redact/teacher.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'statuses' => $statuses,
            'updating_element' => $element,
        ]);
    }

    // Создание пункта меню
    #[Route(path: '/admin/create/header-menu', name: 'admin_create_header-menu')] 
    function adminCreateHeaderMenu(Request $request, $element = null) {
        if ($request->isMethod('POST')) {
            $headerItem = (isset($_POST['isUpdate'])) ? $this->em->getRepository(HeaderMenu::class)->find($_POST['updateId']) : new HeaderMenu;
            $headerItem->setParentId($_POST['parent_id']);
            $headerItem->setName($_POST['name']);
            $headerItem->setUrl($_POST['url'] ?? NULL);
            $headerItem->setPlaceOrder($_POST['place_order']);
            $headerItem->setStatus($_POST['status']);
            $this->em->persist($headerItem);
            $this->em->flush();
            return $this->redirectToRoute('admin_header-menu');
        }
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Меню' => 'admin_header-menu',
            'Добавить пункт меню' => 'admin_create_header-menu',
        ], $this->router);
        $parents = $this->em->getRepository(HeaderMenu::class)->findAll();
        $statuses = $this->em->getRepository(Status::class)->findAll();
        return $this->render('admin/redact/header_menu.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'parents' => $parents,
            'statuses' => $statuses,
            'updating_element' => $element,
        ]);
    }
    
    // Создание файла
    #[Route(path: '/admin/create/file', name: 'admin_create_file')] 
    function adminCreateFile(Request $request, $element = null) {
        if ($request->isMethod('POST')) {
            $error = false;
            $user = $this->getUser();
            $file = (isset($_POST['isUpdate'])) ? $this->em->getRepository(FileEntity::class)->find($_POST['updateId']) : new FileEntity;
            $fileInfo = $_FILES['file'];

            $fileName = (!empty($_POST['name'])) ? $_POST['name'] : $fileInfo['name'];
            $fileExtension = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
            $realFileName = uniqid('file_', true) . '.' . $fileExtension;

            $uploadDirectory = $this->getParameter('kernel.project_dir').'/public/met_files';
                
            $uploadedFile = $request->files->get('file'); // Получаем файл из запроса

            if ($uploadedFile) {
                try {
                    $uploadedFile->move(   
                        $uploadDirectory,
                        $realFileName
                    );
                    
                    $this->addFlash('success', 'Файл успешно загружен!');
                } catch (\Exception $e) {
                    $error = true;
                    $this->addFlash('error', 'Ошибка при загрузке файла: '.$e->getMessage());
                }            
            }
            
            if (!$error) {
                if (isset($_POST['isUpdate'])) {
                    $file->setFileName($fileName);
                } else {
                    $file->setFileName($fileName);
                    $file->setSize($fileInfo['size']);
                    $file->setRealFileName($realFileName);
                    $file->setExtension($fileExtension);
                    $file->setCreatedBy($user->getId());
                }
                $this->em->persist($file);
                $this->em->flush();
                $element = $file;
            }
        }
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Файлы' => 'admin_files',
            'Добавить файл' => 'admin_create_file',
        ], $this->router);

        $fileId = (!empty($element)) ? $element->getId() : 0;
        $AllGroups = $this->em->getRepository(Group::class)->findAll();
        $groupsForThisTest = $this->em->getRepository(Group::class)->findFileGroups($fileId);

        return $this->render('admin/redact/file.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'groups' => $AllGroups,
            'notes' => $groupsForThisTest,
            'updating_element' => $element,
        ]);
    }

    // Добавление группы для файла
    #[Route(path: '/admin/create/file/{fileId}/add-groups', name: 'admin_create_file_add_groups')] 
    function adminCreateFileAddGroups($fileId) {
        $groupId = $_POST['group'];
        $file = $this->em->getRepository(FileEntity::class)->find($fileId);
        $groups = $file->getForGroups();
        $groups .= " $groupId,";
        $file->setForGroups($groups);
        $this->em->persist($file);
        $this->em->flush();
        return $this->redirectToRoute('admin_update_note', ['type' => 'files', 'id' => $fileId]);
    }

    // Удаление группы для файла
    #[Route(path: '/admin/create/file/{fileId}/delete-groups/{groupId}', name: 'admin_create_file_delete_groups')] 
    function adminCreateFileDeleteGroups($fileId, $groupId) {
        $file = $this->em->getRepository(FileEntity::class)->find($fileId);
        $groups = $file->getForGroups();
        $groups = str_replace(" $groupId,", '', $groups);
        $file->setForGroups($groups);
        $this->em->persist($file);
        $this->em->flush();
        return $this->redirectToRoute('admin_update_note', ['type' => 'files', 'id' => $fileId]);
    }
    

    // Сохранение файлов
    #[Route(path: '/admin/create/file/save', name: 'file_save')] 
    function saveFile() {
        foreach ($_FILES as $index => $file) {
            $url = $_POST["url_$index"];
            $this->file->saveFile($file, $url);
        }         
        
        die;        
        // return $this->redirectToRoute('admin_file', ['id' => $id]);
    }

    // Создание редиректа
    #[Route(path: '/admin/create/redirect', name: 'admin_create_redirect')] 
    function adminCreateRedirect(Request $request, $element = null) {
        if ($request->isMethod('POST')) {
            $red = (isset($_POST['isUpdate'])) ? $this->em->getRepository(Redirect::class)->find($_POST['updateId']) : new Redirect;
            $red->setDescription($_POST['description']);
            $red->setRedirectFrom($_POST['from']);
            $red->setRedirectTo($_POST['to']);
            $red->setStatus($_POST['status']);
            $this->em->persist($red);
            $this->em->flush();
            return $this->redirectToRoute('admin_redirects');
        }
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Редиректы' => 'admin_redirects',
            'Добавить редирект' => 'admin_create_redirect',
        ], $this->router);
        $parents = $this->em->getRepository(HeaderMenu::class)->findAll();
        $statuses = $this->em->getRepository(Status::class)->findAll();
        return $this->render('admin/redact/redirect.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'parents' => $parents,
            'statuses' => $statuses,
            'updating_element' => $element,
        ]);
    }

    // Создание группы
    #[Route(path: '/admin/create/group', name: 'admin_create_group')] 
    function adminCreateGroup(Request $request, $element = null) {
        if ($request->isMethod('POST')) {
            $group = (isset($_POST['isUpdate'])) ? $this->em->getRepository(Group::class)->find($_POST['updateId']) : new Group;
            $group->setName($_POST['name']);
            $group->setCode($_POST['code']);
            $group->setYear($_POST['year']);
            $group->setSemester($_POST['semester']);
            $group->setCourse($_POST['course']);
            $group->setGroupToken($this->helper->generateRandomString(50));
            $group->setFull($_POST['isFull']);
            $group->setEdStartsFirst($_POST['first_date']);
            $group->setEdStartsSecond($_POST['second_date']);
            $group->setDescription($_POST['description']);
            $group->setStatus($_POST['status']);
            $this->em->persist($group);
            $this->em->flush();
            return $this->redirectToRoute('admin_update_note', ['type' => 'groups', 'id' => $group->getId()]);
        }
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Группы' => 'admin_groups',
            'Добавить группу' => ($element != null) ? ['admin_update_note', ['type' => 'groups', 'id' => $element->getId()]] : 'admin_create_group',
        ], $this->router);
        $statuses = $this->em->getRepository(Status::class)->findAll();
        return $this->render('admin/redact/group.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'statuses' => $statuses,
            'updating_element' => $element,
        ]);
    }

    // Создание теста
    #[Route(path: '/admin/create/test', name: 'admin_create_test')] 
    function adminCreateTest(Request $request, $element = null) {
        if ($request->isMethod('POST')) {
            $test = (isset($_POST['isUpdate'])) ? $this->em->getRepository(Test::class)->find($_POST['updateId']) : new Test;
            $test->setName($_POST['name']);
            $test->setShuffle($_POST['shuffle']);
            $test->setTime($_POST['time']);
            $test->setAttempts($_POST['attempts']);
            $test->setPointsFor3($_POST['points_for_3']);
            $test->setPointsFor4($_POST['points_for_4']);
            $test->setPointsFor5($_POST['points_for_5']);
            $test->setStatus($_POST['status']);
            $this->em->persist($test);
            $this->em->flush();
            if ($_POST['action'] == 'appoint') {
                return $this->redirectToRoute('admin_tests_appoint', ['testId' => $test->getId()]); // Назначение групп 
            } else if ($_POST['action'] == 'redact') {
                return $this->redirectToRoute('admin_tests_redact',  ['testId' => $test->getId()]); // Добавление вопросов                
            } else {
                return $this->redirectToRoute('admin_tests'); // Обычное сохранение
            }
        }
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Тесты' => 'admin_tests',
            'Добавить тест' => 'admin_create_test',
        ], $this->router);
        $statuses = $this->em->getRepository(Status::class)->findAll();
        $totalPoints = ($element) ? $this->em->getRepository(Test::class)->countTotalPoints($element->getId()) : 0;
        return $this->render('admin/redact/test.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'statuses' => $statuses,
            'updating_element' => $element,
            'totalPoints' => $totalPoints,
        ]);
    }  

    // Создание аудитории
    #[Route(path: '/admin/create/schedule/classroom', name: 'admin_create_schedule_classroom')] 
    function adminCreateScheduleClassroom(Request $request, $element = null) {
        if ($request->isMethod('POST')) {
            $classroom = (isset($_POST['isUpdate'])) ? $this->em->getRepository(ScheduleClassroom::class)->find($_POST['updateId']) : new ScheduleClassroom;
            $classroom->setName($_POST['name']);
            $classroom->setClassroomType($_POST['type']);
            $this->em->persist($classroom);
            $this->em->flush();
            return $this->redirectToRoute('admin_schedule_classrooms');
        }
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Аудитории' => 'admin_schedule_classrooms',
            'Добавить аудиторию' => 'admin_create_schedule_classroom',
        ], $this->router);
        return $this->render('admin/redact/schedule_classroom.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'updating_element' => $element,
        ]);
    }

    // Создание типа занятия
    #[Route(path: '/admin/create/schedule/lesson-type', name: 'admin_create_schedule_lesson_type')] 
    function adminCreateScheduleLessonType(Request $request, $element = null) {
        if ($request->isMethod('POST')) {
            $type = (isset($_POST['isUpdate'])) ? $this->em->getRepository(ScheduleLessonType::class)->find($_POST['updateId']) : new ScheduleLessonType;
            $type->setName($_POST['name']);            
            $this->em->persist($type);
            $this->em->flush();
            return $this->redirectToRoute('admin_schedule_lesson_types');
        }
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Типы занятий' => 'admin_schedule_lesson_types',
            'Добавить тип занятия' => 'admin_create_schedule_lesson_type',
        ], $this->router);
        
        return $this->render('admin/redact/schedule_lesson_type.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'updating_element' => $element,
        ]);
    }

    // Создание предмета
    #[Route(path: '/admin/create/schedule/subject', name: 'admin_create_schedule_subject')] 
    function adminCreateScheduleSubject(Request $request, $element = null) {
        if ($request->isMethod('POST')) {
            $subject = (isset($_POST['isUpdate'])) ? $this->em->getRepository(ScheduleSubject::class)->find($_POST['updateId']) : new ScheduleSubject;
            $subject->setName($_POST['name']);
            $subject->setGroupId($_POST['group_id']);
            $subject->setUserId($_POST['user_id']);
            $this->em->persist($subject);
            $this->em->flush();
            return $this->redirectToRoute('admin_schedule_subjects');
        }

        $groups = $this->em->getRepository(Group::class)->findBy(['status' => 1]);
        $teachers = $this->em->getRepository(Teacher::class)->findBy(['status' => 1]);

        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Предметы' => 'admin_schedule_subjects',
            'Добавить предмет' => 'admin_create_schedule_subject',
        ], $this->router);

        return $this->render('admin/redact/schedule_subject.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'groups' => $groups,
            'teachers' => $teachers,
            'updating_element' => $element,
        ]);
    }

    // Создание времени занятия
    #[Route(path: '/admin/create/schedule/lesson-time', name: 'admin_create_schedule_lesson_time')] 
    function adminCreateScheduleLessonTime(Request $request, $element = null) {
        if ($request->isMethod('POST')) {
            $time = (isset($_POST['isUpdate'])) ? $this->em->getRepository(ScheduleTime::class)->find($_POST['updateId']) : new ScheduleTime;
            $time->setLessonNumber($_POST['lesson_number']);
            $time->setStartTime($_POST['start_time']);
            $time->setEndTime($_POST['end_time']);
            $this->em->persist($time);
            $this->em->flush();
            return $this->redirectToRoute('admin_schedule_lesson_times');
        }
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Время занятия' => 'admin_schedule_lesson_times',
            'Добавить время занятия' => 'admin_create_schedule_lesson_time',
        ], $this->router);

        return $this->render('admin/redact/schedule_lesson_time.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'updating_element' => $element,
        ]);
    }

    // Создание расписания
    #[Route('/admin/schedule/{groupId}', 'admin_create_schedule')] 
    function adminSchedule($groupId) {
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Создание расписания' => 'admin_schedule_lesson_times',
        ], $this->router);

        $currentGroup = $this->em->getRepository(Group::class)->find($groupId);
        // Загружаем существующее расписание для группы
        $existingSchedules = $this->em->getRepository(Schedule::class)->findBy([
            'schedule_group_id' => $groupId
        ], ['week_number' => 'ASC', 'schedule_day' => 'ASC', 'schedule_time_id' => 'ASC']);

        $times = $this->em->getRepository(ScheduleTime::class)->findAll();
        $subjects = $this->em->getRepository(ScheduleSubject::class)->findAll();
        $types = $this->em->getRepository(ScheduleLessonType::class)->findAll();
        $teachers = $this->em->getRepository(Teacher::class)->findAll();
        $classrooms = $this->em->getRepository(ScheduleClassroom::class)->findAll();

        return $this->render('admin/redact/schedule.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'times' => $times,
            'subjects' => $subjects,
            'lesson_types' => $types,
            'teachers' => $teachers,
            'classrooms' => $classrooms,
            'currentGroup' => $currentGroup,
            'existingSchedules' => $existingSchedules,
        ]);
    }

    // Далее идут маршруты действий

    // Удаление элемента
    #[Route(path: '/admin/delete/{type}/{id}', name: 'admin_delete_note')] 
    function adminDeleteNote($id, $type) {
        $element = match($type) {
            'header-menu' => $this->em->getRepository(HeaderMenu::class)->find($id),
            'redirects' => $this->em->getRepository(Redirect::class)->find($id),
            'groups' => $this->em->getRepository(Group::class)->find($id),
            'students' => $this->em->getRepository(Student::class)->find($id),
            'teachers' => $this->em->getRepository(Teacher::class)->find($id),
            'tests' => $this->em->getRepository(Test::class)->find($id),
            'files' => $this->em->getRepository(FileEntity::class)->find($id),
            'schedule_classrooms' => $this->em->getRepository(ScheduleClassroom::class)->find($id),
            'schedule_lesson_times' => $this->em->getRepository(ScheduleTime::class)->find($id),
            'schedule_lesson_types' => $this->em->getRepository(ScheduleLessonType::class)->find($id),
            'schedule_subjects' => $this->em->getRepository(ScheduleSubject::class)->find($id),
        };
        if ($type == 'files') {
            $path = $this->getParameter('kernel.project_dir').'/public/met_files/' . $element->getRealFileName();
            if (file_exists($path)) {
                unlink($path);
            }
        }
        $this->em->remove($element);
        $this->em->flush();
        return $this->redirectToRoute("admin_$type");
    }

    // Убрать студента их группы
    #[Route(path: '/admin/delete-student-from-group/{studentId}', name: 'admin_delete_student_from_group')] 
    function adminStudentFromGroup($studentId) {
        $student = $this->em->getRepository(Student::class)->find($studentId);
        $currentGroup = $student->getGroupId();
        $student->setGroupId(null);
        $this->em->persist($student);
        $this->em->flush();
        return $this->redirectToRoute("admin_groups_students", [
            'groupId' => $currentGroup,
        ]);
    }
    
    // Редактирование элемента
    #[Route(path: '/admin/update/{type}/{id}', name: 'admin_update_note')] 
    function adminUpdateNote($type, $id, Request $request) {
        switch ($type) {
            case 'header-menu':
                $element = $this->em->getRepository(HeaderMenu::class)->find($id);
                return $this->adminCreateHeaderMenu($request, $element);
            case 'redirects':
                $element = $this->em->getRepository(Redirect::class)->find($id);
                return $this->adminCreateRedirect($request, $element);
            case 'groups':
                $element = $this->em->getRepository(Group::class)->find($id);
                return $this->adminCreateGroup($request, $element);
            case 'students':
                $element = $this->em->getRepository(Student::class)->find($id);
                return $this->adminCreateStudent($request, $element);
            case 'teachers': 
                $element = $this->em->getRepository(Teacher::class)->find($id);
                return $this->adminCreateTeacher($request, $element);
            case 'tests':
                $element = $this->em->getRepository(Test::class)->find($id);
                return $this->adminCreateTest($request, $element);
            case 'files':
                $element = $this->em->getRepository(FileEntity::class)->find($id);
                return $this->adminCreateFile($request, $element);
            case 'schedule_classrooms':
                $element = $this->em->getRepository(ScheduleClassroom::class)->find($id);
                return $this->adminCreateScheduleClassroom($request, $element);
            case 'schedule_lesson_times':
                $element = $this->em->getRepository(ScheduleTime::class)->find($id);
                return $this->adminCreateScheduleLessonTime($request, $element);
            case 'schedule_lesson_types':
                $element = $this->em->getRepository(ScheduleLessonType::class)->find($id);
                return $this->adminCreateScheduleLessonType($request, $element);
            case 'schedule_subjects':
                $element = $this->em->getRepository(ScheduleSubject::class)->find($id);
                return $this->adminCreateScheduleSubject($request, $element);
            default:
                return $this->redirectToRoute('admin_moderators');
        }
    }

    // Далее идут вспомогательные функции

    // Присоединение аккаунта к группе
    #[Route('/connect-with-group/{groupToken}', name: 'connect_with_group')]
    public function connectWithGroup($groupToken) {
        $group = $this->em->getRepository(Group::class)->findOneBy(['group_token' => $groupToken]);
        $student = $this->em->getRepository(Student::class)->findOneBy(['user_id' => $this->getUser()->getId()]);
        $student->setGroupId($group->getId());
        $this->em->persist($student);
        $this->em->flush();
        return $this->redirectToRoute('account');
    }
}