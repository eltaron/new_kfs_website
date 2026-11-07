<x-filament::page>
    @php
        $stats = $this->getStats();
        $activity = $this->getActivityData();
        $stages = $this->getStageBreakdown();
        $centers = $this->getCenterBreakdown();
        $recent = $this->getRecentOrders();
        
        $stageLabels = [
            'created' => 'قرار جديد',
            'engineering_review' => 'مراجعة التنظيم',
            'spatial_pending' => 'تحديد مسئول البعد المكاني',
            'spatial_dimensioned' => 'إضافة البعد المكاني',
            'pdf_ready' => 'مراجعة المركز الرئيسي',
            'systems_processing' => 'التحليل المكاني',
            'local_unit_approval' => 'الاعتماد من الوحدة المحلية',
            'gis_pending' => 'التوجيه للاعتماد',
            'visa_pending' => 'الاعتماد من السلطة المختصة',
            'completed' => 'منتهية',
        ];
    @endphp

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div class="fi-card fi-stats-overview-card bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</div>
            <div class="text-sm text-gray-500">إجمالي القرارات</div>
        </div>
        <div class="fi-card fi-stats-overview-card bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm border-r-4 border-danger-500">
            <div class="text-2xl font-bold text-danger-600">{{ number_format($stats['new_violations']) }}</div>
            <div class="text-sm text-gray-500">مخالفات بدون ترخيص</div>
        </div>
        <div class="fi-card fi-stats-overview-card bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm border-r-4 border-warning-500">
            <div class="text-2xl font-bold text-warning-600">{{ number_format($stats['licensed_violations']) }}</div>
            <div class="text-sm text-gray-500">مخالفات ترخيص</div>
        </div>
        <div class="fi-card fi-stats-overview-card bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm border-r-4 border-success-500">
            <div class="text-2xl font-bold text-success-600">{{ number_format($stats['completed']) }}</div>
            <div class="text-sm text-gray-500">تم التنفيذ</div>
        </div>
        <div class="fi-card fi-stats-overview-card bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm border-r-4 border-primary-500">
            <div class="text-2xl font-bold text-primary-600">{{ number_format($stats['in_review']) }}</div>
            <div class="text-sm text-gray-500">قيد المراجعة</div>
        </div>
        <div class="fi-card fi-stats-overview-card bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm border-r-4 border-gray-500">
            <div class="text-2xl font-bold text-gray-600">{{ number_format($stats['preparing']) }}</div>
            <div class="text-sm text-gray-500">قيد الإعداد</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Stage Breakdown --}}
        <div class="fi-card bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold mb-4">توزيع القرارات حسب المرحلة</h3>
            @forelse($stages as $stage => $count)
                @php
                    $percent = $stats['total'] > 0 ? round(($count / $stats['total']) * 100) : 0;
                    $color = match($stage) {
                        'completed' => 'success',
                        'engineering_review' => 'warning',
                        'systems_processing' => 'violet',
                        'local_unit_approval' => 'teal',
                        'gis_pending' => 'violet',
                        'visa_pending' => 'indigo',
                        'created' => 'gray',
                        default => 'primary',
                    };
                @endphp
                <div class="mb-3">
                    <div class="flex justify-between text-sm mb-1">
                        <span>{{ $stageLabels[$stage] ?? $stage }}</span>
                        <span class="font-semibold">{{ $count }} ({{ $percent }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-{{ $color }}-500 h-2 rounded-full" style="width: {{ $percent }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-gray-500">لا توجد بيانات</p>
            @endforelse
        </div>

        {{-- Center Breakdown --}}
        <div class="fi-card bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold mb-4">توزيع القرارات حسب المركز</h3>
            @forelse($centers as $center => $count)
                @php
                    $percent = $stats['total'] > 0 ? round(($count / $stats['total']) * 100) : 0;
                @endphp
                <div class="mb-3">
                    <div class="flex justify-between text-sm mb-1">
                        <span>{{ $center }}</span>
                        <span class="font-semibold">{{ $count }} ({{ $percent }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-primary-500 h-2 rounded-full" style="width: {{ $percent }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-gray-500">لا توجد بيانات</p>
            @endforelse
        </div>
    </div>

    {{-- Activity by Person --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Created By --}}
        <div class="fi-card bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold mb-4">من أنشأ القرارات</h3>
            @forelse($activity['created'] as $item)
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                    <span class="text-sm">{{ $item['name'] }}</span>
                    <span class="fi-badge bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 px-2 py-1 rounded-full text-xs font-semibold">{{ $item['total'] }}</span>
                </div>
            @empty
                <p class="text-gray-500">لا توجد بيانات</p>
            @endforelse
        </div>

        {{-- Engineers --}}
        <div class="fi-card bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold mb-4">المهندسين المسؤولين عن المراجعة</h3>
            @forelse($activity['engineers'] as $item)
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                    <span class="text-sm">{{ $item['name'] }}</span>
                    <span class="fi-badge bg-warning-100 dark:bg-warning-900 text-warning-700 dark:text-warning-300 px-2 py-1 rounded-full text-xs font-semibold">{{ $item['total'] }}</span>
                </div>
            @empty
                <p class="text-gray-500">لا توجد بيانات</p>
            @endforelse
        </div>

        {{-- Spatial Managers --}}
        <div class="fi-card bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold mb-4">إدارة مساحية</h3>
            @forelse($activity['spatial_managers'] as $item)
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                    <span class="text-sm">{{ $item['name'] }}</span>
                    <span class="fi-badge bg-info-100 dark:bg-info-900 text-info-700 dark:text-info-300 px-2 py-1 rounded-full text-xs font-semibold">{{ $item['total'] }}</span>
                </div>
            @empty
                <p class="text-gray-500">لا توجد بيانات</p>
            @endforelse
        </div>

        {{-- Systems --}}
        <div class="fi-card bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold mb-4">إدخال البيانات (محللي النظم)</h3>
            @forelse($activity['systems'] as $item)
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                    <span class="text-sm">{{ $item['name'] }}</span>
                    <span class="fi-badge bg-success-100 dark:bg-success-900 text-success-700 dark:text-success-300 px-2 py-1 rounded-full text-xs font-semibold">{{ $item['total'] }}</span>
                </div>
            @empty
                <p class="text-gray-500">لا توجد بيانات</p>
            @endforelse
        </div>
    </div>

    {{-- Recent Orders --}}
    <div class="fi-card bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm">
        <h3 class="text-lg font-semibold mb-4">آخر 10 قرارات</h3>
        @if($recent->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-right py-3 px-2">رقم القرار</th>
                            <th class="text-right py-3 px-2">المالك</th>
                            <th class="text-right py-3 px-2">المركز</th>
                            <th class="text-right py-3 px-2">أنشأه</th>
                            <th class="text-right py-3 px-2">المهندس المسؤول</th>
                            <th class="text-right py-3 px-2">المرحلة</th>
                            <th class="text-right py-3 px-2">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recent as $order)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-3 px-2 font-semibold">{{ $order->stop_order_number ?? '-' }}</td>
                                <td class="py-3 px-2">{{ $order->owner_name }}</td>
                                <td class="py-3 px-2">
                                    <span class="fi-badge bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-xs">{{ $order->center }}</span>
                                </td>
                                <td class="py-3 px-2">{{ $order->creator?->name ?? '-' }}</td>
                                <td class="py-3 px-2">{{ $order->engineeringEngineer?->name ?? '-' }}</td>
                                <td class="py-3 px-2">
                                    @php
                                        $stageColor = match($order->stage) {
                                            'completed' => 'success',
                                            'engineering_review' => 'warning',
                                            'systems_processing' => 'violet',
                                            'local_unit_approval' => 'teal',
                                            'gis_pending' => 'violet',
                                            'visa_pending' => 'indigo',
                                            'created' => 'gray',
                                            default => 'primary',
                                        };
                                    @endphp
                                    <span class="fi-badge bg-{{ $stageColor }}-100 dark:bg-{{ $stageColor }}-900 text-{{ $stageColor }}-700 dark:text-{{ $stageColor }}-300 px-2 py-1 rounded-full text-xs">
                                        {{ \Modules\RemovalGovernance\Models\RemovalOrder::stages()[$order->stage] ?? $order->stage }}
                                    </span>
                                </td>
                                <td class="py-3 px-2 text-gray-500">{{ $order->created_at->format('Y-m-d') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 text-center py-8">لا توجد قرارات حالياً</p>
        @endif
    </div>
</x-filament::page>
