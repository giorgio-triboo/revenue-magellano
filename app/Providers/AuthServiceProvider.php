<?php
namespace App\Providers;

use App\Models\Publisher;
use App\Models\SubPublisher;
use App\Models\FileUpload;
use App\Models\Statement;
use App\Models\User;
use App\Policies\PublisherPolicy;
use App\Policies\SubPublisherPolicy;
use App\Policies\UploadPolicy;
use App\Policies\StatementPolicy;
use App\Policies\UserManagementPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Publisher::class => PublisherPolicy::class,
        User::class => UserManagementPolicy::class,
        SubPublisher::class => SubPublisherPolicy::class,
        Statement::class => StatementPolicy::class,
        FileUpload::class => UploadPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Gate per l'accesso alla piattaforma
        Gate::define('access-platform', function (User $user) {
            Log::debug('Checking platform access', [
                'user_id' => $user->id,
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at,
                'terms_accepted' => $user->terms_accepted,
                'terms_verified_at' => $user->terms_verified_at
            ]);

            return $user->is_active && 
                   $user->email_verified_at !== null;
                //    $user->hasAcceptedTerms();
        });

        // Gate per gli upload
        // Ottimizzato: il ruolo viene già caricato nel controller per evitare query aggiuntive
        Gate::define('view-uploads', function (User $user) {
            // Se il ruolo non è già caricato, lo carichiamo una volta sola
            if (!$user->relationLoaded('role')) {
                $user->load('role');
            }
            return in_array($user->role?->code, ['admin', 'operator']);
        });

        Gate::define('upload-files', function (User $user) {
            return in_array($user->role?->code, ['admin', 'operator']);
        });

        Gate::define('download-files', function (User $user) {
            return in_array($user->role?->code, ['admin', 'operator']);
        });

        Gate::define('publish-files', function (User $user) {
            return in_array($user->role?->code, ['admin', 'operator']);
        });

        Gate::define('send-notifications', function (User $user) {
            return in_array($user->role?->code, ['admin', 'operator']);
        });
        
        // Gate per i consuntivi
        Gate::define('view-statements', function (User $user) {
            Log::debug('Gate view-statements check', [
                'user_id' => $user->id,
                'role' => $user->role?->code,
                'publisher_id' => $user->publisher_id
            ]);

            return in_array($user->role?->code, ['admin', 'operator']) ||
                ($user->role?->code === 'publisher' && $user->publisher_id !== null);
        });

        // Gate per il download dei consuntivi
        Gate::define('download-statement', function (User $user, Statement $statement) {
            Log::debug('Gate download-statement check', [
                'user_id' => $user->id,
                'role' => $user->role?->code,
                'statement_id' => $statement->id,
                'statement_publisher_id' => $statement->publisher_id,
                'user_publisher_id' => $user->publisher_id,
                'statement_status' => $statement->is_published
            ]);

            if (!$statement->is_published) {
                return false;
            }

            if (in_array($user->role?->code, ['admin'])) {
                return true;
            }

            return $user->role?->code === 'publisher' &&
                   $statement->publisher_id === $user->publisher_id;
        });

        // Gate per la pubblicazione dei file
        Gate::define('publish-file', function (User $user, FileUpload $fileUpload) {
            Log::debug('Gate publish-file check', [
                'user_id' => $user->id,
                'role' => $user->role?->code,
                'file_upload_id' => $fileUpload->id,
                'file_status' => $fileUpload->status
            ]);

            if (!in_array($user->role?->code, ['admin', 'operator'])) {
                return false;
            }

            return $fileUpload->status === FileUpload::STATUS_COMPLETED;
        });

        // Gate per la gestione utenti
        Gate::define('manage-users', function (User $user) {
            Log::debug('Gate manage-users check', [
                'user_id' => $user->id,
                'role' => $user->role?->code
            ]);

            return $user->role?->code === 'admin';
        });

        // Gate per la gestione dei publisher
        Gate::define('manage-publishers', function (User $user) {
            Log::debug('Gate manage-publishers check', [
                'user_id' => $user->id,
                'role' => $user->role?->code
            ]);

            return in_array($user->role?->code, ['admin', 'operator']);
        });

        // Gate per l'esportazione dati
        Gate::define('export-data', function (User $user) {
            Log::debug('Gate export-data check', [
                'user_id' => $user->id,
                'role' => $user->role?->code
            ]);

            return in_array($user->role?->code, ['admin', 'operator']);
        });

        Gate::define('view-any-upload', function (User $user) {
            return true;
        });

        Gate::define('upload-to-sftp', function (User $user) {
            return $user->role->code === 'admin' || $user->role->code === 'editor';
        });
    }
}