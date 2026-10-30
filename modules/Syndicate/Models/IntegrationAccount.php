<?php

namespace Modules\Syndicate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

/**
 * حساب تكامل لنظام نقابة المهندسين. توكنات Sanctum تُصدر لهذا الحساب (وليس لمستخدم بشري)
 * بالقدرة 'syndicate' عبر الأمر: php artisan syndicate:token {name}
 */
class IntegrationAccount extends Model
{
    use HasApiTokens;

    protected $table = 'syndicate_integration_accounts';

    protected $fillable = ['name', 'description', 'allowed_ips', 'is_active', 'last_used_at'];

    protected $casts = [
        'allowed_ips' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function apiLogs(): HasMany
    {
        return $this->hasMany(ApiLog::class, 'integration_account_id');
    }
}
