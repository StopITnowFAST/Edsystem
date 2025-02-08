<?php

namespace App\Controller;

use App\Entity\Student;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\RegistrationFormType;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;

class StudentsController extends AbstractController {

    private $em;
    function __construct(
        EntityManagerInterface $em,
    ) {
        $this->em = $em;     
    }

    #[Route('admin/students/{page}', name: 'admin_students')]
    public function adminStudents($page = 1) {
        $students = $this->em->getRepository(Student::class)->findAll();
        

        
        return $this->render('admin/students.html.twig', [
            'tableData' => $students,
        ]);
    }
}