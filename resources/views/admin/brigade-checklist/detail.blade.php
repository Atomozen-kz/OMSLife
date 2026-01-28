@extends('platform::dashboard')

@section('title', 'Детали чек-листа')
@section('description', 'Подробная информация о заполненном чек-листе')

@section('navbar')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">Детали чек-листа</h1>
        <div>
            <a href="{{ route('platform.brigade-checklist.session.export-pdf', $session->id) }}"
               class="btn btn-danger me-2">
                <i class="icon-printer"></i> Экспорт в PDF
            </a>
            <a href="{{ route('platform.brigade-checklist.responses') }}"
               class="btn btn-secondary">
                <i class="icon-arrow-left"></i> Назад к списку
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <!-- Общая информация -->
            <div class="bg-white rounded shadow-sm p-4 mb-4">
                <h4 class="mb-3">Общая информация</h4>
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Дата и время заполнения:</dt>
                            <dd class="col-sm-7">{{ $session->formatted_completed_at }}</dd>

                            <dt class="col-sm-5">Мастер (ФИО):</dt>
                            <dd class="col-sm-7">{{ $session->full_name_master }}</dd>

                            <dt class="col-sm-5">Бригада:</dt>
                            <dd class="col-sm-7">{{ $session->brigade_name }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Номер скважины:</dt>
                            <dd class="col-sm-7">{{ $session->well_number }}</dd>

                            <dt class="col-sm-5">ТК:</dt>
                            <dd class="col-sm-7">{{ $session->tk }}</dd>

                            <dt class="col-sm-5">Статистика ответов:</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-danger">Опасно: {{ $session->dangerous_count }}</span>
                                <span class="badge bg-success">Безопасно: {{ $session->safe_count }}</span>
                                <span class="badge bg-info">Другое: {{ $session->other_count }}</span>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Таблица с ответами -->
            @include('orchid.brigade-checklist.responses-table', ['responses' => $responses])
        </div>
    </div>
@endsection
