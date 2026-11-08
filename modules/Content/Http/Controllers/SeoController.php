<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\EServices;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Citizen\Models\Service;
use Modules\Content\Models\Post;
use Modules\Investment\Models\Investment;
use Modules\Investment\Models\Project;
use Modules\Tourism\Models\Landmark;

/**
 * Search-engine & answer-engine endpoints: /sitemap.xml, /robots.txt, /llms.txt.
 * URLs are built from the current host, so the same code works locally and in production.
 */
class SeoController extends Controller
{
    /** Public sections (route name, params, changefreq, priority). Hidden e-services are skipped. */
    private const PAGES = [
        ['home', [], 'daily', '1.0'],
        ['services.index', [], 'weekly', '0.9'],
        ['posts.index', [], 'daily', '0.9'],
        ['about', [], 'monthly', '0.8'],
        ['about.governor', [], 'monthly', '0.7'],
        ['officials.show', ['role' => 'governor'], 'monthly', '0.6'],
        ['officials.show', ['role' => 'deputy-governor'], 'monthly', '0.5'],
        ['officials.show', ['role' => 'secretary-general'], 'monthly', '0.5'],
        ['officials.show', ['role' => 'assistant-secretary-general'], 'monthly', '0.5'],
        ['projects.index', [], 'weekly', '0.8'],
        ['projects.hay-karima', [], 'monthly', '0.7'],
        ['investments.index', [], 'weekly', '0.8'],
        ['investment.plans.index', [], 'monthly', '0.6'],
        ['landmarks.index', [], 'monthly', '0.8'],
        ['governorate.map', [], 'monthly', '0.7'],
        ['directory.index', [], 'monthly', '0.8'],
        ['requests.index', [], 'monthly', '0.8'],
        ['emergency.create', [], 'monthly', '0.7'],
        ['contact.index', [], 'yearly', '0.5'],
        ['suggestions.create', [], 'yearly', '0.4'],
        ['estidama.index', [], 'weekly', '0.6'],
        ['exam-results.index', [], 'monthly', '0.6'],
        ['market.home', [], 'weekly', '0.6'],
        ['site-validity.landing', [], 'monthly', '0.6'],
        ['gis.index', [], 'monthly', '0.5'],
    ];

    public function sitemap()
    {
        $xml = Cache::store('file')->remember('seo:sitemap:' . request()->getHost(), now()->addHour(), function () {
            $urls = $this->sitemapUrls();
            $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
            foreach ($urls as $u) {
                $out .= '  <url><loc>' . e($u['loc']) . '</loc>'
                    . (! empty($u['lastmod']) ? '<lastmod>' . $u['lastmod'] . '</lastmod>' : '')
                    . '<changefreq>' . $u['freq'] . '</changefreq><priority>' . $u['priority'] . '</priority>'
                    . (! empty($u['image']) ? '<image:image><image:loc>' . e($u['image']) . '</image:loc></image:image>' : '')
                    . "</url>\n";
            }

            return $out . '</urlset>' . "\n";
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots()
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            // Private panels, accounts and transactional pages.
            'Disallow: /admin', 'Disallow: /gis', 'Disallow: /estidama-admin', 'Disallow: /council', 'Disallow: /vendor-panel',
            'Disallow: /erp-governorate', 'Disallow: /dashboard', 'Disallow: /citizen', 'Disallow: /account',
            'Disallow: /login', 'Disallow: /register', 'Disallow: /password', 'Disallow: /two-factor',
            'Disallow: /payments', 'Disallow: /efinance', 'Disallow: /receipts', 'Disallow: /secure-files',
            'Disallow: /store/cart', 'Disallow: /store/checkout', 'Disallow: /store/orders', 'Disallow: /store/wishlist',
            'Disallow: /search', 'Disallow: /livewire', 'Disallow: /storage/private',
            '',
            'Sitemap: ' . url('sitemap.xml'),
        ];

        return response(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** llms.txt — a plain-language map of the site for AI answer engines (https://llmstxt.org). */
    public function llms()
    {
        $txt = Cache::store('file')->remember('seo:llms:' . request()->getHost(), now()->addHour(), function () {
            $link = fn (string $name, array $p = []) => Route::has($name) && EServices::visible($name) ? route($name, $p) : null;
            $md = "# محافظة كفر الشيخ — الموقع الرسمي\n\n";
            $md .= "> الموقع الرسمي لمحافظة كفر الشيخ (جمهورية مصر العربية): الخدمات الإلكترونية للمواطنين، أخبار المحافظة، المشروعات القومية، فرص الاستثمار، المعالم السياحية، وأرقام الطوارئ. اللغة: العربية.\n\n";
            $md .= "أرقام الطوارئ في مصر: الإسعاف 123، شرطة النجدة 122، الحماية المدنية 180.\n\n";

            $md .= "## الصفحات الرئيسية\n\n";
            foreach ([
                ['about', 'عن المحافظة', 'التاريخ، سبب التسمية، الرؤية والرسالة، الموارد ورموز المحافظة'],
                ['about.governor', 'كلمة المحافظ', 'رسالة محافظ كفر الشيخ'],
                ['services.index', 'دليل الخدمات', 'الخدمات الحكومية وشروطها ومستنداتها'],
                ['requests.index', 'الشكاوى والبلاغات', 'تقديم شكوى عبر منظومة الشكاوى الموحدة أو بلاغ عاجل'],
                ['emergency.create', 'بلاغ عاجل', 'نموذج الإبلاغ عن حالة طارئة مع تحديد الموقع'],
                ['directory.index', 'دليل الهاتف', 'أرقام الطوارئ والشكاوى والوزارات'],
                ['posts.index', 'الأخبار', 'أخبار المحافظة'],
                ['projects.index', 'المشروعات القومية', 'المشروعات التنموية الكبرى ومواقعها'],
                ['investments.index', 'الاستثمار', 'الفرص الاستثمارية والمناطق الصناعية'],
                ['investment.plans.index', 'الخطة الاستثمارية', 'أرشيف الخطط الاستثمارية وتقاريرها الختامية'],
                ['landmarks.index', 'السياحة والمعالم', 'المعالم السياحية والأثرية'],
                ['governorate.map', 'الخريطة التفاعلية', 'المعالم والمشروعات والخدمات على خريطة واحدة'],
                ['contact.index', 'تواصل معنا', 'التواصل مع المحافظة'],
            ] as [$route, $title, $desc]) {
                if ($u = $link($route)) {
                    $md .= "- [{$title}]({$u}): {$desc}\n";
                }
            }

            $services = Service::active()->ordered()->whereNull('parent_id')->get();
            if ($services->isNotEmpty()) {
                $md .= "\n## الخدمات الإلكترونية\n\n";
                foreach ($services as $s) {
                    $md .= '- [' . $this->plain($s->title ?: trim($s->title_line_1 . ' ' . $s->title_line_2)) . '](' . $s->publicUrl() . ')'
                        . ($s->description ? ': ' . Str::limit($this->plain($s->description), 140) : '') . "\n";
                }
            }

            $posts = Post::where('is_published', true)->latest('published_at')->take(15)->get(['id', 'slug', 'title', 'published_at', 'created_at']);
            if ($posts->isNotEmpty() && Route::has('posts.show')) {
                $md .= "\n## أحدث الأخبار\n\n";
                foreach ($posts as $p) {
                    $md .= '- [' . $this->plain($p->title) . '](' . route('posts.show', $p) . ') — ' . optional($p->published_at ?? $p->created_at)->format('Y-m-d') . "\n";
                }
            }

            return $md;
        });

        return response($txt, 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }

    private function sitemapUrls(): Collection
    {
        $urls = collect();
        $add = function (?string $loc, $freq, $priority, $lastmod = null, $image = null) use ($urls) {
            if ($loc) {
                $urls->push(['loc' => $loc, 'freq' => $freq, 'priority' => $priority,
                    'lastmod' => $lastmod ? \Illuminate\Support\Carbon::parse($lastmod)->toAtomString() : null, 'image' => $image]);
            }
        };
        $img = fn ($path) => $path ? (Str::startsWith($path, ['http://', 'https://']) ? $path : asset(\Storage::url($path))) : null;

        foreach (self::PAGES as [$name, $params, $freq, $priority]) {
            if (Route::has($name) && EServices::visible($name)) {
                $add(route($name, $params), $freq, $priority);
            }
        }

        if (Route::has('posts.show')) {
            Post::where('is_published', true)->latest('published_at')->take(2000)->get(['id', 'slug', 'thumbnail', 'updated_at'])
                ->each(fn ($p) => $add(route('posts.show', $p), 'monthly', '0.6', $p->updated_at, $img($p->thumbnail)));
        }
        if (Route::has('projects.show')) {
            Project::whereNotNull('slug')->get(['id', 'slug', 'thumbnail', 'updated_at'])
                ->each(fn ($p) => $add(route('projects.show', $p->slug), 'monthly', '0.6', $p->updated_at, $img($p->thumbnail)));
        }
        if (Route::has('landmarks.show')) {
            Landmark::get(['id', 'thumbnail', 'updated_at'])
                ->each(fn ($l) => $add(route('landmarks.show', $l->id), 'monthly', '0.6', $l->updated_at, $img($l->thumbnail)));
        }
        if (Route::has('services.show')) {
            Service::active()->get()->filter(fn ($s) => ! $s->opensExternally() && $s->isAvailable())
                ->each(fn ($s) => $add($s->publicUrl(), 'monthly', '0.6', $s->updated_at));
        }
        if (Route::has('investments.show')) {
            Investment::get(['id', 'updated_at'])->each(fn ($i) => $add(route('investments.show', $i), 'monthly', '0.5', $i->updated_at));
        }

        return $urls->unique('loc')->values();
    }

    private function plain($html): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }
}
