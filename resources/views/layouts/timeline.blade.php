@php
    use App\Models\Appeal;
    use Illuminate\Support\Carbon;
@endphp

<style>
.timeline {
    background: white;
    border-radius: 8px;
    padding: 0;
    margin-bottom: 15px;
}

.timeline-header {
    background: #f8f9fa;
    padding: 12px 15px;
    border-bottom: 1px solid #e9ecef;
    font-weight: 600;
    font-size: 0.9rem;
    color: #495057;
    display: flex;
    align-items: center;
    gap: 8px;
    border-radius: 8px 8px 0 0;
}

.timeline-content {
    padding: 15px;
}

.timeline-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f1f3f4;
}

.timeline-item:last-child {
    border-bottom: none;
}

.timeline-dot {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    flex-shrink: 0;
    margin-top: 2px;
}

.timeline-dot.status-new {
    background: #007bff;
}

.timeline-dot.status-in-progress {
    background: #ffc107;
}

.timeline-dot.status-answered {
    background: #17a2b8;
}

.timeline-dot.status-closed {
    background: #28a745;
}

.timeline-dot.status-rejected {
    background: #dc3545;
}

.timeline-item-content {
    flex: 1;
}

.timeline-status-change {
    font-size: 0.9rem;
    color: #495057;
    margin-bottom: 4px;
}

.timeline-status-change .status-from {
    color: #6c757d;
}

.timeline-status-change .status-to {
    font-weight: 600;
}

.timeline-meta {
    font-size: 0.8rem;
    color: #6c757d;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.timeline-comment {
    font-size: 0.85rem;
    color: #495057;
    padding: 8px 12px;
    background: #f8f9fa;
    border-radius: 4px;
    margin-top: 8px;
}

.timeline-icon {
    width: 14px;
    height: 14px;
}

@media (max-width: 768px) {
    .timeline-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
}
</style>

<div class="timeline">
    <div class="timeline-header">
        <svg class="timeline-icon" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
        </svg>
        История изменения статуса
    </div>
    
    <div class="timeline-content">
        @forelse($statusHistory as $index => $history)
            @php
                $statusClass = match($history->new_status) {
                    Appeal::STATUS_NEW => 'status-new',
                    Appeal::STATUS_IN_PROGRESS => 'status-in-progress', 
                    Appeal::STATUS_ANSWERED => 'status-answered',
                    Appeal::STATUS_CLOSED => 'status-closed',
                    Appeal::STATUS_REJECTED => 'status-rejected',
                    default => 'status-new'
                };
            @endphp
            
            <div class="timeline-item">
                <div class="timeline-dot {{ $statusClass }}"></div>
                
                <div class="timeline-item-content">
                    <div class="timeline-status-change">
                        @if($history->old_status)
                            <span class="status-from">{{ $history->old_status_name }}</span>
                            →
                        @endif
                        <span class="status-to">{{ $history->status_name }}</span>
                    </div>
                    
                    <div class="timeline-meta">
                        <span>{{ $history->changedBy->name ?? 'Система' }}</span>
                        <span>{{ Carbon::parse($history->created_at)->format('d.m.Y в H:i') }}</span>
                    </div>
                    
                    @if($history->comment)
                        <div class="timeline-comment">{{ $history->comment }}</div>
                    @endif
                </div>
            </div>
        @empty
            <div class="timeline-item">
                <div class="text-muted text-center">
                    <em>История изменений отсутствует</em>
                </div>
            </div>
        @endforelse
    </div>
</div>
