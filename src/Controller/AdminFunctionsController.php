<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Schedule;
use App\Entity\Student;
use App\Entity\Day;
use App\Entity\Group;
use App\Entity\Subject;
use App\Entity\Grades;
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

class AdminFunctionsController extends AbstractController {

    private $em;
    function __construct(
        EntityManagerInterface $em,
    ) {
        $this->em = $em;
    }

    #[Route(path: '/admin/students/create', name: 'admin_create_teacher')] 
    function adminStudentsCreate() {    
        
        return $this->redirectToRoute('admin_teachers');
    }
}