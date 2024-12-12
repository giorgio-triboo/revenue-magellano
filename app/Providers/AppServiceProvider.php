<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Events\QueryExecuted;
use App\Models\Publisher;
use App\Observers\PublisherObserver;
use App\Models\FileUpload;
use App\Observers\FileUploadObserver;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->environment('local')) {
            $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
        }
    }

    public function boot(): void
    {
        Publisher::observe(PublisherObserver::class);
        User::observe(UserObserver::class);

        if (config('app.debug')) {
            \DB::listen(function (QueryExecuted $query) {
                Log::info('Query SQL', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms'
                ]);
            });
        }

        Log::debug('AppServiceProvider: Registrazione observer FileUploadObserver.');
        FileUpload::observe(FileUploadObserver::class);
        Blade::withoutDoubleEncoding();

    }
}