<?php

namespace Modules\SiteValidity;

use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Modules\SiteValidity\Events\RequestTransitioned;
use Modules\SiteValidity\Filament\SiteValidityPanelProvider;
use Modules\SiteValidity\Listeners\SendSiteValidityNotifications;
use Modules\SiteValidity\Models\AttachmentType;
use Modules\SiteValidity\Models\InspectionField;
use Modules\SiteValidity\Models\RequestFile;
use Modules\SiteValidity\Models\ServiceType;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Policies\AdminOnlyPolicy;
use Modules\SiteValidity\Policies\RequestFilePolicy;
use Modules\SiteValidity\Policies\SiteValidityRequestPolicy;

/**
 * خدمة «طلب صلاحية الموقع» (Site validity request).
 *
 *   /site-validity-request        صفحات المواطن (الهبوط، التقديم، طلباتي، التفاصيل، التنزيل المصرح)
 *   /site-validity                لوحة Filament (المركز الجيومكاني، مهندس التنظيم، مسؤول الخدمة)
 *   payments.pay type=site_validity_request   الدفع الموحد عبر e-Finance
 *
 * Views: sitevalidity::*   Migrations: database/migrations (جداول sv_*)
 */
class SiteValidityServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'SiteValidity';
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/site-validity.php', 'site-validity');
        $this->app->register(SiteValidityPanelProvider::class);
    }

    protected function bootModule(): void
    {
        // Payable alias (route('payments.pay', ['type' => 'site_validity_request', 'id' => ...])).
        Relation::morphMap([
            (string) config('site-validity.payable_alias', 'site_validity_request') => SiteValidityRequest::class,
            (string) config('site-validity.completion_payable_alias', 'site_validity_completion') => \Modules\SiteValidity\Models\Completion::class,
        ]);

        Gate::policy(SiteValidityRequest::class, SiteValidityRequestPolicy::class);
        Gate::policy(RequestFile::class, RequestFilePolicy::class);
        foreach ([ServiceType::class, InspectionField::class, AttachmentType::class] as $model) {
            Gate::policy($model, AdminOnlyPolicy::class);
        }

        Event::listen(RequestTransitioned::class, [SendSiteValidityNotifications::class, 'handle']);

        View::composer('sitevalidity::web.*', function ($view) {
            $css = public_path('css/site-validity.css');
            $view->with('svCssUrl', asset('css/site-validity.css') . '?v=' . (is_file($css) ? filemtime($css) : '1'));
        });
    }
}
