const SCHEDULE_SECTION = 'schedule';
const CHAT_SECTION = 'chat';
const SUBJECT_SECTION = 'subjects';
const PROFILE_SECTION = 'profile';
const TEST_SECTION = 'tests';
const WIKI_SECTION = 'wiki';
const JOURNAL_SECTION = 'journal';

const SCHEDULE_SECTION_URL = '/request/get/user/' + SCHEDULE_SECTION + '/';
const CHAT_SECTION_URL = '/request/get/user/' + CHAT_SECTION + '/';
const MESSAGES_URL = '/request/get/user/messages/' + CHAT_SECTION + '/';
const SEND_MESSAGE_URL = '/request/send/user/message/' + CHAT_SECTION + '/';
const GET_UPDATES_URL = '/request/get/user/updates/' + CHAT_SECTION + '/';
const SUBJECT_SECTION_URL = '/request/get/user/' + SUBJECT_SECTION + '/';
const PROFILE_SECTION_URL = '/request/get/user/' + PROFILE_SECTION + '/';
const TEST_SECTION_ULR = '/request/get/user/' + TEST_SECTION + '/';
const WIKI_SECTION_URL = '/request/get/user/' + WIKI_SECTION + '/';
const CREATE_WIKI_ENTRY_URL = '/request/create/wiki/entry';
const UPLOAD_WIKI_FILE_URL = '/request/upload/wiki/file';
const GET_WIKI_FILES_URL = '/request/get/wiki/files';
const JOURNAL_SECTION_URL = '/request/get/user/' + JOURNAL_SECTION + '/';

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
            const schedulePage = getSchedulePage(json.schedule);
            CONTENT_CONTAINER.innerHTML = schedulePage.html;
            schedulePage.init();
            break;
        case CHAT_SECTION:
            json = await getDataFromServer(CHAT_SECTION_URL);
            globalChatArray = json.data;
            content = getChatPage(globalChatArray);
            renderContent(content);   
            break;
        case WIKI_SECTION:
            json = await getDataFromServer(WIKI_SECTION_URL);
            window.userSubjects = json.subjects;
            content = getWikiPage(json.data, userType);
            renderContent(content);
            initWikiPage(userType);
            break;
        case SUBJECT_SECTION:
            json = await getDataFromServer(SUBJECT_SECTION_URL);
            content = getSubjectPage(json.data, userType);
            renderContent(content);
            initSubjectPage(userType);
            break;
        case PROFILE_SECTION:
            json = await getDataFromServer(PROFILE_SECTION_URL);
            content = renderProfilePage(json.data);
            renderContent(content);
            break;
        case TEST_SECTION:
            json = await getDataFromServer(TEST_SECTION_ULR);
            content = getTestsPage(json.data);
            renderContent(content);   
            break;
        case JOURNAL_SECTION:
            json = await getDataFromServer(JOURNAL_SECTION_URL);
            content = getJournalPage(json.data, userType);
            renderContent(content);
            initJournalPage(userType);
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

// -------------------------------------------------------------------------------------------------------------------------------------------------------------
// БЛОК С ЧАТОМ ------------------------------------------------------------------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------------------------------------------------------------------

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

// -------------------------------------------------------------------------------------------------------------------------------------------------------------
// БЛОК С РАСПИСАНИЕМ ------------------------------------------------------------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------------------------------------------------------------------

function getSchedulePage(lessons) {
    // 1. Сортируем и группируем занятия по датам
    const lessonsByDate = {};
    lessons.sort((a, b) => a.lesson_number - b.lesson_number).forEach(lesson => {
        if (!lessonsByDate[lesson.date]) {
            lessonsByDate[lesson.date] = [];
        }
        lessonsByDate[lesson.date].push(lesson);
    });

    // 2. Получаем все даты и разбиваем на 2 недели
    const allDates = Object.keys(lessonsByDate).sort((a, b) => parseDate(a) - parseDate(b));
    
    // Создаем полный список дат на 2 недели (14 дней)
    const fullDates = generateFullTwoWeeksDates(allDates);
    const week1Dates = fullDates.slice(0, 6);
    const week2Dates = fullDates.slice(7, 13);

    // 3. Генерируем HTML
    const html = `
        <div class="schedule-container">
            <div class="schedule-header">
                <h1 class="schedule-title">Мое расписание</h1>
                <div class="schedule-period">${getCurrentPeriod(fullDates)}</div>
            </div>
            
            <div class="schedule-weeks">
                ${renderWeek(1, week1Dates, lessonsByDate)}
                ${renderWeek(2, week2Dates, lessonsByDate)}
            </div>
        </div>
    `;

    return {
        html: html,
        init: function() {
            initScheduleHighlighting();
            setInterval(initScheduleHighlighting, 60000);
        }
    };
}

// Генерируем полные 2 недели с заполнением пропущенных дат
function generateFullTwoWeeksDates(existingDates) {
    if (existingDates.length === 0) return [];
    
    const startDate = parseDate(existingDates[0]);
    const fullDates = [];
    
    // Находим понедельник первой недели
    const firstMonday = new Date(startDate);
    firstMonday.setDate(startDate.getDate() - startDate.getDay() + (startDate.getDay() === 0 ? -6 : 1));
    
    // Генерируем 14 дней (2 недели)
    for (let i = 0; i < 14; i++) {
        const date = new Date(firstMonday);
        date.setDate(firstMonday.getDate() + i);
        fullDates.push(formatDateToKey(date));
    }
    
    return fullDates;
}

// Функции рендеринга с оригинальными стилями
function renderWeek(weekNum, dates, lessonsData) {
    return `
        <div class="week-section" data-week="${weekNum}">
            <h2 class="week-title">${weekNum}</h2>
            <span class="week-subtitle">НЕДЕЛЯ</span>
            
            <div class="schedule-days">
                ${dates.map(date => renderDay(date, lessonsData[date])).join('')}
                ${dates.length < 7 ? '<div class="empty-day"></div>'.repeat(7 - dates.length) : ''}
            </div>
        </div>
    `;
}

function renderDay(date, lessons = []) {
    const dateObj = parseDate(date);
    const hasLessons = lessons && lessons.length > 0;
    
    return `
        <div class="schedule-day" data-date="${date}">
            <div class="day-header">
                <h3 class="day-title">${getDayName(dateObj.getDay())}</h3>
                <div class="day-date">${formatDate(dateObj)}</div>
            </div>
            
            <div class="day-lessons">
                ${hasLessons 
                    ? lessons.map(renderLesson).join('') 
                    : '<div class="no-lessons">Нет занятий в этот день</div>'
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
                <div class="lesson-group">${lesson.code}</div>
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

// Вспомогательные функции
function parseDate(dateStr) {
    const [day, month] = dateStr.split('.').map(Number);
    return new Date(new Date().getFullYear(), month - 1, day);
}

function formatDate(date) {
    const options = { day: 'numeric', month: 'long' };
    return date.toLocaleDateString('ru-RU', options);
}

function getDayName(dayIndex) {
    const days = ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'];
    return days[dayIndex];
}

function getCurrentPeriod(dates) {
    if (dates.length === 0) return '';
    const first = parseDate(dates[0]);
    const last = parseDate(dates[dates.length - 1]);
    return `Период: ${formatDate(first)} - ${formatDate(last)}`;
}

function initScheduleHighlighting() {
    const now = new Date();
    const todayStr = formatDateToKey(now);
    
    // Сброс подсветки
    document.querySelectorAll('.schedule-day, .lesson-card').forEach(el => {
        el.classList.remove('current-day', 'current-lesson');
    });
    
    // Подсветка текущего дня
    const currentDay = document.querySelector(`.schedule-day[data-date="${todayStr}"]`);
    if (currentDay) {
        currentDay.classList.add('current-day');
        highlightCurrentLessons(currentDay);
    }
}

function highlightCurrentLessons(dayElement) {
    const now = new Date();
    dayElement.querySelectorAll('.lesson-card').forEach(lesson => {
        const timeRange = lesson.querySelector('.lesson-time-range').textContent;
        const [start, end] = timeRange.split(' - ').map(t => t.split(':'));
        
        const startTime = new Date();
        startTime.setHours(parseInt(start[0]), parseInt(start[1]));
        
        const endTime = new Date();
        endTime.setHours(parseInt(end[0]), parseInt(end[1]));
        
        if (now >= startTime && now <= endTime) {
            lesson.classList.add('current-lesson');
        }
    });
}

function formatDateToKey(date) {
    return `${date.getDate().toString().padStart(2, '0')}.${(date.getMonth() + 1).toString().padStart(2, '0')}`;
}

// -------------------------------------------------------------------------------------------------------------------------------------------------------------
// БЛОК С ПРЕДМЕТАМИ--------------------------------------------------------------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------------------------------------------------------------------


function getSubjectPage(data, userType) {
    if (!data || data.length == 0) {
        return `
            <div class="wiki-no-data">
                <i class="fas fa-book-open"></i>
                <p>Нет доступных предметов</p>
            </div>
        `;
    }

    const subjects = Object.keys(data);
    let subjectTabs = subjects.map(subject => 
        `<div class="subject-tab" data-subject="${subject}">${subject}</div>`
    ).join('');

    let subjectCards = '';
    for (const [subjectName, lessons] of Object.entries(data)) {
        subjectCards += `
            <div class="subject-cards-container" data-subject="${subjectName}">
                <div class="subject-cards-grid">
                    ${lessons.map(lesson => createLessonCard(lesson, userType)).join('')}
                </div>
                ${userType == 'teacher' ? '<div class="students-table-container" style="display:none;"></div>' : ''}
            </div>
        `;
    }

    return `
        <div class="subject-section">
            <div class="subject-tabs-container">
                ${subjectTabs}
            </div>
            ${subjectCards}
            ${userType == 'teacher' ? '<button id="save-grades-btn" style="display:none;">Сохранить изменения</button>' : ''}
        </div>
    `;
}


function createLessonCard(lesson, userType) {
    const [year, month, day] = lesson.date.split('-');
    const monthNames = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 
                       'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
    const formattedDate = `${parseInt(day)} ${monthNames[parseInt(month) - 1]}`;

    // Определяем классы для карточки
    const cardClasses = ['subject-lesson-card'];
    
    // Добавляем класс absent если студент отсутствовал
    if (lesson.attendance === 'absent') {
        cardClasses.push('absent');
    }
    
    // Добавляем класс has-grade если есть оценка
    if (lesson.grade) {
        cardClasses.push('has-grade');
    }

    // Добавляем класс по типу занятия
    cardClasses.push(lesson.type.toLowerCase().replace(' ', '-'));

    return `
        <div class="${cardClasses.join(' ')}" 
             data-date="${lesson.date}" 
             data-type="${lesson.type}"
             data-time="${lesson.time}"
             ${userType == 'teacher' ? 'data-has-students="true"' : ''}>
            <div class="subject-lesson-date">${formattedDate}</div>
            <div class="subject-lesson-type ${lesson.type.toLowerCase().replace(' ', '-')}">
                ${lesson.type}
            </div>
            <div class="subject-lesson-time">${lesson.time}</div>
            
            ${lesson.grade ? `<div class="subject-lesson-grade">${lesson.grade}</div>` : ''}
            
            ${userType == 'teacher' ? `
            <div class="subject-lesson-controls">
                <button class="show-students-btn">Показать студентов</button>
            </div>
            ` : ''}
        </div>
    `;
}


// Инициализация после рендеринга
function initSubjectPage(userType) {
    // Переключение между предметами
    const tabs = document.querySelectorAll('.subject-tab');
    if (tabs.length > 0) {
        tabs[0].classList.add('active');
        document.querySelector(`.subject-cards-container[data-subject="${tabs[0].dataset.subject}"]`)
            .classList.add('active');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                document.querySelectorAll('.subject-cards-container').forEach(container => {
                    container.classList.remove('active');
                });
                
                const subject = tab.dataset.subject;
                document.querySelector(`.subject-cards-container[data-subject="${subject}"]`)
                    .classList.add('active');
            });
        });
    }

    let subject = {};
    let date = '';
    let type = '';
    let time = '';
    
    if (userType == 'teacher') {
        // Обработка кликов по карточкам занятий
        document.querySelectorAll('.subject-lesson-card').forEach(card => {
            card.addEventListener('click', async function(e) {
                if (e.target.closest('.show-students-btn')) {
                    const container = this.closest('.subject-cards-container');
                    const tableContainer = container.querySelector('.students-table-container');
                    subject = container.dataset.subject;
                    date = this.dataset.date;
                    type = this.dataset.type;
                    time = this.dataset.time;
                    
                    // Если таблица уже видима - просто скрываем ее
                    if (tableContainer.style.display !== 'none') {
                        tableContainer.style.display = 'none';
                        document.getElementById('save-grades-btn').style.display = 'none';
                        return;
                    }
                    
                    // Показываем индикатор загрузки
                    tableContainer.innerHTML = '<div class="loading-message">Загрузка данных...</div>';
                    tableContainer.style.display = 'block';
                    
                    try {
                        // Всегда загружаем свежие данные с сервера
                        const students = await loadStudentsForLesson(subject, date, type, time);
                        tableContainer.innerHTML = createStudentsTable(students);
                        this.dataset.hasStudents = 'true';
                        
                        // Обновляем текст кнопки
                        const btn = this.querySelector('.show-students-btn');
                        if (btn) btn.textContent = 'Показать оценки';
                        
                    } catch (error) {
                        console.error('Ошибка загрузки студентов:', error);
                        tableContainer.innerHTML = '<div class="error-message">Не удалось загрузить список студентов</div>';
                    }
                    
                    document.getElementById('save-grades-btn').style.display = 'block';
                }
            });
        });

        buttons = document.getElementById('save-grades-btn');
        if (buttons) {
            // Обработка сохранения изменений
            buttons.addEventListener('click', async function() {
                const changes = collectGradeChanges(subject, date, type, time);
                try {
                    await saveGradeChanges(changes);
                    alert('Изменения успешно сохранены');
                } catch (error) {
                    console.error('Ошибка сохранения:', error);
                    alert('Ошибка при сохранении изменений');
                }
            });
        }        
    }
}


function createStudentsTable(students) {
    return `
        <div class="students-table-wrapper">
            <table class="students-table">
                <thead>
                    <tr>
                        <th>Студент</th>
                        <th>Посещение</th>
                        <th>Оценка</th>
                    </tr>
                </thead>
                <tbody>
                    ${students.map(student => `
                        <tr data-student-id="${student.id}">
                            <td>${student.full_name}</td>
                            <td>
                                <select class="attendance-select">
                                    <option value="present" ${student.attendance === 'present' ? 'selected' : ''}>Присутствовал</option>
                                    <option value="absent" ${student.attendance === 'absent' ? 'selected' : ''}>Отсутствовал</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" min="2" max="5" 
                                       class="grade-input" 
                                       value="${student.grade || ''}">
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

async function loadStudentsForLesson(subject, date, type, time) {
    const response = await fetch('/request/get/user/students', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            subject,
            date,
            type,
            time,
        })
    });
    
    if (!response.ok) {
        throw new Error('Ошибка загрузки данных');
    }
    
    return await response.json();
}

function collectGradeChanges(subject, date, type, time) {
    const changes = [];

    const lessonData = {
        subject: subject,
        date: date,
        type: type,
        time: time
    };
    let activeCard = document.querySelector('.subject-cards-container.active');
    activeCard.querySelectorAll('.students-table tbody tr').forEach(row => {
        changes.push({
            studentId: row.dataset.studentId,
            attendance: row.querySelector('.attendance-select').value,
            grade: row.querySelector('.grade-input').value,
            ...lessonData // добавляем данные о занятии
        });
    });
    
    return changes;
}

async function saveGradeChanges(changes) {
    const response = await fetch('/request/set/user/grade', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(changes)
    });
    
    if (!response.ok) {
        throw new Error('Ошибка сохранения данных');
    }
    
    return await response.json();
}

// -----------------------------------------------------------------------------
// БЛОК С WIKI (УЧЕБНЫЕ МАТЕРИАЛЫ И ЗАДАНИЯ)
// -----------------------------------------------------------------------------

function getWikiPage(wikiData, userType) {

    console.log(wikiData);

    // Получаем список всех предметов пользователя (даже без записей)
    const allSubjects = getAllUserSubjects(); // Эта функция должна быть реализована
    
    if (!allSubjects || allSubjects.length === 0) {
        return `
            <div class="wiki-no-data">
                <i class="fas fa-book-open"></i>
                <p>Нет доступных предметов</p>
            </div>
        `;
    }

    const subjectTabs = allSubjects.map(subject => 
        `<div class="subject-tab" data-subject="${subject.name}">${subject.name}</div>`
    ).join('');

    let subjectContents = '';
    for (const subject of allSubjects) {
        const entries = wikiData[subject.name] || [];
        subjectContents += `
            <div class="wiki-subject-container" data-subject="${subject.name}" style="display: none;">
                ${renderWikiEntries(entries, userType, subject.id)}
            </div>
        `;
    }

    return `
        <div class="wiki-section">
            <div class="subject-tabs-container">
                ${subjectTabs}
            </div>
            ${subjectContents}
        </div>
    `;
}



function renderWikiEntries(entries, userType, subjectId) {
    const noEntriesHtml = `
        <div class="no-wiki-entries">
            <i class="fas fa-info-circle"></i>
            <p>Нет учебных материалов по этому предмету</p>
            ${userType === 'teacher' ? 
                `<button class="btn create-entry-btn" data-subject-id="${subjectId}">
                    Создать первую запись
                </button>` : 
                ''}
        </div>
    `;

    if (!entries || entries.length === 0) {
        return noEntriesHtml;
    }

    return `
        <div class="wiki-entries-list">
            ${userType === 'teacher' ? 
                `<button class="btn create-entry-btn" data-subject-id="${subjectId}">
                    Создать новую запись
                </button>` : 
                ''}
            ${entries.map(entry => renderWikiEntry(entry, userType)).join('')}
        </div>
    `;
}

function renderWikiEntry(entry, userType, userId) {
    const isAssignment = entry.can_upload_file;

    // Первая строка текста - заголовок
    const title = entry.text.split('\n')[0];
    const content = entry.text.split('\n').slice(1).join('<br>');

    // Фильтруем файлы студента - показываем только его файлы
    const studentFiles = userType === 'student' 
        ? entry.student_files.filter(file => file.student_id == userId)
        : entry.student_files;

    console.log(entry); 

    return `
        <div class="wiki-entry" data-entry-id="${entry.id}">
            <div class="wiki-entry-header">
                <h3 class="wiki-entry-title">${title}</h3>
                <div class="wiki-entry-meta">
                    <span class="wiki-entry-date">${entry.created_at}</span>
                    ${isAssignment ? '<span class="wiki-entry-type">Задание</span>' : ''}
                </div>
            </div>
            
            <div class="wiki-entry-content">
                ${content}
            </div>
            
            ${isAssignment ? `
                <div class="wiki-entry-files">
                    ${entry.teacher_files.length != 0 ? `
                        <h4>Файлы преподавателя:</h4>
                        <div class="teacher-files">
                            ${entry.teacher_files.map(file => renderFileItem(file, 'teacher', 'teacher')).join('')}
                        </div>
                    ` : ''}
                    
                    ${userType === 'teacher' ? `
                        <div class="wiki-file-upload">
                            <input type="file" id="teacher-file-upload-${entry.id}" class="file-input-hidden"
                                accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.zip,.rar">
                            <label for="teacher-file-upload-${entry.id}" class="file-upload-button">
                                <i class="fas fa-plus"></i> Файл
                            </label>
                        </div>
                    ` : ''}
                    
                    <!-- Блок для студенческих файлов -->
                    ${userType === 'student' && studentFiles.length > 0 ? `
                        <h4>Мои файлы:</h4>
                        <div class="student-files">
                            ${studentFiles.map(file => renderFileItem(file, 'student', 'student')).join('')}
                        </div>
                    ` : ''}
                    
                    ${userType === 'teacher' && entry.student_answers.length > 0 ? `
                        <h4>Ответы студентов:</h4>
                        <div class="student-files">
                            ${entry.student_answers.map(file => renderFileItem(file, 'student', 'teacher')).join('')}
                        </div>
                    ` : ''}
                    
                    ${userType === 'student' ? `
                        <div class="wiki-file-upload">
                            <input type="file" id="student-file-upload-${entry.id}" class="file-input-hidden"
                                accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.zip,.rar">
                            <label for="student-file-upload-${entry.id}" class="file-upload-button">
                                <i class="fas fa-plus"></i> ${studentFiles.length > 0 ? 'Файл' : 'Файл'}
                            </label>
                        </div>
                    ` : ''}
                </div>
            ` : `
                <div class="wiki-entry-files">
                    ${userType === 'teacher' ? `
                        <div class="wiki-file-upload">
                            <input type="file" id="teacher-file-upload-${entry.id}" class="file-input-hidden"
                                accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.zip,.rar">
                            <label for="teacher-file-upload-${entry.id}" class="file-upload-button">
                                <i class="fas fa-plus"></i> Файл
                            </label>
                        </div>
                    ` : ''}
                        
                    <h4>Файлы:</h4>
                    <div class="teacher-files">
                        ${entry.teacher_files.length != 0 ? entry.teacher_files.map(file => renderFileItem(file, 'teacher', 'teacher')).join('') : 'Нет файлов'}
                    </div>
                </div>
            `}
            
            ${userType === 'teacher' ? `
                <div class="wiki-entry-controls">
                    <button class="btn delete-btn">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            ` : ''}
        </div>
    `;
}



function renderFileItem(file, fileType, role) {
    return `
        <div class="wiki-file-item" data-file-id="${file.id}">
            <a href="${file.url}" class="wiki-file-link" download>
                <i class="${getFileIcon(file.name)}"></i>
                ${file.name}
            </a>
            ${fileType === 'student' ? `
                <span class="file-info">
                    ${file.student_name || 'Студент'}
                    ${file.upload_date ? `(${new Date(file.upload_date).toLocaleDateString()})` : ''}
                </span>
            ` : ''}
            ${fileType === 'student' && role != 'teacher' && file.student_id == window.currentUserId ? `
                <button class="btn btn-sm delete-file-btn button-like-icon" data-file-id="${file.id}">
                    <i class="fas fa-times"></i>
                </button>
            ` : ''}
        </div>
    `;
}

function getFileIcon(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    const icons = {
        pdf: 'fa-file-pdf',
        jpg: 'fa-file-image',
        jpeg: 'fa-file-image',
        png: 'fa-file-image',
        gif: 'fa-file-image',
        doc: 'fa-file-word',
        docx: 'fa-file-word',
        xls: 'fa-file-excel',
        xlsx: 'fa-file-excel',
        zip: 'fa-file-archive',
        rar: 'fa-file-archive'
    };
    return `fas ${icons[ext] || 'fa-file'}`;
}

function initWikiPage(userType) {
    // Обработка переключения между предметами
    const tabs = document.querySelectorAll('.subject-tab');
    if (tabs.length > 0) {
        tabs[0].classList.add('active');
        const firstSubject = tabs[0].dataset.subject;
        document.querySelector(`.wiki-subject-container[data-subject="${firstSubject}"]`).style.display = 'block';
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                const subject = tab.dataset.subject;
                document.querySelectorAll('.wiki-subject-container').forEach(container => {
                    container.style.display = 'none';
                });
                document.querySelector(`.wiki-subject-container[data-subject="${subject}"]`).style.display = 'block';
            });
        });
    }
    if (userType === 'teacher') {
        document.querySelectorAll('.create-entry-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const subjectId = this.dataset.subjectId;
                showCreateWikiModal(subjectId);
            });
        });
        // Обработка создания новой записи
        document.getElementById('create-wiki-entry')?.addEventListener('click', showCreateWikiModal);
        
        // Обработка переключения возможности загрузки файлов
        document.querySelectorAll('.toggle-upload-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const entryId = this.closest('.wiki-entry').dataset.entryId;
                const canUpload = this.dataset.canUpload === 'true';
                
                try {
                    const response = await fetch('/request/toggle/wiki/upload/', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            entry_id: entryId,
                            can_upload: !canUpload
                        })
                    });
                    
                    if (response.ok) {
                        location.reload();
                    }
                } catch (error) {
                    console.error('Ошибка:', error);
                    alert('Не удалось изменить настройки загрузки');
                }
            });
        });
        
        // Обработка удаления записи
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                if (confirm('Вы уверены, что хотите удалить эту запись?')) {
                    const entryId = this.closest('.wiki-entry').dataset.entryId;
                    
                    try {
                        const response = await fetch('/request/delete/wiki/entry/', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                entry_id: entryId
                            })
                        });
                        
                        if (response.ok) {
                            location.reload();
                        }
                    } catch (error) {
                        console.error('Ошибка:', error);
                        alert('Не удалось удалить запись');
                    }
                }
            });
        });
    }
    
    // Обработка загрузки файлов (для преподавателей и студентов)
    document.querySelectorAll('.file-input-hidden').forEach(input => {
        input.addEventListener('change', async function() {
            const entryId = this.id.split('-').pop();
            const fileType = this.id.includes('teacher-file') ? 'teacher' : 'student';
            const files = this.files;
            
            if (files.length === 0) return;
            
            try {
                const formData = new FormData();
                formData.append('entry_id', entryId);
                formData.append('file_type', fileType);
                for (let i = 0; i < files.length; i++) {
                    formData.append('files[]', files[i]);
                }
                
                const response = await fetch(UPLOAD_WIKI_FILE_URL, {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    location.reload();
                }
            } catch (error) {
                console.error('Ошибка загрузки файла:', error);
                alert('Не удалось загрузить файл');
            }
        });
    });

    // Обработчик удаления файлов
    document.querySelectorAll('.delete-file-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.stopPropagation();
            const fileId = this.dataset.fileId;
            const fileItem = this.closest('.wiki-file-item');
            
            if (confirm('Вы уверены, что хотите удалить этот файл?')) {
                try {
                    // Добавляем индикатор загрузки
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    this.disabled = true;
                    
                    const response = await fetch('/request/delete/wiki/file', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            file_id: fileId
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.status === 'success') {
                        // Анимация удаления
                        fileItem.style.opacity = '0';
                        setTimeout(() => {
                            fileItem.remove();
                            
                            // Обновляем список файлов, если это был последний файл
                            const filesContainer = fileItem.closest('.student-files');
                            if (filesContainer && filesContainer.querySelectorAll('.wiki-file-item').length === 0) {
                                const header = filesContainer.previousElementSibling;
                                if (header && header.tagName === 'H4') {
                                    header.remove();
                                }
                            }
                        }, 300);
                    } else {
                        alert('Ошибка: ' + (result.message || 'Не удалось удалить файл'));
                        this.innerHTML = '<i class="fas fa-times"></i>';
                        this.disabled = false;
                    }
                } catch (error) {
                    console.error('Ошибка при удалении файла:', error);
                    alert('Ошибка соединения с сервером');
                    this.innerHTML = '<i class="fas fa-times"></i>';
                    this.disabled = false;
                }
            }
        });
    });
}

function showCreateWikiModal(subjectId) {
    const modalHtml = `
        <div class="wiki-modal" id="wiki-create-modal">
            <div class="wiki-modal-content">
                <h3>Создать новую запись</h3>
                <textarea id="wiki-entry-text" placeholder="Первая строка будет заголовком..."></textarea>
                <div class="wiki-modal-options">
                    <label>
                        <input type="checkbox" id="wiki-can-upload">
                        Разрешить студентам загружать файлы (создать задание)
                    </label>
                </div>
                <div class="wiki-modal-buttons">
                    <button id="wiki-cancel-create" class="btn cancel-btn">Отмена</button>
                    <button id="wiki-confirm-create" class="btn confirm-btn" data-subject-id="${subjectId}">Создать</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Обработчики событий для модального окна
    document.getElementById('wiki-cancel-create').addEventListener('click', () => {
        document.getElementById('wiki-create-modal').remove();
    });
    
    document.getElementById('wiki-confirm-create').addEventListener('click', async () => {
        const text = document.getElementById('wiki-entry-text').value.trim();
        const canUpload = document.getElementById('wiki-can-upload').checked;
        const subjectId = document.getElementById('wiki-confirm-create').dataset.subjectId;
        
        if (!text) {
            alert('Текст записи не может быть пустым');
            return;
        }
        
        try {
            const response = await fetch(CREATE_WIKI_ENTRY_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    text: text,
                    can_upload: canUpload,
                    subject_id: subjectId
                })
            });
            
            if (response.ok) {
                location.reload();
            }
        } catch (error) {
            console.error('Ошибка создания записи:', error);
            alert('Не удалось создать запись');
        }
    });
}

function getAllUserSubjects() {
    if (!window.userSubjects) return [];
    
    return Object.entries(window.userSubjects).map(([name, id]) => ({
        name,
        id
    }));
}

function renderProfilePage(profileData) {
    return `
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="profile-info">
                    <h2>${profileData.full_name}</h2>
                    <div class="profile-meta">
                        <span class="profile-type ${profileData.type}">
                            ${profileData.type === 'teacher' ? 'Преподаватель' : 'Студент'}
                        </span>
                        ${profileData.birth_date ? `
                            <span class="profile-birthdate">
                                <i class="fas fa-birthday-cake"></i>
                                ${new Date(profileData.birth_date).toLocaleDateString()}
                            </span>
                        ` : ''}
                    </div>
                </div>
            </div>

            ${profileData.type === 'student' ? renderStudentProfile(profileData) : renderTeacherProfile(profileData)}
        </div>
    `;
}

function renderStudentProfile(studentData) {
    return `
        <div class="profile-section">
            <h3><i class="fas fa-user-graduate"></i> Учебная информация</h3>
            <div class="profile-grid">
                <div class="profile-item">
                    <label>Группа</label>
                    <div>${studentData.group.code || 'Не указана'}</div>
                </div>
                <div class="profile-item">
                    <label>Курс</label>
                    <div>${studentData.group.course || '-'}</div>
                </div>
                <div class="profile-item">
                    <label>Семестр</label>
                    <div>${studentData.group.semester || '-'}</div>
                </div>
                <div class="profile-item">
                    <label>Год поступления</label>
                    <div>${studentData.group.admission_year || '-'}</div>
                </div>
            </div>
        </div>

        <div class="profile-section">
            <h3><i class="fas fa-book-open"></i> Предметы и оценки</h3>
            <div class="subjects-grades">
                ${studentData.subjects.map(subject => `
                    <div class="subject-grade-item">
                        <div class="subject-header">
                            <h4>${subject.name}</h4>
                            <div class="subject-average">Средний балл: ${subject.average_grade.toFixed(2)}</div>
                        </div>
                        <div class="grades-list">
                            ${subject.grades.length > 0 ? `
                                <table class="grades-table">
                                    <thead>
                                        <tr>
                                            <th>Дата</th>
                                            <th>Тип занятия</th>
                                            <th>Оценка</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${subject.grades.map(grade => (grade.value) ? `
                                            <tr>
                                                <td>${grade.date ? new Date(grade.date).toLocaleDateString() : ''}</td>
                                                <td>${grade.lesson_type || ''}</td>
                                                <td class="grade-value ${grade.value ? getGradeClass(grade.value) : ''}">
                                                    ${grade.value || ''}
                                                </td>
                                            </tr>
                                        ` : '').join('')}
                                </table>
                            ` : '<p>Нет оценок по этому предмету</p>'}
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

function renderTeacherProfile(teacherData) {
    return `
        <div class="profile-section">
            <h3><i class="fas fa-chalkboard-teacher"></i> Преподаваемые предметы</h3>
            <div class="teacher-subjects">
                ${teacherData.subjects.map(subject => `
                    <div class="subject-item">
                        <div class="subject-name">${subject.name}</div>
                        <div class="subject-groups">Группы: ${subject.groups.join(', ')}</div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

function getGradeClass(grade) {
    if (grade >= 4.5) return 'excellent';
    if (grade >= 3.5) return 'good';
    if (grade >= 2.5) return 'satisfactory';
    return 'unsatisfactory';
}


function getJournalPage(journalData, userType) {
    if (userType !== 'teacher') {
        return `
            <div class="no-access-message">
                <i class="fas fa-lock"></i>
                <p>Доступ только для преподавателей</p>
            </div>
        `;
    }

    if (!journalData || !journalData.subjects || journalData.subjects.length === 0) {
        return `
            <div class="journal-no-data">
                <i class="fas fa-book"></i>
                <p>Нет доступных предметов для просмотра журнала</p>
            </div>
        `;
    }

    // Создаем табы для предметов
    const subjectTabs = journalData.subjects.map(subject => 
        `<div class="subject-tab" data-subject-id="${subject.id}">${subject.name}</div>`
    ).join('');

    // Создаем контейнеры для каждого предмета
    const subjectContents = journalData.subjects.map(subject => `
        <div class="journal-subject-container" data-subject-id="${subject.id}" style="display: none;">
            ${renderJournalSubject(subject)}
        </div>
    `).join('');

    return `
        <div class="journal-section">            
            <div class="subject-tabs-container">
                ${subjectTabs}
            </div>            
                    
            ${subjectContents}
        </div>
    `;
}

function renderJournalSubject(subject) {
    if (!subject.groups || subject.groups.length === 0) {
        return `
            <div class="no-groups-message">
                <i class="fas fa-users-slash"></i>
                <p>Нет групп для этого предмета</p>
            </div>
        `;
    }

    // Создаем табы для групп
    const groupTabs = subject.groups.map(group => 
        `<div class="group-tab" data-group-id="${group.id}">${group.code}</div>`
    ).join('');

    // Создаем контейнеры для каждой группы
    const groupContents = subject.groups.map(group => `
        <div class="journal-group-container" data-group-id="${group.id}" style="display: none;">
            ${renderJournalGroup(group)}
        </div>
    `).join('');

    return `
        <div class="journal-subject-content">
            <div class="group-tabs-container">
                ${groupTabs}
            </div>
            
            <div class="journal-group-content">
                ${groupContents}
            </div>
        </div>
    `;
}

function renderJournalGroup(group) {
    if (!group.students || group.students.length === 0) {
        return `
            <div class="no-students-message">
                <i class="fas fa-user-graduate"></i>
                <p>Нет студентов в этой группе</p>
            </div>
        `;
    }

    // Собираем все уникальные даты занятий
    const allDates = [];
    group.students.forEach(student => {
        student.grades.forEach(grade => {
            if (!allDates.includes(grade.date)) {
                allDates.push(grade.date);
            }
        });
    });
    allDates.sort();

    // Создаем заголовки таблицы
    const dateHeaders = allDates.map(date => `
        <th>${new Date(date).toLocaleDateString()}</th>
    `).join('');

    // Создаем строки для каждого студента
    const studentRows = group.students.map(student => `
        <tr>
            <td>${student.last_name} ${student.first_name}</td>
            ${allDates.map(date => {
                const grade = student.grades.find(g => g.date === date);
                return `<td class="grade-cell ${grade && grade.value ? getGradeClass(grade.value) : ''}">
                    ${grade && grade.value ? grade.value : '-'}
                </td>`;
            }).join('')}
        </tr>
    `).join('');

    return `
        <div class="journal-table-container">
            <table class="journal-table">
                <thead>
                    <tr>
                        <th>Студент</th>
                        ${dateHeaders}
                    </tr>
                </thead>
                <tbody>
                    ${studentRows}
                </tbody>
            </table>
        </div>
    `;
}

function initJournalPage(userType) {
    if (userType !== 'teacher') return;

    // Обработка переключения между предметами
    const subjectTabs = document.querySelectorAll('.subject-tab');
    if (subjectTabs.length > 0) {
        subjectTabs[0].classList.add('active');
        const firstSubjectId = subjectTabs[0].dataset.subjectId;
        document.querySelector(`.journal-subject-container[data-subject-id="${firstSubjectId}"]`).style.display = 'block';
        
        subjectTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                subjectTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                const subjectId = tab.dataset.subjectId;
                document.querySelectorAll('.journal-subject-container').forEach(container => {
                    container.style.display = 'none';
                });
                document.querySelector(`.journal-subject-container[data-subject-id="${subjectId}"]`).style.display = 'block';
            });
        });
    }

    // Обработка переключения между группами
    document.querySelectorAll('.group-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const container = this.closest('.group-tabs-container');
            container.querySelectorAll('.group-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            const groupId = this.dataset.groupId;
            const contentContainer = this.closest('.journal-subject-content').querySelector('.journal-group-content');
            contentContainer.querySelectorAll('.journal-group-container').forEach(container => {
                container.style.display = 'none';
            });
            contentContainer.querySelector(`.journal-group-container[data-group-id="${groupId}"]`).style.display = 'block';
        });
    });

    // Первая группа в первом предмете должна быть активной
    const firstGroupTab = document.querySelector('.group-tab');
    if (firstGroupTab) {
        firstGroupTab.classList.add('active');
        const firstGroupId = firstGroupTab.dataset.groupId;
        document.querySelector(`.journal-group-container[data-group-id="${firstGroupId}"]`).style.display = 'block';
    }

    // Обработка экспорта в Excel
    document.getElementById('export-journal-btn')?.addEventListener('click', exportJournalToExcel);
}