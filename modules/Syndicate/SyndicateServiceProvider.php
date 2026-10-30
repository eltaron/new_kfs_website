<?php

namespace Modules\Syndicate;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Syndicate\Channels\LogSmsGateway;
use Modules\Syndicate\Console\CreateSyndicateTokenCommand;
use Modules\Syndicate\Contracts\SmsGateway;
use Modules\Syndicate\Events\SupervisionCancelled;
use Modules\Syndicate\Events\SupervisionCertificateIssued;
use Modules\Syndicate\Events\SyndicateRequestStatusChanged;
use Modules\Syndicate\Events\SyndicateRequestSubmitted;
use Modules\Syndicate\Http\ApiResponse;
use Modules\Syndicate\Listeners\SendOutboundWebhook;
use Modules\Syndicate\Listeners\SendSyndicateNotifications;
use Modules\Syndicate\Authorization\SyndicateStaffPolicy;
use Modules\Syndicate\Models\Certificate;
use Modules\Syndicate\Models\Engineer;
use Modules\Syndicate\Models\IntegrationAccount;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Models\SyndicateRequest;

/**
 * نقابة المهندسين — شهادات الإشراف على المواقع وإلغاء الإشراف.
 *
 *   /syndicate            لوحة Filament لموظفي النقابة
 *   /council              لوحة Filament لمجلس المدينة / الوحدات المحلية
 *   /syndicate/verify     التحقق العام من الشهادات (QR)
 *   /syndicate/my-projects لوحة المواطن / المهندس
 *   /api/syndicate/v1     واجهة برمجة النقابة (Sanctum + IP allow-list + throttle + audit)
 */
class SyndicateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/syndicate.php', 'syndicate');

        // لوحتا Filament: /syndicate (موظفو النقابة) و /council (مجلس المدينة).
        $this->app->register(Filament\Panels\SyndicatePanelProvider::class);
        $this->app->register(Filament\Panels\CouncilPanelProvider::class);

        // مزود SMS الافتراضي (سجل فقط). استبدله بربط تنفيذ فعلي لـ SmsGateway.
        $this->app->bindIf(SmsGateway::class, LogSmsGateway::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'syndicate');

        // اسم ثابت لحسابات التكامل في personal_access_tokens.tokenable_type
        Relation::morphMap(['syndicate_integration' => IntegrationAccount::class]);

        // سياسات مبنية على الأدوار (تتقدم على سياسات Shield المولدة تلقائياً).
        foreach ([Project::class, SyndicateRequest::class, Certificate::class, Engineer::class] as $model) {
            Gate::policy($model, SyndicateStaffPolicy::class);
        }

        $this->registerRateLimiter();
        $this->registerEvents();

        if ($this->app->runningInConsole()) {
            $this->commands([CreateSyndicateTokenCommand::class]);
        }

        if (file_exists(__DIR__.'/routes/web.php')) {
            Route::middleware('web')->group(__DIR__.'/routes/web.php');
        }
        if (file_exists(__DIR__.'/routes/api.php')) {
            Route::prefix('api')->middleware('api')->group(__DIR__.'/routes/api.php');
        }
    }

    protected function registerRateLimiter(): void
    {
        RateLimiter::for('syndicate-api', function (Request $request) {
            $account = $request->attributes->get('syndicate_account');
            $key = $account instanceof IntegrationAccount ? 'acct:'.$account->getKey() : 'ip:'.$request->ip();

            return Limit::perMinute(max(1, (int) config('syndicate.api.rate_limit_per_minute', 60)))
                ->by('syndicate-api|'.$key)
                ->response(fn (Request $request, array $headers) => ApiResponse::error(
                    429, 'too_many_requests', 'تم تجاوز الحد المسموح', 'تم تجاوز عدد الطلبات المسموح به في الدقيقة.'
                )->withHeaders($headers));
        });
    }

    protected function registerEvents(): void
    {
        Event::listen(SyndicateRequestSubmitted::class, [SendSyndicateNotifications::class, 'onRequestSubmitted']);
        Event::listen(SyndicateRequestStatusChanged::class, [SendSyndicateNotifications::class, 'onRequestStatusChanged']);
        Event::listen(SupervisionCertificateIssued::class, [SendSyndicateNotifications::class, 'onSupervisionIssued']);
        Event::listen(SupervisionCancelled::class, [SendSyndicateNotifications::class, 'onSupervisionCancelled']);

        foreach ([SyndicateRequestSubmitted::class, SyndicateRequestStatusChanged::class,
            SupervisionCertificateIssued::class, SupervisionCancelled::class] as $event) {
            Event::listen($event, [SendOutboundWebhook::class, 'handle']);
        }
    }
}
