<div class="bg-body-tertiary rounded p-4 mb-3">
    <h5 class="mb-3">📈 История обработки</h5>

    @if($request->statusHistory && $request->statusHistory->count() > 0)
        <div class="timeline">
            @foreach($request->statusHistory as $history)
                <div class="timeline-item mb-3">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            @php
                                $iconClass = match($history->status) {
                                    1 => 'bg-warning',
                                    2 => 'bg-success',
                                    3 => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                                $icon = match($history->status) {
                                    1 => 'clock',
                                    2 => 'check',
                                    3 => 'close',
                                    default => 'question'
                                };
                            @endphp
                            <div class="timeline-icon {{ $iconClass }} rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 40px; height: 40px;">
                                <i class="icon-{{ $icon }} text-white"></i>
                            </div>
                        </div>

                        <div class="flex-grow-1 ms-3">
                            <div class="card border-0 bg-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="card-title mb-1">
                                                Статус изменен на:
                                                <span class="badge {{ $iconClass }}">
                                                    {{ $request::getStatuses()[$history->new_status] ?? 'Неизвестно' }}
                                                </span>
                                            </h6>

                                            @if($history->comment)
                                                <p class="card-text text-muted">{{ $history->comment }}</p>
                                            @endif

                                            <small class="text-muted">
                                                <i class="icon-user"></i>
                                                {{ $history->changedBy->name ?? 'Система' }}
                                            </small>
                                        </div>

                                        <div class="text-end">
                                            <small class="text-muted">
                                                {{ $history->changed_at ? $history->changed_at->format('d.m.Y H:i') : $history->created_at->format('d.m.Y H:i') }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center text-muted py-4">
            <i class="icon-clock" style="font-size: 48px; opacity: 0.3;"></i>
            <p class="mt-3">История изменений пуста</p>
            <small>Заявка была создана {{ $request->created_at->format('d.m.Y в H:i') }}</small>
        </div>
    @endif
</div>

<style>
.timeline-item:not(:last-child) .timeline-icon::after {
    content: '';
    position: absolute;
    top: 40px;
    left: 50%;
    transform: translateX(-50%);
    width: 2px;
    height: 30px;
    background-color: #dee2e6;
}

.timeline-icon {
    position: relative;
}
</style>
