const SCHEDULE_SECTION = 'schedule';
const CHAT_SECTION = 'chat';
const SUBJECT_SECTION = 'subjects';
const PROFILE_SECTION = 'profile';
const TEST_SECTION = 'tests';

const SCHEDULE_SECTION_URL = '';
const CHAT_SECTION_URL = '';
const SUBJECT_SECTION_URL = '';
const PROFILE_SECTION_ULR = '';
const TEST_SECTION_ULR = '/request/get/user/tests/';

let CONTENT_CONTAINER = document.getElementById('content');

document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.sidebar-button');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            buttons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            const section = this.getAttribute('title').toLowerCase();
            loadContent(section);
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
            // pageData = getDataFromServer(SCHEDULE_SECTION_URL);
            content = '';
            break;
        case CHAT_SECTION:
            json = await getDataFromServer(CHAT_SECTION_URL);
            content = getChatPage(json.data);
            break;
        case SUBJECT_SECTION:
            // pageData = getDataFromServer(SUBJECT_SECTION_URL);
            content = '';
            break;
        case PROFILE_SECTION:
            // pageData = getDataFromServer(PROFILE_SECTION_ULR);
            content = '';
            break;
        case TEST_SECTION:
            json = await getDataFromServer(TEST_SECTION_ULR);
            content = getTestsPage(json.data);
            break;
    }
    renderContent(content);    
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

function getChatPage(chatData) {
    return `
        <div class="chat-container">
            <!-- 1. Панель групп (студенты/преподаватели) -->
            <div class="chat-group-panel">
                <div class="chat-group active" data-group="students">
                    <i class="fas fa-user-graduate"></i>
                    <span>Студенты</span>
                </div>
                <div class="chat-group" data-group="teachers">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Преподаватели</span>
                </div>
            </div>

            <!-- 2. Панель списка чатов -->
            <div class="chat-list-panel">
                <div class="chat-list" id="chat-list">
                    <!-- Сюда будут загружаться чаты через AJAX -->
                    <div class="chat-list-placeholder">
                        <i class="fas fa-comment-dots"></i>
                        <p>Выберите группу</p>
                    </div>
                </div>
            </div>

            <!-- 3. Панель сообщений -->
            <div class="chat-messages-panel">
                <div class="chat-header">
                </div>
                <div class="chat-messages" id="chat-messages">
                    <div class="empty-chat">
                        <i class="fas fa-comments"></i>
                        <p>Выберите чат, чтобы начать общение</p>
                    </div>
                </div>
                <div class="chat-input">
                    <textarea placeholder="Введите сообщение..." disabled></textarea>
                    <button class="send-button" disabled><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    `;
}

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