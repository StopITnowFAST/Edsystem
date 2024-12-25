<?php

namespace App\Controller;

use App\Entity\Student;
use App\Entity\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;

class AccountsController extends AbstractController {

    private $em;
    function __construct(
        EntityManagerInterface $em,
    ) {
        $this->em = $em;     
    }

    // Учительская страница редактирования ученика
    #[Route(path: '/panel/accounts', name: 'panel_accounts')] 
    function panelAccounts(Request $request, SluggerInterface $slugger) {
        // Обрабатываем форму
        if ($request->isMethod('POST')) {
            $studentId = $_REQUEST['student'];
            $userId = $_REQUEST['user'];

            // Обновляем студенту user_id
            $student = $this->em->getRepository(Student::class)->find($studentId);
            if ($student && $userId) {
                $user = $this->em->getRepository(User::class)->find($userId);
                $student->setUserId($userId); // Присваиваем user объект пользователю
                $this->em->flush(); // Сохраняем изменения в базе данных
            }

            // Перезагружаем страницу с обновленными данными
            return $this->redirectToRoute('panel_accounts');
        }

        $users = $this->em->getRepository(User::class)->findAll();
        $students = $this->em->getRepository(Student::class)->findBy(['user_id' => null]);
        $studentsId = $this->em->getRepository(Student::class)->findAll();
        $userIds = [];
        foreach ($users as $user) {
            $userIds[] = $user->getId();
        }

        $studentUserIds = [];
        foreach ($studentsId as $student) {
            if ($student->getUserId()) {
                $studentUserIds[] = $student->getUserId();
            }
        }
        $uniqueUserIds = array_diff($userIds, $studentUserIds);
        $uniqueUsers = [];
        foreach ($uniqueUserIds as $id) {
            $user = $this->em->getRepository(User::class)->find($id);
            if ($user) {
                $uniqueUsers[] = $user;
            }
        }

        return $this->render('panel/accounts.html.twig', [
            'students' => $students,
            'uniqueUsers' => $uniqueUsers,
        ]);
    }
}