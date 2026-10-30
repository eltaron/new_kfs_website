<?php

namespace App\Filament\Widgets;

use Modules\Content\Models\Comment;
use Modules\Tourism\Models\Landmark;
use Modules\Content\Models\Post;
use Modules\Investment\Models\Project;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use App\Concerns\Widgets\SuperAdminOnly;

class StatsOverview extends BaseWidget
{
    use SuperAdminOnly; // ✅ سطر واحد يخفي الويدجت لغير السوبر أدمن
    protected static ?int $sort = 1;
    protected static bool $isLazy = true;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $postsToday = Post::whereDate('created_at', Carbon::today())->count();
        $usersToday = User::whereDate('created_at', Carbon::today())->count();

        $totalPosts = Post::count();
        $totalUsers = User::count();
        $totalProjects = Project::count();
        $totalLandmarks = Landmark::count();
        $pendingComments = Comment::where('is_approved', false)->count();
        $latestPost = Post::latest()->first();
        $latestUser = User::latest()->first();

        return [
            // Row 1
            Stat::make('إجمالي المقالات', $totalPosts)
                ->description($postsToday > 0 ? "{$postsToday} مقال جديد اليوم" : 'لا مقالات جديدة اليوم')
                ->descriptionIcon($postsToday > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-minus')
                ->color($postsToday > 0 ? 'success' : 'gray'),

            Stat::make('إجمالي المشروعات', $totalProjects)
                ->description('مشروعات مسجلة في النظام')
                ->descriptionIcon('heroicon-m-building-office-2'),

            Stat::make('إجمالي المعالم السياحية', $totalLandmarks)
                ->description('معالم سياحية متاحة للاستكشاف')
                ->descriptionIcon('heroicon-m-flag'),

            // Row 2
            Stat::make('إجمالي المستخدمين', $totalUsers)
                ->description($usersToday > 0 ? "{$usersToday} مستخدم جديد اليوم" : 'لا مستخدمين جدد اليوم')
                ->descriptionIcon($usersToday > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-minus')
                ->color($usersToday > 0 ? 'success' : 'gray'),

            Stat::make('تعليقات للمراجعة', $pendingComments)
                ->description('تعليقات في انتظار الموافقة عليها')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingComments > 0 ? 'danger' : 'success'),

            Stat::make('أحدث مستخدم', $latestUser ? $latestUser->name : 'N/A')
                ->description('آخر من قام بالتسجيل في الموقع')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('gray'),

        ];
    }
}
