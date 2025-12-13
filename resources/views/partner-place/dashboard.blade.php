@extends('partner-place.layout')

@section('title', 'Дашборд - ' . $partnerPlace->name)

@section('content')
    <h2 class="mb-4">Добро пожаловать, {{ $partnerPlace->name }}</h2>

    @if($partnerPlace->address)
        <p class="text-muted mb-4">
            <i class="bi bi-geo-alt"></i> {{ $partnerPlace->address }}
            @if($partnerPlace->category)
                | <span class="badge bg-secondary">{{ $partnerPlace->category }}</span>
            @endif
        </p>
    @endif

    <!-- Сводная статистика -->
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-card today">
                <div class="number">{{ $visitsToday }}</div>
                <div class="label">Сегодня</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-card week">
                <div class="number">{{ $visitsThisWeek }}</div>
                <div class="label">За неделю</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-card month">
                <div class="number">{{ $visitsThisMonth }}</div>
                <div class="label">За месяц</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-card total">
                <div class="number">{{ $totalVisits }}</div>
                <div class="label">Всего</div>
            </div>
        </div>
    </div>

    <!-- QR-код -->
    <div class="alert alert-info mb-4">
        <strong>QR-код для сканирования:</strong>
        <code class="ms-2">{{ $partnerPlace->qr_code }}</code>
    </div>

    <!-- Фильтр по дате -->
    <div class="filter-form">
        <form method="GET" action="{{ route('partner-place.dashboard') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="date_from" class="form-label">Дата с</label>
                <input type="date" name="date_from" id="date_from" class="form-control" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-4">
                <label for="date_to" class="form-label">Дата по</label>
                <input type="date" name="date_to" id="date_to" class="form-control" value="{{ $dateTo }}">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Применить фильтр</button>
                <a href="{{ route('partner-place.dashboard') }}" class="btn btn-outline-secondary">Сбросить</a>
            </div>
        </form>
    </div>

    <!-- Таблица посетителей -->
    <div class="table-container">
        <h5 class="mb-3">Список посетителей</h5>

        @if($visits->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>ФИО</th>
                            <th>Табельный номер</th>
                            <th>Дата и время</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($visits as $index => $visit)
                            <tr>
                                <td>{{ $visits->firstItem() + $index }}</td>
                                <td>{{ $visit->sotrudnik->full_name ?? 'Не указано' }}</td>
                                <td>{{ $visit->sotrudnik->tabel_nomer ?? '-' }}</td>
                                <td>{{ $visit->visited_at->format('d.m.Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Пагинация -->
            <div class="d-flex justify-content-center">
                {{ $visits->links() }}
            </div>
        @else
            <div class="alert alert-secondary text-center">
                Нет данных о посещениях за выбранный период
            </div>
        @endif
    </div>
@endsection

