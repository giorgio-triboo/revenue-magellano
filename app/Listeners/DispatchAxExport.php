<?php

namespace App\Listeners;

use App\Events\FileUploadProcessed;
use App\Jobs\GenerateAxExport;
use Illuminate\Support\Facades\Log;

class DispatchAxExport
{
    public function handle(FileUploadProcessed $event)
{
    Log::channel('ax_export')->info('DispatchAxExport: Evento FileUploadProcessed ricevuto', [
        'upload_id' => $event->upload->id,
        'status' => $event->upload->status
    ]);

    try {
        // Aggiorna lo stato solo se non è già in processing
        if ($event->upload->status === 'completed' && $event->upload->ax_export_status !== 'processing') {
            $event->upload->ax_export_status = 'processing';
            $event->upload->save();

            GenerateAxExport::dispatch($event->upload)
                ->onConnection('redis')
                ->onQueue('ax-export');

            Log::channel('ax_export')->info('DispatchAxExport: Job GenerateAxExport dispatchato', [
                'upload_id' => $event->upload->id,
                'queue' => 'ax-export'
            ]);
        }
    } catch (\Exception $e) {
        Log::channel('ax_export')->error('DispatchAxExport: Errore durante il dispatch', [
            'upload_id' => $event->upload->id,
            'error' => $e->getMessage()
        ]);
        throw $e;
    }
}
}