<?php

use App\Http\Controllers\Api\PublisherController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/publishers/{publisher}', [PublisherController::class, 'show'])
         ->name('publishers.show')
         ->middleware('can:view,publisher');
});