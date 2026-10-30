<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Last line of defence for every Filament FileUpload (registered globally in
 * SecurityServiceProvider): rejects server-side code / active web content and
 * SVG images that carry scripts, whatever acceptedFileTypes() a resource sets.
 * Public-disk files are served from this origin, so an SVG or HTML file with
 * script would run with the site's cookies (stored XSS).
 */
class SafeUpload implements ValidationRule
{
    /** Extensions that are never accepted, by client name or by detected type. */
    public const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phps', 'phar',
        'html', 'htm', 'xhtml', 'xht', 'shtml', 'shtm', 'mht', 'mhtml', 'hta',
        'js', 'mjs', 'cjs', 'jsp', 'jspx', 'asp', 'aspx', 'ashx', 'asmx', 'cer',
        'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'bat', 'cmd', 'exe', 'dll', 'msi', 'ps1', 'vbs',
        'htaccess', 'htpasswd', 'ini', 'user.ini', 'config', 'swf', 'xsl', 'xslt',
    ];

    /** Markup that makes an SVG/XML document active. */
    private const ACTIVE_SVG_PATTERN = '/<\s*script|<\s*foreignObject|<\s*iframe|<\s*embed|<\s*object|<\s*handler|\bon[a-z]+\s*=|javascript\s*:|vbscript\s*:|data\s*:\s*text\/html|<!ENTITY|xlink:href\s*=\s*["\']?\s*(?!#|data:image\/)[a-z]+:/i';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return; // existing stored paths (strings) are not re-validated
        }

        $names = array_filter([
            strtolower((string) $value->getClientOriginalName()),
            strtolower((string) $value->getFilename()),
        ]);

        foreach ($names as $name) {
            // Check every dotted segment so "shell.php.jpg" and "x.phtml" are refused.
            $segments = array_slice(explode('.', $name), 1);
            foreach ($segments as $segment) {
                // Windows/NTFS normalises "x.php " , "x.php." and "x.php::$DATA" to x.php:
                // compare the leading alphanumeric run of each segment as well.
                $core = preg_match('/^([a-z0-9]+)(?:[\s.:]*|::\$data)$/', $segment, $m) ? $m[1] : $segment;
                if (in_array($segment, self::BLOCKED_EXTENSIONS, true) || in_array($core, self::BLOCKED_EXTENSIONS, true)) {
                    $fail('نوع الملف غير مسموح به لأسباب أمنية.');

                    return;
                }
            }
        }

        $mime = strtolower((string) $value->getMimeType());
        $guessed = strtolower((string) $value->guessExtension());
        if (in_array($guessed, self::BLOCKED_EXTENSIONS, true)
            || str_contains($mime, 'html') || str_contains($mime, 'javascript') || str_contains($mime, 'x-php')) {
            $fail('نوع الملف غير مسموح به لأسباب أمنية.');

            return;
        }

        $isSvg = str_contains($mime, 'svg') || in_array($mime, ['text/xml', 'application/xml'], true)
            || in_array(strtolower((string) $value->getClientOriginalExtension()), ['svg', 'svgz', 'xml'], true);

        if ($isSvg) {
            $content = @file_get_contents($value->getRealPath(), false, null, 0, 5 * 1024 * 1024);
            if ($content === false || preg_match(self::ACTIVE_SVG_PATTERN, $content)) {
                $fail('ملف SVG يحتوي على محتوى نشط (سكربت) وغير مسموح به.');
            }
        }
    }
}
