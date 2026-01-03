<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <h5 class="card-title text-muted">План</h5>
                <h3 class="mb-0">{{ $plan->plan ?? 0 }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <h5 class="card-title text-muted">Факт</h5>
                <h3 class="mb-0 {{ ($plan->fact ?? 0) >= ($plan->plan ?? 0) ? 'text-success' : 'text-danger' }}">
                    {{ $plan->fact ?? 0 }}
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <h5 class="card-title text-muted">Отклонение</h5>
                @php
                    $deviation = ($plan->fact ?? 0) - ($plan->plan ?? 0);
                @endphp
                <h3 class="mb-0 {{ $deviation >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $deviation >= 0 ? '+' : '' }}{{ $deviation }}
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <h5 class="card-title text-muted">УНВ План</h5>
                <h3 class="mb-0">{{ $plan->unv_plan ?? 0 }} ч</h3>
            </div>
        </div>
    </div>
</div>

