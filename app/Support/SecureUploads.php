<?php

namespace App\Support;

use Modules\Citizen\Models\Complaint;
use Modules\Citizen\Models\EmergencyReport;
use Modules\Estidama\Models\TrainingApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Private (non-public) storage for sensitive uploads: national-ID images,
 * complaint / emergency attachments and Estidama application documents.
 *
 * New files go to the private "local" disk (storage/app/private) and are only
 * reachable through the authorized route "secure-files.show". Records created
 * before this change still point at files on the "public" disk; diskFor()
 * transparently falls back to it so old records keep working.
 */
class SecureUploads
{
    public const DISK = 'local';

    /** route type => [model class, allowed fields] */
    public const TYPES = [
        'complaint' => [Complaint::class, ['attachment']],
        'emergency-report' => [EmergencyReport::class, ['attachments']],
        'training-application' => [TrainingApplication::class, ['national_id_front_image', 'national_id_back_image', 'personal_statement']],
        'user' => [User::class, ['national_id_image']],
    ];

    public static function store(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, self::DISK);
    }

    public static function diskFor(?string $path): string
    {
        if ($path && Storage::disk(self::DISK)->exists($path)) {
            return self::DISK;
        }

        return 'public';
    }

    public static function typeFor(Model $model): ?string
    {
        foreach (self::TYPES as $type => [$class]) {
            if ($model instanceof $class) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Authorized URL(s) for a file field. Returns a string for single-file
     * fields, an array for multi-file (array) fields, or null when empty.
     */
    public static function url(Model $model, string $field): string|array|null
    {
        $type = self::typeFor($model);
        $value = $model->getAttribute($field);

        if (! $type || blank($value)) {
            return null;
        }

        if (is_string($value) && str_starts_with(trim($value), '[')) {
            $value = json_decode($value, true) ?: [];
        }

        if (is_array($value)) {
            return collect(array_values($value))
                ->keys()
                ->map(fn ($i) => route('secure-files.show', [$type, $model->getKey(), $field, $i]))
                ->all();
        }

        return route('secure-files.show', [$type, $model->getKey(), $field]);
    }
}
