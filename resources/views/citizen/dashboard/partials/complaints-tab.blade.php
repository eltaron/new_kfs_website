<div class="tab-pane fade show active" id="mon-complaints">
    @forelse($complaints as $complaint)
        <div class="dash-accordion-item mb-2">
            <button class="dash-accordion-header collapsed" data-bs-toggle="collapse" data-bs-target="#comp-{{ $complaint->id }}">
                <div class="title-side">
                    <span class="status-indicator {{ $complaint->status }}"></span>
                    <h6>{{ $complaint->subject }}</h6>
                </div>
                <div class="meta-side">
                    <span class="time-text">{{ $complaint->created_at->diffForHumans() }}</span>
                    <span class="badge-status-{{ $complaint->status }}">
                        @lang("statuses.complaints.{$complaint->status}")
                    </span>
                    <i class="fas fa-chevron-down arrow-ico"></i>
                </div>
            </button>
            <div id="comp-{{ $complaint->id }}" class="collapse">
                <div class="p-4 border-top">
                    <div class="msg-box user">
                        <label>رسالتك:</label>
                        <p>{{ $complaint->message }}</p>
                    </div>
                    @if ($complaint->admin_reply)
                        <div class="msg-box admin">
                            <label>الرد الرسمي:</label>
                            <div class="reply-content">{!! clean($complaint->admin_reply) !!}</div>
                        </div>
                    @else
                        <p class="small text-muted mt-3 italic"><i class="fas fa-hourglass-half me-1"></i> بانتظار المراجعة من الجهة المختصة...</p>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-4 text-muted">
            <i class="fas fa-folder-open fa-2x mb-2"></i>
            <p>لا توجد شكاوي مسجلة</p>
        </div>
    @endforelse
</div>