<div class="bg-body-tertiary rounded p-4 mb-3">
    <h5 class="mb-3">📊 Статистика заявок</h5>
    
    <div class="row">
        <div class="col-md-3">
            <div class="card border-0 bg-primary text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $total_count }}</h3>
                    <small>Всего заявок</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 bg-warning text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $pending_count }}</h3>
                    <small>На рассмотрении</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 bg-success text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $approved_count }}</h3>
                    <small>Одобрено</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 bg-danger text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $rejected_count }}</h3>
                    <small>Отклонено</small>
                </div>
            </div>
        </div>
    </div>
    
    @if($total_count > 0)
    <div class="row mt-3">
        <div class="col-12">
            <div class="progress" style="height: 20px;">
                <div class="progress-bar bg-warning" role="progressbar" 
                     style="width: {{ ($pending_count / $total_count) * 100 }}%" 
                     title="На рассмотрении: {{ $pending_count }}">
                </div>
                <div class="progress-bar bg-success" role="progressbar" 
                     style="width: {{ ($approved_count / $total_count) * 100 }}%" 
                     title="Одобрено: {{ $approved_count }}">
                </div>
                <div class="progress-bar bg-danger" role="progressbar" 
                     style="width: {{ ($rejected_count / $total_count) * 100 }}%" 
                     title="Отклонено: {{ $rejected_count }}">
                </div>
            </div>
            <small class="text-muted mt-1 d-block">
                Распределение заявок: 
                {{ round(($pending_count / $total_count) * 100, 1) }}% на рассмотрении, 
                {{ round(($approved_count / $total_count) * 100, 1) }}% одобрено, 
                {{ round(($rejected_count / $total_count) * 100, 1) }}% отклонено
            </small>
        </div>
    </div>
    @endif
</div>
