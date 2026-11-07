<?php

namespace App\Filament\Gis\Resources;

use App\Filament\Gis\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource as AdminRoleResource;

class RoleResource extends AdminRoleResource
{
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['super_admin', 'Admin', 'مدير المركز']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => Pages\ViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    protected static function getResourceLabel(array $entity): string
    {
        return (string) ($entity['model'] ?? $entity['resource'] ?? '');
    }
}
