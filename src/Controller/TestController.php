<?php

namespace App\Controller;

use App\Entity\UserCard;
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

class TestController extends AbstractController {

    const PAGINATION_SIZE = 40;

    function __construct(
        private EntityManagerInterface $em, 
        private TableWidget $table,
        private File $file,
    ) {
    }  

    // Добавление вопросов для теста
    #[Route('/admin/tests', name: 'admin_tests_redact')]
    function adminTestsRedact() {
        $tests = $this->em->getRepository(Test::class)->findAll();
        return $this->render('admin/tests.html.twig', [
            'notes' => $tests,
        ]);
    }


    // Назначение групп для теста
    #[Route('/admin/tests', name: 'admin_tests_appoint')]
    function adminTestsAppoint() {
        $tests = $this->em->getRepository(Test::class)->findAll();
        return $this->render('admin/tests.html.twig', [
            'notes' => $tests,
        ]);
    }
}
