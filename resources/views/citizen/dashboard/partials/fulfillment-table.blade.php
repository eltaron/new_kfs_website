@if ($fulfillmentItems->isNotEmpty())
    <div class="white-section-card shadow-sm p-4 mb-5">
        <div class="d-flex align-items-center mb-3 border-bottom pb-2">
            <i class="fas fa-tasks text-warning fs-4 ms-2"></i>
            <h3 class="m-0 section-head">الاستيفاء — إجراءات مطلوبة منك</h3>
            <span class="badge bg-warning text-dark me-2">{{ $fulfillmentItems->count() }}</span>
        </div>
        <div class="table-responsive">
            <table id="fulfillmentTable" class="table table-hover align-middle custom-dash-table">
                <thead>
                    <tr>
                        <th>النظام</th>
                        <th>الخدمة</th>
                        <th>الإجراء المطلوب</th>
                        <th>تاريخ التقديم</th>
                        <th class="text-center">إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($fulfillmentItems as $item)
                        <tr>
                            <td data-label="النظام">
                                <span class="badge bg-{{ match ($item->type) { 'gis' => 'info', 'site_validity' => 'success', default => 'warning' } }}">
                                    {{ $item->source }}
                                </span>
                            </td>
                            <td data-label="الخدمة">{{ $item->reference }}</td>
                            <td data-label="الإجراء المطلوب">
                                <span class="fulfillment-badge-{{ match ($item->fulfillment_action) { 'مراجعة بيانات' => 'review', 'دفع رسوم' => 'pay', 'مستندات ناقصة', 'استيفاء مطلوب' => 'docs', default => 'retry' } }}">
                                    {{ $item->fulfillment_action }}
                                </span>
                                @if (!empty($item->fulfillment_reason))
                                    <br><small class="text-muted">{{ $item->fulfillment_reason }}</small>
                                @endif
                            </td>
                            <td data-label="تاريخ التقديم"><span class="text-muted small">{{ $item->created_at->format('Y/m/d') }}</span></td>
                            <td data-label="إجراء" class="text-center">
                                @if ($item->type === 'site_validity')
                                    @if ($item->fulfillment_action === 'دفع رسوم')
                                        <a href="{{ route('payments.pay', ['type' => config('site-validity.payable_alias', 'site_validity_request'), 'id' => $item->id]) }}"
                                           class="btn btn-danger btn-sm rounded-pill px-3">
                                            <i class="fas fa-credit-card"></i> دفع {{ number_format((float) $item->amount, 2) }} ج.م
                                        </a>
                                    @else
                                        <a href="{{ route('site-validity.requests.show', $item->id) }}#completion"
                                           class="btn btn-success btn-sm rounded-pill px-3">
                                            <i class="fas fa-file-upload"></i> إرسال الاستيفاء
                                        </a>
                                    @endif
                                @elseif ($item->fulfillment_action === 'دفع رسوم')
                                    <a href="{{ route('payments.pay', ['type' => $item->type === 'gis' ? 'gis_submission' : 'service_submission', 'id' => $item->id]) }}"
                                       class="btn btn-danger btn-sm rounded-pill px-3">
                                        <i class="fas fa-credit-card"></i> دفع الآن
                                    </a>
                                    @php($pendingFee = \Modules\Payments\Models\FulfillmentFee::query()->where('feeable_type', $item->type === 'gis' ? 'gis_submission' : 'service_submission')->where('feeable_id', (string) $item->id)->where('status', 'pending')->latest('id')->first())
                                    @if ($pendingFee)
                                        <a href="{{ route('payments.pay', ['type' => 'fulfillment_fee', 'id' => $pendingFee->id]) }}"
                                           class="btn btn-outline-danger btn-sm rounded-pill px-3 mt-1">
                                            <i class="fas fa-receipt"></i> رسوم استيفاء {{ number_format((float) $pendingFee->amount, 2) }} ج.م
                                        </a>
                                    @endif
                                @elseif ($item->fulfillment_action === 'مراجعة بيانات' || $item->fulfillment_action === 'تعديل بيانات')
                                    @if ($item->type === 'service')
                                        <a href="{{ route('citizen.submissions.show', $item->id) }}"
                                           class="btn btn-warning btn-sm rounded-pill px-3">
                                            <i class="fas fa-edit"></i> تعديل البيانات
                                        </a>
                                    @else
                                        <span class="text-muted small">بانتظار مراجعة الموظف</span>
                                    @endif
                                @elseif ($item->fulfillment_action === 'مستندات ناقصة')
                                    <a href="{{ route('citizen.dashboard.gis') }}"
                                       class="btn btn-info btn-sm rounded-pill px-3">
                                        <i class="fas fa-file-upload"></i> رفع المستندات
                                    </a>
                                @elseif ($item->fulfillment_action === 'إعادة تقديم')
                                    <a href="{{ route('services.index') }}"
                                       class="btn btn-secondary btn-sm rounded-pill px-3">
                                        <i class="fas fa-redo"></i> إعادة تقديم
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
