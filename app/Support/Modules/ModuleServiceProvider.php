<?php

namespace App\Support\Modules;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Base provider for the modular-monolith bounded contexts in modules/<Name>.
 * See docs/architecture.md.
 *
 * Each module declares the Eloquent models it took over from App\Models. For those
 * models this provider keeps everything that is keyed by the *old* class name working:
 *
 *  - stored morph types: 'App\Models\X' stays the morph class (Relation::morphMap
 *    legacy entry) unless the model already has a short alias (e.g. payables such as
 *    'service_submission'), so activity_log / images / *_type rows never change;
 *  - policies: App\Policies\XPolicy is registered explicitly for the new class
 *    (Laravel's policy auto-discovery guesses from the class namespace);
 *  - the old class name itself resolves through a class_alias stub in app/Models.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /** Module name, e.g. "Content" (directory modules/Content). */
    abstract protected function moduleName(): string;

    /** @return list<class-string<\Illuminate\Database\Eloquent\Model>> models moved from App\Models */
    protected function movedModels(): array
    {
        return [];
    }

    public function boot(): void
    {
        $dir = base_path('modules/'.$this->moduleName());

        if (is_dir($dir.'/database/migrations')) {
            $this->loadMigrationsFrom($dir.'/database/migrations');
        }
        if (is_dir($dir.'/resources/views')) {
            $this->loadViewsFrom($dir.'/resources/views', strtolower($this->moduleName()));
        }

        $this->registerLegacyModels();
        $this->bootModule();

        if (! $this->app->routesAreCached()) {
            if (is_file($dir.'/routes/web.php')) {
                Route::middleware('web')->group($dir.'/routes/web.php');
            }
            if (is_file($dir.'/routes/api.php')) {
                Route::prefix('api')->middleware('api')->group($dir.'/routes/api.php');
            }
        }
    }

    /** Hook for module specific boot logic (gates, observers, event listeners...). */
    protected function bootModule(): void {}

    protected function registerLegacyModels(): void
    {
        $alreadyMapped = array_values(Relation::morphMap());
        $legacy = [];

        foreach ($this->movedModels() as $model) {
            $short = class_basename($model);

            if (! in_array($model, $alreadyMapped, true)) {
                $legacy['App\\Models\\'.$short] = $model;
            }

            $policy = 'App\\Policies\\'.$short.'Policy';
            if (class_exists($policy) && Gate::getPolicyFor($model) === null) {
                Gate::policy($model, $policy);
            }
        }

        if ($legacy !== []) {
            Relation::morphMap($legacy);
        }
    }
}
