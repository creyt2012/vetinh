<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use App\Models\Module;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // We delay discovery to boot time where DB is ready, 
        // unless we need to register specific classes early.
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole() && !str_contains(request()->fullUrl() ?? '', 'artisan')) {
            // Avoid issues during some console commands if needed
        }

        try {
            if (!Schema::hasTable('modules')) {
                return;
            }
        } catch (\Exception $e) {
            return;
        }

        $modulesPath = base_path('Modules');
        if (!File::isDirectory($modulesPath)) {
            return;
        }

        $modules = File::directories($modulesPath);
        $activeModules = Module::where('is_active', true)->pluck('name')->toArray();

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);

            if (in_array($moduleName, $activeModules)) {
                $this->bootModule($moduleName, $modulePath);
            }
        }
    }

    protected function bootModule(string $name, string $path): void
    {
        // Register the module's own Service Provider if it exists
        $provider = "Modules\\{$name}\\Providers\\{$name}ServiceProvider";
        if (class_exists($provider)) {
            $this->app->register($provider);
        }

        // Auto-load routes
        if (File::exists("{$path}/routes/web.php")) {
            $this->loadRoutesFrom("{$path}/routes/web.php");
        }

        if (File::exists("{$path}/routes/api.php")) {
            $this->loadRoutesFrom("{$path}/routes/api.php");
        }

        // Auto-load views
        if (File::isDirectory("{$path}/resources/views")) {
            $this->loadViewsFrom("{$path}/resources/views", strtolower($name));
        }

        // Auto-load migrations
        if (File::isDirectory("{$path}/database/migrations")) {
            $this->loadMigrationsFrom("{$path}/database/migrations");
        }

        // Auto-load translations
        if (File::isDirectory("{$path}/resources/lang")) {
            $this->loadTranslationsFrom("{$path}/resources/lang", strtolower($name));
        }
    }
}
