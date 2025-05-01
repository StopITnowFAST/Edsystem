function copyToClipboard(text) {
    navigator.clipboard.writeText(text)
        .then(() => {
            alert('Ссылка скопирована в буфер обмена!');
        })
        .catch(err => {
            console.error('Ошибка копирования: ', err);
            // Fallback для старых браузеров
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            alert('Ссылка скопирована!');
        });
}