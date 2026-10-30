<?php

namespace App\Providers;

use Modules\Investment\Models\Advertisement;
use Modules\Citizen\Models\Complaint;
use Modules\Citizen\Models\EmergencyReport;
use Modules\Gis\Models\GisSubmission;
use Modules\RemovalGovernance\Models\RemovalOrder;
use Modules\Estidama\Models\TrainingApplication;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Authorization gates and rate limiters added by the security hardening pass.
 */
class SecurityServiceProvider extends ServiceProvider
{
    /** Roles that always have full staff access. */
    public const ADMIN_ROLES = ['super_admin', 'Super Admin', 'Admin'];

    public function boot(): void
    {
        $this->defineGates();
        $this->defineRateLimiters();
        $this->hardenFileUploads();
    }

    /**
     * Every Filament FileUpload (all panels, incl. modules) also runs SafeUpload:
     * no server-side code, no HTML/JS, no SVG with script — whatever the
     * resource's own acceptedFileTypes() allow (->image() accepts image/svg+xml).
     */
    protected function hardenFileUploads(): void
    {
        if (! class_exists(\Filament\Forms\Components\FileUpload::class)) {
            return;
        }

        \Filament\Forms\Components\FileUpload::configureUsing(function (\Filament\Forms\Components\FileUpload $upload): void {
            $upload->rule(fn () => new \App\Rules\SafeUpload());
        });
    }

    protected function defineGates(): void
    {
        // Staff who can work in the GIS panel (same rule as User::canAccessPanel).
        Gate::define('gis-staff', function (User $user): bool {
            if ($user->hasAnyRole(self::ADMIN_ROLES)) {
                return true;
            }

            try {
                $panel = Filament::getPanel('gis');

                return $panel && $user->canAccessPanel($panel);
            } catch (\Throwable) {
                return false;
            }
        });

        // GIS submission documents (print / invoice): owner or GIS staff.
        Gate::define('view-gis-submission', function (User $user, GisSubmission $submission): bool {
            return (string) $submission->user_id === (string) $user->getKey()
                || Gate::forUser($user)->allows('gis-staff')
                || $user->can('view_pending::gis::submissions');
        });

        // Removal orders are internal staff records.
        Gate::define('view-removal-order', function (User $user, RemovalOrder $order): bool {
            return Gate::forUser($user)->allows('gis-staff')
                || $user->can('view_removal::order')
                || (string) $order->created_by === (string) $user->getKey();
        });

        // Advertising-guide points: creator or staff with the Shield permission.
        Gate::define('manage-advertisement', function (User $user, Advertisement $ad): bool {
            return ($ad->user_id !== null && (string) $ad->user_id === (string) $user->getKey())
                || $user->hasAnyRole(self::ADMIN_ROLES)
                || $user->can('update_advertisement');
        });

        // Monitoring dashboard (DashboardMonitorController) audience.
        Gate::define('view-dashboard-monitor', function (User $user): bool {
            return $user->hasAnyRole(['super_admin', 'Admin', 'مستعرض بيانات']);
        });

        // Private uploads served through SecureFileController.
        Gate::define('view-secure-file', function (User $user, $model): bool {
            if ($user->hasAnyRole(self::ADMIN_ROLES)) {
                return true;
            }

            // national_id on these records is typed into public forms, so only
            // staff-approved (identity verified) accounts may match on it.
            $sameNationalId = fn (?string $nid) => filled($nid)
                && $user->status === 'approved'
                && filled($user->national_id)
                && hash_equals((string) $user->national_id, (string) $nid);

            return match (true) {
                $model instanceof Complaint => $user->can('view_complaint') || $user->can('view_any_complaint')
                    || $sameNationalId($model->national_id),
                $model instanceof EmergencyReport => $user->can('view_emergency::report') || $user->can('view_any_emergency::report')
                    || $sameNationalId($model->reporter_national_id),
                $model instanceof TrainingApplication => $user->can('view_training::application') || $user->can('view_any_training::application')
                    || $user->hasAnyRole(['estidama_admin', 'estidama_trainer'])
                    || $sameNationalId($model->national_id),
                $model instanceof User => (string) $model->getKey() === (string) $user->getKey()
                    || $user->can('view_user') || $user->can('update_user'),
                default => false,
            };
        });
    }

    protected function defineRateLimiters(): void
    {
        // Password login: per national id + IP, and per IP overall.
        RateLimiter::for('login', function (Request $request) {
            $key = mb_strtolower((string) $request->input('national_id')) . '|' . $request->ip();

            return [
                Limit::perMinute(5)->by($key),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });

        // 2FA code entry: per pending/authenticated user + IP.
        RateLimiter::for('two-factor', function (Request $request) {
            $id = $request->session()->get(\App\Services\Security\TwoFactorAuthenticator::SESSION_PENDING_USER)
                ?? optional($request->user())->getKey()
                ?? 'guest';

            return Limit::perMinute(5)->by($id . '|' . $request->ip());
        });

        // Public form submissions (bookings, comments, messages...).
        RateLimiter::for('public-forms', function (Request $request) {
            return Limit::perMinute(6)->by(optional($request->user())->getKey() ?: $request->ip());
        });
    }
}
