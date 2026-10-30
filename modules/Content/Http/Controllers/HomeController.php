<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tourism\Models\CityGuideCategory;
use Modules\Content\Models\HeroSlider;
use Modules\Content\Models\Category;
use Modules\Investment\Models\Investment;
use Modules\Investment\Models\InvestmentPlan;
use Modules\Tourism\Models\Landmark;
use Modules\Tourism\Models\Location;
use Modules\Content\Models\Partner;
use Modules\Content\Models\Post;
use Modules\Investment\Models\Project;
use Modules\Citizen\Models\Service;
use Modules\Content\Models\Setting;
use Modules\Content\Models\Statistic;

class HomeController extends Controller
{
    public function index()
    {
        // 1. Settings (very important)
        $settings = Setting::pluck('value', 'key');

        // 2. Hero Slider
        $heroSlides = HeroSlider::where('is_active', true)->orderBy('order')->get();

        // 3. Events Section (assuming events are posts from a specific category)
        $events = Post::whereHas('category', fn($q) => $q->where('slug', 'events'))
            ->where('is_published', true)
            ->latest()
            ->take(6)
            ->get();

        // 4. Statistics
        $statistics = Statistic::orderBy('order')->take(8)->get();

        // 5. News Section
        $featuredNews = Post::where('is_featured', true)
            ->where('is_published', true)
            ->whereHas('category', fn($q) => $q->where('slug', '!=', 'events')) //  <--  التعديل هنا
            ->latest('published_at')
            ->first();

        // Get the latest news, but EXCLUDE events
        $latestNews = Post::where('is_featured', false)
            ->where('is_published', true)
            ->whereHas('category', fn($q) => $q->where('slug', '!=', 'events')) //  <--  التعديل هنا
            ->latest('published_at')
            ->take(2)
            ->get();
        // 5.1. News Categories
        $newsCategories = Category::where('slug', '!=', 'events')
            ->whereHas('posts', fn($q) => $q->where('is_published', true))
            ->withCount(['posts' => fn($q) => $q->where('is_published', true)])
            ->get();

        // 6. Services
        $services = Service::whereNull('parent_id')
            ->take(3)
            ->get();

        // 7. Investments
        $investments = Investment::orderBy('order')->take(2)->get();

        // 8. Landmarks (Tourism)
        $landmarks = Landmark::orderBy('order')->get();

        // 9. Projects
        $projects = Project::where('category', '!=', 'استثماري')->orderBy('is_highlighted', 'desc')->take(3)->get(); // Show highlighted first

        // 10. Achievements (assuming they are posts from another category)
        // You might need a separate model if they are more complex
        $achievements = Post::whereHas('category', fn($q) => $q->where('slug', 'achievements'))
            ->latest()->take(3)->get();
        $mainAchievementVideo = Setting::where('key', 'achievements_video_url')->first(); // Assuming you add this setting

        // 11. Partners (Apps)
        $partners = Partner::orderBy('created_at', 'asc')->get();

        // 12. City Guide
        $guideCategories = CityGuideCategory::withCount('locations')->get();
        $allLocations = Location::with('cityGuideCategory')->get();

        $nationalProjects = Project::whereIn('category', ['قومي', 'حياة كريمة'])->take(3)->get();
        $investmentProjects = Project::where('category', 'استثماري')->take(3)->get();

        // Fetch Investment Plans
        $investmentPlans = InvestmentPlan::orderBy('year_range', 'desc')->take(6)->get();

        return view('home.index', compact(
            'settings',
            'heroSlides',
            'events',
            'statistics',
            'featuredNews',
            'latestNews',
            'newsCategories',
            'services',
            'investments',
            'landmarks',
            'projects',
            'achievements',
            'mainAchievementVideo',
            'partners',
            'guideCategories',
            'allLocations',
            'nationalProjects',
            'investmentProjects',
            'investmentPlans'
        ));
    }
    public function wait()
    {
        return view('home.wait');
    }
}
