document.addEventListener('DOMContentLoaded', function() {
    // Инициализация активной кнопки
    const buttons = document.querySelectorAll('.sidebar-button');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            // Удаляем активный класс у всех кнопок
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // Добавляем активный класс текущей кнопке
            this.classList.add('active');
            
            // Загружаем контент
            const section = this.getAttribute('title').toLowerCase();
            loadContent(section);
        });
    });

    // По умолчанию загружаем профиль
    // loadContent('profile');
});

// function loadContent(section) {
//     const contentContainer = document.getElementById('ajax-content');
//     contentContainer.innerHTML = '<p>Загрузка...</p>';
    
//     // Здесь будет ваш AJAX-запрос
//     fetch(`/account/${section}`)
//         .then(response => response.text())
//         .then(data => {
//             contentContainer.innerHTML = data;
//         })
//         .catch(error => {
//             contentContainer.innerHTML = '<p>Ошибка загрузки данных</p>';
//             console.error('Error:', error);
//         });
// }