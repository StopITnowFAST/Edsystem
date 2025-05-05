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
        $currentDate = new \DateTime();
        $startFirst = new \DateTime($group->getEdStartsFirst());
        $startSecond = $group->getEdStartsSecond() ? new \DateTime($group->getEdStartsSecond()) : null;
    
        // Определяем текущий семестр
        if ($currentDate >= $startFirst && ($startSecond === null || $currentDate < $startSecond)) {
            $semesterStart = clone $startFirst;
            $semesterEnd = $startSecond ? (clone $startSecond)->modify('-1 day') : new \DateTime($startFirst->format('Y') . '-12-31');
        } else if ($startSecond !== null && $currentDate >= $startSecond) {
            $semesterStart = clone $startSecond;
            $semesterEnd = new \DateTime($startSecond->format('Y') . '-06-01');
        } else {
            throw new \Exception("Не удалось определить текущий семестр");
        }
    
        // 3. Получаем все записи расписания для группы
        $scheduleItems = $this->em->getRepository(Schedule::class)->findBy(
            ['schedule_group_id' => $group_id],
            ['week_number' => 'ASC', 'schedule_day' => 'ASC', 'schedule_time_id' => 'ASC']
        );
    
        // 4. Подготавливаем структуру для результатов
        $result = [];
        $currentDate = clone $semesterStart;
        $endDate = clone $semesterEnd;
    
        // 5. Проходим по всем дням от начала до конца семестра
        while ($currentDate <= $endDate) {
            // Пропускаем воскресенья (день 7)
            if ($currentDate->format('N') == 7) {
                $currentDate->modify('+1 day');
                continue;
            }
    
            // Определяем номер недели (1 или 2)
            $daysDiff = $currentDate->diff($semesterStart)->days;
            $currentWeekNumber = (floor($daysDiff / 7) % 2) + 1;
    
            // Получаем номер дня недели (1-6, понедельник-суббота)
            $dayOfWeek = $currentDate->format('N'); // 1-7 (пн-вс)
    
            // Ищем занятия на текущий день недели
            foreach ($scheduleItems as $item) {
                if ($item->getWeekNumber() == $currentWeekNumber && 
                    $item->getScheduleDay() == $dayOfWeek) {
                    
                    // Получаем данные предмета и типа занятия
                    $subject = $this->em->getRepository(ScheduleSubject::class)
                        ->find($item->getScheduleSubjectId())->getName();
                    
                    $lessonType = $this->em->getRepository(ScheduleLessonType::class)
                        ->find($item->getScheduleLessonTypeId())->getName();
                    
                    $time = $this->em->getRepository(ScheduleTime::class)
                        ->find($item->getScheduleTimeId());
    
                    // Формируем запись о паре
                    $lessonData = [
                        'date' => $currentDate->format('Y-m-d'),
                        'day_of_week' => $this->getDayOfWeekName($dayOfWeek),
                        'type' => $lessonType,
                        'week_number' => $currentWeekNumber,
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
            $currentDate->modify('+1 day');
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

    function getTeacherSubjectsDates($userId) {
        // 1. Получаем преподавателя
        $teacher = $this->em->getRepository(Teacher::class)->findOneBy(['user_id' => $userId]);
        if (!$teacher) {
            throw new \Exception("Преподаватель не найден");
        }
    
        // 2. Получаем все записи расписания для этого преподавателя
        $scheduleItems = $this->em->getRepository(Schedule::class)->findBy(
            ['user_id' => $userId],
            ['schedule_group_id' => 'ASC', 'week_number' => 'ASC', 'schedule_day' => 'ASC', 'schedule_time_id' => 'ASC']
        );
    
        // 3. Группируем записи по предметам и группам
        $groupedItems = [];
        foreach ($scheduleItems as $item) {
            $subject = $this->em->getRepository(ScheduleSubject::class)
                ->find($item->getScheduleSubjectId())->getName();
            
            $group = $this->em->getRepository(Group::class)
                ->find($item->getScheduleGroupId());
            
            $key = $subject . '_' . $item->getScheduleGroupId();
            
            if (!isset($groupedItems[$key])) {
                $groupedItems[$key] = [
                    'subject' => $subject,
                    'group' => $group,
                    'items' => []
                ];
            }
            
            $groupedItems[$key]['items'][] = $item;
        }
    
        // 4. Подготавливаем структуру для результатов
        $result = [];
        $currentDate = new \DateTime();
    
        foreach ($groupedItems as $groupedItem) {
            $subject = $groupedItem['subject'];
            $group = $groupedItem['group'];
            $items = $groupedItem['items'];
    
            // Определяем даты начала и конца семестра для группы
            $startFirst = new \DateTime($group->getEdStartsFirst());
            $startSecond = $group->getEdStartsSecond() ? new \DateTime($group->getEdStartsSecond()) : null;
    
            // Определяем текущий семестр для группы
            if ($currentDate >= $startFirst && ($startSecond === null || $currentDate < $startSecond)) {
                $semesterStart = clone $startFirst;
                $semesterEnd = $startSecond ? (clone $startSecond)->modify('-1 day') : new \DateTime($startFirst->format('Y') . '-12-31');
            } else if ($startSecond !== null && $currentDate >= $startSecond) {
                $semesterStart = clone $startSecond;
                $semesterEnd = new \DateTime($startSecond->format('Y') . '-06-01');
            } else {
                continue; // Пропускаем если семестр не определен
            }
    
            // Проходим по всем дням семестра
            $date = clone $semesterStart;
            $endDate = clone $semesterEnd;
    
            while ($date <= $endDate) {
                // Пропускаем воскресенья
                if ($date->format('N') == 7) {
                    $date->modify('+1 day');
                    continue;
                }
    
                // Определяем номер недели (1 или 2)
                $daysDiff = $date->diff($semesterStart)->days;
                $currentWeekNumber = (floor($daysDiff / 7) % 2) + 1;
    
                // Получаем номер дня недели (1-6, понедельник-суббота)
                $dayOfWeek = $date->format('N');
    
                // Ищем занятия преподавателя на текущий день недели
                foreach ($items as $item) {
                    if ($item->getWeekNumber() == $currentWeekNumber && 
                        $item->getScheduleDay() == $dayOfWeek) {
                        
                        $lessonType = $this->em->getRepository(ScheduleLessonType::class)
                            ->find($item->getScheduleLessonTypeId())->getName();
                        
                        $time = $this->em->getRepository(ScheduleTime::class)
                            ->find($item->getScheduleTimeId());
    
                        // Формируем запись о паре
                        $lessonData = [
                            'date' => $date->format('Y-m-d'),
                            'day_of_week' => $this->getDayOfWeekName($dayOfWeek),
                            'type' => $lessonType,
                            'week_number' => $currentWeekNumber,
                            'time' => $time->getLessonNumber() . ' пара (' . 
                                      $time->getStartTime() . '-' . $time->getEndTime() . ')',
                            'group' => $group->getName(),
                            'group_id' => $group->getId()
                        ];
    
                        // Добавляем в результат, группируя по предмету
                        if (!isset($result[$subject])) {
                            $result[$subject] = [];
                        }
                        $result[$subject][] = $lessonData;
                    }
                }
    
                $date->modify('+1 day');
            }
        }
    
        // Сортируем предметы по алфавиту и датам
        ksort($result);
        foreach ($result as &$lessons) {
            usort($lessons, function($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });
        }
    
        return $result;
    }
}