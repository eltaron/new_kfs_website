<?php

namespace Modules\SiteValidity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** ملف مرفق بطلب (على القرص الخاص — يُنزّل فقط عبر route('site-validity.files.download')). */
class RequestFile extends Model
{
    public const KIND_SKETCH = 'sketch';
    public const KIND_CONTRACT = 'contract';
    public const KIND_ID_CARD = 'id_card';
    public const KIND_POA = 'poa';
    public const KIND_FLOORS = 'floors';
    public const KIND_ATTACHMENT = 'attachment';
    public const KIND_COMPLETION = 'completion';
    public const KIND_FINAL = 'final';

    /** مستندات يرفعها المواطن عند التقديم. */
    public const CITIZEN_KINDS = [self::KIND_SKETCH, self::KIND_CONTRACT, self::KIND_ID_CARD, self::KIND_POA, self::KIND_FLOORS];

    public const KIND_LABELS = [
        self::KIND_SKETCH => 'الكروكي',
        self::KIND_CONTRACT => 'صورة عقد الملكية',
        self::KIND_ID_CARD => 'صورة البطاقة',
        self::KIND_POA => 'صورة التوكيل',
        self::KIND_FLOORS => 'ملف موقف الأدوار القائمة',
        self::KIND_ATTACHMENT => 'مرفق فني',
        self::KIND_COMPLETION => 'مستند استيفاء',
        self::KIND_FINAL => 'الملف النهائي',
    ];

    /** أنواع الملفات التي يراها المواطن صاحب الطلب (المرفقات الفنية الداخلية لا تظهر له). */
    public const CITIZEN_VISIBLE = [...self::CITIZEN_KINDS, self::KIND_COMPLETION, self::KIND_FINAL];

    protected $table = 'sv_request_files';

    protected $fillable = ['kind', 'original_name'];

    protected $casts = ['size' => 'integer'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(SiteValidityRequest::class, 'request_id');
    }

    public function attachmentType(): BelongsTo
    {
        return $this->belongsTo(AttachmentType::class, 'attachment_type_id');
    }

    public function label(): string
    {
        if ($this->kind === self::KIND_ATTACHMENT && $this->attachmentType) {
            return $this->attachmentType->name;
        }

        return self::KIND_LABELS[$this->kind] ?? $this->kind;
    }

    public function url(): string
    {
        return route('site-validity.files.download', $this);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }

    public function humanSize(): string
    {
        $size = (int) $this->size;

        return $size >= 1048576 ? number_format($size / 1048576, 1) . ' م.ب' : max(1, (int) round($size / 1024)) . ' ك.ب';
    }
}
