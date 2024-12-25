document.addEventListener('DOMContentLoaded', () => {
    const updateGrandTotal = () => {
        let grandTotal = 0;
        document.querySelectorAll('.total').forEach(totalCell => {
            grandTotal += parseFloat(totalCell.textContent);
        });
        document.getElementById('grandTotal').textContent = grandTotal.toFixed(2);
    };

    document.querySelectorAll('.quantity').forEach(input => {
        input.addEventListener('input', function () {
            const row = this.closest('tr');
            const price = parseFloat(this.dataset.price);
            const quantity = parseInt(this.value) || 1; 
            const totalCell = row.querySelector('.total');
            totalCell.textContent = (price * quantity).toFixed(2);
            updateGrandTotal();
        });
    });

    document.querySelectorAll('.remove-item-btn').forEach(button => {
        button.addEventListener('click', function () {
            const flowerId = this.dataset.id;

            // Удаляем строку из таблицы
            const row = document.querySelector(`tr[data-flower-id="${flowerId}"]`);
            if (row) {
                row.remove();
            }

            // Обновляем общий итог
            updateGrandTotal();
        });
    });

    // Инициализация начального значения общего итога
    updateGrandTotal();
});
