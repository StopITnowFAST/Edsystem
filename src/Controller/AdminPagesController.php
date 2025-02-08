<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Schedule;
use App\Entity\Student;
use App\Entity\Day;
use App\Entity\Group;
use App\Entity\Subject;
use App\Entity\Grades;
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

    private $em;
    private $table;
    function __construct(
        EntityManagerInterface $em, TableController $table
    ) {
        $this->em = $em;     
        $this->table = $table;
    }
    
    // Модераторы
    #[Route(path: '/admin/moderators/{page}', name: 'admin_moderators')] 
    function adminModerators($page = 1) {
        $paginationSize = 20;   
        $pagination = $this->table->createPagination($page, $this->em->getRepository(User::class), $paginationSize);
        return $this->render('admin/moderators.html.twig', [
            'notes' => $pagination['data'],
            'totalNotes' => $pagination['totalNotes'],
            'pagRow' => $pagination['row'],
            'currentPage' => $page,
            'paginationSize' => $paginationSize,
            'formName' => 'admin_moderators',
        ]);
    }

    // Группы
    #[Route('/admin/groups/{page}', 'admin_groups')] 
    function adminGroups($page = 1) {
        $teachers = $this->em->getRepository(Teacher::class)->findAll();
        return $this->render('admin/groups.html.twig', [
            'tableData' => $teachers,
        ]);
    }

    // Чаты
    #[Route('admin/chats/{page}', 'admin_chats')]
    function adminChats($page = 1) {
        $teachers = $this->em->getRepository(Teacher::class)->findAll();
        return $this->render('admin/chats.html.twig', [
            'tableData' => $teachers,
        ]);
    }

    // Загруженные файлы
    #[Route('admin/files/{page}', name: 'admin_files')]
    function adminFiles($page = 1) {
        $teachers = $this->em->getRepository(Teacher::class)->findAll();
        return $this->render('admin/files.html.twig', [
            'tableData' => $teachers,
        ]);
    }
    
    // Студенты
    #[Route('admin/students/{page}', name: 'admin_students')]
    function adminStudents($page = 1) {
        $paginationSize = 20;   
        $pagination = $this->table->createPagination($page, $this->em->getRepository(Student::class), $paginationSize);
        return $this->render('admin/students.html.twig', [
            'notes' => $pagination['data'],
            'totalNotes' => $pagination['totalNotes'],
            'pagRow' => $pagination['row'],
            'currentPage' => $page,
            'paginationSize' => $paginationSize,
            'formName' => 'admin_students',
        ]);
    }

    // Преподаватели
    #[Route(path: '/admin/teachers/{page}', name: 'admin_teachers')] 
    function adminTeachers($page = 1) {
        $paginationSize = 20;   
        $pagination = $this->table->createPagination($page, $this->em->getRepository(Teacher::class), $paginationSize);
        return $this->render('admin/teachers.html.twig', [
            'notes' => $pagination['data'],
            'totalNotes' => $pagination['totalNotes'],
            'pagRow' => $pagination['row'],
            'currentPage' => $page,
            'paginationSize' => $paginationSize,
            'formName' => 'admin_teachers',
        ]);
    }

    // Тесты
    #[Route('admin/tests/{page}', name: 'admin_tests')]
    function adminTests($page = 1) {
        $teachers = $this->em->getRepository(Teacher::class)->findAll();
        return $this->render('admin/tests.html.twig', [
            'tableData' => $teachers,
        ]);
    }




















}