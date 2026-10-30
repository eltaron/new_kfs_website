<?php

namespace Modules\Estidama\Models;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingCertificate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'issued_at' => 'datetime',
        'revoked_at' => 'datetime',
        'final_score' => 'integer',
    ];

    /** Unambiguous alphabet for serials (no 0/O, 1/I/L). */
    protected const SERIAL_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function examSubmission(): BelongsTo
    {
        return $this->belongsTo(ExamSubmission::class);
    }

    public function isValid(): bool
    {
        return $this->revoked_at === null;
    }

    /** Unique, hard-to-guess serial such as KFS-EST-2026-7Q4K-M2XD. */
    public static function generateSerial(): string
    {
        $chunk = function (): string {
            $out = '';
            for ($i = 0; $i < 4; $i++) {
                $out .= self::SERIAL_ALPHABET[random_int(0, strlen(self::SERIAL_ALPHABET) - 1)];
            }

            return $out;
        };

        do {
            $serial = 'KFS-EST-' . now()->format('Y') . '-' . $chunk() . '-' . $chunk();
        } while (static::where('serial', $serial)->exists());

        return $serial;
    }

    public function verificationUrl(): string
    {
        return route('lms.certificates.verify', ['serial' => $this->serial]);
    }

    public function qrSvg(int $size = 140): string
    {
        $renderer = new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd());
        $svg = (new Writer($renderer))->writeString($this->verificationUrl());

        return trim(preg_replace('/^<\?xml[^>]*\?>/', '', $svg));
    }

    /** Holder name for the public verification page: first name + initials. */
    public function publicHolderName(): string
    {
        $parts = preg_split('/\s+/u', trim((string) $this->holder_name)) ?: [];
        if (count($parts) <= 1) {
            return (string) $this->holder_name;
        }
        $first = array_shift($parts);

        return $first . ' ' . implode(' ', array_map(fn ($p) => mb_substr($p, 0, 1) . '.', $parts));
    }
}
