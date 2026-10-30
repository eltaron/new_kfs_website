<?php

namespace Modules\Syndicate\Filament\Resources;

use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Syndicate\Filament\PanelContext;
use Modules\Syndicate\Filament\Resources\CertificateResource\Pages;
use Modules\Syndicate\Models\Certificate;

/** الشهادات تصدر فقط عبر إجراءات الطلبات (آلة الحالات) — لا إنشاء/تعديل/حذف مباشر. */
class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static ?string $slug = 'certificates';
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationLabel = 'الشهادات';
    protected static ?string $modelLabel = 'شهادة';
    protected static ?string $pluralModelLabel = 'الشهادات';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'number';

    public static function canAccess(): bool
    {
        return PanelContext::userCanUsePanel();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'engineer_name', 'engineer_syndicate_number', 'owner_name', 'owner_national_id'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['النوع' => $record->kind_label, 'المهندس' => $record->engineer_name, 'الحالة' => $record->status_label];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('رقم الشهادة')->searchable()->sortable()->copyable()->weight('bold'),
                Tables\Columns\TextColumn::make('kind')->label('النوع')->badge()
                    ->formatStateUsing(fn ($state) => Certificate::kinds()[$state] ?? $state)
                    ->color(fn ($state) => $state === Certificate::KIND_CANCELLATION ? 'danger' : 'info'),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->getStateUsing(fn (Certificate $r) => $r->status_label)
                    ->color(fn (Certificate $r) => $r->kind === Certificate::KIND_CANCELLATION ? 'danger' : (Certificate::statusColors()[$r->status] ?? 'gray')),
                Tables\Columns\TextColumn::make('engineer_name')->label('المهندس')->searchable()
                    ->description(fn (Certificate $r) => $r->office_name),
                Tables\Columns\TextColumn::make('engineer_syndicate_number')->label('رقم العضوية')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('owner_name')->label('المالك')->searchable(),
                Tables\Columns\TextColumn::make('project.code')->label('المشروع')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('city')->label('المركز')->toggleable(),
                Tables\Columns\TextColumn::make('request.transaction_number')->label('رقم المعاملة')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('issued_at')->label('تاريخ الإصدار')->dateTime('Y-m-d')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kind')->label('النوع')->options(Certificate::kinds()),
                Tables\Filters\SelectFilter::make('status')->label('حالة شهادة الإشراف')->options(Certificate::statuses()),
                Tables\Filters\SelectFilter::make('city')->label('المركز')
                    ->options(array_combine(config('syndicate.centers'), config('syndicate.centers'))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('عرض'),
                Tables\Actions\Action::make('print')->label('طباعة')->icon('heroicon-o-printer')->color('gray')
                    ->url(fn (Certificate $r) => $r->number ? $r->printUrl() : null, shouldOpenInNewTab: true)
                    ->visible(fn (Certificate $r) => filled($r->number)),
            ])
            ->defaultSort('issued_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('الشهادة')
                ->schema([
                    Infolists\Components\TextEntry::make('number')->label('رقم الشهادة')->copyable()->weight('bold'),
                    Infolists\Components\TextEntry::make('kind')->label('النوع')->badge()
                        ->formatStateUsing(fn ($state) => Certificate::kinds()[$state] ?? $state)
                        ->color(fn ($state) => $state === Certificate::KIND_CANCELLATION ? 'danger' : 'info'),
                    Infolists\Components\TextEntry::make('status_label')->label('الحالة')->badge()
                        ->color(fn (Certificate $r) => $r->kind === Certificate::KIND_CANCELLATION ? 'danger' : (Certificate::statusColors()[$r->status] ?? 'gray')),
                    Infolists\Components\TextEntry::make('issued_at')->label('تاريخ الإصدار')->dateTime('Y-m-d H:i'),
                    Infolists\Components\TextEntry::make('issued_by_name')->label('أصدرها')->placeholder('—'),
                    Infolists\Components\TextEntry::make('request.transaction_number')->label('رقم المعاملة')->placeholder('—')
                        ->url(fn (Certificate $r) => $r->request_id ? RequestResource::getUrl('view', ['record' => $r->request_id]) : null),
                    Infolists\Components\TextEntry::make('cancels.number')->label('تلغي شهادة الإشراف')
                        ->visible(fn (Certificate $r) => (bool) $r->cancels_certificate_id),
                    Infolists\Components\TextEntry::make('cancellation.number')->label('أُلغيت بالشهادة')
                        ->visible(fn (Certificate $r) => $r->status === Certificate::STATUS_CANCELLED),
                    Infolists\Components\TextEntry::make('cancelled_at')->label('تاريخ الإلغاء')->dateTime('Y-m-d H:i')
                        ->visible(fn (Certificate $r) => (bool) $r->cancelled_at),
                    Infolists\Components\TextEntry::make('superseded_at')->label('تاريخ الاستبدال')->dateTime('Y-m-d H:i')
                        ->visible(fn (Certificate $r) => (bool) $r->superseded_at),
                    Infolists\Components\TextEntry::make('notes')->label('ملاحظات')->placeholder('—')->columnSpanFull(),
                ])->columns(3),
            Infolists\Components\Section::make('المهندس / المكتب الهندسي')
                ->schema([
                    Infolists\Components\TextEntry::make('engineer_name')->label('الاسم'),
                    Infolists\Components\TextEntry::make('engineer_syndicate_number')->label('رقم العضوية'),
                    Infolists\Components\TextEntry::make('engineer_national_id')->label('الرقم القومي')->placeholder('—'),
                    Infolists\Components\TextEntry::make('engineer_department')->label('التخصص')->placeholder('—'),
                    Infolists\Components\TextEntry::make('engineer_phone')->label('الهاتف')->placeholder('—'),
                    Infolists\Components\TextEntry::make('engineer_email')->label('البريد')->placeholder('—'),
                    Infolists\Components\TextEntry::make('office_name')->label('المكتب')->placeholder('—'),
                    Infolists\Components\TextEntry::make('office_registration_number')->label('رقم قيد المكتب')->placeholder('—'),
                ])->columns(4),
            Infolists\Components\Section::make('المالك والموقع')
                ->schema([
                    Infolists\Components\TextEntry::make('owner_name')->label('المالك'),
                    Infolists\Components\TextEntry::make('owner_national_id')->label('الرقم القومي'),
                    Infolists\Components\TextEntry::make('project.code')->label('كود المشروع')->placeholder('—')
                        ->url(fn (Certificate $r) => $r->project_id ? ProjectResource::getUrl('view', ['record' => $r->project_id]) : null),
                    Infolists\Components\TextEntry::make('project.work_status_label')->label('حالة المشروع الآن')->badge()
                        ->color(fn (Certificate $r) => \Modules\Syndicate\Models\Project::workStatusColors()[$r->project?->work_status] ?? 'gray'),
                    Infolists\Components\TextEntry::make('project_address')->label('العنوان')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('license_number')->label('رقم الترخيص')->placeholder('—'),
                    Infolists\Components\TextEntry::make('city')->label('المركز')->placeholder('—'),
                ])->columns(4),
            Infolists\Components\Section::make('التحقق')
                ->schema([
                    Infolists\Components\ViewEntry::make('qr')->view('syndicate::filament.certificate-qr')->columnSpanFull(),
                ])->collapsible(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['project', 'request']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificates::route('/'),
            'view' => Pages\ViewCertificate::route('/{record}'),
        ];
    }
}
