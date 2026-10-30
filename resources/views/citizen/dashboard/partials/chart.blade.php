<div class="white-section-card shadow-sm p-4 mb-5">
    <div class="d-flex align-items-center mb-3 border-bottom pb-2">
        <i class="fas fa-chart-line text-success fs-4 ms-2"></i>
        <h3 class="m-0 section-head">نشاطك — آخر 7 أيام</h3>
    </div>
    <div class="chart-container">
        <canvas id="activityChart"></canvas>
    </div>
</div>
@push('scripts')
    <script>
        var ctx = document.getElementById('activityChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {{ \Illuminate\Support\Js::from($chartData['labels']) }},
                datasets: [{
                    label: 'خدمات عامة',
                    data: {{ \Illuminate\Support\Js::from($chartData['services']) }},
                    borderColor: '#26302D',
                    backgroundColor: 'rgba(38,48,45,0.08)',
                    tension: 0.3,
                    fill: true
                }, {
                    label: 'خدمات مكانية',
                    data: {{ \Illuminate\Support\Js::from($chartData['gis']) }},
                    borderColor: '#00788A',
                    backgroundColor: 'rgba(0,120,138,0.08)',
                    tension: 0.3,
                    fill: true
                }, {
                    label: 'شكاوي',
                    data: {{ \Illuminate\Support\Js::from($chartData['complaints']) }},
                    borderColor: '#B88920',
                    backgroundColor: 'rgba(216,176,62,0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', rtl: true }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    </script>
@endpush