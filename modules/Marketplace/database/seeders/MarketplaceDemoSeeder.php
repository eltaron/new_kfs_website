<?php

namespace Modules\Marketplace\database\seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Marketplace\Models\MarketCategory;
use Modules\Marketplace\Models\MarketProduct;
use Modules\Marketplace\Models\MarketVendor;
use Spatie\Permission\Models\Role;

/**
 * Demo data for متجر ذوي الهمم. All people, stores and numbers are fictional
 * (emails end in .invalid so no mail is ever sent). Idempotent: safe to re-run.
 *
 *   php artisan db:seed --class="Modules\Marketplace\database\seeders\MarketplaceDemoSeeder"
 */
class MarketplaceDemoSeeder extends Seeder
{
    private const CATEGORIES = [
        ['name' => 'المشغولات اليدوية', 'icon' => 'fa-hands', 'color' => '#B88920'],
        ['name' => 'الخوص والجريد', 'icon' => 'fa-basket-shopping', 'color' => '#8F6917'],
        ['name' => 'منتجات خشبية', 'icon' => 'fa-chair', 'color' => '#6B4F2A'],
        ['name' => 'الملابس والتطريز', 'icon' => 'fa-shirt', 'color' => '#008FA5'],
        ['name' => 'منتجات طبيعية وعناية', 'icon' => 'fa-spa', 'color' => '#3D7A50'],
        ['name' => 'الإكسسوارات والهدايا', 'icon' => 'fa-gift', 'color' => '#A63A32'],
    ];

    private const VENDORS = [
        [
            'user' => 'حساب تجريبي — بائعة مشغولات', 'email' => 'demo.vendor1@market.kfs.invalid',
            'store' => 'مشغولات أمل اليدوية', 'center' => 'دسوق', 'status' => 'approved',
            'description' => 'مشغولات يدوية من الصوف والكروشيه، كل قطعة تُصنع بحب وصبر في دسوق.',
            'products' => [
                ['المشغولات اليدوية', 'سجادة يدوية من الصوف الطبيعي', 'سجادة منسوجة يدوياً من صوف الأغنام بألوان الأزرق والذهبي. المقاس 120×80 سم، قابلة للغسيل اليدوي.', 650, 780, 4, 'سجادة صوف يدوية مستطيلة بزخارف زرقاء وذهبية', true],
                ['الملابس والتطريز', 'حقيبة كروشيه ملونة', 'حقيبة يد مصنوعة بالكروشيه من خيوط قطنية متينة، مبطنة من الداخل ولها سوستة.', 220, null, 10, 'حقيبة يد كروشيه بألوان متعددة ومقبض قصير', false, [['name' => 'اللون', 'options' => ['بيج', 'أزرق', 'وردي']]]],
                ['الملابس والتطريز', 'مفرش طاولة مطرز يدوياً', 'مفرش قطني أبيض مطرز بوحدات زخرفية مستوحاة من التراث الريفي. المقاس 150×150 سم.', 380, null, 6, 'مفرش طاولة أبيض بتطريز يدوي باللون الذهبي على الأطراف', true],
            ],
        ],
        [
            'user' => 'حساب تجريبي — حرفي خوص', 'email' => 'demo.vendor2@market.kfs.invalid',
            'store' => 'خوص وجريد البرلس', 'center' => 'البرلس', 'status' => 'approved',
            'description' => 'منتجات من خوص النخيل والجريد بطرق تقليدية توارثناها في البرلس.',
            'products' => [
                ['الخوص والجريد', 'سلة خوص للخبز', 'سلة مستديرة من خوص النخيل الطبيعي بغطاء، مناسبة لحفظ الخبز والفاكهة.', 120, null, 15, 'سلة خوص مستديرة بلون القش الطبيعي ولها غطاء', false],
                ['الخوص والجريد', 'صينية تقديم من الجريد', 'صينية تقديم خفيفة الوزن من جريد النخيل مع مقبضين، مدهونة بورنيش آمن.', 160, 190, 8, 'صينية مستطيلة من الجريد بمقبضين جانبيين', true],
                ['الإكسسوارات والهدايا', 'شنطة خوص للسوق', 'شنطة واسعة من الخوص بمقابض من الحبال القطنية، تتحمل حتى 8 كيلو.', 140, null, 0, 'شنطة تسوق من الخوص بمقابض حبال', false],
            ],
        ],
        [
            'user' => 'حساب تجريبي — نجار', 'email' => 'demo.vendor3@market.kfs.invalid',
            'store' => 'ورشة النور للنجارة', 'center' => 'بيلا', 'status' => 'approved',
            'description' => 'ورشة صغيرة لمنتجات الخشب الطبيعي: حوامل ورفوف وألعاب تعليمية للأطفال.',
            'products' => [
                ['منتجات خشبية', 'حامل مصحف خشبي محفور', 'حامل قابل للطي من خشب الزان بنقوش محفورة يدوياً.', 250, null, 12, 'حامل مصحف خشبي مفتوح بنقوش هندسية محفورة', true],
                ['منتجات خشبية', 'رف حائط خشبي', 'رف حائط بثلاث طبقات من الخشب الطبيعي، يُثبت بمسامير مرفقة.', 300, null, 5, 'رف حائط خشبي بثلاث طبقات معلق على جدار أبيض', false, [['name' => 'اللون', 'options' => ['خشبي طبيعي', 'بني غامق']]]],
                ['منتجات خشبية', 'لعبة حروف عربية خشبية', 'لوحة خشبية بالحروف العربية البارزة لتعليم الأطفال، ألوان آمنة.', 180, 220, 20, 'لوحة خشبية ملونة عليها الحروف العربية بشكل بارز', false],
            ],
        ],
        [
            'user' => 'حساب تجريبي — منتجات طبيعية', 'email' => 'demo.vendor4@market.kfs.invalid',
            'store' => 'منتجات الريف الطبيعية', 'center' => 'قلين', 'status' => 'approved',
            'description' => 'صابون وشموع طبيعية مصنوعة منزلياً بمكونات من ريف كفر الشيخ.',
            'products' => [
                ['منتجات طبيعية وعناية', 'صابون زيت الزيتون الطبيعي', 'قطعة صابون 120 جرام من زيت الزيتون بدون عطور صناعية، مناسبة للبشرة الحساسة.', 45, null, 50, 'قطعتان من صابون زيت الزيتون بلون أخضر فاتح', false],
                ['منتجات طبيعية وعناية', 'شمعة معطرة بالياسمين', 'شمعة من شمع الصويا في برطمان زجاجي، تدوم حتى 30 ساعة.', 85, null, 25, 'شمعة بيضاء في برطمان زجاجي وبجوارها زهور ياسمين', false, [['name' => 'الرائحة', 'options' => ['ياسمين', 'لافندر', 'فانيليا']]]],
            ],
        ],
        [
            'user' => 'حساب تجريبي — متقدمة جديدة', 'email' => 'demo.vendor5@market.kfs.invalid',
            'store' => 'إبداعات الخرز', 'center' => 'فوه', 'status' => 'pending',
            'description' => 'إكسسوارات من الخرز والأحجار الملونة (طلب قيد المراجعة للتجربة).',
            'products' => [],
        ],
    ];

    public function run(): void
    {
        Role::findOrCreate('مشرف المتجر', 'web');

        DB::transaction(function () {
            $categories = $this->seedCategories();

            foreach (self::VENDORS as $i => $data) {
                $vendor = $this->seedVendor($i + 1, $data);
                foreach ($data['products'] as $p) {
                    $this->seedProduct($vendor, $categories[$p[0]], $p);
                }
            }
        });

        $this->command?->info('متجر ذوي الهمم: تم إنشاء البيانات التجريبية (كلمة مرور الحسابات التجريبية: password).');
    }

    /** @return array<string, MarketCategory> */
    private function seedCategories(): array
    {
        $out = [];
        foreach (self::CATEGORIES as $i => $c) {
            $out[$c['name']] = MarketCategory::query()->firstOrCreate(
                ['name' => $c['name']],
                ['icon' => $c['icon'], 'sort_order' => $i + 1, 'is_active' => true, 'description' => 'منتجات '.$c['name'].' من صنع أبناء المحافظة.'],
            );
        }

        return $out;
    }

    private function seedVendor(int $n, array $data): MarketVendor
    {
        $user = User::query()->firstOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['user'],
                // SECURITY: no well-known password on demo accounts (same rule as UserSeeder).
                'password' => Hash::make((string) (env('DEMO_PASSWORD') ?: Str::random(40))),
                'national_id' => '2990101'.str_pad((string) $n, 7, '0', STR_PAD_LEFT),
                'phone' => '0100000000'.$n,
                'center' => $data['center'],
                'status' => 'approved',
            ],
        );

        $vendor = MarketVendor::withTrashed()->firstOrNew(['user_id' => $user->getKey()]);
        $vendor->fill([
            'store_name' => $data['store'],
            'description' => $data['description'],
            'disability_card_number' => 'DEMO-'.str_pad((string) $n, 6, '0', STR_PAD_LEFT),
            'national_id' => $user->national_id,
            'phone' => $user->phone,
            'center' => $data['center'],
            'payout_method' => 'wallet',
            'payout_details' => ['wallet_number' => '0100000000'.$n],
        ]);
        $vendor->forceFill([
            'status' => $data['status'],
            'reviewed_at' => $data['status'] === 'approved' ? now() : null,
            'deleted_at' => null,
        ])->save();

        return $vendor;
    }

    private function seedProduct(MarketVendor $vendor, MarketCategory $category, array $p): void
    {
        [, $name, $description, $price, $compare, $stock, $alt, $featured] = $p;
        $variants = $p[8] ?? null;

        $product = MarketProduct::withTrashed()->firstOrNew(['vendor_id' => $vendor->getKey(), 'name' => $name]);
        $product->fill([
            'category_id' => $category->getKey(),
            'description' => $description,
            'price' => $price,
            'compare_at_price' => $compare,
            'stock' => $stock,
            'variants' => $variants,
            'status' => MarketProduct::STATUS_PUBLISHED,
        ]);
        $product->forceFill(['is_featured' => $featured, 'deleted_at' => null])->save();

        if (! $product->images()->exists()) {
            $color = collect(self::CATEGORIES)->firstWhere('name', $category->name)['color'] ?? '#008FA5';
            $path = 'marketplace/demo/'.Str::slug('p'.$product->getKey()).'.svg';
            Storage::disk(config('marketplace.media_disk', 'public'))->put($path, $this->placeholderSvg($name, $color));
            $product->images()->create(['path' => $path, 'alt_text' => $alt, 'sort_order' => 0]);
        }
    }

    /** Simple branded placeholder image (no third-party photos). */
    private function placeholderSvg(string $title, string $color): string
    {
        $t = htmlspecialchars($title, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600" viewBox="0 0 800 600" role="img">
  <rect width="800" height="600" fill="#FAF6D7"/>
  <rect x="0" y="520" width="800" height="80" fill="{$color}" opacity=".9"/>
  <circle cx="400" cy="250" r="150" fill="{$color}" opacity=".18"/>
  <circle cx="400" cy="250" r="95" fill="none" stroke="{$color}" stroke-width="10"/>
  <text x="400" y="572" font-family="Tajawal, Arial, sans-serif" font-size="34" font-weight="700" fill="#fff" text-anchor="middle" direction="rtl">{$t}</text>
</svg>
SVG;
    }
}
