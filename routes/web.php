<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\SubPublisherController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\StatementController;
use App\Http\Controllers\Auth\TermsController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\SettingsController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/database', function() {
    return File::get(public_path('database/index.php'));
})->middleware('check.ip');


/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['guest'])->group(function () {
    // Login Routes
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    // Registration Routes
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/api/check-vat', [RegisterController::class, 'checkVat'])->name('api.check-vat');

    // Password Reset Routes
    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])
        ->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])
        ->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])
        ->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
        ->name('password.update');

    // Email Verification Route
    Route::get('/verify-email/{token}', [EmailVerificationController::class, 'verify'])
        ->name('verification.verify');
});

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Logout e Terms routes rimangono come sono
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/terms', [TermsController::class, 'show'])->name('terms.show');
    Route::post('/terms/accept', [TermsController::class, 'accept'])->name('terms.accept');

    // Cambia questa parte
    Route::middleware(['can:access-platform'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show'])->name('profile.show');
            Route::put('/update', [ProfileController::class, 'updateProfile'])->name('profile.update');
            Route::post('/request-password-reset', [ProfileController::class, 'requestPasswordReset'])
                ->name('profile.password.reset.request');
            Route::put('/toggle-notifications', [ProfileController::class, 'toggleNotifications'])
                ->name('profile.notifications.toggle');
            Route::post('/deactivate', [ProfileController::class, 'deactivateAccount'])
                ->name('profile.deactivate');
        });

        // Support
        Route::prefix('support')->group(function () {
            Route::get('/', [SupportController::class, 'show'])->name('support.show');
            Route::post('/send', [SupportController::class, 'send'])->name('support.send');
        });


        // Publishers Management
        Route::prefix('publishers')->group(function () {
            Route::get('/', [PublisherController::class, 'index'])->name('publishers.index');
            Route::get('/export', [PublisherController::class, 'export'])
                ->name('publishers.export')
                ->middleware('can:export,App\Models\Publisher');

            // SubPublisher (Database) Routes
            Route::prefix('{publisher}/databases')->group(function () {
                Route::get('/', [SubPublisherController::class, 'index'])
                    ->name('publishers.databases.index');
                Route::post('/', [SubPublisherController::class, 'store'])
                    ->name('publishers.databases.store')
                    ->middleware('can:create,App\Models\SubPublisher');
                Route::put('/{subPublisher}', [SubPublisherController::class, 'update'])
                    ->name('publishers.databases.update')
                    ->middleware('can:update,subPublisher');
                Route::delete('/{subPublisher}', [SubPublisherController::class, 'destroy'])
                    ->name('publishers.databases.destroy')
                    ->middleware('can:delete,subPublisher');
            });

            Route::get('/{publisher}', [PublisherController::class, 'show'])->name('publishers.show');
            Route::get('/{publisher}/edit', [PublisherController::class, 'edit'])
                ->name('publishers.edit')
                ->middleware('can:update,publisher');
            Route::put('/{publisher}', [PublisherController::class, 'update'])
                ->name('publishers.update')
                ->middleware('can:update,publisher');
        });

        // User Management Routes
        Route::prefix('users')->group(function () {
            Route::get('/', [UserManagementController::class, 'index'])->name('users.index');
            Route::get('/{user}', [UserManagementController::class, 'show'])->name('users.show');
            Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
            Route::put('/{user}', [UserManagementController::class, 'update'])->name('users.update');
            Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
            Route::put('/{user}/role', [UserManagementController::class, 'updateRole'])->name('users.updateRole');
            Route::get('/users/export', [UserManagementController::class, 'export'])->name('users.export');
            Route::post('/{user}/restore', [UserManagementController::class, 'restore'])->name('users.restore');
            Route::get('/{user}/details', [UserManagementController::class, 'getDetails'])->name('users.details');
            Route::post('/{user}/send-password-reset', [UserManagementController::class, 'sendPasswordReset'])
                ->name('users.send-password-reset');
            Route::post('/{user}/validate', [UserManagementController::class, 'updateValidationStatus'])
                ->name('users.update-validation')
                ->middleware('can:update,user');
            Route::post('/{user}/activate', [UserManagementController::class, 'updateActiveStatus'])
                ->name('users.update-active')
                ->middleware('can:update,user');
        });

        Route::prefix('statements')->group(function () {
            Route::get('/', [StatementController::class, 'index'])->name('statements.index');
            Route::get('/details', [StatementController::class, 'details'])->name('statements.details');
            Route::get('/export', [StatementController::class, 'export'])->name('statements.export');
            Route::get('/{statement}', [StatementController::class, 'show'])->name('statements.show');
        });


        // Upload Routes
        Route::prefix('uploads')->group(function () {
            Route::get('/', [UploadController::class, 'index'])->name('uploads.index');
            Route::post('/', [UploadController::class, 'store'])->name('uploads.store');
            Route::get('/list', [UploadController::class, 'list'])->name('uploads.list');
            Route::post('/{id}/unpublish', [UploadController::class, 'unpublish'])
                ->name('uploads.unpublish')
                ->where('id', '[0-9]+');
            Route::post('/{id}/publish', [UploadController::class, 'publish'])
                ->name('uploads.publish')
                ->where('id', '[0-9]+');
            Route::get('/{upload}/export', [UploadController::class, 'export'])
                ->name('uploads.export')
                ->where('id', '[0-9]+');
            Route::delete('/{id}', [UploadController::class, 'destroy'])
                ->name('uploads.destroy')
                ->where('id', '[0-9]+');
            Route::get('/uploads/template', [UploadController::class, 'downloadTemplate'])
                ->name('uploads.template')
                ->middleware(['auth', 'can:create,App\Models\FileUpload']);
            Route::post('/{upload}/send-email', [UploadController::class, 'sendEmail'])
                ->name('uploads.send-email')
                ->whereNumber('upload');
            Route::post('/{upload}/send-test-email', [UploadController::class, 'sendTestEmail'])
                ->name('uploads.send-test-email')
                ->whereNumber('upload');
            Route::post('/{upload}/upload-sftp', [App\Http\Controllers\UploadController::class, 'uploadToSftp'])
                ->name('uploads.upload-sftp');
        });

    });
});

/*
|--------------------------------------------------------------------------
| API Web Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('/publishers/search', [PublisherController::class, 'search'])
        ->name('api.publishers.search');
    Route::get('/publishers/{publisher}', [PublisherController::class, 'getDetails'])
        ->name('api.publishers.details');
});