<div class="table-responsive">
    <table id="gisTable" class="table table-hover align-middle custom-dash-table">
        <thead>
            <tr>
                <th>#</th>
                <th>الخدمة</th>
                <th>التاريخ</th>
                <th>الحالة</th>
                <th>الدفع</th>
            </tr>
        </thead>
        <tbody>
            @forelse($gisSubmissions as $sub)
                <tr>
                    <td data-label="رقم الطلب"><span class="fw-bold text-navy">#{{ substr($sub->id, 0, 8) }}</span></td>
                    <td data-label="الخدمة">{{ $sub->subService?->name ?? 'خدمة مكانية' }}</td>
                    <td data-label="التاريخ"><span class="text-muted small">{{ $sub->created_at->format('Y/m/d') }}</span></td>
                    <td data-label="الحالة">
                        <span class="badge-status-{{ $sub->status }}">
                            @switch($sub->status)
                                @case('received') تم الاستلام @break
                                @case('processing') قيد المعالجة @break
                                @case('completed') مكتمل @break
                                @case('rejected') مرفوض @break
                                @default {{ $sub->status }}
                            @endswitch
                        </span>
                    </td>
                    <td data-label="الدفع">
                        <span class="badge bg-{{ $sub->payment_status === 'paid' ? 'success' : ($sub->payment_status === 'pending' ? 'warning' : 'secondary') }} text-white px-3 py-1" style="font-size:0.7rem">
                            {{ $sub->payment_status === 'paid' ? 'مدفوع' : ($sub->payment_status === 'pending' ? 'غير مدفوع' : $sub->payment_status) }}
                        </span>
                        @if (method_exists($sub, 'canBePaid') && $sub->canBePaid())
                            <a href="{{ route('payments.pay', ['type' => 'gis_submission', 'id' => $sub->id]) }}" class="btn btn-danger btn-sm rounded-pill mt-1">
                                <i class="fas fa-credit-card"></i> سداد {{ number_format((float) $sub->total_amount, 2) }} ج.م
                            </a>
                        @endif
                        @if ($sub->fulfillment_action === 'payment' && $sub->fulfillment_status === 'requested' && ($gisFee = $sub->openFulfillmentFee()))
                            <a href="{{ route('payments.pay', ['type' => 'fulfillment_fee', 'id' => $gisFee->id]) }}" class="btn btn-outline-danger btn-sm rounded-pill mt-1">
                                <i class="fas fa-receipt"></i> رسوم استيفاء {{ number_format((float) $gisFee->amount, 2) }} ج.م
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="dash-empty">
                        <i class="fas fa-map-marked-alt fa-2x mb-2"></i>
                        <p>لم تقم بالتقديم على خدمات مكانية بعد</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>