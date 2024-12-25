<?php

namespace App\Controller;

use App\Entity\Subject;
use App\Entity\Schedule;
use App\Entity\Student;
use App\Entity\Grades;
use App\Entity\Group;
use App\Entity\Lesson;
use App\Entity\Day;

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

class GradesController extends AbstractController {

    private $em;
    function __construct(
        EntityManagerInterface $em,
    ) {
        $this->em = $em;     
    }

    
    #[Route(path: '/panel/grades', name: 'panel_grades')] 
    public function panelGrades(Request $request, SluggerInterface $slugger) {
        $groups = $this->em->getRepository(Group::class)->findAll();
        $groupId = $request->query->get('groupId');
        $studentId = $request->query->get('studentId');        
        $students = [];

        if ($groupId) {
            $students = $this->em->getRepository(Student::class)->findBy(['group_id' => $groupId]);
        }    
        $lessons = [];

        if ($groupId) {
            $schedule = $this->em->getRepository(Schedule::class)->findOneBy(['group_id' => $groupId]);            
            if ($schedule) {
                $days = $this->em->getRepository(Day::class)->findBy(['schedule_id' => $schedule]);                
                foreach ($days as $day) {
                    $dayLessons = $this->em->getRepository(Lesson::class)->findBy(['day_id' => $day]);                    
                    foreach ($dayLessons as $lesson) {
                        $lessons[] = $this->em->getRepository(Subject::class)->find($lesson->getSubjectId())->getName();
                    }
                    $lessons = array_unique($lessons);
                }
            }
        }

        if ($studentId) {
            $grades = [];
            $student = $this->em->getRepository(Student::class)->find($studentId);
            foreach ($lessons as $lessonName) {
                $subject = $this->em->getRepository(Subject::class)->findOneBy(['name' => $lessonName]);
                if ($subject) {
                    $gradesArray = $this->em->getRepository(Grades::class)->findBy([
                        'student_id' => $student->getId(),
                        'subject_id' => $subject->getId()
                    ]);
                    if ($gradesArray) {
                        $grades[$lessonName] = array_map(function($grade) {
                            return $grade->getGrade();
                        }, $gradesArray);
            
                        $average = array_sum($grades[$lessonName]) / count($grades[$lessonName]);
                        $averages[$lessonName] = round($average, 2); 
                    } else {
                        $grades[$lessonName] = [];
                        $averages[$lessonName] = null; 
                    }
                }
            }
        }

        $grade = new Grades();
        if ($request->isMethod('POST') && $_REQUEST['groupId'] && $_REQUEST['studentId'] && $_REQUEST['lessonName'] && $_REQUEST['grade']) {
            $lessonName = $_REQUEST['lessonName'];
            $selectedStudent = $this->em->getRepository(Student::class)->find($_REQUEST['studentId']);
            $selectedSchedule = $this->em->getRepository(Schedule::class)->findOneBy(['group_id' => $_REQUEST['groupId']]);
    
            if ($selectedStudent && $selectedSchedule) {
                $grade->setStudentId($selectedStudent->getId());
                $grade->setSubjectId($this->em->getRepository(Subject::class)->findOneBy(['name' => $lessonName])->getId());
                $grade->setGrade($request->request->get('grade'));
    
                $this->em->persist($grade);
                $this->em->flush();
    
                $this->addFlash('success', 'Оценка успешно добавлена!');
                return $this->redirectToRoute('panel_grades', [
                    'groupId' => $groupId,
                    'studentId' => $studentId
                ]);
            }
        }       
    
        return $this->render('panel/grades.html.twig', [
            'groups' => $groups,
            'students' => $students,
            'lessons' => $lessons,
            'groupId' => $groupId,
            'studentId' => $studentId,
            'user' => ($studentId != null) ? $this->em->getRepository(Student::class)->find($studentId) : null,
            'grades' => $grades ?? null,
            'averages' => $averages ?? null,
        ]);
    }

    #[Route(path: '/panel/subjects', name: 'panel_subjects')] 
    function panelSubjects(Request $request, SluggerInterface $slugger) {
        $subjects = $this->em->getRepository(Subject::class)->findAll();
        return $this->render('panel/subjects.html.twig', [
            'subjects' => $subjects,
        ]);

    }

    #[Route(path: '/panel/subjects/add', name: 'add_subject')] 
    function addSubjects(Request $request, SluggerInterface $slugger) {
        $subject = new Subject;
        $subject->setName($_REQUEST['subject_name']);
        $this->em->persist($subject);
        $this->em->flush();
        $subjects = $this->em->getRepository(Subject::class)->findAll();
        return $this->render('panel/subjects.html.twig', [
            'subjects' => $subjects,
        ]);
    }
}