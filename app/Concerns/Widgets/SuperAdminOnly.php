<?php

namespace App\Concerns\Widgets;

trait SuperAdminOnly
{
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }
    
    public static function canAccess(): bool
    {
        return static::canView();
    }
}