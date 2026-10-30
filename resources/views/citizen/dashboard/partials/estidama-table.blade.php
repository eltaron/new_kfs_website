<div class="table-responsive">
    <table id="estidamaTable" class="table table-hover align-middle custom-dash-table">
        <thead>
            <tr>
                <th>البرنامج</th>
                <th>النوع</th>
                <th>التاريخ</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            @php
                $merged = collect();
                foreach ($enrollments as $e) {
                    $merged->push((object) [
                        'program' => $e->trainingProgram?->title ?? 'برنامج تدريبي',
                        'type_label' => 'تسجيل',
                        'type_class' => 'success',
                        'date' => $e->created_at,
                        'status' => $e->status,
                    ]);
                }
                foreach ($trainingApplications as $t) {
                    $merged->push((object) [
                        'program' => $t->trainingProgram?->title ?? 'برنامج تدريبي',
                        'type_label' => 'طلب التحاق',
                        'type_class' => 'info',
                        'date' => $t->created_at,
                        'status' => $t->status,
                    ]);
                }
                $merged = $merged->sortByDesc('date');
            @endphp
            @forelse($merged as $item)
                <tr>
                    <td data-label="البرنامج">{{ $item->program }}</td>
                    <td data-label="النوع"><span class="badge bg-{{ $item->type_class }}">{{ $item->type_label }}</span></td>
                    <td data-label="التاريخ"><span class="text-muted small">{{ $item->date->format('Y/m/d') }}</span></td>
                    <td data-label="الحالة">
                        <span class="badge bg-{{ $item->status === 'approved' ? 'success' : ($item->status === 'pending' ? 'warning' : ($item->status === 'rejected' ? 'danger' : 'info')) }} text-white px-3 py-1" style="font-size:0.7rem">
                            @switch($item->status)
                                @case('approved') مقبول @break
                                @case('pending') قيد المراجعة @break
                                @case('rejected') مرفوض @break
                                @case('completed') مكتمل @break
                                @default {{ $item->status }}
                            @endswitch
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="dash-empty">
                        <i class="fas fa-graduation-cap fa-2x mb-2"></i>
                        <p>لم تسجل في أي برنامج استدامة بعد</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>