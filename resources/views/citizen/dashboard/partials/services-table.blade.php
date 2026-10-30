<div class="table-responsive">
    <table id="servicesTable" class="table table-hover align-middle custom-dash-table">
        <thead>
            <tr>
                <th>#</th>
                <th>الخدمة</th>
                <th>التاريخ</th>
                <th>الحالة</th>
                <th class="text-center">إجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($serviceSubmissions as $sub)
                <tr>
                    <td data-label="رقم الطلب"><span class="fw-bold text-navy">#{{ $sub->id }}</span></td>
                    <td data-label="الخدمة">
                        <div class="d-flex align-items-center">
                            <div class="icon-sm-circle ms-2"><i class="{{ $sub->service?->icon ?? 'fas fa-cog' }}"></i></div>
                            <span>{{ $sub->service?->title ?? 'خدمة' }}</span>
                        </div>
                    </td>
                    <td data-label="التاريخ"><span class="text-muted small">{{ $sub->created_at->format('Y/m/d') }}</span></td>
                    <td data-label="الحالة">
                        <span class="badge-status-{{ $sub->status }}">
                            @switch($sub->status)
                                @case('pending') قيد المراجعة @break
                                @case('awaiting_payment') بانتظار الدفع @break
                                @case('in_progress') قيد التنفيذ @break
                                @case('paid') تم الدفع @break
                                @case('completed') تم التنفيذ @break
                                @case('rejected') مرفوض @break
                                @default {{ $sub->status }}
                            @endswitch
                        </span>
                    </td>
                    <td data-label="إجراءات" class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="{{ route('citizen.submissions.show', $sub->id) }}" class="btn btn-outline-gold btn-sm rounded-pill">
                                <span>التفاصيل</span>
                                <i class="fas fa-eye fa-xs ms-2"></i>
                            </a>
                            @if (method_exists($sub, 'canBePaid') && $sub->canBePaid())
                                <a href="{{ route('payments.pay', ['type' => 'service_submission', 'id' => $sub->id]) }}" class="btn btn-danger btn-sm rounded-pill">
                                    <i class="fas fa-credit-card"></i> سداد {{ number_format((float) $sub->total_amount, 2) }} ج.م
                                </a>
                            @endif
                            @if ($sub->fulfillment_action === 'payment' && $sub->fulfillment_status === 'requested' && ($fee = $sub->openFulfillmentFee()))
                                <a href="{{ route('payments.pay', ['type' => 'fulfillment_fee', 'id' => $fee->id]) }}" class="btn btn-danger btn-sm rounded-pill">
                                    <i class="fas fa-credit-card"></i> رسوم استيفاء {{ number_format((float) $fee->amount, 2) }} ج.م
                                </a>
                            @endif
                            @if ($sub->fulfillment_action === 'data_correction' && $sub->fulfillment_status === 'requested')
                                <a href="{{ route('citizen.submissions.show', $sub->id) }}" class="btn btn-warning btn-sm rounded-pill">
                                    <i class="fas fa-edit"></i> تعديل
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="dash-empty">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p>لم تقم بالتقديم على خدمات عامة بعد</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
