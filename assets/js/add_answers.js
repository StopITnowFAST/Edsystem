answerCounter = 0;

function addAnswerItem() {
    


    // Увеличиваем счетчик
    answerCounter++;
    
    // Создаем новый элемент
    const newAnswerDiv = document.createElement('div');
    newAnswerDiv.className = 'admin-create__field';
    newAnswerDiv.innerHTML = `
        <div class="admin-create__answer-item">
            <textarea rows="2" id="text_${answerCounter}" 
                    class="textarea-input admin-create__input ckeditor" 
                    name="answers_${answerCounter}" required></textarea>
            <dib class="admin-create__question-controls">
                <button type="button" class="btn btn-main" onclick="this.parentNode.parentNode.parentNode.remove()">Удалить</button>
                <div style="display: flex; justify-content: center; align-items: center; gap: 10px;">                
                    <label for="points_${answerCounter}">Баллы</label>
                    <input id="points_${answerCounter}" type="points" name="points_${answerCounter}" class="admin-create__input">   
                </div>       
                <div style="display: flex; justify-content: center; align-items: center; gap: 10px;">
                    <label for="checkbox_${answerCounter}">Правильный ответ</label>
                    <input id="checkbox_${answerCounter}" type="checkbox" name="isCorrect_${answerCounter}" class="admin-create__checkbox">   
                </div>       
            </dib>
        </div>
    `;
    
    // Добавляем в контейнер
    document.getElementById('answersDiv').appendChild(newAnswerDiv);
    
    // Инициализируем CKEditor с отключенными уведомлениями
    textarea = document.getElementById(`text_${answerCounter}`);
    init(textarea);
}

document.addEventListener('DOMContentLoaded', function() {
    // Инициализация всех существующих редакторов
    document.querySelectorAll('textarea').forEach(function(textarea) {
        init(textarea);
    });
    answerCounter = findMaxId();
});

function init(textarea) {
    console.log("Инициализация " + textarea.id);
    const id = textarea.id;
    CKEDITOR.replace(id, {
        height: '120px', // Фиксированная высота (~6 строк)
        bodyClass: 'dark-theme',
        contentsCss: [
            'body { background: var(--color-grey-200); color: #fff; }',
            'a { color: var(--color-main); }'
        ],
        startupOutlineBlocks: false,
        removePlugins: 'div,flash,forms,iframe,resize',
        startupFocus: false,
        disableNotifications: true,
        on: {
            instanceReady: function(ev) {
                // 1. Убираем уведомления
                var notifications = ev.editor._.notificationArea;
                if (notifications) {
                    notifications.hideAll();
                }
                
                // 2. Устанавливаем начальный стиль
                ev.editor.setData('<p style="color:#fff">&#8203;</p>');
                
                // 3. Принудительно применяем стили к iframe
                var iframe = ev.editor.window.getFrame();
                if (iframe) {
                    iframe.$.contentDocument.body.style.backgroundColor = 'var(--color-grey-200)';
                    iframe.$.contentDocument.body.style.color = '#fff';
                }
            }
        }
    });
}

function findMaxId() {
    const elements = document.querySelectorAll('[id^="text_"]');
    if (elements.length > 0) {
        const ids = Array.from(elements).map(el => el.id);
        const numbers = ids.map(id => {
            const match = id.match(/^text_(\d+)$/);
            return match ? parseInt(match[1], 10) : 0;
        });
        const maxId = Math.max(...numbers);
        const nextId = maxId + 1;    
        return nextId;
    } else {
        return 1;
    }
}