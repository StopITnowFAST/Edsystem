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

class StudentsController extends AbstractController {

    private $em;
    function __construct(
        EntityManagerInterface $em,
    ) {
        $this->em = $em;     
    }

    // Учительская страница редактирования ученика
    #[Route(path: '/panel/students', name: 'panel_students')] 
    function panelStudent(Request $request, SluggerInterface $slugger) {
        $studentsFromDB = $this->em->getRepository(Student::class)->findAll();
        $groupsFromDB = $this->em->getRepository(Group::class)->findAll();
        foreach($groupsFromDB as $group) {
            $groups[] = [
                'id' => $group->getId(),
                'name' => $group->getName(),
            ];
        }
        $students = [];            
        if (!empty($studentsFromDB)) {
            foreach ($studentsFromDB as $note) {
                $d1 = new DateTime(); 
                $d2 = new DateTime('@' . $note->getBirthdayDate()); 
                $students[] = [
                    'id' => $note->getId(),
                    'first_name' => $note->getFirstName(),
                    'last_name' => $note->getLastName(),
                    'middle_name' => $note->getMiddleName(),
                    'group_id' => $this->em->getRepository(Group::class)->find($note->getGroupId())->getName(),
                    'age' => $d2->diff($d1)->y,
                ];
            }
        }    
        return $this->render('panel/student.html.twig', [
            'students' => $students,
            'groups' => $groups,
        ]);
    }
    
    #[Route('/panel/students/add', name: 'add_student', methods: ['POST'])]
    function addStudent(Request $request, EntityManagerInterface $entityManager): Response {
        $firstName = $request->request->get('first_name');
        $middleName = $request->request->get('middle_name');
        $lastName = $request->request->get('last_name');
        $groupId = $request->request->get('group_id');
        $birthdayDate = $request->request->get('birthday_date');
         
        $student = new Student();
        $student->setFirstName($firstName);
        $student->setMiddleName($middleName);
        $student->setLastName($lastName);
        $student->setGroupId($groupId);
        $student->setBirthdayDate(strtotime($birthdayDate)); 
        $student->setStudentStatus(1);

        $entityManager->persist($student);
        $entityManager->flush();

        return $this->redirectToRoute('panel_students'); 
    }

    #[Route('/panel/students/delete/{id}', name: 'delete_student', methods: ['GET'])]
    function deleteStudent(Request $request, EntityManagerInterface $entityManager, $id): Response {
        $student = $entityManager->getRepository(Student::class)->find($id);

        if ($student) {
            $entityManager->remove($student);
            $entityManager->flush();
        } 

        // Перенаправляем на страницу списка студентов
        return $this->redirectToRoute('panel_students');
    }
}