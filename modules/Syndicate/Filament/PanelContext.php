<?php

namespace Modules\Syndicate\Filament;

use Filament\Facades\Filament;

/** الموارد مشتركة بين لوحتي /syndicate و /council؛ الإجراءات تختلف حسب اللوحة الحالية. */
class PanelContext
{
    public static function id(): ?string
    {
        try {
            return Filament::getCurrentPanel()?->getId();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function isSyndicate(): bool
    {
        return static::id() === 'syndicate';
    }

    public static function isCouncil(): bool
    {
        return static::id() === 'council';
    }

    public static function syndicateRoles(): array
    {
        return array_merge([config('syndicate.roles.syndicate')], (array) config('syndicate.roles.admins'));
    }

    public static function councilRoles(): array
    {
        return array_merge([config('syndicate.roles.council')], (array) config('syndicate.roles.council_extra'), (array) config('syndicate.roles.admins'));
    }

    public static function userCanUsePanel(): bool
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'hasAnyRole')) {
            return false;
        }

        return match (static::id()) {
            'syndicate' => $user->hasAnyRole(static::syndicateRoles()),
            'council' => $user->hasAnyRole(static::councilRoles()),
            default => $user->hasAnyRole((array) config('syndicate.roles.admins')),
        };
    }
}
