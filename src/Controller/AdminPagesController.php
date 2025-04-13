<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Status;
use App\Entity\Student;
use App\Service\File;
use App\Entity\File as FileEntity;
use App\Entity\Group;
use App\Entity\Teacher;
use App\Service\TableWidget;
use App\Entity\Redirect;
use App\Entity\HeaderMenu;
use App\Entity\Test;
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
    #[Route(path: '/admin/create/file', name: 'admin_file')] 
    function adminCreateFileCard() {        
        return $this->render('admin/redact/file.html.twig', [

        ]);
    }

    // Сохранение файлов
    #[Route(path: '/admin/create/file/save', name: 'file_save')] 
    function saveFile() {
        var_dump($_POST); die;
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
            $group->setDescription($_POST['description']);
            $group->setStatus($_POST['status']);
            $this->em->persist($group);
            $this->em->flush();
            return $this->redirectToRoute('admin_groups');
        }
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Группы' => 'admin_groups',
            'Добавить группу' => 'admin_create_group',
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
            $test->setStatus($_POST['status']);
            $this->em->persist($test);
            $this->em->flush();
            if ($_POST['action'] == 'appoint') {
                return $this->redirectToRoute('admin_tests_appoint', ['testId' => $test->getId()]); // Назначение групп 
            } else if ($_POST['action'] == 'redact') {
                return $this->redirectToRoute('admin_tests_redact'); // Добавление вопросов                
            } else {
                return $this->redirectToRoute('admin_tests'); // Обычное сохранение
            }
        }
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Тесты' => 'admin_tests',
            'Добавить тест' => 'admin_create_test',
        ], $this->router);
        $statuses = $this->em->getRepository(Status::class)->findAll();
        return $this->render('admin/redact/test.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'statuses' => $statuses,
            'updating_element' => $element,
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
        };        
        $this->em->remove($element);
        $this->em->flush();
        return $this->redirectToRoute("admin_$type");
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
            default:
                return $this->redirectToRoute('admin_moderators');
        }
    }

    // Далее идут вспомогательные функции

}