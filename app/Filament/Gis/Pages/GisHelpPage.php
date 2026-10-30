<?php

namespace App\Filament\Gis\Pages;

use Filament\Pages\Page;

class GisHelpPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static string $view = 'filament.pages.help';
    protected static ?string $title = 'مركز المساعدة';
    protected static ?string $navigationLabel = 'مساعدة';
    protected static ?string $navigationGroup = 'إعدادات النظام والأمان';
    protected static ?int $navigationSort = 200;
    protected static ?string $slug = 'help';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['super_admin', 'Admin', 'مدير المركز', 'رؤوساء الاقسام', 'مدير الادارة الهندسية', 'مدير المتغيرات', 'عضو المتغيرات']);
    }
}
