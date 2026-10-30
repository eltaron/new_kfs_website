<?php

namespace Modules\SiteValidity\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * يولّد «المعرف (لاتيني)» من الاسم العربي: ترجمة كلمة بكلمة من قاموس مصطلحات الخدمة،
 * ونقل حرفي (Str::slug) لما لا يوجد في القاموس. بدون أي خدمة ترجمة خارجية.
 *
 *   «صورة بطاقة الرقم القومي»        → national_id_card_image
 *   «عرض الشارع (متر)»               → street_width
 *   «صلاحية موقع لترخيص بناء» (slug) → building-license-site-validity
 */
final class LatinKey
{
    /** عبارات تُترجم ككتلة واحدة (قبل تقسيم الكلمات). */
    private const PHRASES = [
        'هل يوجد' => 'has',
        'هل توجد' => 'has',
        'الرقم القومي' => 'national_id',
        'رقم قومي' => 'national_id',
        'صلاحيه موقع' => 'site_validity',
        'صلاحيه الموقع' => 'site_validity',
        'خط التنظيم' => 'planning_line',
        'ملف الكاد' => 'cad_file',
        'ملف كاد' => 'cad_file',
        'الرفع المساحي' => 'survey',
        'رفع مساحي' => 'survey',
        'مهندس نقابي' => 'licensed_engineer',
        'مكتب هندسي' => 'engineering_office',
    ];

    /** كلمة (بعد التطبيع وحذف «ال») => الإنجليزية. */
    private const WORDS = [
        'صوره' => 'image', 'صور' => 'images', 'بطاقه' => 'card', 'هويه' => 'id', 'رقم' => 'number', 'قومي' => 'national',
        'عقد' => 'contract', 'ملكيه' => 'ownership', 'تمليك' => 'ownership', 'كروكي' => 'sketch', 'رسم' => 'drawing',
        'مخطط' => 'plan', 'لوحه' => 'sheet', 'ملف' => 'file', 'كاد' => 'cad', 'خريطه' => 'map', 'موقع' => 'site',
        'صلاحيه' => 'validity', 'ترخيص' => 'license', 'رخصه' => 'license', 'بناء' => 'building', 'مبني' => 'building',
        'تعليه' => 'extension', 'هدم' => 'demolition', 'ترميم' => 'restoration', 'مرافق' => 'utilities', 'توصيل' => 'connection',
        'كهرباء' => 'electricity', 'مياه' => 'water', 'صرف' => 'sewage', 'صحي' => 'sanitary', 'تصالح' => 'reconciliation',
        'مخالفات' => 'violations', 'مخالفه' => 'violation', 'مشروع' => 'project', 'مشروعات' => 'projects', 'استثماري' => 'investment',
        'استثمار' => 'investment', 'حدود' => 'boundaries', 'واضحه' => 'clear', 'واضح' => 'clear', 'بروز' => 'projection',
        'تعدي' => 'encroachment', 'خط' => 'line', 'تنظيم' => 'planning', 'ارتداد' => 'setback', 'مطابق' => 'compliant',
        'مطابقه' => 'compliant', 'عرض' => 'width', 'طول' => 'length', 'شارع' => 'street', 'نوع' => 'type', 'ارض' => 'land',
        'مساحه' => 'area', 'تقريبيه' => 'approx', 'تقريبا' => 'approx', 'حاله' => 'status', 'وصف' => 'description',
        'جيران' => 'neighbors', 'ملاحظات' => 'notes', 'ملاحظه' => 'note', 'اخري' => 'other', 'اخرى' => 'other',
        'سكني' => 'residential', 'زراعي' => 'agricultural', 'تجاري' => 'commercial', 'صناعي' => 'industrial',
        'خدمي' => 'service', 'املاك' => 'property', 'دوله' => 'state', 'ايصال' => 'receipt', 'سداد' => 'payment',
        'رسوم' => 'fees', 'شهاده' => 'certificate', 'معتمد' => 'approved', 'معتمده' => 'approved', 'مهندس' => 'engineer',
        'نقابه' => 'syndicate', 'مكتب' => 'office', 'هندسي' => 'engineering', 'توكيل' => 'proxy', 'وكاله' => 'proxy',
        'طلب' => 'request', 'خدمه' => 'service', 'نهائي' => 'final', 'تقرير' => 'report', 'معاينه' => 'inspection',
        'فوتوغرافيه' => 'photo', 'ارتفاع' => 'height', 'عدد' => 'count', 'ادوار' => 'floors', 'دور' => 'floor',
        'عقار' => 'property', 'مالك' => 'owner', 'وجه' => 'front', 'امامي' => 'front', 'خلفي' => 'back', 'ظهر' => 'back',
        'احداثيات' => 'coordinates', 'نقاط' => 'points', 'نقطه' => 'point', 'مساحي' => 'survey', 'جيومكاني' => 'geospatial',
        'مركز' => 'center', 'قريه' => 'village', 'مدينه' => 'city', 'عنوان' => 'address', 'تاريخ' => 'date', 'اسم' => 'name',
        'هاتف' => 'phone', 'موبايل' => 'mobile', 'توقيع' => 'signature', 'اقرار' => 'declaration', 'تعهد' => 'undertaking',
        'استمارة' => 'form', 'استماره' => 'form', 'نموذج' => 'form', 'مستند' => 'document', 'مستندات' => 'documents',
        'قرار' => 'decision', 'تخصيص' => 'allocation', 'حيز' => 'zone', 'عمراني' => 'urban', 'متاحه' => 'available',
        'متوفره' => 'available', 'كامل' => 'full', 'جزئي' => 'partial', 'اول' => 'first', 'ثاني' => 'second', 'يوجد' => 'has',
        'تعد' => 'encroachment', 'متر' => 'meter', 'م' => 'sqm', 'مسموح' => 'allowed', 'جديد' => 'new', 'جديده' => 'new',
        'تجريبي' => 'demo', 'تجريبيه' => 'demo', 'قديم' => 'old', 'حالي' => 'current', 'سابق' => 'previous', 'بدل' => 'replacement',
        'فاقد' => 'lost', 'اعاده' => 're', 'دراسه' => 'study', 'سبب' => 'reason', 'غرض' => 'purpose', 'شخصي' => 'personal',
    ];

    /** كلمات تُحذف (حروف جر وأدوات وضمائر). */
    private const STOP = ['هل', 'من', 'في', 'على', 'علي', 'الى', 'الي', 'عن', 'مع', 'او', 'و', 'ثم', 'التي', 'الذي', 'اي', 'ب', 'ل', 'ك', 'بها', 'به', 'لها', 'له', 'فيها', 'فيه', 'منها', 'هذا', 'هذه'];

    /** @var array<string, string>|null القاموس بعد تطبيع مفاتيحه (ة→ه، ئ→ي…) */
    private static ?array $words = null;

    /**
     * @param  class-string<Model>|null  $model  لتفادي التكرار: يضيف _2، _3… إن كان المعرف مستخدماً.
     */
    public static function make(?string $text, string $separator = '_', int $max = 64, ?string $model = null, string $column = 'key', string $fallback = 'item'): string
    {
        // ما بين الأقواس وحدات/توضيحات: «عرض الشارع (متر)» → street_width
        $text = self::normalize(preg_replace('/\([^)]*\)/u', ' ', (string) $text));

        foreach (self::PHRASES as $ar => $en) {
            $text = preg_replace('/(^|\s)(?:وال|بال|لل|ال)?' . preg_quote(self::normalize($ar), '/') . '(?=\s|$)/u', '$1' . $en, $text);
        }

        $parts = [];
        foreach (preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) as $word) {
            if (preg_match('/^[a-z0-9_]+$/', $word)) {
                $parts[] = $word; // عبارة مترجمة أو كلمة لاتينية أصلاً
                continue;
            }
            $en = self::translateWord($word);
            if ($en !== null && $en !== '') {
                $parts[] = $en;
            }
        }

        // ترتيب الإنجليزية عكس الإضافة العربية: «صورة بطاقة» → card image، مع إبقاء has في البداية.
        $parts = array_values(array_unique(array_filter($parts)));
        $lead = ($parts[0] ?? null) === 'has' ? [array_shift($parts)] : [];
        $parts = [...$lead, ...array_reverse($parts)];
        $key = Str::slug(implode(' ', $parts), $separator);
        $key = str_replace(['_', '-'], $separator, $key);
        $key = trim(Str::limit($key, $max, ''), $separator);

        if ($key === '' || ! preg_match('/^[a-z]/', $key)) {
            $key = trim($fallback . $separator . $key, $separator);
        }

        return $model ? self::unique($key, $separator, $max, $model, $column) : $key;
    }

    private static function translateWord(string $word): ?string
    {
        $words = self::$words ??= array_combine(array_map(self::normalize(...), array_keys(self::WORDS)), self::WORDS);

        if (in_array($word, self::STOP, true)) {
            return null;
        }
        if (isset($words[$word])) {
            return $words[$word];
        }
        // حذف البادئات: «وال، بال، فال، كال، لل، ال، و، ب، ل»
        foreach (['وال', 'بال', 'فال', 'كال', 'لل', 'ال', 'و', 'ب', 'ل'] as $prefix) {
            if (mb_strlen($word) > mb_strlen($prefix) + 1 && str_starts_with($word, $prefix)) {
                $stem = mb_substr($word, mb_strlen($prefix));
                if (isset($words[$stem])) {
                    return $words[$stem];
                }
                if (in_array($prefix, ['ال', 'لل', 'وال', 'بال'], true) && ! in_array($stem, self::STOP, true)) {
                    return Str::slug($stem, '_') ?: null; // نقل حرفي للكلمة بدون «ال»
                }
            }
        }

        return Str::slug($word, '_') ?: null;
    }

    private static function normalize(string $text): string
    {
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text);   // تشكيل وتطويل
        $text = strtr($text, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ة' => 'ه', 'ى' => 'ي', 'ؤ' => 'و', 'ئ' => 'ي']);
        $text = preg_replace('/[^\p{Arabic}a-zA-Z0-9\s]+/u', ' ', $text);           // علامات الترقيم
        $text = preg_replace('/[\x{060C}\x{061B}\x{061F}]/u', ' ', $text);          // ، ؛ ؟

        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $text)));
    }

    /** @param  class-string<Model>  $model */
    private static function unique(string $key, string $separator, int $max, string $model, string $column): string
    {
        $candidate = $key;
        for ($i = 2; $model::query()->where($column, $candidate)->exists(); $i++) {
            $suffix = $separator . $i;
            $candidate = trim(Str::limit($key, $max - strlen($suffix), ''), $separator) . $suffix;
        }

        return $candidate;
    }
}
