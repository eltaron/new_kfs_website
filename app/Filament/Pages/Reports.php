<?php
// app/Filament/Pages/Reports.php

namespace App\Filament\Pages;

use App\Filament\Resources\PostResource;
use Modules\Content\Models\Post;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class Reports extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string $view = 'filament.pages.reports';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationGroup = 'المحتوى';
    protected static ?string $navigationLabel = 'تقارير المحتوى';
    protected static ?string $title = 'تقارير المحتوى والأداء';
    protected static ?string $slug = 'reports/content';
    public static function canAccess(): bool
    {
        // يمكنك هنا وضع أسماء الأدوار التي يحق لها دخول هذه الصفحة تحديداً
        return auth()->user()->hasAnyRole([
           'super_admin', 
        ]);
    }
    public function table(Table $table): Table
    {
        return $table
            // The base query for the report
            ->query(Post::query()->with('category'))

            // Re-order button to allow custom ordering
            ->reorderable('order_column')

            // Default sort order
            ->defaultSort('published_at', 'desc')

            // Header of the table
            ->heading('تقرير أداء المقالات')
            ->description('عرض تحليلي للمقالات المنشورة وتفاعلات المستخدمين معها.')

            // Table columns
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->label('الصورة')
                    ->square()
                    ->width(60),

                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان المقال')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Post $record): string => $record->slug)
                    ->url(fn(Post $record): string => PostResource::getUrl('edit', ['record' => $record])),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('الفئة')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                // Combined column for engagement metrics
                Tables\Columns\TextColumn::make('engagement')
                    ->label('التفاعلات')
                    ->numeric()
                    ->html()
                    ->formatStateUsing(
                        fn($record) =>
                        '<div><span class="font-semibold text-primary-600">' . $record->likes_count . '</span> <span class="text-xs text-gray-500">إعجاب</span></div>' .
                            '<div><span class="font-semibold text-primary-600">' . $record->comments()->where('is_approved', true)->count() . '</span> <span class="text-xs text-gray-500">تعليق</span></div>'
                    ),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('منشور؟')
                    ->boolean(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('تاريخ النشر')
                    ->date('d M, Y')
                    ->sortable(),
            ])

            // Table filters
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload()
                    ->label('تصفية حسب الفئة'),

                Tables\Filters\Filter::make('published_at')
                    ->form([
                        DatePicker::make('published_from')->label('منشور من تاريخ'),
                        DatePicker::make('published_until')->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['published_from'], fn(Builder $query, $date): Builder => $query->whereDate('published_at', '>=', $date))
                            ->when($data['published_until'], fn(Builder $query, $date): Builder => $query->whereDate('published_at', '<=', $date));
                    }),

                Tables\Filters\TernaryFilter::make('is_featured')->label('المقالات المميزة'),
            ])

            // Table actions
            ->actions([
                Tables\Actions\Action::make('view_post')
                    ->label('عرض')
                    ->url(fn(Post $record): string => PostResource::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-o-eye'),
            ]);
    }
}
