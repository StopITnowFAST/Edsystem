document.addEventListener('DOMContentLoaded', function() {
    // Добавление новой пары
    document.querySelectorAll('.add-lesson-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const container = this.closest('.schedule-day').querySelector('.lessons-container');
            const template = container.querySelector('.template');
            const newRow = template.cloneNode(true);
            
            newRow.style.display = 'flex';
            newRow.classList.remove('template');
            container.appendChild(newRow);
            
            // Добавляем обработчик удаления для новой строки
            newRow.querySelector('.remove-lesson-btn').addEventListener('click', function() {
                this.closest('.lesson-row').remove();
            });
        });
    });

    // Обработчики удаления для существующих строк
    document.querySelectorAll('.lesson-row:not(.template) .remove-lesson-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.lesson-row').remove();
        });
    });
    
    // Сохранение расписания
    document.getElementById('save-schedule').addEventListener('click', function() {
        const scheduleData = [];
        
        document.querySelectorAll('.schedule-day').forEach(dayCard => {
            const weekNumber = dayCard.dataset.week;
            const dayNumber = dayCard.dataset.day;
            
            dayCard.querySelectorAll('.lesson-row:not(.template)').forEach(lessonRow => {
                const timeId = lessonRow.querySelector('.time-select').value;
                const subjectId = lessonRow.querySelector('.subject-select').value;
                const typeId = lessonRow.querySelector('.type-select').value;
                const teacherId = lessonRow.querySelector('.teacher-select').value;
                const classroomId = lessonRow.querySelector('.classroom-select').value;
                
                if (timeId && subjectId && typeId && teacherId && classroomId) {
                    scheduleData.push({
                        week_number: weekNumber,
                        schedule_day: dayNumber,
                        schedule_time_id: timeId,
                        schedule_lesson_type_id: typeId,
                        schedule_classroom_id: classroomId,
                        user_id: teacherId,
                        subject_id: subjectId
                    });
                }
            });
        });
        
        // Отправка данных на сервер
        fetch('/request/save/schedule/' + GROUP_ID, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                schedule: scheduleData
            }),
        })
        .then(response => response.json())
        .then(data => {
            alert('Расписание успешно сохранено!');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Произошла ошибка при сохранении расписания');
        });
    });
    
    // Сброс изменений
    document.getElementById('reset-schedule').addEventListener('click', function() {
        if (confirm('Вы уверены, что хотите сбросить все изменения?')) {
            document.querySelectorAll('.lesson-row:not(.template)').forEach(row => {
                row.remove();
            });
        }
    });
});