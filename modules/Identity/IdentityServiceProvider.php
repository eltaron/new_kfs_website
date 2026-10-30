<?php

namespace Modules\Identity;

use App\Support\Modules\ModuleServiceProvider;

/**
 * الهوية (shared kernel) — 2FA, password reset, protected files. User/Role/Permission stay in App\Models as the kernel.
 * See docs/architecture.md.
 */
class IdentityServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Identity';
    }

    protected function movedModels(): array
    {
        return [];
    }
}
