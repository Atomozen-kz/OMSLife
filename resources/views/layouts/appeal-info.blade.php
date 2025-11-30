@php
    use App\Models\Appeal;
    use Illuminate\Support\Carbon;
@endphp

<style>
.appeal-info-card {
    background: #667eea;
    border-radius: 8px;
    padding: 0;
    margin-bottom: 20px;
    overflow: hidden;
}

.appeal-header {
    background: #667eea;
    padding: 20px;
    color: white;
}

.appeal-title {
    color: white;
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.appeal-status {
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
    color: white;
}

.appeal-status.status-new {
    background: #007bff;
}

.appeal-status.status-in-progress {
    background: #ffc107;
    color: #212529;
}

.appeal-status.status-answered {
    background: #17a2b8;
}

.appeal-status.status-closed {
    background: #28a745;
}

.appeal-status.status-rejected {
    background: #dc3545;
}

.appeal-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0;
    background: #667eea;
}

.appeal-field {
    padding: 15px 20px;
    color: white;
    border-right: 1px solid rgba(255,255,255,0.1);
}

.appeal-field:last-child {
    border-right: none;
}

.appeal-field-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    opacity: 0.8;
    margin-bottom: 5px;
    letter-spacing: 0.5px;
}

.appeal-field-value {
    font-size: 0.9rem;
    font-weight: 500;
    line-height: 1.3;
}

.appeal-body {
    background: white;
    padding: 0;
}

.appeal-section {
    background: white;
    margin-bottom: 15px;
    border-radius: 8px;
}

.appeal-section-header {
    background: #f8f9fa;
    padding: 12px 15px;
    border-bottom: 1px solid #e9ecef;
    font-weight: 600;
    font-size: 0.9rem;
    color: #495057;
    display: flex;
    align-items: center;
    gap: 8px;
}

.appeal-section-content {
    padding: 15px;
}

.appeal-description-text {
    font-size: 0.95rem;
    color: #495057;
    line-height: 1.5;
    margin: 0;
}

.appeal-files-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.appeal-file-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: #28a745;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.appeal-file-btn:hover {
    background: #218838;
    color: white;
    text-decoration: none;
}

.no-files {
    color: #6c757d;
    font-style: italic;
    font-size: 0.9rem;
}

.appeal-icon {
    width: 14px;
    height: 14px;
}

@media (max-width: 768px) {
    .appeal-grid {
        grid-template-columns: 1fr;
    }
    
    .appeal-field {
        border-right: none;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .appeal-field:last-child {
        border-bottom: none;
    }
    
    .appeal-title {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>

<div class="appeal-info-card">
    <div class="appeal-header">
        <div class="appeal-title">
            <span>{{ $appeal->title }}</span>
            @php
                $statusClass = match($appeal->status) {
                    Appeal::STATUS_NEW => 'status-new',
                    Appeal::STATUS_IN_PROGRESS => 'status-in-progress',
                    Appeal::STATUS_ANSWERED => 'status-answered',
                    Appeal::STATUS_CLOSED => 'status-closed',
                    Appeal::STATUS_REJECTED => 'status-rejected',
                    default => 'status-new'
                };
            @endphp
            <span class="appeal-status {{ $statusClass }}">{{ $appeal->status_name }}</span>
        </div>
        
        <div class="appeal-grid">
            <div class="appeal-field">
                <div class="appeal-field-label">Сотрудник</div>
                <div class="appeal-field-value">{{ $appeal->sotrudnik->fio }}</div>
            </div>
            
            <div class="appeal-field">
                <div class="appeal-field-label">Организация</div>
                <div class="appeal-field-value">{{ $appeal->organization->name_ru }}</div>
            </div>
            
            <div class="appeal-field">
                <div class="appeal-field-label">Тема обращения</div>
                <div class="appeal-field-value">{{ $appeal->topic->title_ru }}</div>
            </div>
            
            <div class="appeal-field">
                <div class="appeal-field-label">Дата создания</div>
                <div class="appeal-field-value">{{ Carbon::parse($appeal->created_at)->format('d.m.Y в H:i') }}</div>
            </div>
        </div>
    </div>
    
    <div class="appeal-body">
        @if($appeal->description)
            <div class="appeal-section">
                <div class="appeal-section-header">
                    <svg class="appeal-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Описание обращения
                </div>
                <div class="appeal-section-content">
                    <div class="appeal-description-text">{{ $appeal->description }}</div>
                </div>
            </div>
        @endif
        
        <div class="appeal-section">
            <div class="appeal-section-header">
                <svg class="appeal-icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
                Прикрепленные файлы
            </div>
            <div class="appeal-section-content">
                @if($appeal->appealMedia->isEmpty())
                    <div class="no-files">Файлы не прикреплены</div>
                @else
                    <div class="appeal-files-list">
                        @foreach($appeal->appealMedia as $media)
                            <a href="{{ asset('storage/'. $media->file_path) }}" target="_blank" class="appeal-file-btn">
                                Файл #{{ $media->id }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
