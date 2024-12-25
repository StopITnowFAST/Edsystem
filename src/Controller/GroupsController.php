<?php

namespace App\Controller;

use App\Entity\Student;
use App\Entity\Schedule;
use App\Entity\Day;
use App\Entity\Lesson;
use App\Entity\Group;

use DateTime;
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

class GroupsController extends AbstractController {

    private $em;
    function __construct(
        EntityManagerInterface $em,
    ) {
        $this->em = $em;     
    }

    // Учительская страница редактирования групп
    #[Route(path: '/panel/groups', name: 'panel_groups')] 
    function panelGroups(Request $request, SluggerInterface $slugger) {
        $groupsFromDB = $this->em->getRepository(Group::class)->findAll();
        $groups = [];
        foreach ($groupsFromDB as $group) {
            $studentsFromDB = $this->em->getRepository(Student::class)->findBy(['group_id' => $group->getId()]);
            
            // Инициализация массива студентов для каждой группы
            $students = [];
            
            foreach ($studentsFromDB as $student) {
                $students[] = [
                    'last_name' => $student->getLastName(),
                    'first_name' => $student->getFirstName(),
                    'group' => $group->getName(), // У нас уже есть объект группы, не нужно искать его повторно
                ];
            }
            
            $groups[] = [
                'id' => $group->getId(),
                'name' => $group->getName(),
                'code' => $group->getCode(),
                'description' => $group->getDescription(),
                'group_status' => $group->getGroupStatus(),
                'students' => $students, // Каждый раз присваиваем новый массив студентов
            ];
        }
    
        return $this->render('panel/groups.html.twig', [
            'groups' => $groups,
        ]);
    }
    

    #[Route('/panel/groups/add', name: 'add_group', methods: ['POST'])]
    function addGroup(Request $request): Response {
        $firstName = $request->request->get('name');
        $middleName = $request->request->get('code');
        $lastName = $request->request->get('description');       
        $group = new Group();
        $group->setName($firstName);
        $group->setCode($middleName);
        $group->setDescription($lastName);
        $group->setGroupStatus(1);
        $this->em->persist($group);
        $this->em->flush();        

        $schedule = new Schedule;
        $schedule->setGroupId($this->em->getRepository(Group::class)->findOneBy(['code' => $middleName])->getId());
        $schedule->setStatus(1);
        $this->em->persist($schedule);
        $this->em->flush();      

        return $this->redirectToRoute('panel_groups');
    }
}