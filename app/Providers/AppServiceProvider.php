<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One shared AI profiler per request / console command, so OpenAiService,
        // AiQueryExecutorService and the chat orchestrator all record into the
        // same collector. No-op overhead when ai.profiling.enabled is false.
        $this->app->scoped(\App\Services\AiProfiler::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Paginator::useBootstrapFive();

        if (! app()->runningInConsole()) {
            URL::forceRootUrl(request()->getSchemeAndHttpHost());
        }
    }
}
