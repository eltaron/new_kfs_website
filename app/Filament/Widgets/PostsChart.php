<?php

namespace App\Filament\Widgets;

use Modules\Content\Models\Post;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Concerns\Widgets\SuperAdminOnly;

class PostsChart extends ChartWidget
{
    use SuperAdminOnly; // ✅ سطر واحد يخفي الويدجت لغير السوبر أدمن
    protected static ?string $heading = 'نمو عدد المقالات';
    protected static ?int $sort = 2; // Keep the same sort order to appear next to the other chart

    /**
     * @var string | array<string, string | null> | null
     */
    public ?string $filter = 'last_month'; // Default filter

    protected function getFilters(): ?array
    {
        return [
            'last_week' => 'آخر 7 أيام',
            'last_month' => 'آخر 30 يومًا',
            'last_3_months' => 'آخر 3 أشهر',
            'all_time' => 'كل الأوقات',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        // Grouping logic based on filter
        list($startDate, $unit, $format) = match ($activeFilter) {
            'last_week' => [Carbon::now()->subDays(7), 'day', 'D'],
            'last_3_months' => [Carbon::now()->subMonths(3), 'month', 'M'],
            'all_time' => [Post::oldest()->first()->created_at ?? Carbon::now(), 'month', 'M Y'],
            'last_month' => [Carbon::now()->subDays(30), 'day', 'd M'],
            default => [Carbon::now()->subDays(30), 'day', 'd M'],
        };

        $query = Post::query()
            ->where('created_at', '>=', $startDate)
            ->select([
                DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d') as date"),
                DB::raw('COUNT(*) as count')
            ])
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        // This is a simple approach. A more robust approach would fill in the empty dates.
        // For simplicity, we will use what we get from the database directly.
        $data = $query->pluck('count')->toArray();
        $labels = $query->pluck('date')->map(fn($date) => Carbon::parse($date)->format($format))->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'مقالات جديدة',
                    'data' => $data,
                    'backgroundColor' => '#DAA520',
                    'borderColor' => '#DAA5-20',
                    'tension' => 0.4, // Make the line smooth
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line'; // A line chart is best for showing trends over time
    }
}
