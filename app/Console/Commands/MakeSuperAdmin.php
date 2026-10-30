<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class MakeSuperAdmin extends Command
{
    protected $signature = 'user:make-super {uuid : UUID of the user}';
    protected $description = 'Make a user super admin using Filament Shield';

    public function handle(): int
    {
        $uuid = $this->argument('uuid');

        $user = User::findOrFail($uuid);

        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web'
        ]);

        if ($user->hasRole('super_admin')) {
            $this->warn("⚠️ المستخدم {$user->name} هو بالفعل Super Admin");
            return self::SUCCESS;
        }

        $user->assignRole($role);

        $this->info("✅ تم تعيين {$user->name} كـ Super Admin بنجاح");
        return self::SUCCESS;
    }
}
