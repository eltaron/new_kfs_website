<?php

namespace Modules\Syndicate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** دليل المهندسين والمكاتب الهندسية (يُزامَن من نظام النقابة). */
class Engineer extends Model
{
    protected $table = 'syndicate_engineers';

    protected $fillable = [
        'syndicate_number', 'national_id', 'name', 'phone', 'email', 'specialization', 'grade',
        'registration_number', 'office_name', 'office_registration_number', 'membership_status',
        'user_id', 'synced_at',
    ];

    protected $casts = ['synced_at' => 'datetime'];

    public static function membershipStatuses(): array
    {
        return ['active' => 'عضوية سارية', 'suspended' => 'موقوف', 'expired' => 'منتهية'];
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'engineer_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name . ' — ' . $this->syndicate_number . ($this->office_name ? ' (' . $this->office_name . ')' : '');
    }
}
