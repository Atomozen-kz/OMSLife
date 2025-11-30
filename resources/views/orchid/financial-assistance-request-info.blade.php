<div class="bg-body-tertiary rounded p-4 mb-3">
    <h5 class="mb-3">📋 Основная информация</h5>

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <strong>ID заявки:</strong>
                <span class="badge bg-primary">#{{ $request->id }}</span>
            </div>

            <div class="mb-3">
                <strong>Статус:</strong>
                @php
                    $statusClass = match($request->status) {
                        1 => 'badge-warning',
                        2 => 'badge-success',
                        3 => 'badge-danger',
                        default => 'badge-secondary'
                    };
                @endphp
                <span class="bg-primary badge {{ $statusClass }}">{{ $request->status_name }}</span>
            </div>

            <div class="mb-3">
                <strong>Тип материальной помощи:</strong>
                <br>{{ $request->assistanceType->name ?? 'Не указано' }}
            </div>

            <div class="mb-3">
                <strong>Дата подачи:</strong>
                <br>{{ $request->submitted_at ? $request->submitted_at->format('d.m.Y H:i') : 'Не указана' }}
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <strong>Заявитель:</strong>
                <br>{{ $request->sotrudnik->Fio ?? $request->sotrudnik->name ?? 'Не указано' }}
                @if($request->sotrudnik->position->name_ru)
                    <br><small class="text-muted">{{ $request->sotrudnik->position->name_ru }}</small>
                @endif
                @if($request->sotrudnik->organization->name_ru)
                    <br><small class="text-muted">{{ $request->sotrudnik->organization->name_ru }}</small>
                @endif
            </div>

            <div class="mb-3">
                <strong>Дата рассмотрения:</strong>
                <br>{{ $request->processed_at ? $request->processed_at->format('d.m.Y H:i') : 'Не рассмотрена' }}
            </div>

            @if($request->signer)
            <div class="mb-3">
                <strong>Подписант:</strong>
                <br>{{ $request->signer->full_name }}
                @if($request->signer->position)
                    <br><small class="text-muted">{{ $request->signer->position }}</small>
                @endif
            </div>
            @endif
        </div>
    </div>

    @if($request->comment)
    <div class="mt-3 p-3 bg-light rounded">
        <strong>Комментарий к решению:</strong>
        <p class="mb-0 mt-2">{{ $request->comment }}</p>
    </div>
    @endif
</div>
