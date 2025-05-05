const SCHEDULE_SECTION = 'schedule';
const CHAT_SECTION = 'chat';
const SUBJECT_SECTION = 'subjects';
const PROFILE_SECTION = 'profile';
const TEST_SECTION = 'tests';

const SCHEDULE_SECTION_URL = '/request/get/user/' + SCHEDULE_SECTION + '/';
const CHAT_SECTION_URL = '/request/get/user/' + CHAT_SECTION + '/';
const MESSAGES_URL = '/request/get/user/messages/' + CHAT_SECTION + '/';
const SEND_MESSAGE_URL = '/request/send/user/message/' + CHAT_SECTION + '/';
const GET_UPDATES_URL = '/request/get/user/updates/' + CHAT_SECTION + '/';
const SUBJECT_SECTION_URL = '/request/get/user/' + SUBJECT_SECTION + '/';
const PROFILE_SECTION_ULR = '/request/get/user/' + PROFILE_SECTION + '/';
const TEST_SECTION_ULR = '/request/get/user/' + TEST_SECTION + '/';

const CONTENT_CONTAINER = document.getElementById('content');
const POLL_DELAY = 1000;

let colors = ['#4a6fa5', '#5cb85c', '#5bc0de', '#f0ad4e', '#d9534f'];
let isPolling = false;
let isDialogActive = false;
let currentGroup = 'student';
let currentDialog = 0;

let currentSubject = null;
let subjectScheduleData = {};

startPolling();

document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.sidebar-button');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            buttons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            const section = this.getAttribute('title').toLowerCase();
            loadContent(section);
            isDialogActive = false;
        });
    });

    button = document.getElementById(STARTING_SECTION);
    button.classList.add('active');
    loadContent(STARTING_SECTION);
});

async function loadContent(section) {
    loadPlaceHolder();
    switch(section) {
        case SCHEDULE_SECTION:
            json = await getDataFromServer(SCHEDULE_SECTION_URL);
            const schedulePage = getSchedulePage(json.schedule, START_FIRST, START_SECOND);
            CONTENT_CONTAINER.innerHTML = schedulePage.html;
            schedulePage.init();
            break;
        case CHAT_SECTION:
            json = await getDataFromServer(CHAT_SECTION_URL);
            globalChatArray = json.data;
            content = getChatPage(globalChatArray);
            renderContent(content);   
            break;
        case SUBJECT_SECTION:
            json = await getDataFromServer(SUBJECT_SECTION_URL);
            content = getSubjectPage(json.data);
            renderContent(content);   
            break;
        case PROFILE_SECTION:
            // pageData = getDataFromServer(PROFILE_SECTION_ULR);
            content = '';
            renderContent(content);   
            break;
        case TEST_SECTION:
            json = await getDataFromServer(TEST_SECTION_ULR);
            content = getTestsPage(json.data);
            renderContent(content);   
            break;
    } 
}

function loadPlaceHolder() {
    CONTENT_CONTAINER.innerHTML = getPlaceholder();
}

function renderContent(content) {
    CONTENT_CONTAINER.innerHTML = content;
}

function getDataFromServer(url) {
    const fetchOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'include'
    };
    return fetch(url + USER_ID, fetchOptions)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }            
            const contentType = response.headers.get('content-type');            
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            }
            return response.text();
        })
        .catch(error => {
            console.error('Error in getDataFromServer:', error);
            throw error;
        });
}

// --------------
// БЛОК С ТЕСТАМИ
// --------------

function getTestsPage(testsData) {
    if (!testsData || !testsData.length) {
        return `
            <div class="no-tests-message">
                <i class="fas fa-info-circle"></i>
                <p>Нет доступных тестов</p>
            </div>
        `;
    }

    return `
        <h2 class="tests-title">Тестирование</h2>
        <div class="tests-container">
            <div class="tests-list">
                ${testsData.map(test => renderTestCard(test)).join('')}
            </div>
        </div>
    `;
}

function renderTestCard(test) {
    // Упрощенный статус: только пройдено/не пройдено
    const isCompleted = test.grade > 2;
    const statusClass = isCompleted ? 'completed' : 'not-completed';
    const statusIcon = isCompleted ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-circle"></i>';

    // Время для теста (только для непройденных)
    const timeInfo = !isCompleted && test.timeLimit ? `
        <div class="info-item">
            <i class="fas fa-clock"></i>
            <span style="color: var(--color-font-bright);">Время на тест: ${test.timeLimit}:00</span>
        </div>
    ` : '';

    return `
        <div class="test-card ${statusClass}" data-test-id="${test.id}">
            <div class="test-header">
                <h3 class="test-title">${test.title}</h3>
                <span class="test-status ${statusClass}">
                    ${statusIcon}
                    ${isCompleted ? 'Пройден' : 'Не пройден'}
                </span>
            </div>
            
            <div class="test-body">
                <div class="test-info">
                    ${timeInfo}
                </div>
                
                <div class="test-stats">
                    <div class="stat-item">
                        <span class="stat-label">Лучшая оценка:</span>
                        <span class="stat-value ${test.grade ? '' : 'no-grade'}">
                            ${test.grade || '---'}
                        </span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Вопросов:</span>
                        <span class="stat-value">${test.questionsCount}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Попыток:</span>
                        <span class="stat-value ${test.attemptsLeft > 0 ? '' : 'no-attempts'}">
                            ${test.attemptsLeft}
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="test-footer">
                ${renderTestButton(test)}
            </div>
        </div>
    `;
}

function renderTestButton(test) {
    const isCompleted = test.status === 'completed';
    
    if (isCompleted) {
        return `<button class="btn completed-btn" disabled>Тест пройден</button>`;
    }
    
    if (test.attemptsLeft > 0) {
        return `<a href="/tests/preview/${test.id}" class="btn start-btn">Открыть тест</a>`;
    }
    
    return `<button class="btn disabled-btn" disabled>Нет попыток</button>`;
}

// --------------
// БЛОК С ЧАТАМИ
// --------------

function getChatPage(chatData) {
    
    // Генерируем HTML для списка чатов студентов
    const studentsChats = generateChatListHTML(chatData.student, 'student');
    
    // Генерируем HTML для списка чатов преподавателей
    const teachersChats = generateChatListHTML(chatData.teacher, 'teacher');

    return `
        <div class="chat-container">
            <!-- 1. Панель групп (студенты/преподаватели) -->
            <div class="chat-group-panel">
                <div id="students_group" class="chat-group active" data-group="students" onclick="changePlatform('student')">
                    <i class="fas fa-user-graduate"></i>
                    <span>Студенты</span>
                </div>
                <div id="teachers_group" class="chat-group" data-group="teachers" onclick="changePlatform('teacher')">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Преподаватели</span>
                </div>
            </div>

            <!-- 2. Панель списка чатов -->
            <div id="chat_list" class="chat-list-panel">
                <div class="chat-list" id="chat-list">
                    <!-- Секция чатов студентов -->
                    <div id="chat_list_students" class="chat-group-section" data-group-section="students">
                        ${studentsChats || `
                            <div class="chat-list-placeholder">
                                <i class="fas fa-comment-slash"></i>
                                <p>Нет чатов с студентами</p>
                            </div>
                        `}
                    </div>
                    
                    <!-- Секция чатов преподавателей (изначально скрыта) -->
                    <div id="chat_list_teachers" class="chat-group-section" data-group-section="teachers" style="display:none">
                        ${teachersChats || `
                            <div class="chat-list-placeholder">
                                <i class="fas fa-comment-slash"></i>
                                <p>Нет чатов с преподавателями</p>
                            </div>
                        `}
                    </div>
                </div>
            </div>

            <!-- 3. Панель сообщений -->
            <div id="message-panel" class="chat-messages-panel">
                <div class="chat-header">
                    <div class="chat-info">
                        <h3 id="current-chat-name">Выберите чат</h3>
                    </div>
                </div>
                <div class="chat-messages" id="chat-messages">
                    <div class="empty-chat">
                        <i class="fas fa-comments"></i>
                        <p>Выберите чат, чтобы начать общение</p>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Вспомогательная функция для генерации списка чатов
function generateChatListHTML(users, type) {
    if (!users || Object.keys(users).length === 0) return null;

    // Преобразуем в массив и сортируем по дате (новые → выше)
    const sortedUsers = Object.values(users).sort((a, b) => {
        // Если у одного нет даты, помещаем его в конец
        if (!a.last_message_date) return 1;
        if (!b.last_message_date) return -1;
        return b.last_message_date - a.last_message_date;
    });

    return sortedUsers.map(user => {
        const lastMessage = (user.last_message_text || user.file_name)
            ? `<p class="last-message">${truncateText((user.file_name) ? user.file_name : user.last_message_text, 30)}</p>`
            : '<p class="last-message no-messages">Нет сообщений</p>';
            
        const lastDate = user.last_message_date 
            ? `<span class="last-message-date">${formatMessageTime(user.last_message_date)}</span>`
            : '';
        
        let userName = (user.user_id == USER_ID) ? 'Избранное' : `${user.last_name} ${user.first_name}`;
        
        let color = colors[user.user_id % colors.length];

        return `
            <div class="chat-item" onclick="renderDialog(${user.user_id})" data-user-id="${user.user_id}" data-user-type="${type}">
                <div style="background-color: ${color}" class="chat-avatar">${getInitials(user.first_name, user.last_name, user.user_id)}</div>
                <div class="chat-info">
                    <h4>${userName}</h4>
                    ${lastMessage}
                </div>
                ${lastDate}
                ${user.unread_count > 0 ? `<span class="unread-count">${user.unread_count}</span>` : ''}
            </div>
        `;
    }).join('');
}

// Вспомогательные функции
function truncateText(text, maxLength) {
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function getInitials(firstName, lastName, id) {
    if (id == USER_ID) {
        return 'И';
    }
    return (lastName.charAt(0) + firstName.charAt(0)).toUpperCase();
}

// Функция для начала получения обновлений
function startPolling() {
    if (isPolling) return;    
    isPolling = true;
    poll();
}

// Функция для остановки получения обновлений
function stopPolling() {
    isPolling = false;
}

// Функция для смены платформы с чатом
function changePlatform(id) {
    currentGroup = id;
    let students_chat_list = document.getElementById("chat_list_students");
    let teachers_chat_list = document.getElementById("chat_list_teachers");
    let students_group = document.getElementById("students_group");
    let teachers_group = document.getElementById("teachers_group");
    if (id == 'student') {
        // Открытие группы студентов
        students_chat_list.style.display = "block";
        teachers_chat_list.style.display = "none";
        students_group.classList.add("active");
        teachers_group.classList.remove("active");
    } else if (id == 'teacher') {
        // Открытие группы преподавателей
        students_chat_list.style.display = "none";
        teachers_chat_list.style.display = "block";
        teachers_group.classList.add("active");
        students_group.classList.remove("active");
    }
}

// Функция отображает выбранный пользователем диалог
async function renderDialog(userId) {
    currentDialog = userId;
    
    // 1. Помечаем сообщения как прочитанные
    try {
        await fetch(`/request/mark-messages-read/chat/${userId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
    } catch (e) {
        console.error('Ошибка пометки сообщений как прочитанных:', e);
    }
    
    // 2. Продолжаем стандартную логику отображения
    document.getElementById("chat-messages").innerHTML = '';
    
    if (!isDialogActive) {
        document.getElementById("message-panel").insertAdjacentHTML('beforeend', getInputElement());
        document.getElementById('file-upload').addEventListener('change', function(e) {
            const previewContainer = document.getElementById('file-preview');
            previewContainer.innerHTML = '';
            if (this.files.length > 0) {
                const fileName = this.files[0].name;
                const fileNameElement = document.createElement('div');
                fileNameElement.className = 'file-name-only';
                fileNameElement.textContent = fileName;
                previewContainer.appendChild(fileNameElement);
            }
        });
        document.getElementById("text-input").addEventListener("keydown", (e) => {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        isDialogActive = true;
    }
    
    let dialogName = (userId == USER_ID) ? 'Избранное' : globalChatArray[currentGroup][userId].last_name + " " + globalChatArray[currentGroup][userId].first_name;
    document.getElementById("current-chat-name").textContent = dialogName;
    processMessages(globalMessageArray[userId]);
}

// Функция закрывает диалог с пользователем
function closeDialog() {
    messageBox = document.getElementById("chat-messages");
    messageBox.innerHTML = `
        <div class="empty-chat">
        <i class="fas fa-comments"></i>
        <p>Выберите чат, чтобы начать общение</p>
        </div>
    `;
    isDialogActive = false;
}

// Функция для обработки сообщений для диалога
function processMessages(messages) {
    if (messages && isDialogActive) {
        messageBox = document.getElementById("chat-messages");
        
        messages.forEach(message => {
            const msgElement = makeMessageElement(message);
            messageBox.insertAdjacentHTML('beforeend', msgElement);
            messageBox.scrollTop = messageBox.scrollHeight;
            // lastId = message['id']        
        });
    }
}

// Функция для создания элемента сообщения с временем
function makeMessageElement(message) {
    const divClass = message['type'] === 'incoming' ? "incoming-message" : "outcoming-message";
    let contentParts = [];
    let hasText = false;
    
    // Добавляем текст, если он есть
    if (message['text'] && message['text'].trim() !== '') {
        hasText = true;
        const safeText = message['text'].replace(/</g, "&lt;").replace(/>/g, "&gt;");
        contentParts.push(`<div class="message-text">${safeText}</div>`);
    }
    
    // Добавляем файл, если он есть
    if (message['filelink']) {
        if (hasText) {
            contentParts.push('<br>');
        }
        const fileName = message['file_name'] || 'Файл';
        contentParts.push(`
            <div class="message-file">
                <a href="${message['filelink']}" class="file-message" download>
                    <i class="fas fa-file-download"></i>
                    ${fileName}
                </a>
            </div>
        `);
    }
    
    // Добавляем время отправки (если есть)
    if (message['date']) {
        const formattedTime = formatMessageTime(message['date']);
        contentParts.push(`<div class="message-time">${formattedTime}</div>`);
    }
    
    // Объединяем все части
    const content = contentParts.join('');
    
    return `
        <div message_id="${message['id']}" class="${divClass}">
            ${content}
        </div>
    `;
}

// Вспомогательная функция для форматирования времени
function formatMessageTime(timestamp) {
    try {
        // Пробуем разные форматы timestamp
        const date = new Date(isNaN(timestamp) ? timestamp : timestamp * 1000);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } catch (e) {
        console.error('Ошибка форматирования времени:', e);
        return '';
    }
}

// Функция для отправки сообщения с поддержкой файлов
async function sendMessage() {
    const messageInput = document.getElementById("text-input");
    const fileInput = document.getElementById("file-upload");
    const text = messageInput.value.trim();
    const files = fileInput.files;
    
    // Не отправляем если нет ни текста ни файлов
    if (!text && files.length === 0) return;

    try {
        // Создаем FormData вместо JSON
        const formData = new FormData();
        formData.append('text', text);
        formData.append('from_id', USER_ID);
        formData.append('to_id', currentDialog);
        
        // Добавляем все выбранные файлы
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }

        const response = await fetch(`${SEND_MESSAGE_URL}${USER_ID}`, {
            method: 'POST',
            body: formData
            // Не устанавливаем Content-Type вручную - браузер сам установит с boundary
        });
        
        if (!response.ok) throw new Error("Ошибка HTTP: " + response.status);
        
        // Очищаем поля ввода после успешной отправки
        messageInput.value = '';
        fileInput.value = '';
        document.getElementById('file-preview').innerHTML = ''; // Очищаем превью файлов
        messageInput.focus();
        
    } catch (error) {
        console.error("Ошибка отправки:", error);
        alert("Не удалось отправить сообщение. Проверьте консоль.");
    }
}

// Функция для получения обновлений
async function poll() {    
    try {
        let userUpdates = formUpdateQueryArray();
        const response = await fetch(GET_UPDATES_URL + USER_ID, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(userUpdates)
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            updateTotalMessages(data.newMessages);
            if (data.newMessages[currentDialog]) {
                processMessages(data.newMessages[currentDialog]);
            }
        }

        setTimeout(() => poll(), POLL_DELAY);

    } catch (error) {
        console.error('Polling error:', error);
        // Повторяем запрос после ошибки
        setTimeout(() => poll(), POLL_DELAY * 2);
    }    
}

// Функция формирует массив с id последних сообщения для всех чатов
function formUpdateQueryArray() {
    let update = new FormData();
    for (chatId in globalChatArray) {
        for (user in globalChatArray[chatId]) {
            update[user] = 0;
        }
    }
    for (chatId in globalMessageArray) {
        update[chatId] = globalMessageArray[chatId][globalMessageArray[chatId].length - 1].id
    }
    return update;
}

// Функция обновляет массив с сообщениями
async function updateTotalMessages(newMessages) {
    for (userId in newMessages) {
        let messages = newMessages[userId];
        messages.forEach(message => {
            if(!(userId in globalMessageArray)) {
                globalMessageArray[userId] = [];
            }
            globalMessageArray[userId].push(message);
            updateChats(userId, message);
        });
    }
}

function updateChats(id, message) {
    let chatContainer = 0;
    if (id in globalChatArray['student']) {
        chatContainer = document.getElementById('chat_list_students');
    } else if (id in globalChatArray['teacher']) {
        chatContainer = document.getElementById('chat_list_teachers');
    }

    let chatWithNewMessage = document.querySelector('.chat-item[data-user-id="' + id + '"]');    
    
    chatWithNewMessage.querySelector('.last-message').textContent = (message.filelink) ? message.file_name : message.text;
    chatWithNewMessage.remove();
    chatContainer.insertBefore(chatWithNewMessage, chatContainer.firstChild);
}

function getInputElement() {
    return `
        <div class="file-preview" id="file-preview"></div>
        <div class="chat-input">
            <div class="file-upload-container">
                <label for="file-upload" class="file-upload-button">
                    <i class="fas fa-paperclip"></i>
                    <input type="file" id="file-upload" 
                        accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.zip,.rar"
                        style="display: none;">
                </label>
            </div>
            <textarea id="text-input" placeholder="Введите сообщение..."></textarea>
            <button class="send-button" onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    `;
}

// Основная функция для генерации страницы расписания
function getSchedulePage(scheduleData, START_FIRST, START_SECOND) {
    // Группируем данные по неделям и дням
    const groupedData = groupScheduleData(scheduleData);
    
    const html = `
        <div class="schedule-container">
            <div class="schedule-header">
                <h1 class="schedule-title">Мое расписание</h1>
                <div class="schedule-period"></div> 
            </div>
            
            <div class="schedule-weeks">
                ${renderWeek(1, groupedData[1])}
                ${renderWeek(2, groupedData[2])}
            </div>
        </div>
    `;
    
    // Возвращаем HTML и функции для последующей инициализации
    return {
        html: html,
        init: function() {
            setupScheduleWithDates(START_FIRST, START_SECOND);
            // Обновляем каждую минуту для актуальной подсветки
            setInterval(() => setupScheduleWithDates(START_FIRST, START_SECOND), 60000);
        }
    };
}

function groupScheduleData(data) {
    const result = {1: {}, 2: {}};
    const dayNames = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'];
    
    // Инициализация структуры
    for (let week = 1; week <= 2; week++) {
        for (let day = 1; day <= 6; day++) {
            result[week][day] = {
                dayName: dayNames[day - 1],
                lessons: []
            };
        }
    }
    
    // Заполнение данными
    data.forEach(item => {
        result[item.week_number][item.schedule_day].lessons.push(item);
    });
    
    return result;
}

function renderWeek(weekNum, weekData) {
    return `
        <div class="week-section" data-week="${weekNum}">
            <h2 class="week-title">${weekNum}</h2>
            <span class="week-subtitle">НЕДЕЛЯ</span>
            
            <div class="schedule-days">
                ${Object.entries(weekData).map(([dayNum, dayData]) => 
                    renderDay(dayNum, dayData)
                ).join('')}
            </div>
        </div>
    `;
}

function renderDay(dayNum, dayData) {
    return `
        <div class="schedule-day" data-day="${dayNum}">
            <div class="day-header">
                <h3 class="day-title">${dayData.dayName}</h3>
                <div class="day-date"></div>
            </div>
            
            <div class="day-lessons">
                ${dayData.lessons.length > 0 
                    ? dayData.lessons.map(lesson => renderLesson(lesson)).join('')
                    : '<div class="no-lessons">Нет занятий</div>'
                }
            </div>
        </div>
    `;
}

function renderLesson(lesson) {
    return `
        <div class="lesson-card">
            <div class="lesson-time">
                <span class="lesson-number">${lesson.lesson_number} пара</span>
                <span class="lesson-time-range">${lesson.start_time} - ${lesson.end_time}</span>
            </div>
            
            <div class="lesson-main">
                <div class="lesson-subject">${lesson.subject}</div>
                <div class="lesson-type">${lesson.lesson_type}</div>
            </div>
            
            <div class="lesson-details">
                <div class="lesson-classroom">
                    <i class="fas fa-door-open"></i>
                    ${lesson.classroom}
                </div>
                <div class="lesson-teacher">
                    <i class="fas fa-chalkboard-teacher"></i>
                    ${lesson.last_name} ${lesson.first_name}
                </div>
            </div>
        </div>
    `;
}

// Функции для работы с датами и подсветкой
function calculateAcademicPeriods(START_FIRST, START_SECOND) {
    const now = new Date();
    now.setHours(0, 0, 0, 0); // Убираем время для точного сравнения дат
    
    // Определяем текущее полугодие (1 или 2)
    const startFirst = new Date(START_FIRST);
    const startSecond = new Date(START_SECOND);
    
    let semesterStart, semesterNumber;
    
    if (now >= startFirst && now < startSecond) {
        semesterStart = new Date(startFirst);
        semesterNumber = 1;
    } else {
        semesterStart = new Date(startSecond);
        semesterNumber = 2;
    }
    
    // Вычисляем сколько недель прошло с начала семестра
    const weeksPassed = Math.floor((now - semesterStart) / (7 * 24 * 60 * 60 * 1000));
    const currentPeriodWeek = (weeksPassed % 2) + 1; // 1 или 2
    
    // Вычисляем даты для текущего двухнедельного периода
    const periodStartDate = new Date(semesterStart);
    periodStartDate.setDate(periodStartDate.getDate() + (weeksPassed - (currentPeriodWeek - 1)) * 7);
    
    const periodEndDate = new Date(periodStartDate);
    periodEndDate.setDate(periodEndDate.getDate() + 13); // +13 дней = 2 недели
    
    return {
        semester: semesterNumber,
        currentWeek: currentPeriodWeek,
        periodStart: periodStartDate,
        periodEnd: periodEndDate,
        datesForWeeks: generateDatesForWeeks(periodStartDate)
    };
}

function generateDatesForWeeks(startDate) {
    const dates = {};
    const date = new Date(startDate);
    
    for (let week = 1; week <= 2; week++) {
        dates[week] = {};
        
        for (let day = 1; day <= 6; day++) { // Понедельник-Суббота (1-6)
            dates[week][day] = new Date(date);
            date.setDate(date.getDate() + 1);
        }
        
        // Добавляем воскресенье (7 день) с пропуском даты
        dates[week][7] = null; // Воскресенье не имеет даты
        
        // Если это не последняя неделя, переходим к следующему понедельнику
        if (week < 2) {
            date.setDate(date.getDate() + 1); // Пропускаем воскресенье
        }
    }
    
    return dates;
}

function setupScheduleWithDates(START_FIRST, START_SECOND) {
    // Получаем данные о текущем периоде
    const { 
        currentWeek, 
        datesForWeeks,
        periodStart,
        periodEnd
    } = calculateAcademicPeriods(START_FIRST, START_SECOND);
    
    // Отображаем диапазон дат
    const periodElement = document.querySelector('.schedule-period');
    if (periodElement) {
        periodElement.textContent = 
            `Период: ${formatDate(periodStart)} - ${formatDate(periodEnd)}`;
    }
    
    // Сбрасываем все подсветки
    document.querySelectorAll('.schedule-day').forEach(day => {
        day.classList.remove('current-day');
    });
    document.querySelectorAll('.lesson-card').forEach(lesson => {
        lesson.classList.remove('current-lesson');
    });
    
    // Добавляем даты к дням недели
    document.querySelectorAll('.week-section').forEach(weekSection => {
        const weekNum = parseInt(weekSection.dataset.week);
        const days = weekSection.querySelectorAll('.schedule-day');
        
        days.forEach(day => {
            const dayNum = parseInt(day.dataset.day);
            const date = datesForWeeks[weekNum][dayNum];
            const dateElement = day.querySelector('.day-date');
            
            if (date && dateElement) {
                dateElement.textContent = formatDate(date);
                
                // Подсвечиваем текущий день
                if (isSameDate(date, new Date()) && weekNum === currentWeek) {
                    day.classList.add('current-day');
                    highlightCurrentLesson(day);
                }
            }
        });
    });
}

function highlightCurrentLesson(dayElement) {
    const now = new Date();
    const currentHours = now.getHours();
    const currentMinutes = now.getMinutes();
    
    dayElement.querySelectorAll('.lesson-card').forEach(lesson => {
        const timeRange = lesson.querySelector('.lesson-time-range').textContent;
        const [startTime, endTime] = timeRange.split(' - ').map(t => t.split(':'));
        
        const startHours = parseInt(startTime[0]);
        const startMinutes = parseInt(startTime[1]);
        const endHours = parseInt(endTime[0]);
        const endMinutes = parseInt(endTime[1]);
        
        if ((currentHours > startHours || (currentHours === startHours && currentMinutes >= startMinutes)) &&
            (currentHours < endHours || (currentHours === endHours && currentMinutes <= endMinutes))) {
            lesson.classList.add('current-lesson');
        }
    });
}

// Вспомогательные функции
function formatDate(date) {
    const options = { day: 'numeric', month: 'long' };
    return date.toLocaleDateString('ru-RU', options);
}

function isSameDate(date1, date2) {
    return date1.getDate() === date2.getDate() && 
           date1.getMonth() === date2.getMonth() && 
           date1.getFullYear() === date2.getFullYear();
}



function getPlaceholder() {
    return `
        <div class="loading-container">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p style="color: white !important;">Загрузка данных...</p>
            </div>
        </div>

        <style>
            .loading-container {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100%;
                min-height: 300px;
            }
            
            .loading-spinner {
                text-align: center;
            }
            
            .spinner {
                width: 50px;
                height: 50px;
                border: 5px solid #f3f3f3;
                border-top: 5px solid #7289da;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto 15px;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    `;
}