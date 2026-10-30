<?php

namespace Modules\SiteValidity\Services;

use App\Models\User;
use App\Rules\SafeUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\SiteValidity\Models\RequestFile;
use Modules\SiteValidity\Models\SiteValidityRequest;

/**
 * حفظ ملفات الخدمة على القرص الخاص (storage/app/private/site-validity/{id}/...).
 *
 * يقبل ملفاً مرفوعاً (UploadedFile) أو مساراً مؤقتاً على نفس القرص رفعته لوحة Filament
 * (site-validity/tmp/...) — في الحالتين يُتحقق من الامتداد والحجم وSafeUpload قبل الحفظ.
 */
class FileStore
{
    public const TMP_DIR = 'site-validity/tmp';

    public function disk(): string
    {
        return (string) config('site-validity.disk', 'local');
    }

    /**
     * @param  list<string>  $extensions
     */
    public function validate(UploadedFile|string $file, array $extensions, int $maxKb, string $label): void
    {
        if ($file instanceof UploadedFile) {
            $validator = Validator::make(['file' => $file], [
                'file' => ['required', 'file', 'max:' . $maxKb, 'extensions:' . implode(',', $extensions), new SafeUpload()],
            ], [
                'file.extensions' => 'نوع الملف غير مسموح (المسموح: ' . implode('، ', $extensions) . ').',
                'file.max' => 'حجم الملف أكبر من المسموح (' . $maxKb . ' ك.ب).',
            ], ['file' => $label]);

            if ($validator->fails()) {
                throw new WorkflowException($label . ': ' . $validator->errors()->first('file'), ['file' => $validator->errors()->all()]);
            }

            return;
        }

        $path = $this->normalizeTmpPath($file);
        $disk = Storage::disk($this->disk());
        if ($path === null || ! $disk->exists($path)) {
            throw new WorkflowException($label . ': الملف المرفوع غير موجود، أعد رفعه.');
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($ext, $extensions, true) || in_array($ext, SafeUpload::BLOCKED_EXTENSIONS, true)) {
            throw new WorkflowException($label . ': نوع الملف غير مسموح (المسموح: ' . implode('، ', $extensions) . ').');
        }
        if ($disk->size($path) > $maxKb * 1024) {
            throw new WorkflowException($label . ': حجم الملف أكبر من المسموح (' . $maxKb . ' ك.ب).');
        }
        // نفس فحص SafeUpload على المحتوى (SVG/HTML نشط).
        $validator = Validator::make(['file' => new UploadedFile($disk->path($path), basename($path), null, null, true)], [
            'file' => [new SafeUpload()],
        ]);
        if ($validator->fails()) {
            throw new WorkflowException($label . ': ' . $validator->errors()->first('file'));
        }
    }

    public function store(
        SiteValidityRequest $request,
        UploadedFile|string $file,
        string $kind,
        ?User $by,
        ?int $attachmentTypeId = null,
        ?int $completionId = null,
        ?string $originalName = null,
    ): RequestFile {
        $disk = Storage::disk($this->disk());
        $dir = trim((string) config('site-validity.directory', 'site-validity'), '/') . '/' . $request->getKey();

        if ($file instanceof UploadedFile) {
            $ext = strtolower($file->getClientOriginalExtension() ?: ($file->guessExtension() ?: 'bin'));
            $path = $file->storeAs($dir, $kind . '-' . Str::random(24) . '.' . $ext, $this->disk());
            $original = $originalName ?? $file->getClientOriginalName();
            $mime = $file->getMimeType();
            $size = $file->getSize();
        } else {
            $tmp = $this->normalizeTmpPath($file) ?? throw new WorkflowException('مسار ملف غير صالح.');
            $ext = strtolower(pathinfo($tmp, PATHINFO_EXTENSION));
            $path = $dir . '/' . $kind . '-' . Str::random(24) . '.' . $ext;
            $disk->move($tmp, $path);
            $original = $originalName ?? basename($tmp);
            $mime = $disk->mimeType($path) ?: null;
            $size = $disk->size($path);
        }

        $record = new RequestFile(['kind' => $kind, 'original_name' => Str::limit(strip_tags((string) $original), 180, '')]);
        $record->forceFill([
            'request_id' => $request->getKey(),
            'attachment_type_id' => $attachmentTypeId,
            'completion_id' => $completionId,
            'path' => $path,
            'mime' => $mime ? Str::limit($mime, 120, '') : null,
            'size' => (int) $size,
            'uploaded_by' => $by?->getKey(),
        ])->save();

        return $record;
    }

    /** يقبل فقط مسارات داخل مجلد الرفع المؤقت للخدمة (لا ../ ولا مسارات مطلقة). */
    public function normalizeTmpPath(string $path): ?string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (str_contains($path, '..') || ! str_starts_with($path, self::TMP_DIR . '/')) {
            return null;
        }

        return $path;
    }
}
