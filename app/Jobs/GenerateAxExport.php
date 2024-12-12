<?php

namespace App\Jobs;

use App\Models\FileUpload;
use App\Services\AxExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAxExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 3600;
    public $backoff = [60, 180, 300];
    protected $upload;

    public function __construct(FileUpload $upload)
    {
        $this->upload = $upload;
    }

    public function handle(AxExportService $axExportService)
    {
        Log::channel('ax_export')->info('GenerateAxExport: Inizio generazione export AX.', [
            'upload_id' => $this->upload->id,
        ]);

        try {
            // Aggiorna lo stato a processing
            $this->upload->ax_export_status = 'processing';
            $this->upload->save();

            // Genera il file TSV
            $fileName = $axExportService->generateTsvExport($this->upload);

            // Aggiorna lo stato e il percorso nel database con un singolo save
            $this->upload->update([
                'ax_export_status' => 'completed',
                'ax_export_path' => 'private/' . $fileName
            ]);

            Log::channel('ax_export')->info('GenerateAxExport: Export AX completato con successo.', [
                'upload_id' => $this->upload->id,
                'file_name' => $fileName,
            ]);
        } catch (\Exception $e) {
            Log::channel('ax_export')->error('GenerateAxExport: Errore durante la generazione.', [
                'upload_id' => $this->upload->id,
                'error' => $e->getMessage(),
            ]);

            $this->upload->update(['ax_export_status' => 'error']);
        }
    }


    public function failed(\Throwable $exception)
    {
        Log::channel('ax_export')->error('GenerateAxExport: Job fallito.', [
            'upload_id' => $this->upload->id,
            'error' => $exception->getMessage(),
        ]);

        $this->upload->ax_export_status = 'error';
        $this->upload->save();
    }
}