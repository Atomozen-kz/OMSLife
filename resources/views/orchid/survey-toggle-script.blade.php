<script>
    document.addEventListener('DOMContentLoaded', function() {
        const switcher = document.querySelector('.is-all-switcher input[type=checkbox]');
        const organizationsField = document.querySelector('.organizations-field').closest('.field-wrapper');

        function toggleOrganizationsField() {
            console.log('YES');
            if (switcher.checked) {
                if (organizationsField) {
                    organizationsField.style.display = 'none';
                }
            } else {
                if (organizationsField) {
                    organizationsField.style.display = 'block';
                }
            }
        }

        if (switcher && organizationsField) {
            // Инициализация видимости при загрузке
            toggleOrganizationsField();
            // Прослушивание событий изменения состояния переключателя
            switcher.addEventListener('change', toggleOrganizationsField);
        }

        // Дополнительные логи для отладки
        console.log('Switcher элемент:', switcher);
        console.log('Organizations Field элемент:', organizationsField);
    });
</script>
