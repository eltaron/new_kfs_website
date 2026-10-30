<?php

namespace App\Models;

// Identity shared kernel (docs/architecture.md). Relations below point at module models.
use Modules\Citizen\Models\Complaint;
use Modules\Citizen\Models\Suggestion;
use Modules\Content\Models\Comment;
use Modules\Estidama\Models\Badge;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles, HasUuids, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'national_id',
        'phone',
        'job_title',
        'center',
        'address',
        'national_id_image',
        'status',
        'fcm_token'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'two_factor_secret' => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted',
        'two_factor_confirmed_at' => 'datetime',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        // 0. الحسابات الموقوفة/المرفوضة لا تدخل أي لوحة (القيم الفعلية: pending | approved | rejected [+ inactive])
        $blockedStatuses = (array) config('security.login.blocked_statuses', ['rejected', 'inactive']);
        if (in_array(strtolower((string) $this->status), array_map('strtolower', [...$blockedStatuses, 'suspended', 'blocked', 'disabled']), true)) {
            return false;
        }

        // 1. السوبر أدمن يدخل كل اللوحات
        if ($this->hasRole('super_admin')) {
            return true;
        }

        // 2. تحديد الصلاحيات بناءً على معرف اللوحة (Panel ID)
        return match ($panel->getId()) {
            'admin' => $this->hasAnyRole([
                'super_admin',
                'Admin',
                'مسئول اعلام',
                'مسئول استثمار ',
                'مسئول سياحة',
                'مسئول المركز التكنولوجي',
                'مسئول التخطيط الاستراتيجي'
            ]),
            'gis'   => $this->hasAnyRole([
                'super_admin',
                'Admin',
                'مدير المركز',
                'مدير الادارة الهندسية',
                'مهندس التنظيم',
                'مدير التنظيم',
                'فني التنظيم',
                'مدير الوحدة الفرعية',
                'العضو الميداني',
                'مدخل البيانات بالوحدة الفرعية',
                'محللي النظم',
                'الدعم الاداري',
                'رؤوساء الاقسام',
                'مدير المتغيرات',
                'عضو المتغيرات',
                'أخصائي النظم',
                'مكتب المحافظ',
                'النائب المختص بمتابعة المنظومة طبقا لكشف الحضور',
                'رئيس المركز والمدينة'
            ]),
            'estidama_panal' => $this->hasAnyRole(['super_admin', 'Admin', 'estidama_trainer', 'estidama_admin']),
            // Modules\Syndicate: لوحة نقابة المهندسين ولوحة مجلس المدينة (الأدوار في config/syndicate)
            'syndicate' => $this->hasAnyRole(\Modules\Syndicate\Filament\PanelContext::syndicateRoles()),
            'council' => $this->hasAnyRole(\Modules\Syndicate\Filament\PanelContext::councilRoles()),
            // Modules\Marketplace: لوحة البائع ولوحة إدارة المتجر (القرار في MarketplaceAccess)
            'vendor', 'store-admin' => \Modules\Marketplace\Support\MarketplaceAccess::canAccessPanel($this, $panel->getId()),
            // Modules\SiteValidity: لوحة خدمة «طلب صلاحية الموقع» (site_validity_admin | geo_center | planning_engineer)
            'site-validity' => \Modules\SiteValidity\Support\SvAccess::canAccessPanel($this),
            default => false,
        };
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Comment::class);
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email'])
            ->logOnlyDirty()
            ->useLogName('User Actions')
            ->setDescriptionForEvent(fn(string $eventName) => "User account has been {$eventName}")
            ->dontSubmitEmptyLogs();
    }

    public function complaints()
    {
        // foreignKey: 'national_id' on the 'complaints' table
        // localKey: 'national_id' on the 'users' table
        return $this->hasMany(Complaint::class, 'national_id', 'national_id');
    }

    public function suggestions()
    {
        // foreignKey: 'national_id' on the 'suggestions' table
        // localKey: 'national_id' on the 'users' table
        return $this->hasMany(Suggestion::class, 'national_id', 'national_id');
    }
    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badges')->withTimestamps()->withPivot('awarded_at');
    }
    public function isEmployee(): bool
    {
        // مقارنة الرقم القومي للمستخدم الحالي بالأرقام المسجلة في جدول الموظفين
        return Employee::where('national_id', $this->national_id)->exists();
    }
}
