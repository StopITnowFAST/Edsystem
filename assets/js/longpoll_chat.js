// Константы
const SEND_SMART_TEXT_URL = '/api/smart-meter/send-message';
const POLL_MESSAGES_URL = '/api/poll-messages';
const SEND_TEXT_URL = '/api/send-message';
const GET_USERNAME_URL = '/api/get/username';
const POLL_DELAY = 1000;
const YANDEX_GPT_ID = 80;

// Элементы страницы
let messageBox = document.getElementById('messageBox');
let chatBox = document.getElementById('chat_box');
let messageInput = document.getElementById('messageInput');
let sendButton = document.getElementById('sendButton');
let messageWindow = document.getElementById('messageWindow');

// Переменные запроса
let lastId = (messageBox.lastElementChild) ? messageBox.lastElementChild.getAttribute('message_id') : 0;
let isPolling = false;
let currentChatId = null;
let currentPlatform = 'tg';

console.log(totalChats);

// Сразу начинаем проверять обновления
startPolling();

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

// Фунцкия для получения обновлений для сообщений
async function poll() {
    console.log("Начинаю посылать запросы");
    try {
        let userUpdates = formUpdateQueryArray();
        const response = await fetch(POLL_MESSAGES_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(userUpdates)
        });
        const data = await response.json();
        
        if (data.status === 'success' && data.newMessages.length > 0) {
            console.log("Нашел новые сообщения");
            processMessages(data.newMessages);
            updateTotalMessages(data.newMessages);
        }
        
        // Независимо от результата, запускаем следующий запрос
        setTimeout(() => poll(), POLL_DELAY);
        
    } catch (error) {
        console.error('Polling error:', error);
        // Повторяем запрос после ошибки
        setTimeout(() => poll(), POLL_DELAY * 2);
    }
}

// Функция для обработки сообщений для чата
function processMessages(messages, voice = true) {
    if (messages && isDialogActive()) {
        messages.forEach(message => {
            // Добавляем сообщение в интерфейс
            const msgElement = makeMessageElement(message['text'], message['type'], message['id']);
            messageBox.insertAdjacentHTML('beforeend', msgElement);
            if (message['type'] == 'incoming' && voice) {
                playVoice(message['voice']);
            }
            messageBox.scrollTop = messageBox.scrollHeight;
            lastId = message['id']        
        });
    }
}

function processChats(chats) {
    if (chats) {
        chats.forEach(chat => {
            let username = (chat.first_name) ? chat.first_name + ' ' + chat.last_name : chat.tg_nickname;
            let chatElement = makeChatElement(username, chat.user_id, chat.text);
            chatBox.insertAdjacentHTML('beforeend', chatElement);
        });
    }
}

// Функция для создания элемента сообщения
function makeMessageElement(text, type, message_id) {
    let divClass = (type == 'incoming') ? "history__user-message" : "history__bot-message";
    let content = text;
    content = content.replace('<', "&lt;");
    content = content.replace('>', "&gt;");    
    let element = '<div message_id=' + message_id + ' class=' + divClass + '>' + content + '</div>';
    return element;
}

function makeChatElement(name, id, lastMessage) {
    newChat = `
    <div class="history__chat-item" onclick="changeDialog(` + id + `)">
        <div class="history__user-nickname">
            ` + name + `
        </div>
        <div class="history__last-message">
            `+ lastMessage +`
        </div>                        
    </div>
    `;
    return newChat;
}

// Функция для проигрывания звук
function playVoice(voiceData) {
    if (!voiceData?.content) return;

    try {
        // 1. Декодируем base64
        const binaryStr = atob(voiceData.content);
        const bytes = new Uint8Array(binaryStr.length);
        for (let i = 0; i < binaryStr.length; i++) {
            bytes[i] = binaryStr.charCodeAt(i);
        }

        // 2. Создаем Blob
        const blob = new Blob([bytes], { 
            type: voiceData.content_type || 'audio/mpeg' 
        });

        // 3. Создаем Blob URL
        const blobUrl = URL.createObjectURL(blob);

        // 4. Создаем или находим audio элемент
        let audio = document.querySelector('#voice-player');
        if (!audio) {
            audio = document.createElement('audio');
            audio.id = 'voice-player';
            audio.controls = true;
            document.body.appendChild(audio);
        }

        // 5. Настраиваем источник
        audio.src = blobUrl;
        audio.load();

        // 6. Воспроизводим с обработкой ошибок
        audio.play().catch(e => {
            console.error(e);
        });

        // 7. Очистка при завершении
        audio.addEventListener('ended', () => {
            URL.revokeObjectURL(blobUrl);
        }, { once: true });

    } catch (e) {
        console.error("Audio processing failed:", e);
    }
}

// Функция для отправки сообщения
async function send() {
    let data = new FormData();
    data['text'] = messageInput.value;
    data['id'] = currentChatId;
    messageInput.value = '';
    await fetch((currentChatId == YANDEX_GPT_ID) ? SEND_SMART_TEXT_URL : SEND_TEXT_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    });
}

// Функция формирует массив с id последних сообщения для всех чатов
function formUpdateQueryArray() {
    let update = new FormData();
    for (chatId in totalMessages) {
        update[chatId] = totalMessages[chatId][totalMessages[chatId].length - 1].id
    }
    return update;
}

// Функция обновляет массив с сообщениями
async function updateTotalMessages(newMessages) {
    for (index in newMessages) {
        let message = newMessages[index];
        if(!(message.user_id in totalMessages)) {
            // Если id пользователя нет в массиве всех сообщений
            totalMessages[message.user_id] = [];
            await handleNewChat(message);
        } 
        totalMessages[message.user_id].push(message);
    }
}

// Функция возвращает данные о id пользователя
async function handleNewChat(message) {
    requestData = new FormData();
    requestData['user_id'] = message.user_id;
    const response = await fetch(GET_USERNAME_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    });
    const json = await response.json();
    if (message.platform == currentPlatform) {
        newChat = makeChatElement(json.username, message.user_id, message.text);
        chatBox.insertAdjacentHTML('beforeend', newChat);
    }
    let newChatObj = {};
    newChatObj.user_id = message.user_id;
    newChatObj.first_name = null;
    newChatObj.last_name = null;
    newChatObj.tg_nickname = json.username;
    newChatObj.platform = message.platform;
    newChatObj.text = message.text;
    totalChats[message.platform].push(newChatObj);
    console.log("Все чаты после обновления - ");
    console.log(totalChats); 
}

// Функция включает или отключает диалоговое окно
function setDialogState(state) {
    if (state) {
        messageWindow.classList.remove('message-window--no-dialog');
        messageWindow.classList.add('message-window--has-dialog');
    } else {
        messageBox.innerHTML = '';
        messageWindow.classList.remove('message-window--has-dialog');
        messageWindow.classList.add('message-window--no-dialog');
    }
}

// Функция возвращает статус диалогового окна
function isDialogActive() {
    return messageWindow.classList.contains('message-window--has-dialog');
}

// Функция для переключения чата
async function changeDialog(chatId) {    
    currentChatId = chatId;
    // Очищаем текущее окно чата
    messageBox.innerHTML = '';    
    // Включаем диалог
    setDialogState(true);
    // Добавляем сообщения
    processMessages(totalMessages[chatId], false);    
}

// Функция для смены платформы
function changePlatform(platform) {
    currentPlatform = platform;
    // Очищаем текущее окно чата
    chatBox.innerHTML = '';
    // Выключаем диалог
    setDialogState(false);
    // Добавляем сообщения
    processChats(totalChats[platform]);
}