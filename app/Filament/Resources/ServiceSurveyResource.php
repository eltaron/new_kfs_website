<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceSurveyResource\Pages;
use Modules\Citizen\Models\ServiceSurvey;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists; // <-- Import Infolist components
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServiceSurveyResource extends Resource
{
    protected static ?string $model = ServiceSurvey::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'الخدمات الإلكترونية';
    protected static ?string $modelLabel = 'تقييم خدمة';
    protected static ?string $pluralModelLabel = 'تقييمات المراكز التكنولوجية';
    protected static ?int $navigationSort = 3;


    public static function infolist(Infolist $infolist): Infolist
    {
        // A helper function to create a rating entry
        $ratingEntry = fn($name, $label) => Infolists\Components\TextEntry::make($name)
            ->label($label)
            ->formatStateUsing(fn($state) => match ((int) $state) {
                5 => '★★★★★ (راضٍ جدًا)',
                4 => '★★★★☆ (راضٍ)',
                3 => '★★★☆☆ (محايد)',
                2 => '★★☆☆☆ (غير راضٍ)',
                1 => '★☆☆☆☆ (غير راضٍ تمامًا)',
                default => 'N/A'
            })
            ->color(fn($state) => match ((int) $state) {
                5, 4 => 'success',
                3 => 'warning',
                2, 1 => 'danger',
                default => 'gray'
            });

        return $infolist->schema([
            Infolists\Components\Section::make('معلومات التقييم')
                ->schema([
                    Infolists\Components\TextEntry::make('name')->label('اسم المشارك'),
                    Infolists\Components\TextEntry::make('center_name')->label('المركز التكنولوجي'),
                    Infolists\Components\TextEntry::make('phone')->label('رقم الهاتف'),
                    Infolists\Components\TextEntry::make('age_group')->label('الفئة العمرية'),
                    Infolists\Components\TextEntry::make('gender')->label('الجنس'),
                    Infolists\Components\TextEntry::make('created_at')->label('تاريخ التقديم')->dateTime(),
                ])->columns(3),

            // ================= START OF THE TABS SECTION =================
            Infolists\Components\Tabs::make('Tabs')
                ->columnSpanFull()
                ->tabs([
                    Infolists\Components\Tabs\Tab::make('جودة الخدمات')
                        ->icon('heroicon-o-check-circle') // Add an icon to the tab
                        ->schema([
                            $ratingEntry('q1_1_accessibility', 'سهولة الوصول إلى المراكز التكنولوجية'),
                            $ratingEntry('q1_2_procedure_clarity', 'وضوح إجراءات طلب الخدمة'),
                            $ratingEntry('q1_3_needs_fulfillment', 'مدى تلبية الخدمات المقدمة للاحتياجات'),
                            $ratingEntry('q1_4_guidance', 'الإرشاد حول استخدام بوابة الخدمات الحكومية'),
                            $ratingEntry('q1_5_staff_cooperation', 'تعاون موظفي المركز معك'),
                            $ratingEntry('q1_6_process_handling', 'سهولة ووضوح الخطوات داخل المراكز'),
                        ])->columns(2),

                    Infolists\Components\Tabs\Tab::make('سرعة الخدمات')
                        ->icon('heroicon-o-clock')
                        ->schema([
                            $ratingEntry('q2_1_service_speed', 'سرعة تقديم الخدمة في المركز'),
                            $ratingEntry('q2_2_wait_time', 'وضوح المدة المتوقعة لإنجاز الطلب'),
                            $ratingEntry('q2_3_delay_justification', 'وضوح تبرير التأخير (إن وجد)'),
                        ])->columns(2),

                    Infolists\Components\Tabs\Tab::make('أداء الموظفين')
                        ->icon('heroicon-o-users')
                        ->schema([
                            $ratingEntry('q3_1_staff_treatment', 'تعامل موظفي المركز'),
                            $ratingEntry('q3_2_problem_solving', 'اهتمام الموظفين بحل المشكلة'),
                            $ratingEntry('q3_3_communication_ease', 'سهولة التواصل مع الموظفين'),
                            $ratingEntry('q3_4_fees_clarity', 'وضوح وشفافية الرسوم'),
                        ])->columns(2),

                    Infolists\Components\Tabs\Tab::make('بيئة المركز')
                        ->icon('heroicon-o-building-office')
                        ->schema([
                            $ratingEntry('q4_1_cleanliness', 'نظافة وتنظيم المركز'),
                            $ratingEntry('q4_2_seating_comfort', 'راحة أماكن الجلوس والانتظار'),
                            $ratingEntry('q4_3_accessibility_tools', 'الوسائل المتاحة لذوي الهمم'),
                        ])->columns(2),

                    Infolists\Components\Tabs\Tab::make('الآراء المفتوحة')
                        ->icon('heroicon-o-pencil-square')
                        ->schema([
                            Infolists\Components\TextEntry::make('suggestions')
                                ->label('الاقتراحات والتوصيات')
                                ->placeholder('لا يوجد') // Display text if null
                                ->markdown() // Render new lines as paragraphs
                                ->columnSpanFull(),

                            Infolists\Components\TextEntry::make('complaint_employee_name')
                                ->label('اسم الموظف المشكو منه')
                                ->placeholder('لا يوجد')
                                ->columnSpan(1),

                            Infolists\Components\TextEntry::make('complaint_reason')
                                ->label('سبب الشكوى من الموظف')
                                ->placeholder('لا يوجد')
                                ->markdown()
                                ->columnSpanFull(),
                        ])->columns(2),

                ])
            // ================ END OF THE TABS SECTION ==================
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('اسم المشارك')->searchable()->placeholder('غير مسجل'),
                Tables\Columns\TextColumn::make('center_name')->label('المركز')->searchable(),
                Tables\Columns\IconColumn::make('is_reviewed')->label('تمت مراجعته')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ التقييم')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_reviewed')->label('حالة المراجعة'),
                Tables\Filters\SelectFilter::make('center_name')
                    ->options(fn() => ServiceSurvey::pluck('center_name', 'center_name')->unique())
                    ->label('تصفية حسب المركز'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('من تاريخ'),
                        Forms\Components\DatePicker::make('created_until')->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'], fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    // Notification badge for unreviewed surveys
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('is_reviewed', false)->count();
        return $count > 0 ? $count : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceSurveys::route('/'),
            'view' => Pages\ViewServiceSurvey::route('/{record}'), // Only index and view pages
        ];
    }
}
