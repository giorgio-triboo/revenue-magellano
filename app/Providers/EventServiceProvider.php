<?php

namespace App\Providers;

use App\Events\FileUploadProcessed;
use App\Listeners\DispatchAxExport;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Log;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        FileUploadProcessed::class => [
            DispatchAxExport::class,
        ],
    ];
    
    

    public function boot()
    {
        Log::debug('EventServiceProvider: Registrazione eventi completata.');
        parent::boot();
    }
}
