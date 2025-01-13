<?php

namespace App\Observers;

use App\Models\FileUpload;
use App\Models\User;
use App\Mail\UploadCompletedNotification;
use App\Mail\SftpUploadNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FileUploadObserver
{
    public function updated(FileUpload $upload)
    {
        Log::info('FileUploadObserver: aggiornamento rilevato', ['upload_id' => $upload->id]);

        // Gestione notifiche SFTP
        if ($upload->isDirty('sftp_status') && in_array($upload->sftp_status, ['completed', 'error'])) {
            Log::info('FileUploadObserver: Stato FTP finale aggiornato', [
                'upload_id' => $upload->id,
                'sftp_status' => $upload->sftp_status
            ]);

            try {
                if (method_exists(User::class, 'getActiveAdmins')) {
                    $admins = User::getActiveAdmins();
                    Log::info('FileUploadObserver: Trovati admin attivi', ['admin_count' => $admins->count()]);

                    foreach ($admins as $admin) {
                        Mail::to($admin->email)->send(new SftpUploadNotification($upload));
                        Log::info('FileUploadObserver: Notifica SFTP inviata', [
                            'admin_email' => $admin->email,
                            'sftp_status' => $upload->sftp_status
                        ]);
                    }
                } else {
                    Log::error('FileUploadObserver: Metodo getActiveAdmins non trovato nella classe User');
                }
            } catch (\Exception $e) {
                Log::error('Errore invio notifica SFTP', [
                    'error' => $e->getMessage(),
                    'upload_id' => $upload->id
                ]);
            }
        }

        // Notifica completamento upload (mantenuta come era)
        if ($upload->isDirty('status') && $upload->status === FileUpload::STATUS_COMPLETED) {
            Log::info('FileUploadObserver: Stato file impostato a completato', ['upload_id' => $upload->id]);

            try {
                if (method_exists(User::class, 'getActiveAdmins')) {
                    $admins = User::getActiveAdmins();
                    Log::info('FileUploadObserver: Trovati admin attivi', ['admin_count' => $admins->count()]);

                    foreach ($admins as $admin) {
                        Log::info('FileUploadObserver: Invio email a admin', ['email' => $admin->email]);
                        Mail::to($admin->email)->send(new UploadCompletedNotification($upload));
                    }
                } else {
                    Log::error('FileUploadObserver: Metodo getActiveAdmins non trovato nella classe User');
                }
            } catch (\Exception $e) {
                Log::error('Errore invio email notifica upload completato', [
                    'error' => $e->getMessage(),
                    'upload_id' => $upload->id
                ]);
            }
        }
    }
}