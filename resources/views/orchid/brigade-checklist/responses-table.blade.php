<div class="bg-white rounded shadow-sm p-4 mb-4">
    <h4 class="mb-3">Ответы на вопросы чек-листа</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th width="5%">№</th>
                    <th width="10%">Правило</th>
                    <th width="40%">Наименование мероприятия</th>
                    <th width="15%">Тип ответа</th>
                    <th width="30%">Комментарий</th>
                </tr>
            </thead>
            <tbody>
                @foreach($responses as $index => $response)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $response->checklistItem->rule ?? '—' }}</td>
                    <td>{{ $response->checklistItem->event_name ?? '—' }}</td>
                    <td class="text-center">
                        @if($response->response_type === 'dangerous')
                            <span class="badge bg-danger">Опасно</span>
                        @elseif($response->response_type === 'safe')
                            <span class="badge bg-success">Безопасно</span>
                        @elseif($response->response_type === 'other')
                            <span class="badge bg-info">Другое</span>
                        @else
                            <span class="badge bg-secondary">{{ $response->response_type }}</span>
                        @endif
                    </td>
                    <td>{{ $response->response_text ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
