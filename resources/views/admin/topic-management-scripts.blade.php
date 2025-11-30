<script>
function removeUserAssignment(topicId, userId) {
    // Создаем и отправляем форму
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("platform.appeal.topics.remove-assignment") }}';
    
    const topicInput = document.createElement('input');
    topicInput.type = 'hidden';
    topicInput.name = 'topic_id';
    topicInput.value = topicId;
    
    const userInput = document.createElement('input');
    userInput.type = 'hidden';
    userInput.name = 'user_id';
    userInput.value = userId;
    
    const tokenInput = document.createElement('input');
    tokenInput.type = 'hidden';
    tokenInput.name = '_token';
    tokenInput.value = '{{ csrf_token() }}';
    
    form.appendChild(topicInput);
    form.appendChild(userInput);
    form.appendChild(tokenInput);
    
    document.body.appendChild(form);
    form.submit();
}

// Показать уведомление об успешном действии
function showSuccessNotification(message) {
    // Создаем элемент уведомления
    const notification = document.createElement('div');
    notification.className = 'alert alert-success alert-dismissible fade show position-fixed';
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Автоматически убираем через 5 секунд
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Обработка ответов от сервера для всех AJAX запросов
document.addEventListener('DOMContentLoaded', function() {
    // Перехватываем все формы с классом ajax-form
    document.querySelectorAll('.ajax-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            
            if (submitButton) {
                const originalText = submitButton.innerHTML;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Обработка...';
                submitButton.disabled = true;
            }
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessNotification(data.message);
                    window.location.reload();
                } else {
                    alert('Ошибка: ' + (data.message || 'Произошла ошибка'));
                    if (submitButton) {
                        submitButton.innerHTML = originalText;
                        submitButton.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при выполнении операции');
                if (submitButton) {
                    submitButton.innerHTML = originalText;
                    submitButton.disabled = false;
                }
            });
        });
    });
});
</script>
