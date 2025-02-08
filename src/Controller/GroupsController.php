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

class GroupsController extends AbstractController {

    private $security;
    private $em;
    function __construct(
        EntityManagerInterface $em,
        Security $security,
    ) {
        $this->em = $em;     
        $this->security = $security;
    }

    #[Route(path: '/admin/groups/{page}', name: 'admin_groups')] 
    function adminGroups($page = 1) {
        $teachers = $this->em->getRepository(Teacher::class)->findAll();
        

        
        return $this->render('admin/groups.html.twig', [
            'tableData' => $teachers,
        ]);
    }
}