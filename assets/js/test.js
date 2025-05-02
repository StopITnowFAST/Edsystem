const timer = document.getElementById("timer");

// Запускаем таймер
updateTimer();

// Проставляем ответы
setLastUserAnswer();

// Функция для отправки ответов
function submitSelectedAnswers() {
    const selectedAnswers = document.querySelectorAll('.answer-option.selected');

    console.log(document.querySelector('.options-list').action);
    
    const form = document.createElement('form');
    form.action = '/tests/' + TEST_ID + '/answer/' + CURRENT_QUESTION;
    form.method = 'POST';
    form.style.display = 'none'; 
        
    selectedAnswers.forEach(answer => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'selected_answers[]';
      input.value = answer.id.replace('answer-', '');
      form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
  }

// Функция для выбора варианта ответа
function selectAnswer(index) {
    if (QUESTION_TYPE == 'one') {
        document.querySelectorAll('.answer-option').forEach(element => {
            element.classList.remove('selected');
        });
        clickedElement = document.getElementById("answer-" + index);
        clickedElement.classList.add("selected");        
    } else if (QUESTION_TYPE == 'more') {
        clickedElement = document.getElementById("answer-" + index);
        if (!(clickedElement.classList.contains('selected'))) {
            clickedElement.classList.add("selected");
        } else {
            clickedElement.classList.remove("selected");
        }
    }
}

// Функция для форматирования времени в ЧЧ:ММ:СС
function formatTime(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    return [
        hours.toString().padStart(2, '0'),
        minutes.toString().padStart(2, '0'),
        secs.toString().padStart(2, '0')
    ].join(':');
}
  
// Функция обновления таймера
function updateTimer() {
    timer.textContent = "⏱️ " + formatTime(leftTime);

    if (leftTime > 0) {
        leftTime--;
        setTimeout(updateTimer, 1000); 
    } else {
        alert('Время на прохождение теста закончилось!');
        window.location.href = `/tests/${TEST_ID}/final`;
        clearTimeout(timer);
    }
}

function setLastUserAnswer() {
    console.log(typeof(LAST_ANSWERS));
    LAST_ANSWERS.forEach(id => {
        const elementId = 'answer-' + id;
        const answerElement = document.getElementById(elementId);
        
        if (answerElement) {
            answerElement.classList.add('selected');
        }
    });
}
  