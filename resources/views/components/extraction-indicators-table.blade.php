<table class="table">
    <thead>
    <tr>
        <th>План</th>
        <th>Фактическая добыча</th>
        <th>Дата</th>
        <th>Действия</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($indicators as $indicator)
        <tr>
            <td>{{ number_format($indicator->plan, 0, ',', ' ') }}</td>
            <td>{{ number_format($indicator->real, 0, ',', ' ') }}</td>
            <td>{{ \Carbon\Carbon::make($indicator->date)->format('d.m.Y') }}</td>
            <td>
                <a href="#"
                   data-controller="modal"
                   data-action="modal#open"
                   data-modal-target="modal"
                   data-modal-title="Редактировать"
                   data-modal-url="{{ route('platform.saveIndicator', ['indicator' => $indicator->id]) }}"
                   class="btn btn-sm btn-primary">
                    Редактировать
                </a>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
