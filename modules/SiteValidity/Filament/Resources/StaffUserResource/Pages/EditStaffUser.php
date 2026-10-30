<?php

namespace Modules\SiteValidity\Filament\Resources\StaffUserResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\SiteValidity\Filament\Resources\StaffUserResource;
use Modules\SiteValidity\Support\SvAccess;
use Spatie\Permission\Models\Role;

class EditStaffUser extends EditRecord
{
    protected static string $resource = StaffUserResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $role = (string) ($this->data['sv_role'] ?? '');
        abort_unless(in_array($role, SvAccess::serviceRoles(), true), 422);

        $record->fill(array_intersect_key($data, array_flip(['name', 'email', 'phone', 'national_id', 'status', 'password'])));
        $record->save();

        // استبدال دور الخدمة فقط — الأدوار الأخرى للمستخدم تبقى كما هي.
        foreach (SvAccess::serviceRoles() as $r) {
            if ($r !== $role && $record->hasRole($r)) {
                $record->removeRole($r);
            }
        }
        Role::findOrCreate($role, 'web');
        if (! $record->hasRole($role)) {
            $record->assignRole($role);
        }

        return $record;
    }
}
