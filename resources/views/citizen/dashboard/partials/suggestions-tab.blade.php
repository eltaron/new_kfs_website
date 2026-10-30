<div class="tab-pane fade" id="mon-suggestions">
    @forelse($suggestions as $suggestion)
        <div class="dash-accordion-item mb-2">
            <button class="dash-accordion-header collapsed" data-bs-toggle="collapse" data-bs-target="#sugg-{{ $suggestion->id }}">
                <div class="title-side">
                    <span class="status-indicator {{ $suggestion->status }}"></span>
                    <h6>{{ $suggestion->subject }}</h6>
                </div>
                <div class="meta-side">
                    <span class="time-text">{{ $suggestion->created_at->diffForHumans() }}</span>
                    <i class="fas fa-chevron-down arrow-ico"></i>
                </div>
            </button>
            <div id="sugg-{{ $suggestion->id }}" class="collapse">
                <div class="p-4 border-top">
                    <div class="msg-box user">
                        <label>مقترحك:</label>
                        <p>{{ $suggestion->message }}</p>
                    </div>
                    @if ($suggestion->admin_reply)
                        <div class="msg-box admin">
                            <label>الرد الرسمي:</label>
                            <div class="reply-content">{{ $suggestion->admin_reply }}</div>
                        </div>
                    @else
                        <p class="small text-muted mt-3 italic"><i class="fas fa-hourglass-half me-1"></i> بانتظار المراجعة...</p>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-4 text-muted">
            <i class="fas fa-lightbulb fa-2x mb-2"></i>
            <p>لا توجد مقترحات مقدمة</p>
        </div>
    @endforelse
</div>