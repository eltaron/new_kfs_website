<div class="tab-pane fade" id="mon-contact">
    @forelse($contactMessages as $msg)
        <div class="dash-accordion-item mb-2">
            <button class="dash-accordion-header collapsed" data-bs-toggle="collapse" data-bs-target="#contact-{{ $msg->id }}">
                <div class="title-side">
                    <h6>{{ $msg->subject }}</h6>
                </div>
                <div class="meta-side">
                    <span class="time-text">{{ $msg->created_at->diffForHumans() }}</span>
                    <i class="fas fa-chevron-down arrow-ico"></i>
                </div>
            </button>
            <div id="contact-{{ $msg->id }}" class="collapse">
                <div class="p-4 border-top">
                    <div class="msg-box user">
                        <label>رسالتك:</label>
                        <p>{{ $msg->message }}</p>
                    </div>
                    @if ($msg->admin_reply)
                        <div class="msg-box admin">
                            <label>الرد الرسمي:</label>
                            <div class="reply-content">{{ $msg->admin_reply }}</div>
                        </div>
                    @else
                        <p class="small text-muted mt-3 italic"><i class="fas fa-hourglass-half me-1"></i> بانتظار المراجعة...</p>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-4 text-muted">
            <i class="fas fa-envelope fa-2x mb-2"></i>
            <p>لم ترسل أي رسائل تواصل بعد</p>
        </div>
    @endforelse
</div>