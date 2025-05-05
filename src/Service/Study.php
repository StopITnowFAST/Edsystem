<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Student;
use App\Entity\Test;
use App\Entity\Group;
use App\Entity\Schedule;
use App\Entity\ScheduleLessonType;
use App\Entity\ScheduleSubject;
use App\Entity\ScheduleTime;
use App\Entity\Teacher;

class Study
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    // Получить тип пользоваля по ID (студент | преподаватель | без аккаунта)
    public function getUserType($userId) {
        $student = $this->em->getRepository(Student::class)->findOneBy(['user_id' => $userId]);
        if ($student)
            return 'student';
        $teacher = $this->em->getRepository(Teacher::class)->findOneBy(['user_id' => $userId]);
        return ($teacher) ? 'teacher' : 'hollow';
    }

    // Получить группу студента по ID пользователя
    public function getStudentGroup($userId) {
        $student = $this->em->getRepository(Student::class)->findOneBy(['user_id' => $userId]);
        return $student->getGroupId();
    }

    // Получить доступные тесты для пользователя
    public function getTestsForStudent($userId) {
        $student = $this->em->getRepository(Student::class)->findOneBy(['user_id' => $userId]);
        $groupId = $student->getGroupId();
        $conn = $this->em->getConnection();
        $sql = "
            SELECT t.*
            FROM `test` t
            WHERE t.student_groups LIKE '% $groupId,%' AND t.status = 1
        ";
        $resultSet = $conn->executeQuery($sql);
        $tests = $resultSet->fetchAllAssociative();
        return $tests;
    }

    // Получить количество доступных попыток для теста
    public function getAttemptsForTest($userId, $testId) {
        $totalAttempts = $this->em->getRepository(Test::class)->find($testId)->getAttempts();
        $conn = $this->em->getConnection();
        $sql = "
            SELECT DISTINCT tur.*
            FROM `test_user_result` tur
            WHERE tur.user_id = $userId
        ";
        $resultSet = $conn->executeQuery($sql);
        $attemtps = $resultSet->rowCount();
        return $totalAttempts - $attemtps;
    }

    // Получить текущую попытку для теста
    public function getCurrentAttemptForParams($userId, $testId) {
        $conn = $this->em->getConnection();
        $sql = "
            SELECT DISTINCT tur.*
            FROM `test_user_result` tur
            WHERE tur.user_id = $userId
        ";
        $resultSet = $conn->executeQuery($sql);
        return  $resultSet->rowCount();
    }    

    // Получить текущую попытку для теста
    public function getCurrentAttempt($userId, $testId) {
        $conn = $this->em->getConnection();
        $sql = "
            SELECT tp.* FROM `test_params` tp
            WHERE tp.user_id = $userId AND tp.test_id = $testId
        ";
        $resultSet = $conn->executeQuery($sql);
        return $resultSet->rowCount() - 1;
    }

    function getAllSubjectDates($group_id) {
        // 1. Получаем данные группы
        $group = $this->em->getRepository(Group::class)->find($group_id);
        if (!$group) {
            throw new \Exception("Группа не найдена");
        }
    
        // 2. Определяем даты начала и конца семестра
        $startDate = ($group->getSemester() % 2 == 1) 
            ? new \DateTime($group->getEdStartsSecond())
            : new \DateTime($group->getEdStartsFirst());
        
        if ($group->getSemester() % 2 == 1) {
            // Весенний семестр - заканчивается 1 июня
            $year = $startDate->format('m') > 6 ? $startDate->format('Y') + 1 : $startDate->format('Y');
            $endSemester = new \DateTime($year . '-06-01');
        } else {
            // Осенний семестр - заканчивается 31 декабря
            $endSemester = new \DateTime($startDate->format('Y') . '-12-31');
        }
    
        // 3. Получаем все записи расписания для группы
        $scheduleItems = $this->em->getRepository(Schedule::class)->findBy(
            ['schedule_group_id' => $group_id],
            ['week_number' => 'ASC', 'schedule_day' => 'ASC', 'schedule_time_id' => 'ASC']
        );
    
        // 4. Подготавливаем структуру для результатов
        $result = [];
        $currentWeek = 1;
        $currentDay = 1;
        $date = clone $startDate;
    
        // 5. Проходим по всем дням от начала до конца семестра
        while ($date <= $endSemester) {
            // Определяем номер недели (1 или 2)
            $weekNumber = ($currentWeek % 2) ? 1 : 2;
            
            // Ищем занятия на текущий день недели
            foreach ($scheduleItems as $item) {
                if ($item->getWeekNumber() == $weekNumber && 
                    $item->getScheduleDay() == $currentDay) {
                    
                    // Получаем данные предмета и типа занятия
                    $subject = $this->em->getRepository(ScheduleSubject::class)
                        ->find($item->getScheduleSubjectId())->getName();
                    
                    $lessonType = $this->em->getRepository(ScheduleLessonType::class)
                        ->find($item->getScheduleLessonTypeId())->getName();
                    
                    $time = $this->em->getRepository(ScheduleTime::class)
                        ->find($item->getScheduleTimeId());
    
                    // Формируем запись о паре
                    $lessonData = [
                        'date' => $date->format('Y-m-d'),
                        'day_of_week' => $this->getDayOfWeekName($currentDay),
                        'type' => $lessonType,
                        'week_number' => $weekNumber,
                        'time' => $time->getLessonNumber() . ' пара (' . 
                                  $time->getStartTime() . '-' . $time->getEndTime() . ')'
                    ];
    
                    // Добавляем в результат, группируя по предмету
                    if (!isset($result[$subject])) {
                        $result[$subject] = [];
                    }
                    $result[$subject][] = $lessonData;
                }
            }
    
            // Переходим к следующему дню
            $date->modify('+1 day');
            $currentDay++;
            
            // Если прошли неделю, сбрасываем день и увеличиваем счетчик недель
            if ($currentDay > 7) {
                $currentDay = 1;
                $currentWeek++;
            }
        }
    
        // Сортируем предметы по алфавиту
        ksort($result);
    
        return $result;
    }
    
    // Вспомогательная функция для получения названия дня недели
    private function getDayOfWeekName($dayNumber) {
        $days = [
            1 => 'Понедельник',
            2 => 'Вторник',
            3 => 'Среда', 
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота',
            7 => 'Воскресенье'
        ];
        return $days[$dayNumber] ?? '';
    }
}