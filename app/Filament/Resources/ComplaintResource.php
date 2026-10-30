<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ComplaintResource\Pages;
use Modules\Citizen\Models\Complaint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists; // We will use Infolist components for read-only view
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ComplaintResource extends Resource
{
    protected static ?string $model = Complaint::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationGroup = 'التواصل والشكاوى';
    protected static ?string $modelLabel = 'شكوى';
    protected static ?string $pluralModelLabel = 'الشكاوى';
    protected static ?int $navigationSort = 1;

    // Disable the "Create" button, as complaints come from the frontend.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('بيانات الشكوى الأساسية')
                    ->schema([
                        Infolists\Components\TextEntry::make('subject')->label('موضوع الشكوى')->columnSpanFull(),
                        Infolists\Components\TextEntry::make('message')->label('تفاصيل الشكوى')->columnSpanFull(),
                    ])->columns(2),

                Infolists\Components\Section::make('معلومات المُشتكي')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')->label('الاسم'),
                        Infolists\Components\TextEntry::make('email')->label('البريد الإلكتروني')->copyable(),
                        Infolists\Components\TextEntry::make('phone')->label('رقم الهاتف')->copyable(),
                        Infolists\Components\TextEntry::make('national_id')->label('الرقم القومي')->copyable(),
                        Infolists\Components\TextEntry::make('created_at')->label('تاريخ الاستلام')->dateTime(),
                        Infolists\Components\TextEntry::make('status')
                            ->label('الحالة الحالية')
                            ->badge()
                            ->colors([
                                'warning' => 'pending',
                                'info'    => 'in_progress',
                                'success' => 'resolved',
                            ]),
                    ])->columns(2),

                Infolists\Components\Section::make('المرفقات')
                    ->schema([
                        Infolists\Components\ImageEntry::make('attachment')
                            ->label('صورة المرفق')
                            ->placeholder('لا يوجد مرفق')
                            // Private upload: served through the authorized secure-files route.
                            ->state(fn($record) => \App\Support\SecureUploads::url($record, 'attachment'))
                            ->url(fn($record) => \App\Support\SecureUploads::url($record, 'attachment'))
                            ->openUrlInNewTab(),
                    ]),

                Infolists\Components\Section::make('الرد الإداري')
                    ->schema([
                        Infolists\Components\TextEntry::make('admin_reply')
                            ->label('نص الرد')
                            ->html() // تم تغييرها لـ html لأن الرد قد يحتوي على تنسيقات
                            ->placeholder('لم يتم الرد على هذه الشكوى بعد.'),
                        Infolists\Components\TextEntry::make('replied_at')
                            ->label('تاريخ الرد')
                            ->dateTime()
                            ->visible(fn($record) => !empty($record->admin_reply)),
                    ]),
            ]);
    }

    // We only need a simplified form for the Admin to change the status
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([

                        Forms\Components\Tabs\Tab::make('تغيير الحالة والرد')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('تغيير حالة الشكوى')
                                    ->options([
                                        'pending' => 'قيد الانتظار',
                                        'in_progress' => 'قيد المعالجة',
                                        'resolved' => 'تم الحل',
                                    ])
                                    ->required()
                                    ->native(false), // For better UI

                                Forms\Components\RichEditor::make('admin_reply')
                                    ->label('كتابة أو تعديل الرد')
                                    ->helperText('هذا الرد سيظهر للمواطن. اترك الحقل فارغًا إذا لم ترغب في إرسال رد.')
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('admin_reply')
                    ->label('تم الرد؟')
                    ->boolean()
                    ->trueIcon('heroicon-o-chat-bubble-left-right')
                    ->falseIcon(''),
                Tables\Columns\TextColumn::make('name')->label('اسم المُشتكي')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('subject')->label('الموضوع')->searchable()->limit(40),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'warning' => 'pending',
                        'info'    => 'in_progress',
                        'success' => 'resolved',
                    ]),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الاستلام')->since()->sortable(),
                Tables\Columns\TextColumn::make('response_time')
                    ->label('زمن الاستجابة')
                    ->sortable(false)
                    ->searchable(false)
                    ->getStateUsing(function ($record) {
                        if (!$record->admin_reply || !$record->replied_at) return '—';
                        $diff = $record->created_at->diff($record->replied_at);
                        $parts = [];
                        if ($diff->d > 0) $parts[] = $diff->d . ' يوم';
                        if ($diff->h > 0) $parts[] = $diff->h . ' ساعة';
                        if ($diff->i > 0) $parts[] = $diff->i . ' دقيقة';
                        return implode(' ', $parts) ?: 'أقل من دقيقة';
                    })
                    ->color(fn($record) => $record->admin_reply && $record->replied_at
                        ? ($record->created_at->diffInHours($record->replied_at) <= 24 ? 'success' : 'warning')
                        : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['pending' => 'قيد الانتظار', 'in_progress' => 'قيد المعالجة', 'resolved' => 'تم الحل']),
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
                Tables\Actions\EditAction::make()->label('تغيير الحالة'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // ===== NAVIGATION BADGE & NOTIFICATION COUNT =====
    public static function getNavigationBadge(): ?string
    {
        // Shows a count of pending complaints in the sidebar
        $count = static::getModel()::where('status', 'pending')->count();
        return $count > 0 ? $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger'; // Red badge to indicate urgency
    }

    // ===== GLOBAL SEARCH CONFIGURATION =====
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'subject', 'message', 'national_id'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return "شكوى: " . $record->subject;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['مرسلة من' => $record->name];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComplaints::route('/'),
            // Create page is disabled (complaints are submitted by citizens from the public site;
            // a '/{record}' create route also collided with the view route).

            'edit'  => Pages\EditComplaint::route('/{record}/edit'), // For updating status
            'view'  => Pages\ViewComplaint::route('/{record}'), // For viewing details
        ];
    }
}
