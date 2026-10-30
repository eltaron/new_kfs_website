<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PostResource;
use Modules\Content\Models\Post;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Concerns\Widgets\SuperAdminOnly;

class LatestPosts extends BaseWidget
{
    use SuperAdminOnly; // ✅ سطر واحد يخفي الويدجت لغير السوبر أدمن
    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int | string | array $columnSpan = 'full'; // Make the widget take the full width of the row
    protected static ?int $sort = 3; // Controls the order of widgets on the dashboard

    public static function getHeading(): string
    {
        return 'أحدث الأخبار المضافة';
    }

    public function table(Table $table): Table
    {
        return $table
            // The query to fetch the latest 5 posts
            ->query(
                PostResource::getEloquentQuery()->latest()->limit(5)
            )
            // Define the table's header actions
            ->headerActions([
                Tables\Actions\Action::make('view_all')
                    ->label('عرض كل الأخبار')
                    ->url(PostResource::getUrl('index')),
            ])
            // Define the columns to display
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->limit(30)
                    ->url(fn(Post $record): string => PostResource::getUrl('edit', ['record' => $record])),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('الفئة')
                    ->badge(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('منشور')
                    ->boolean(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('تاريخ النشر')
                    ->since(),
            ])
            // Remove the header to make it look cleaner in a dashboard
            ->heading('اخر الأخبار') // Hides the main table heading, as we have a widget heading
            ->paginated(false); // No pagination needed for a small list
    }
}
