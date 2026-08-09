<?php

namespace App\Providers;

use App\Models\Device;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        Paginator::useBootstrapFive();

        View::composer('*', function ($view): void {
            $navDevice = Device::query()->latest('last_seen_at')->first();

            $view->with([
                'navDevice' => $navDevice,
                'navDeviceOnline' => $navDevice?->isOnline() ?? false,
                'projectMetadata' => config('project'),
            ]);
        });
    }
}
