<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use App\Models\Module;
use Illuminate\Support\Facades\Schema;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        if (!$this->app->runningInConsole() && !Schema::hasTable('modules')) {
            return;
        }

        $modulesPath = base_path('Modules');

        if (!File::isDirectory($modulesPath)) {
            return;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);

            // In a real scenario, we check the DB. 
            // For now, let's load if it's in the DB and active.
            try {
                $module = Module::where('name', $moduleName)->first();
                if ($module && $module->is_active) {
                    $this->registerModule($moduleName, $modulePath);
                }
            } catch (\Exception $e) {
                // DB might not be ready yet
                continue;
            }
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    protected function registerModule(string $name, string $path): void
    {
        $provider = "Modules\\{$name}\\Providers\\{$name}ServiceProvider";

        if (class_exists($provider)) {
            $this->app->register($provider);
        }

        // Auto-load routes if they exist
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
    }
}
