@php
/** @var \Illuminate\Support\Collection $history */
@endphp
<div class="card mt-3">
    <div class="card-header"><strong>История статусов</strong></div>
    <div class="card-body p-0">
        @if($history->isEmpty())
            <div class="p-3">История изменений статуса отсутствует.</div>
        @else
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Статус</th>
                        <th>Изменил</th>
                        <th>Примечание</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($history->sortByDesc('created_at') as $item)
                        <tr>
                            <td>{{ optional($item->created_at)->format('d.m.Y H:i') }}</td>
                            <td>{{ \App\Models\BankIdea::$statusLabels[$item->status] ?? $item->status }}</td>
                            <td>{{ optional($item->changer)->last_name ? optional($item->changer)->last_name . ' ' . optional($item->changer)->first_name : (optional($item->changer)->email ?? 'Система') }}</td>
                            <td>{{ $item->note ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

