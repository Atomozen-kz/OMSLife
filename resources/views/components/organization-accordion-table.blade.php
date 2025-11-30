<table class="table table-condensed" style="border-collapse:collapse;">
    <thead>
    <tr>
        <th>#</th>
        <th>Название (RU)</th>
        <th>Название (KZ)</th>
        <th>Промзона</th>
        <th>Действия</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($structures as $structure)
        <tr data-toggle="collapse" data-target="#structure-{{ $structure['id'] }}" class="accordion-toggle">
            <td>{{ $structure['id'] }}</td>
            <td>{{ $structure['name_ru'] }}</td>
            <td>{{ $structure['name_kz'] }}</td>
            <td>{{ $structure['is_promzona'] ? 'Да' : 'Нет' }}</td>
            <td>
                <button
                    class="btn btn-sm btn-warning"
                    onclick="editStructure({{ $structure['id'] }})">
                    Редактировать
                </button>
            </td>
        </tr>
        <tr>
            <td colspan="5" class="hiddenRow">
                <div id="structure-{{ $structure['id'] }}" class="accordian-body collapse">
                    @if (!empty($structure['children']))
                        <table class="table table-condensed">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Название (RU)</th>
                                <th>Название (KZ)</th>
                                <th>Промзона</th>
                                <th>Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($structure['children'] as $child)
                                <tr>
                                    <td>{{ $child['id'] }}</td>
                                    <td>{{ $child['name_ru'] }}</td>
                                    <td>{{ $child['name_kz'] }}</td>
                                    <td>{{ $child['is_promzona'] ? 'Да' : 'Нет' }}</td>
                                    <td>
                                        <button
                                            class="btn btn-sm btn-warning"
                                            onclick="editStructure({{ $child['id'] }})">
                                            Редактировать
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-muted">Нет дочерних элементов</div>
                    @endif
                </div>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Проверка кликов по строкам
        const rows = document.querySelectorAll('.accordion-toggle');
        rows.forEach(row => {
            row.addEventListener('click', () => {
                const target = row.getAttribute('data-target');
                const element = document.querySelector(target);
                if (element) {
                    element.classList.toggle('show'); // Меняем состояние "показать/скрыть"
                }
            });
        });
    });

    function editStructure(id) {
        // Открываем модальное окно с данными структуры
        const modal = document.querySelector('[data-modal="createOrUpdateModal"]');
        // Используйте AJAX или другой метод для подгрузки данных
        fetch(`/platform/async/organization-structure/${id}`)
            .then(response => response.json())
            .then(data => {
                // Заполняем форму модального окна данными
                Object.keys(data.structure).forEach(key => {
                    const input = modal.querySelector(`[name="structure.${key}"]`);
                    if (input) {
                        input.value = data.structure[key];
                    }
                });
                // Открываем модальное окно
                modal.click();
            });
    }

</script>


