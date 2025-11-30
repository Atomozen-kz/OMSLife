@php
    use Illuminate\Support\Carbon;
@endphp

<style>
.answers-container {
    background: white;
    border-radius: 8px;
    padding: 0;
    margin-bottom: 15px;
}

.answers-header {
    background: #28a745;
    color: white;
    padding: 12px 15px;
    border-radius: 8px 8px 0 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.answers-title {
    font-size: 0.9rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

.answers-count {
    background: rgba(255,255,255,0.2);
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

.answers-content {
    padding: 15px;
}

.answer-item {
    padding: 15px 0;
    border-bottom: 1px solid #f1f3f4;
    display: flex;
    gap: 12px;
}

.answer-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.answer-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #667eea;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.answer-content {
    flex: 1;
}

.answer-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    flex-wrap: wrap;
    gap: 8px;
}

.answer-author-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
}

.answer-author-name {
    font-weight: 600;
    color: #495057;
}

.answer-date {
    color: #6c757d;
    font-size: 0.8rem;
}

.answer-badges {
    display: flex;
    gap: 6px;
    align-items: center;
}

.answer-badge {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 500;
    text-transform: uppercase;
}

.answer-badge.public {
    background: #d4edda;
    color: #155724;
}

.answer-badge.private {
    background: #fff3cd;
    color: #856404;
}

.answer-text {
    font-size: 0.9rem;
    line-height: 1.5;
    color: #495057;
    margin-bottom: 10px;
}

.answer-files {
    margin-top: 10px;
}

.answer-files-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.answer-file-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    background: #17a2b8;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.answer-file-btn:hover {
    background: #138496;
    color: white;
    text-decoration: none;
}

.answer-icon {
    width: 14px;
    height: 14px;
}

.no-answers {
    text-align: center;
    padding: 30px 15px;
    color: #6c757d;
}

.no-answers-text {
    font-size: 0.9rem;
    font-style: italic;
}

@media (max-width: 768px) {
    .answer-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }
    
    .answer-badges {
        order: -1;
    }
    
    .answer-files-list {
        flex-direction: column;
    }
}
</style>

<div class="answers-container">
    <div class="answers-header">
        <div class="answers-title">
            <svg class="answer-icon" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
            </svg>
            Ответы на обращение
        </div>
        <span class="answers-count">{{ $answers->count() }}</span>
    </div>
    
    <div class="answers-content">
        @forelse($answers as $answer)
            <div class="answer-item">
                <div class="answer-avatar">
                    {{ mb_substr($answer->answeredBy->name ?? 'С', 0, 1) }}
                </div>
                
                <div class="answer-content">
                    <div class="answer-header">
                        <div class="answer-author-info">
                            <span class="answer-author-name">{{ $answer->answeredBy->name ?? 'Система' }}</span>
                            <span class="answer-date">{{ Carbon::parse($answer->created_at)->format('d.m.Y в H:i') }}</span>
                        </div>
                        
                        <div class="answer-badges">
                            <span class="answer-badge {{ $answer->is_public ? 'public' : 'private' }}">
                                {{ $answer->is_public ? 'Публичный' : 'Приватный' }}
                            </span>
                            
                            @if($answer->hasAttachments())
                                <span class="answer-badge public">{{ $answer->getAttachmentsCount() }} файл(ов)</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="answer-text">{{ $answer->answer }}</div>
                    
                    @if($answer->hasAttachments())
                        <div class="answer-files">
                            <div class="answer-files-list">
                                @foreach($answer->media as $media)
                                    <a href="{{ asset('storage/'. $media->file_path) }}" target="_blank" class="answer-file-btn">
                                        Файл #{{ $media->id }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="no-answers">
                <div class="no-answers-text">Ответов на обращение пока нет</div>
            </div>
        @endforelse
    </div>
</div>
