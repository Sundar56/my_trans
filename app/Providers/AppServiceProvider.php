<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();
        $this->loadModuleApi();
    }
    protected function loadModuleApi(): void
    {
        $frontendModules  = File::directories(app_path('Api/Frontend/Modules'));
        $adminModules     = File::directories(app_path('Api/Admin/Modules'));
             
        $modules = array_merge($frontendModules,$adminModules);
        foreach ($modules as $modulePath) {
            $module = basename($modulePath);
            $routesPath = $modulePath . '/routes.php';
            if (file_exists($routesPath)) {
                Route::prefix('api')->middleware('api')
                    ->group(function () use ($routesPath) {
                        require $routesPath;
                    });
            }
        }
    }
}
