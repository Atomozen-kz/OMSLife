<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чек-лист бригады #{{ $session->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
        }

        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
            color: #007bff;
        }

        .header p {
            font-size: 10px;
            color: #666;
        }

        .info-section {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #f8f9fa;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-weight: bold;
            width: 180px;
            color: #555;
        }

        .info-value {
            flex: 1;
            color: #333;
        }

        .stats {
            display: inline-block;
            padding: 3px 8px;
            margin-right: 5px;
            border-radius: 3px;
            font-size: 10px;
        }

        .stats-danger {
            background-color: #dc3545;
            color: white;
        }

        .stats-success {
            background-color: #28a745;
            color: white;
        }

        .stats-info {
            background-color: #17a2b8;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th {
            background-color: #007bff;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
        }

        table td {
            border: 1px solid #ddd;
            padding: 8px;
            vertical-align: top;
        }

        table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }

        .badge-danger {
            background-color: #dc3545;
            color: white;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

        .badge-info {
            background-color: #17a2b8;
            color: white;
        }

        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 9px;
            color: #666;
        }

        .text-center {
            text-align: center;
        }

        .text-muted {
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <h3>СКЖ бригадаларында «ҚМГ» ҰК» АҚ жұмысшыларына арналған өмірлік маңызды қағидаларды тексеру парағы</h3>
{{--        <p></p>--}}
    </div>

    <div class="info-section">
        <div class="info-row">
            <div class="info-label">Дата и время заполнения:</div>
            <div class="info-value">{{ $session->formatted_completed_at }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Мастер (ФИО):</div>
            <div class="info-value">{{ $session->full_name_master }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Бригада:</div>
            <div class="info-value">{{ $session->brigade_name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Номер скважины:</div>
            <div class="info-value">{{ $session->well_number }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">ТК:</div>
            <div class="info-value">{{ $session->tk }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Статистика ответов:</div>
            <div class="info-value">
                <span class="stats stats-danger">Опасно: {{ $session->dangerous_count }}</span>
                <span class="stats stats-success">Безопасно: {{ $session->safe_count }}</span>
                <span class="stats stats-info">Другое: {{ $session->other_count }}</span>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">№</th>
                <th width="10%">Правило</th>
                <th width="40%">Наименование мероприятия</th>
                <th width="15%">Тип ответа</th>
                <th width="30%">Комментарий</th>
            </tr>
        </thead>
        <tbody>
            @foreach($session->responses as $index => $response)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $response->checklistItem->rule ?? '—' }}</td>
                <td>{{ $response->checklistItem->event_name ?? '—' }}</td>
                <td class="text-center">
                    @if($response->response_type === 'dangerous')
                        <span class="badge badge-danger">Опасно</span>
                    @elseif($response->response_type === 'safe')
                        <span class="badge badge-success">Безопасно</span>
                    @elseif($response->response_type === 'other')
                        <span class="badge badge-info">Другое</span>
                    @else
                        <span class="badge badge-secondary">{{ $response->response_type }}</span>
                    @endif
                </td>
                <td>{{ $response->response_text ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Документ создан: {{ now()->format('d.m.Y H:i') }}</p>
        <p>OMS Life - Система управления операционной деятельностью</p>
    </div>
</body>
</html>
