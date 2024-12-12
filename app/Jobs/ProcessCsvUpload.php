<?php

namespace App\Jobs;

use App\Events\FileUploadProcessed;
use App\Mail\CsvProcessingCompleted;
use App\Mail\ProcessingError;
use App\Models\FileUpload;
use App\Services\CsvProcessorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ProcessCsvUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 3600;
    public $backoff = [60, 180, 300];
    protected $upload;

    public function __construct(FileUpload $upload)
    {
        $this->upload = $upload;
        Log::info('ProcessCsvUpload: Constructor called', [
            'upload_id' => $upload->id,
            'has_user_relation' => $upload->relationLoaded('user'),
            'memory_id' => spl_object_id($upload)
        ]);
    }

    public function handle()
    {
        Log::info('ProcessCsvUpload: Handle started', [
            'upload_id' => $this->upload->id,
            'initial_status' => $this->upload->status,
            'has_user_relation' => $this->upload->relationLoaded('user'),
            'memory_id' => spl_object_id($this->upload)
        ]);

        if ($this->upload->status === FileUpload::STATUS_COMPLETED) {
            Log::info('ProcessCsvUpload: Job already completed, skipping', [
                'upload_id' => $this->upload->id,
                'job_id' => $this->job->getJobId()
            ]);
            return;
        }

        try {
            // Carica esplicitamente la relazione user prima di iniziare
            $this->upload->load('user');

            Log::info('ProcessCsvUpload: Updating to processing status', [
                'upload_id' => $this->upload->id
            ]);

            $this->upload->update([
                'status' => FileUpload::STATUS_PROCESSING,
                'processing_stats' => [
                    'start_time' => now()->toDateTimeString(),
                    'success' => 0,
                    'errors' => 0,
                    'error_details' => []
                ]
            ]);

            $processor = new CsvProcessorService($this->upload);
            $result = $processor->process();

            $status = empty($result['error_details']) ?
                FileUpload::STATUS_COMPLETED :
                FileUpload::STATUS_ERROR;

            $processingStats = [
                'completed_at' => now()->toDateTimeString(),
                'success' => $result['success'] ?? 0,
                'errors' => $result['errors'] ?? 0,
                'error_details' => $this->formatErrorDetails($result['error_details'] ?? []),
                'total_records' => $result['total_records'] ?? 0,
                'processed_records' => $result['processed_records'] ?? 0,
            ];

            Log::info('ProcessCsvUpload: Updating final status', [
                'upload_id' => $this->upload->id,
                'status' => $status,
                'has_user_relation' => $this->upload->relationLoaded('user'),
                'memory_id' => spl_object_id($this->upload)
            ]);

            $this->upload->update([
                'status' => $status,
                'processed_at' => now(),
                'total_records' => $result['total_records'] ?? 0,
                'processed_records' => $result['processed_records'] ?? 0,
                'progress_percentage' => 100,
                'processing_stats' => $processingStats,
                'error_message' => $status === FileUpload::STATUS_ERROR ?
                    $this->generateErrorSummary($processingStats['error_details']) : null
            ]);

            // Ricarica il modello dopo l'update per assicurarsi di avere i dati aggiornati
            $this->upload = $this->upload->fresh(['user']);

            if ($status === FileUpload::STATUS_COMPLETED) {
                Log::info('ProcessCsvUpload: Processing completed, preparing to emit event', [
                    'upload_id' => $this->upload->id,
                    'status' => $status
                ]);


                Log::info('ProcessCsvUpload: About to emit FileUploadProcessed event', [
                    'upload_id' => $this->upload->id
                ]);

                event(new FileUploadProcessed($this->upload));

                Log::info('ProcessCsvUpload: FileUploadProcessed event emitted', [
                    'upload_id' => $this->upload->id,
                    'time' => now()->toDateTimeString()
                ]);
            }


        } catch (\Exception $e) {
            Log::error('ProcessCsvUpload: Error in job', [
                'upload_id' => $this->upload->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->handleError($e);
            throw $e;
        }
    }

    protected function handleError(\Exception $e)
    {
        $this->upload->update([
            'status' => FileUpload::STATUS_ERROR,
            'error_message' => $e->getMessage(),
            'processing_stats' => [
                'completed_at' => now()->toDateTimeString(),
                'error' => $e->getMessage()
            ]
        ]);


    }

    protected function formatErrorDetails(array $errors): array
    {
        return array_map(function ($error) {
            return [
                'line' => $error['line'] ?? 'N/A',
                'error' => $error['message'] ?? $error['error'] ?? 'Errore sconosciuto'
            ];
        }, $errors);
    }

    protected function generateErrorSummary(array $errorDetails): string
    {
        if (empty($errorDetails)) {
            return 'Si sono verificati degli errori durante l\'elaborazione.';
        }

        $errorCount = count($errorDetails);
        $firstError = $errorDetails[0]['error'] ?? 'Errore sconosciuto';

        return sprintf(
            'Elaborazione completata con %d error%s. Primo errore: %s',
            $errorCount,
            $errorCount === 1 ? 'e' : 'i',
            $firstError
        );
    }

    

    public function failed(Throwable $exception)
    {
        Log::error('ProcessCsvUpload: Job failed', [
            'upload_id' => $this->upload->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        $this->handleError($exception);
    }
}