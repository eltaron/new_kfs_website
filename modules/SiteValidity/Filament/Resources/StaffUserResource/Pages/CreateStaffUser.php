<?php

namespace Modules\SiteValidity\Filament\Resources\StaffUserResource\Pages;

use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\SiteValidity\Filament\Resources\StaffUserResource;
use Modules\SiteValidity\Support\SvAccess;
use Spatie\Permission\Models\Role;

class CreateStaffUser extends CreateRecord
{
    protected static string $resource = StaffUserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $role = (string) ($this->data['sv_role'] ?? '');
        abort_unless(in_array($role, SvAccess::serviceRoles(), true), 422);

        $user = new User(array_intersect_key($data, array_flip(['name', 'email', 'phone', 'national_id', 'status', 'password'])));
        $user->email_verified_at = now();
        $user->save();

        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
