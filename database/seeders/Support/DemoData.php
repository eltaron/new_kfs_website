<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Str;

/**
 * أدوات مشتركة للبيانات التجريبية (Demo data) لبوابة محافظة كفر الشيخ.
 *
 * - لا تعتمد على Faker (تعمل حتى مع composer --no-dev).
 * - عشوائية قابلة للتكرار: كل استدعاء لـ seed() يعيد نفس التسلسل.
 * - الصور: تُولد محلياً بمكتبة GD داخل storage/app/public/demo (بدون تحميل من الإنترنت).
 */
final class DemoData
{
    /** مراكز المحافظة مع إحداثيات تقريبية لمقر المركز. */
    public const CENTERS = [
        'كفر الشيخ'   => [31.1107, 30.9388],
        'دسوق'        => [31.1325, 30.6478],
        'فوه'         => [31.2039, 30.5503],
        'مطوبس'       => [31.2894, 30.5156],
        'بيلا'        => [31.1741, 31.2217],
        'الحامول'     => [31.3116, 31.1501],
        'الرياض'      => [31.2369, 30.9466],
        'سيدي سالم'   => [31.2713, 30.7860],
        'قلين'        => [31.0491, 30.8436],
        'بلطيم'       => [31.5530, 31.0899],
        'برج البرلس'  => [31.5720, 30.9950],
        'سيدي غازي'   => [31.2190, 30.8520],
        'مسير'        => [31.0870, 30.9050],
    ];

    /** قرى/مناطق تابعة لكل مركز (بيانات تجريبية تقريبية). */
    public const VILLAGES = [
        'كفر الشيخ'  => ['سخا', 'القرضا', 'الشين', 'أبو غنيمة', 'الربع', 'دقميرة'],
        'دسوق'       => ['محلة أبو علي', 'شباس الشهداء', 'الصافية', 'سنهور المدينة', 'محلة مالك', 'الكفر الجديد'],
        'فوه'        => ['كفر مجر', 'الحلوانية', 'كوم العرب', 'منية المرشد', 'السالمية', 'الأبعادية'],
        'مطوبس'      => ['إدفينا الجديدة', 'الشيخ مبارك', 'برج مغيزل', 'الجدية', 'كوم الشيخ', 'العجمية'],
        'بيلا'       => ['حمرا الوحش', 'الكفر الشرقي', 'بقلولة', 'تيدة', 'المرازقة', 'الحمراوي'],
        'الحامول'    => ['المنشلين', 'البرلس الجديدة', 'الحمراوي', 'الشهابية', 'طلمبات الحامول', 'المحمودية'],
        'الرياض'     => ['الكوم الطويل', 'منشأة عباس', 'ميت الديبة', 'السواحل', 'الشهيد حمدي', 'شابة'],
        'سيدي سالم'  => ['الحنفي', 'منشأة الصفصاف', 'الكتامية', 'المقلع', 'أبو بدوي', 'الحمامية'],
        'قلين'       => ['شباس عمير', 'البيروم', 'الشقة', 'أبو مندور', 'دمرو', 'كفر الحمام'],
        'بلطيم'      => ['المصيف', 'الحنفي', 'الخاشعة', 'شاطئ بلطيم', 'الشهابية', 'أبو مسلم'],
        'برج البرلس' => ['البرلس', 'المسطح', 'الجزيرة الخضراء', 'شط البرلس', 'المعدية', 'الكوم الأخضر'],
        'سيدي غازي'  => ['المنيل', 'جزيرة القاضي', 'السنانية', 'العامرية', 'الحدادي', 'الطرفاية'],
        'مسير'       => ['الجعفرية', 'الشيخ عيسى', 'المنشية', 'كفر النجباء', 'العزيزية', 'الخادمية'],
    ];

    /** أسماء لاتينية ثابتة للمراكز (slug / أكواد). */
    public const CENTER_SLUGS = [
        'كفر الشيخ' => 'kafr-el-sheikh', 'دسوق' => 'desouk', 'فوه' => 'fuwwah', 'مطوبس' => 'metoubes',
        'بيلا' => 'bila', 'الحامول' => 'el-hamoul', 'الرياض' => 'el-reyad', 'سيدي سالم' => 'sidi-salem',
        'قلين' => 'qallin', 'بلطيم' => 'baltim', 'برج البرلس' => 'borg-el-burullus', 'سيدي غازي' => 'sidi-ghazi',
        'مسير' => 'masir',
    ];

    public const MALE = ['محمد', 'أحمد', 'محمود', 'مصطفى', 'إبراهيم', 'عمرو', 'خالد', 'حسن', 'علي', 'يوسف', 'طارق', 'هشام', 'وائل', 'سامح', 'عبد الرحمن', 'كريم', 'إسلام', 'أيمن', 'شريف', 'ياسر', 'رامي', 'عادل', 'سعيد', 'مجدي', 'حازم'];
    public const FEMALE = ['فاطمة', 'مريم', 'نورا', 'سارة', 'هبة', 'منى', 'آية', 'دعاء', 'شيماء', 'إيمان', 'رحاب', 'أسماء', 'نهى', 'رانيا', 'هالة', 'ياسمين', 'دينا', 'سلمى', 'أميرة', 'نجلاء'];
    public const FAMILY = ['الشناوي', 'البدوي', 'السيد', 'عبد الله', 'الجمل', 'شلبي', 'الفقي', 'النجار', 'عطية', 'سلامة', 'الحداد', 'منصور', 'الشامي', 'خليل', 'زيدان', 'غنيم', 'الدسوقي', 'عبد العزيز', 'فرج', 'رضوان', 'قنديل', 'الصاوي'];

    private static ?string $password = null;
    private static bool $passwordGenerated = false;

    /** بذرة ثابتة لتكرار نفس البيانات في كل تشغيل. */
    public static function seed(int $seed): void
    {
        mt_srand($seed);
    }

    public static function pick(array $items): mixed
    {
        return $items[mt_rand(0, count($items) - 1)];
    }

    public static function chance(int $percent): bool
    {
        return mt_rand(1, 100) <= $percent;
    }

    public static function centerNames(): array
    {
        return array_keys(self::CENTERS);
    }

    public static function name(?bool $female = null): string
    {
        $female ??= self::chance(40);
        $first = self::pick($female ? self::FEMALE : self::MALE);

        return $first . ' ' . self::pick(self::MALE) . ' ' . self::pick(self::FAMILY);
    }

    public static function phone(): string
    {
        return self::pick(['010', '011', '012', '015']) . str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
    }

    /** رقم قومي مصري تجريبي (14 رقماً) بكود محافظة كفر الشيخ 15. */
    public static function nationalId(?bool $female = null): string
    {
        $century = self::chance(80) ? '2' : '3';
        $yy = $century === '2' ? mt_rand(60, 99) : mt_rand(0, 5);
        $serial = mt_rand(100, 999) . ($female ?? self::chance(40) ? self::pick([2, 4, 6, 8]) : self::pick([1, 3, 5, 7, 9]));

        return $century . str_pad((string) $yy, 2, '0', STR_PAD_LEFT)
            . str_pad((string) mt_rand(1, 12), 2, '0', STR_PAD_LEFT)
            . str_pad((string) mt_rand(1, 28), 2, '0', STR_PAD_LEFT)
            . '15' . $serial . mt_rand(1, 9);
    }

    public static function email(string $local): string
    {
        return $local . '@demo.kfs.invalid';
    }

    /** نقطة عشوائية قريبة من مقر المركز. */
    public static function coords(string $center): array
    {
        [$lat, $lng] = self::CENTERS[$center] ?? self::CENTERS['كفر الشيخ'];

        return [round($lat + mt_rand(-150, 150) / 10000, 7), round($lng + mt_rand(-150, 150) / 10000, 7)];
    }

    public static function pastDate(int $maxDays = 180, int $minDays = 0): \Illuminate\Support\Carbon
    {
        return now()->subDays(mt_rand($minDays, $maxDays))->setTime(mt_rand(8, 16), mt_rand(0, 59));
    }

    /**
     * كلمة مرور الحسابات التجريبية: من DEMO_PASSWORD وإلا تُولد مرة واحدة لكل تشغيل.
     * لا توجد كلمة مرور مكتوبة في الكود.
     */
    public static function password(): string
    {
        if (self::$password === null) {
            $env = env('DEMO_PASSWORD');
            if (is_string($env) && $env !== '') {
                self::$password = $env;
            } else {
                self::$password = 'Kfs-' . Str::password(14, symbols: false);
                self::$passwordGenerated = true;
            }
        }

        return self::$password;
    }

    public static function passwordWasGenerated(): bool
    {
        return self::$passwordGenerated;
    }

    /**
     * صورة نائبة بهوية المحافظة (عاجي / سماوي / ذهبي) تُولد بـ GD.
     * تعيد مساراً نسبياً على قرص public مثل demo/posts/post-1.jpg.
     */
    public static function image(string $folder, string $key, int $w = 1200, int $h = 750, ?string $label = null): string
    {
        $relative = 'demo/' . trim($folder, '/') . '/' . Str::slug($key) . '.jpg';
        $absolute = storage_path('app/public/' . $relative);

        if (is_file($absolute) || ! function_exists('imagecreatetruecolor')) {
            return $relative;
        }

        if (! is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0775, true);
        }

        $hash = crc32($folder . $key);
        $img = imagecreatetruecolor($w, $h);
        $ivory = imagecolorallocate($img, 250, 246, 238);
        $ivory2 = imagecolorallocate($img, 241, 234, 220);
        $charcoal = imagecolorallocate($img, 38, 42, 48);
        $palettes = [[14, 165, 183], [196, 154, 62], [8, 112, 130], [176, 132, 46]];
        [$r, $g, $b] = $palettes[$hash % 4];
        $accent = imagecolorallocate($img, $r, $g, $b);
        [$r2, $g2, $b2] = $palettes[($hash + 1) % 4];
        $accent2 = imagecolorallocatealpha($img, $r2, $g2, $b2, 60);
        $soft = imagecolorallocatealpha($img, $r, $g, $b, 95);

        imagefilledrectangle($img, 0, 0, $w, $h, $ivory);
        // خطوط زخرفية قطرية خفيفة
        for ($x = -$h; $x < $w; $x += 46) {
            imageline($img, $x, $h, $x + $h, 0, $ivory2);
        }
        // دوائر هندسية بلون الهوية
        imagefilledellipse($img, (int) ($w * (0.15 + ($hash % 50) / 100)), (int) ($h * 0.35), (int) ($h * 0.9), (int) ($h * 0.9), $soft);
        imagefilledellipse($img, (int) ($w * 0.85), (int) ($h * 0.8), (int) ($h * 0.6), (int) ($h * 0.6), $accent2);
        // شريط سفلي
        imagefilledrectangle($img, 0, $h - (int) ($h * 0.16), $w, $h, $charcoal);
        imagefilledrectangle($img, 0, $h - (int) ($h * 0.16) - 6, $w, $h - (int) ($h * 0.16), $accent);

        $text = $label ?? strtoupper('KAFR EL-SHEIKH  ·  ' . str_replace('-', ' ', $folder));
        $font = self::font();
        $white = imagecolorallocate($img, 250, 246, 238);
        if ($font) {
            $size = max(12, (int) ($h * 0.045));
            // Fit the label inside the strip (portrait images are narrower than
            // their height-based font size allows) — shrink until it spans ≤ 90% width.
            while ($size > 10) {
                $box = imagettfbbox($size, 0, $font, $text);
                if ($box !== false && abs($box[2] - $box[0]) <= $w * 0.9) {
                    break;
                }
                $size--;
            }
            imagettftext($img, $size, 0, (int) ($w * 0.05), $h - (int) ($h * 0.06), $white, $font, $text);
            imagettftext($img, (int) ($size * 0.7), 0, (int) ($w * 0.05), (int) ($h * 0.12), $charcoal, $font, 'DEMO  #' . substr(md5($key), 0, 6));
        } else {
            imagestring($img, 5, (int) ($w * 0.05), $h - (int) ($h * 0.1), $text, $white);
        }

        imagejpeg($img, $absolute, 82);
        imagedestroy($img);

        return $relative;
    }

    /** ملف PDF نائب بسيط (للمرفقات مثل المحاضر والقرارات). */
    public static function pdf(string $folder, string $key): string
    {
        $relative = 'demo/' . trim($folder, '/') . '/' . Str::slug($key) . '.pdf';
        $absolute = storage_path('app/public/' . $relative);
        if (! is_file($absolute)) {
            if (! is_dir(dirname($absolute))) {
                mkdir(dirname($absolute), 0775, true);
            }
            $content = "BT /F1 18 Tf 72 720 Td (KFS DEMO DOCUMENT - {$key}) Tj ET";
            $objects = [
                '<< /Type /Catalog /Pages 2 0 R >>',
                '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
                '<< /Length ' . strlen($content) . " >>\nstream\n{$content}\nendstream",
                '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            ];
            $pdf = "%PDF-1.4\n";
            $offsets = [];
            foreach ($objects as $i => $obj) {
                $offsets[] = strlen($pdf);
                $pdf .= ($i + 1) . " 0 obj\n{$obj}\nendobj\n";
            }
            $xref = strlen($pdf);
            $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
            foreach ($offsets as $o) {
                $pdf .= str_pad((string) $o, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
            }
            $pdf .= 'trailer << /Size ' . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
            file_put_contents($absolute, $pdf);
        }

        return $relative;
    }

    private static function font(): ?string
    {
        foreach ([
            public_path('fonts/demo.ttf'),
            'C:/Windows/Fonts/arialbd.ttf',
            'C:/Windows/Fonts/arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
        ] as $f) {
            if (is_file($f) && function_exists('imagettftext')) {
                return $f;
            }
        }

        return null;
    }
}
