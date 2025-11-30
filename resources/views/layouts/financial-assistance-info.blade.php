<div class="bg-body-tertiary rounded p-4 mb-3">
    <h5 class="mb-3">Информация о типе материальной помощи</h5>

    <div class="row">
        <div class="col-md-3">
            <strong>Статус:</strong>
            @if($type->status)
                <span class="badge bg-success">Активен</span>
            @else
                <span class="badge bg-secondary">Неактивен</span>
            @endif
        </div>

        <div class="col-md-3">
            <strong>Количество полей:</strong>
            <span class="badge bg-info">{{ $type->typeRows->count() }}</span>
        </div>

        <div class="col-md-3">
            <strong>Шаблон заявления:</strong>
            @if($type->statement_html)
                <span class="badge bg-success">Настроен</span>
            @else
                <span class="badge bg-warning">Не настроен</span>
            @endif
        </div>
    </div>

    @if($type->description)
        <div class="mt-3">
            <strong>Описание:</strong>
            <p class="mb-0 mt-1">{{ $type->description }}</p>
        </div>
    @endif

    <div class="mt-3">
        <small class="text-muted">
            <strong>Создан:</strong> {{ $type->created_at->format('d.m.Y H:i') }}
            @if($type->updated_at != $type->created_at)
                | <strong>Обновлен:</strong> {{ $type->updated_at->format('d.m.Y H:i') }}
            @endif
        </small>
    </div>
</div>
