<?php

namespace Modules\Syndicate\Models;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * شهادة إشراف على الموقع أو شهادة إلغاء إشراف — على الجدول القديم syndicate_certificates بعد توسيعه.
 */
class Certificate extends Model
{
    protected $table = 'syndicate_certificates';

    public const KIND_SUPERVISION = 'supervision';
    public const KIND_CANCELLATION = 'cancellation';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_SUPERSEDED = 'superseded';

    // ثوابت الجدول القديم (متوافقة مع الإصدار السابق)
    public const TYPE_EXECUTION = 'execution';
    public const TYPE_DEMOLITION = 'demolition';
    public const SUB_TYPE_CREATION = 'creation';
    public const SUB_TYPE_ELEVATION = 'elevation';

    protected $fillable = [
        'number', 'kind', 'status', 'project_id', 'request_id', 'cancels_certificate_id',
        'type', 'execution_sub_type',
        'engineer_id', 'engineer_name', 'engineer_address', 'engineer_national_id', 'engineer_syndicate_number',
        'engineer_department', 'engineer_registration_number', 'engineer_phone', 'engineer_email',
        'office_name', 'office_registration_number',
        'owner_name', 'owner_address', 'owner_national_id',
        'license_number', 'license_date', 'license_authority',
        'project_address', 'city', 'governorate', 'licensed_works_description',
        'works_value_numbers', 'works_value_words', 'certificate_file',
        'issued_at', 'issued_by', 'issued_by_name', 'cancelled_at', 'superseded_at',
        'verification_code', 'pdf_path', 'notes',
    ];

    protected $casts = [
        'license_date' => 'date',
        'works_value_numbers' => 'decimal:2',
        'issued_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'superseded_at' => 'datetime',
    ];

    protected $hidden = ['verification_code'];

    public static function types(): array
    {
        return [
            self::TYPE_EXECUTION => 'تنفيذ',
            self::TYPE_DEMOLITION => 'هدم',
        ];
    }

    public static function executionSubTypes(): array
    {
        return [
            self::SUB_TYPE_CREATION => 'إنشاء',
            self::SUB_TYPE_ELEVATION => 'تعلية',
        ];
    }

    public static function kinds(): array
    {
        return [
            self::KIND_SUPERVISION => 'شهادة إشراف على الموقع',
            self::KIND_CANCELLATION => 'شهادة إلغاء إشراف',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'سارية',
            self::STATUS_CANCELLED => 'ملغاة',
            self::STATUS_SUPERSEDED => 'مستبدلة',
        ];
    }

    public static function statusColors(): array
    {
        return [
            self::STATUS_ACTIVE => 'success',
            self::STATUS_CANCELLED => 'danger',
            self::STATUS_SUPERSEDED => 'gray',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return self::types()[$this->type] ?? (string) $this->type;
    }

    public function getSubTypeLabelAttribute(): ?string
    {
        return $this->execution_sub_type
            ? (self::executionSubTypes()[$this->execution_sub_type] ?? null)
            : null;
    }

    public function getKindLabelAttribute(): string
    {
        return self::kinds()[$this->kind] ?? (string) $this->kind;
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->kind === self::KIND_CANCELLATION) {
            return 'صادرة';
        }

        return self::statuses()[$this->status] ?? (string) $this->status;
    }

    public function isSupervision(): bool
    {
        return $this->kind === self::KIND_SUPERVISION;
    }

    public function isActive(): bool
    {
        return $this->isSupervision() && $this->status === self::STATUS_ACTIVE;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(SyndicateRequest::class, 'request_id');
    }

    public function engineer(): BelongsTo
    {
        return $this->belongsTo(Engineer::class, 'engineer_id');
    }

    /** شهادة الإشراف التي تلغيها شهادة الإلغاء هذه. */
    public function cancels(): BelongsTo
    {
        return $this->belongsTo(self::class, 'cancels_certificate_id');
    }

    /** شهادة الإلغاء الصادرة لهذه الشهادة (إن وجدت). */
    public function cancellation(): HasOne
    {
        return $this->hasOne(self::class, 'cancels_certificate_id');
    }

    public function verificationUrl(bool $withCode = true): string
    {
        $params = ['number' => $this->number];
        if ($withCode && $this->verification_code) {
            $params['c'] = $this->verification_code;
        }

        return route('syndicate.verify.show', $params);
    }

    public function printUrl(): string
    {
        return route('syndicate.certificates.print', ['number' => $this->number]);
    }

    /** رمز QR (SVG) لرابط التحقق من الشهادة. */
    public function qrSvg(int $size = 150): string
    {
        $renderer = new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd());

        $svg = (new Writer($renderer))->writeString($this->verificationUrl());

        // إزالة تعريف XML ليُضمَّن داخل HTML مباشرة.
        return trim(preg_replace('/^<\?xml[^>]*\?>/', '', $svg));
    }

    public static function maskNationalId(?string $id): string
    {
        if (! $id) {
            return '-';
        }

        return mb_substr($id, 0, 3) . str_repeat('•', max(0, mb_strlen($id) - 6)) . mb_substr($id, -3);
    }
}
