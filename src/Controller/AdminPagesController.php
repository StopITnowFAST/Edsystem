<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Status;
use App\Entity\Student;
use App\Service\File;
use App\Entity\File as FileEntity;
use App\Entity\Group;
use App\Service\TableWidget;
use App\Entity\Redirect;
use App\Entity\Grades;
use App\Entity\HeaderMenu;
use App\Entity\Teacher;
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

class AdminPagesController extends AbstractController {

    const PAGINATION_SIZE = 40;

    function __construct(
        private EntityManagerInterface $em, 
        private TableWidget $table,
        private File $file,
    ) {
    }
    
    // Таблица модераторы
    #[Route(path: '/admin/moderators/{page}', name: 'admin_moderators')] 
    function adminModerators($page = 1) {
        $pagination = $this->table->createPagination($page, $this->em->getRepository(User::class), self::PAGINATION_SIZE);
        return $this->render('admin/moderators.html.twig', [
            'notes' => $pagination['data'],
            'totalNotes' => $pagination['totalNotes'],
            'pagRow' => $pagination['row'],
            'currentPage' => $page,
            'paginationSize' => self::PAGINATION_SIZE,
            'formName' => 'admin_moderators',
        ]);
    }

    // Таблица группы
    #[Route('/admin/groups/{page}', 'admin_groups')] 
    function adminGroups($page = 1) {
        $pagination = $this->table->createPagination($page, $this->em->getRepository(Group::class), self::PAGINATION_SIZE);
        return $this->render('admin/groups.html.twig', [
            'notes' => $pagination['data'],
            'totalNotes' => $pagination['totalNotes'],
            'pagRow' => $pagination['row'],
            'currentPage' => $page,
            'paginationSize' => self::PAGINATION_SIZE,
            'formName' => 'admin_groups',
        ]);
    }

    // Таблица чаты
    #[Route('admin/chats/{page}', 'admin_chats')]
    function adminChats($page = 1) {
        $teachers = $this->em->getRepository(Teacher::class)->findAll();
        return $this->render('admin/chats.html.twig', [
            'tableData' => $teachers,
        ]);
    }

    // Таблица загруженные файлы
    #[Route('admin/files/{page}', name: 'admin_files')]
    function adminFiles($page = 1) {
        $pagination = $this->table->createPagination($page, $this->em->getRepository(FileEntity::class), self::PAGINATION_SIZE);
        return $this->render('admin/files.html.twig', [
            'notes' => $pagination['data'],
            'totalNotes' => $pagination['totalNotes'],
            'pagRow' => $pagination['row'],
            'currentPage' => $page,
            'paginationSize' => self::PAGINATION_SIZE,
            'formName' => 'admin_files',
        ]);
    }
    
    // Таблица студенты
    #[Route('admin/students/{page}', name: 'admin_students')]
    function adminStudents($page = 1) {
        $pagination = $this->table->createPagination($page, $this->em->getRepository(Student::class), self::PAGINATION_SIZE);
        return $this->render('admin/students.html.twig', [
            'notes' => $pagination['data'],
            'totalNotes' => $pagination['totalNotes'],
            'pagRow' => $pagination['row'],
            'currentPage' => $page,
            'paginationSize' => self::PAGINATION_SIZE,
            'formName' => 'admin_students',
        ]);
    }

    // Таблица преподаватели
    #[Route(path: '/admin/teachers/{page}', name: 'admin_teachers')] 
    function adminTeachers($page = 1) {
        $pagination = $this->table->createPagination($page, $this->em->getRepository(Teacher::class), self::PAGINATION_SIZE);
        return $this->render('admin/teachers.html.twig', [
            'notes' => $pagination['data'],
            'totalNotes' => $pagination['totalNotes'],
            'pagRow' => $pagination['row'],
            'currentPage' => $page,
            'paginationSize' => self::PAGINATION_SIZE,
            'formName' => 'admin_teachers',
        ]);
    }

    // Таблица тесты (не работает)
    #[Route('admin/tests/{page}', name: 'admin_tests')]
    function adminTests($page = 1) {
        $teachers = $this->em->getRepository(Teacher::class)->findAll();
        return $this->render('admin/tests.html.twig', [
            'tableData' => $teachers,
        ]);
    }

    // Таблица редиректы (не работает)
    #[Route('admin/tests/{page}', name: 'admin_tests')]
    function adminRedirects($page = 1) {
        $redirects = $this->em->getRepository(Redirect::class)->findAll();
        return $this->render('admin/redirects.html.twig', [
            'redirects' => $redirects,
        ]);
    }

    // Таблица меню header
    #[Route('admin/header-menu', name: 'admin_tests')]
    function adminHeaderMenu() {
        $menu = $this->em->getRepository(HeaderMenu::class)->findAll();
        

        return $this->render('admin/header_menu.html.twig', [
            'notes' => $menu,
        ]);
    }

    // Далее идут пути для редактирования записей

    // Редактирование пользователя
    #[Route(path: '/admin/update/user/{id}', name: 'admin_user')] 
    function adminUpdateUserCard($id) {
        
        return $this->render('admin/update/user.html.twig', [

        ]);
    }

    // Редактирование группы
    #[Route(path: '/admin/update/group/{id}', name: 'admin_group')] 
    function adminUpdateGroupCard($id) {
        
        return $this->render('admin/update/user.html.twig', [

        ]);
    }

    // Редактирование файла
    #[Route(path: '/admin/update/file/{id}', name: 'admin_file')] 
    function adminUpdateFileCard($id) {
        
        return $this->render('admin/update/file.html.twig', [

        ]);
    }

    // Далее идут пути для создания записей

    // Создание группы
    #[Route(path: '/admin/create/group', name: 'admin_create_group')] 
    function adminCreateGroup() {        
        return $this->render('admin/create/group.html.twig', [

        ]);
    }

    // Создание студента
    #[Route(path: '/admin/create/student', name: 'admin_create_student')] 
    function adminCreateStudent() {        
        return $this->render('admin/create/student.html.twig', [

        ]);
    }

    // Создание преподавателя
    #[Route(path: '/admin/create/teacher', name: 'admin_create_teacher')] 
    function adminCreateTeacher() {        
        return $this->render('admin/create/teacher.html.twig', [

        ]);
    }

    // Создание пункта меню
    #[Route(path: '/admin/create/header-menu', name: 'admin_create_header-menu')] 
    function adminCreateHeaderMenu(Request $request) {
        if ($request->isMethod('POST')) {
            $headerItem = new HeaderMenu;
            $headerItem->setParentId($_POST['parent_id']);
            $headerItem->setItemLevel($_POST['item_level']);
            $headerItem->setName($_POST['name']);
            $headerItem->setUrl($_POST['url']);
            $headerItem->setPlaceOrder($_POST['place_order']);
            $headerItem->setStatus($_POST['status']);
            $this->em->persist($headerItem);
            $this->em->flush();
        }
        $parents = $this->em->getRepository(HeaderMenu::class)->findAll();
        $statuses = $this->em->getRepository(Status::class)->findAll();
        return $this->render('admin/create/header_menu.html.twig', [
            'parents' => $parents,
            'statuses' => $statuses,
        ]);
    }
    
    // Создание файла
    #[Route(path: '/admin/create/file', name: 'admin_file')] 
    function adminCreateFileCard() {        
        return $this->render('admin/create/file.html.twig', [

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
    

}