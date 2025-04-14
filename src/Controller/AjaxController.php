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

class AjaxController extends AbstractController {

    private $em;
    function __construct(
        EntityManagerInterface $em,
    ) {
        $this->em = $em;
    }

    #[Route(path: '/request/change/role', name: 'request_change_role')] 
    function changeRole() {    
        $postJsonArray = json_decode(file_get_contents("php://input"), true);
        $user = $this->em->getRepository(User::class)->find($postJsonArray['id']);
        $roles = $user->getRoles();
        $key = array_search("ROLE_MODERATOR", $roles);        
        if ($key !== false) {
            unset($roles[$key]);
            $roles = array_values($roles);
        } 
        else {
            array_push($roles, "ROLE_MODERATOR");
        }
        $user->setRoles($roles);
        $this->em->persist($user);
        $this->em->flush();
        return $this->json([
            'ok' => true,
        ]);
    }
}