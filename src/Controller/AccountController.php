<?php

namespace App\Controller;

use App\Entity\Lesson;
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

class AccountController extends AbstractController {

    private $security;
    private $em;
    function __construct(
        EntityManagerInterface $em,
        Security $security,
    ) {
        $this->em = $em;     
        $this->security = $security;
    }

    // Страница с аккаунтом пользователя
    #[Route(path: '/account', name: 'app_account')] 
    public function showSchedule(): Response {
        $user = $this->security->getUser();
        $user_id = $user ? $user->getId() : null;

        $student = $this->em->getRepository(Student::class)->findOneBy(['user_id' => $user_id]);

        $formattedUser['lastName'] = $student->getLastName();
        $formattedUser['firstName'] = $student->getFirstName();
        $formattedUser['middleName'] = $student->getMiddleName();
        $formattedUser['group']['name'] = $this->em->getRepository(Group::class)->find($student->getGroupId())->getName();
        
        $group_id = $student->getGroupId();
        $group = $this->em->getRepository(Group::class)->find($student->getGroupId())->getName();
        $schedule = $this->em->getRepository(Schedule::class)->findOneBy(['group_id' => $group_id]);
        if (!$schedule) {
            throw $this->createNotFoundException('Расписание не найдено для этой группы');
        }
        $days = $this->em->getRepository(Day::class)->findBy(['schedule_id' => $schedule->getId()], ['day_number' => 'ASC']);
        $lessonsByDay = [];
        foreach ($days as $day) {
            $lessonsByDay[$day->getId()] = $this->em->getRepository(Lesson::class)->findBy(['day_id' => $day->getId()]);
        }

        $lessons = [];
        if ($schedule) {
            $days = $this->em->getRepository(Day::class)->findBy(['schedule_id' => $schedule]);
            foreach ($days as $day) {
                $dayLessons = $this->em->getRepository(Lesson::class)->findBy(['day_id' => $day]);
                foreach ($dayLessons as $lesson) {
                    $lessons[] = $this->em->getRepository(Subject::class)->find($lesson->getSubjectId())->getName();
                }
            }
        }

        $lessons = array_unique($lessons);

        $grades = [];
        foreach ($lessons as $lessonName) {
            $subject = $this->em->getRepository(Subject::class)->findOneBy(['name' => $lessonName]);
            if ($subject) {
                // Получаем все оценки для данного студента и предмета
                $gradesArray = $this->em->getRepository(Grades::class)->findBy([
                    'student_id' => $student->getId(),
                    'subject_id' => $subject->getId()
                ]);
        
                // Сохраняем оценки в массив
                if ($gradesArray) {
                    // Извлекаем оценки
                    $grades[$lessonName] = array_map(function($grade) {
                        return $grade->getGrade();
                    }, $gradesArray);
        
                    // Рассчитываем средний балл
                    $average = array_sum($grades[$lessonName]) / count($grades[$lessonName]);
                    $averages[$lessonName] = round($average, 2); // Округляем до двух знаков после запятой
                } else {
                    $grades[$lessonName] = []; // Нет оценок для данного предмета
                    $averages[$lessonName] = null; // Нет среднего балла
                }
            }
        }
        
        $lessonsByIdFromDB = $this->em->getRepository(Subject::class)->findAll();
        foreach($lessonsByIdFromDB as $key => $note) {
            $lessonsById[$note->getId()] = $note->getName();
        }

        // print_r($lessonsById); die;

        return $this->render('account.html.twig', [
            'user' => $formattedUser,
            'group' => $group,
            'schedule' => $schedule,
            'days' => $days,
            'lessonsByDay' => $lessonsByDay,
            'lessons' => $lessons,
            'grades' => $grades,
            'averages' => $averages,
            'lessonsById' => $lessonsById,
        ]);
    }



    // <p><strong>Фамилия:</strong> {{ user.lastName }}</p>
    // <p><strong>Имя:</strong> {{ user.firstName }}</p>
    // <p><strong>Отчество:</strong> {{ user.middleName }}</p>
    // <p><strong>Группа:</strong> {{ user.group.name }}</p>

}