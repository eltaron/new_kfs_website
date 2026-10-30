<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SyndicateCertificateResource\Pages;
use App\Models\SyndicateCertificate;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Syndicate\Models\Certificate;
use Modules\Syndicate\Models\Project;

/**
 * عرض إداري (للقراءة فقط) لشهادات الإشراف وإلغاء الإشراف في لوحة /admin.
 *
 * الإصدار والإلغاء يتمان فقط من خلال منظومة Modules\Syndicate (آلة الحالات):
 *   - لوحة النقابة /syndicate  — إصدار الشهادات
 *   - لوحة مجلس المدينة /council — تسجيل المشروعات والطلبات
 * لذلك أُزيلت صفحات الإنشاء والتعديل القديمة حتى لا تتجاوز قواعد العمل.
 */
class SyndicateCertificateResource extends Resource
{
    protected static ?string $model = SyndicateCertificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'نقابة المهندسين';
    protected static ?string $navigationLabel = 'شهادات الإشراف وإلغاء الإشراف';
    protected static ?string $modelLabel = 'شهادة';
    protected static ?string $pluralModelLabel = 'شهادات الإشراف';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $u = auth()->user();

        return $u && $u->hasAnyRole(array_merge(
            (array) config('syndicate.roles.admins', ['super_admin', 'Admin']),
            [config('syndicate.roles.syndicate', 'نقابة المهندسين'), config('syndicate.roles.council', 'مجلس المدينة')],
        ));
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('رقم الشهادة')->searchable()->sortable()->copyable()->placeholder('—'),
                Tables\Columns\TextColumn::make('kind')->label('النوع')->badge()
                    ->formatStateUsing(fn ($state) => Certificate::kinds()[$state] ?? $state)
                    ->color(fn ($state) => $state === Certificate::KIND_CANCELLATION ? 'danger' : 'info'),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->getStateUsing(fn (SyndicateCertificate $r) => $r->status_label)
                    ->color(fn (SyndicateCertificate $r) => $r->kind === Certificate::KIND_CANCELLATION ? 'danger' : (Certificate::statusColors()[$r->status] ?? 'gray')),
                Tables\Columns\TextColumn::make('engineer_name')->label('المهندس')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('owner_name')->label('المالك')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->label('نوع التدخل')->badge()
                    ->formatStateUsing(fn ($state) => SyndicateCertificate::types()[$state] ?? '—')
                    ->color(fn ($state) => $state === 'demolition' ? 'danger' : 'success')->toggleable(),
                Tables\Columns\TextColumn::make('license_number')->label('رقم الترخيص')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('city')->label('المركز')->searchable(),
                Tables\Columns\TextColumn::make('project.work_status')->label('حالة المشروع')->badge()
                    ->formatStateUsing(fn ($state) => Project::workStatuses()[$state] ?? '—')
                    ->color(fn ($state) => Project::workStatusColors()[$state] ?? 'gray'),
                Tables\Columns\TextColumn::make('issued_at')->label('تاريخ الإصدار')->date('Y-m-d')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kind')->label('النوع')->options(Certificate::kinds()),
                Tables\Filters\SelectFilter::make('status')->label('الحالة')->options(Certificate::statuses()),
                Tables\Filters\SelectFilter::make('type')->label('نوع التدخل')->options(SyndicateCertificate::types()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('عرض'),
                Tables\Actions\Action::make('print')->label('طباعة')->icon('heroicon-o-printer')->color('gray')
                    ->visible(fn (SyndicateCertificate $r) => filled($r->number))
                    ->url(fn (SyndicateCertificate $r) => $r->printUrl(), shouldOpenInNewTab: true),
            ])
            ->headerActions([
                Tables\Actions\Action::make('syndicatePanel')->label('لوحة النقابة')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(url('/syndicate'), shouldOpenInNewTab: true),
                Tables\Actions\Action::make('councilPanel')->label('لوحة مجلس المدينة')->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')->url(url('/council'), shouldOpenInNewTab: true),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('الشهادة')->schema([
                Infolists\Components\TextEntry::make('number')->label('رقم الشهادة')->placeholder('—'),
                Infolists\Components\TextEntry::make('kind_label')->label('النوع'),
                Infolists\Components\TextEntry::make('status_label')->label('الحالة')->badge(),
                Infolists\Components\TextEntry::make('issued_at')->label('تاريخ الإصدار')->dateTime('Y-m-d H:i')->placeholder('—'),
                Infolists\Components\TextEntry::make('request.transaction_number')->label('رقم المعاملة')->placeholder('—'),
                Infolists\Components\TextEntry::make('project.code')->label('كود المشروع')->placeholder('—'),
            ])->columns(3),
            Infolists\Components\Section::make('المهندس المشرف')->schema([
                Infolists\Components\TextEntry::make('engineer_name')->label('الاسم'),
                Infolists\Components\TextEntry::make('engineer_syndicate_number')->label('رقم العضوية'),
                Infolists\Components\TextEntry::make('engineer_department')->label('الشعبة')->placeholder('—'),
                Infolists\Components\TextEntry::make('engineer_registration_number')->label('رقم السجل الهندسي')->placeholder('—'),
                Infolists\Components\TextEntry::make('office_name')->label('المكتب الهندسي')->placeholder('—'),
            ])->columns(3),
            Infolists\Components\Section::make('المالك والترخيص والمشروع')->schema([
                Infolists\Components\TextEntry::make('owner_name')->label('المالك'),
                Infolists\Components\TextEntry::make('owner_national_id')->label('الرقم القومي'),
                Infolists\Components\TextEntry::make('owner_address')->label('عنوان المالك')->placeholder('—'),
                Infolists\Components\TextEntry::make('license_number')->label('رقم الترخيص')->placeholder('—'),
                Infolists\Components\TextEntry::make('license_date')->label('تاريخ الترخيص')->date('Y-m-d')->placeholder('—'),
                Infolists\Components\TextEntry::make('license_authority')->label('جهة الترخيص')->placeholder('—'),
                Infolists\Components\TextEntry::make('project_address')->label('عنوان المشروع')->columnSpanFull(),
                Infolists\Components\TextEntry::make('licensed_works_description')->label('بيان الأعمال')->placeholder('—')->columnSpanFull(),
                Infolists\Components\TextEntry::make('works_value_numbers')->label('قيمة الأعمال')->money('EGP')->placeholder('—'),
                Infolists\Components\TextEntry::make('certificate_file')->label('ملف مرفق (قديم)')->placeholder('—')
                    ->url(fn (SyndicateCertificate $r) => $r->certificate_file ? asset('storage/'.$r->certificate_file) : null, shouldOpenInNewTab: true),
            ])->columns(3),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSyndicateCertificates::route('/'),
            'view' => Pages\ViewSyndicateCertificate::route('/{record}'),
        ];
    }
}
