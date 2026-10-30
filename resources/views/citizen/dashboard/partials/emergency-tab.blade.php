<div class="tab-pane fade" id="mon-emergency">
    @forelse($emergencyReports as $report)
        <div class="dash-accordion-item mb-2">
            <button class="dash-accordion-header collapsed" data-bs-toggle="collapse" data-bs-target="#emerg-{{ $report->id }}">
                <div class="title-side">
                    <span class="status-indicator {{ $report->status }}"></span>
                    <h6>{{ $report->report_type }} — {{ $report->location_type }}</h6>
                </div>
                <div class="meta-side">
                    <span class="time-text">{{ $report->created_at->diffForHumans() }}</span>
                    <span class="badge-status-{{ $report->status }}">
                        @switch($report->status)
                            @case('pending') قيد المراجعة @break
                            @case('in_progress') قيد المعالجة @break
                            @case('resolved') تم الحل @break
                            @default {{ $report->status }}
                        @endswitch
                    </span>
                    <i class="fas fa-chevron-down arrow-ico"></i>
                </div>
            </button>
            <div id="emerg-{{ $report->id }}" class="collapse">
                <div class="p-4 border-top">
                    <p><strong>الموقع:</strong> {{ $report->location_description }}</p>
                    <p><strong>التفاصيل:</strong> {{ $report->details }}</p>
                    @if ($report->admin_reply)
                        <div class="msg-box admin mt-3">
                            <label>الرد الرسمي:</label>
                            <div class="reply-content">{{ $report->admin_reply }}</div>
                        </div>
                    @else
                        <p class="small text-muted mt-3 italic"><i class="fas fa-hourglass-half me-1"></i> بانتظار المعالجة...</p>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-4 text-muted">
            <i class="fas fa-shield-alt fa-2x mb-2"></i>
            <p>لا توجد بلاغات طوارئ مسجلة</p>
        </div>
    @endforelse
</div>