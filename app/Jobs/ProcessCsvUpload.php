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
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use App\Models\SubPublisher;
use App\Models\Statement;
use Throwable;
use Carbon\Carbon;

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
        Log::channel('upload')->info('ProcessCsvUpload: Constructor called', [
            'upload_id' => $upload->id,
            'has_user_relation' => $upload->relationLoaded('user'),
            'memory_id' => spl_object_id($upload)
        ]);
    }

    public function handle()
    {
        Log::channel('upload')->info('ProcessCsvUpload: Handle started', [
            'upload_id' => $this->upload->id,
            'initial_status' => $this->upload->status,
            'has_user_relation' => $this->upload->relationLoaded('user'),
            'memory_id' => spl_object_id($this->upload)
        ]);

        if ($this->upload->status === FileUpload::STATUS_COMPLETED) {
            Log::channel('upload')->info('ProcessCsvUpload: Job already completed, skipping', [
                'upload_id' => $this->upload->id,
                'job_id' => $this->job->getJobId()
            ]);
            return;
        }

        try {
            $this->upload->load('user');

            Log::channel('upload')->info('ProcessCsvUpload: Starting validation phase', [
                'upload_id' => $this->upload->id
            ]);

            // Fase di validazione
            $validationResult = $this->validateFile();

            if (!$validationResult['valid']) {
                Log::channel('upload')->error('ProcessCsvUpload: Validation failed', [
                    'upload_id' => $this->upload->id,
                    'errors' => $validationResult['errors']
                ]);

                $this->upload->update([
                    'status' => FileUpload::STATUS_ERROR,
                    'error_message' => $validationResult['message'],
                    'processing_stats' => [
                        'completed_at' => now()->toDateTimeString(),
                        'error_details' => $this->formatErrorDetails($validationResult['errors'])
                    ]
                ]);
                return;
            }

            Log::channel('upload')->info('ProcessCsvUpload: Validation passed, starting processing', [
                'upload_id' => $this->upload->id
            ]);

            // Aggiornamento stato a processing
            $this->upload->update([
                'status' => FileUpload::STATUS_PROCESSING,
                'processing_stats' => [
                    'start_time' => now()->toDateTimeString(),
                    'success' => 0,
                    'errors' => 0,
                    'error_details' => []
                ]
            ]);

            // Fase di processing
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

            Log::channel('upload')->info('ProcessCsvUpload: Updating final status', [
                'upload_id' => $this->upload->id,
                'status' => $status,
                'stats' => $processingStats
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

            $this->upload = $this->upload->fresh(['user']);

            if ($status === FileUpload::STATUS_COMPLETED) {
                event(new FileUploadProcessed($this->upload));
                
                Log::channel('upload')->info('ProcessCsvUpload: Processing completed successfully', [
                    'upload_id' => $this->upload->id
                ]);
            }

        } catch (\Exception $e) {
            Log::channel('upload')->error('ProcessCsvUpload: Error in job', [
                'upload_id' => $this->upload->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->handleError($e);
            throw $e;
        }
    }

    protected function validateFile(): array
    {
        try {
            $filePath = Storage::disk('private')->path($this->upload->stored_filename);
            
            if (!file_exists($filePath)) {
                return [
                    'valid' => false,
                    'message' => 'File non trovato nel percorso specificato',
                    'errors' => [['line' => 0, 'message' => 'File non trovato']]
                ];
            }

            $content = file_get_contents($filePath);
            $bom = pack('H*', 'EFBBBF');
            $content = preg_replace("/^$bom/", '', $content);

            $tmpPath = tempnam(sys_get_temp_dir(), 'csv_');
            file_put_contents($tmpPath, $content);

            $csv = Reader::createFromPath($tmpPath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(';');

            $headers = array_map('trim', $csv->getHeader());
            $records = iterator_to_array($csv->getRecords());

            unlink($tmpPath);

            $errors = [];
            $publisherSubPublisherPairs = [];
            $duplicateCheckData = [];

            // Validazione struttura e dati
            foreach ($records as $offset => $record) {
                $lineNumber = $offset + 2;
                
                try {
                    $this->validateRecord($record, $lineNumber);
                    
                    $publisherId = (int)$record['publisher_id'];
                    $subPublisherId = (int)$record['sub_publisher_id'];
                    $publisherSubPublisherPairs[$publisherId . '-' . $subPublisherId] = true;

                    $duplicateCheckData[] = $this->prepareDuplicateCheckData($record);
                } catch (\Exception $e) {
                    $errors[] = ['line' => $lineNumber, 'message' => $e->getMessage()];
                }
            }

            // Verifica publisher-subpublisher
            $invalidPairs = $this->validatePublisherPairs(array_keys($publisherSubPublisherPairs));
            foreach ($invalidPairs as $pair => $lineNumbers) {
                [$publisherId, $subPublisherId] = explode('-', $pair);
                $errors[] = [
                    'line' => implode(', ', $lineNumbers),
                    'message' => "Associazione publisher ($publisherId) - subpublisher ($subPublisherId) non valida"
                ];
            }

            // Verifica duplicati
            $duplicates = $this->checkDuplicates($duplicateCheckData);
            foreach ($duplicates as $duplicate) {
                $errors[] = $duplicate;
            }

            return [
                'valid' => empty($errors),
                'message' => empty($errors) ? 'Validazione completata con successo' : 'Errori di validazione nel file',
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            Log::channel('upload')->error('Errore durante la validazione', [
                'upload_id' => $this->upload->id,
                'error' => $e->getMessage()
            ]);

            return [
                'valid' => false,
                'message' => 'Errore durante la validazione: ' . $e->getMessage(),
                'errors' => [['line' => 0, 'message' => $e->getMessage()]]
            ];
        }
    }

    protected function validateRecord(array $record, int $lineNumber): void
    {
        $requiredFields = [
            'anno_consuntivo', 'mese_consuntivo', 'anno_competenza', 'mese_competenza',
            'nome_campagna_HO', 'publisher_id', 'sub_publisher_id', 'tipologia_revenue',
            'quantita_validata', 'pay', 'importo'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($record[$field]) || trim($record[$field]) === '') {
                throw new \Exception("Campo $field mancante o vuoto");
            }
        }

        // Validazione anni
        foreach (['anno_consuntivo', 'anno_competenza'] as $yearField) {
            if (!is_numeric($record[$yearField]) || $record[$yearField] < 2000 || $record[$yearField] > 2100) {
                throw new \Exception("Anno non valido nel campo $yearField");
            }
        }

        // Validazione mesi
        foreach (['mese_consuntivo', 'mese_competenza'] as $monthField) {
            if (!is_numeric($record[$monthField]) || $record[$monthField] < 1 || $record[$monthField] > 12) {
                throw new \Exception("Mese non valido nel campo $monthField");
            }
        }

        // Validazione tipologia revenue
        if (!in_array(strtolower($record['tipologia_revenue']), ['cpl', 'cpc', 'cpm', 'tmk', 'crg', 'cpa', 'sms'])) {
            throw new \Exception("Tipologia revenue non valida");
        }

        // Validazione numeri
        $numericFields = ['quantita_validata', 'pay', 'importo'];
        foreach ($numericFields as $field) {
            $value = str_replace(',', '.', $record[$field]);
            if (!is_numeric($value) || (float)$value < 0) {
                throw new \Exception("Valore non valido per il campo $field");
            }
        }
    }

    protected function validatePublisherPairs(array $pairs): array
    {
        $invalidPairs = [];
        $validPairs = SubPublisher::all()
            ->mapWithKeys(fn($sub) => [$sub->publisher_id . '-' . $sub->id => true])
            ->all();

        foreach ($pairs as $pair) {
            if (!isset($validPairs[$pair])) {
                $invalidPairs[$pair] = [];
            }
        }

        return $invalidPairs;
    }

    protected function prepareDuplicateCheckData(array $record): array
    {
        return [
            'statement_year' => (int)$record['anno_consuntivo'],
            'statement_month' => (int)$record['mese_consuntivo'],
            'competence_year' => (int)$record['anno_competenza'],
            'competence_month' => (int)$record['mese_competenza'],
            'campaign_name' => trim($record['nome_campagna_HO']),
            'publisher_id' => (int)$record['publisher_id'],
            'sub_publisher_id' => (int)$record['sub_publisher_id'],
            'revenue_type' => strtolower($record['tipologia_revenue'])
        ];
    }

    protected function checkDuplicates(array $records): array
    {
        $duplicates = [];
        $existingRecords = Statement::where(function ($query) {
                $query->where('is_published', true)
                    ->orWhereHas('fileUpload', function ($q) {
                        $q->whereIn('status', [FileUpload::STATUS_COMPLETED, FileUpload::STATUS_PUBLISHED]);
                    });
            })
            ->get();

        foreach ($records as $index => $record) {
            foreach ($existingRecords as $existing) {
                if (
                    $existing->statement_year == $record['statement_year'] &&
                    $existing->statement_month == $record['statement_month'] &&
                    $existing->competence_year == $record['competence_year'] &&
                    $existing->competence_month == $record['competence_month'] &&
                    $existing->campaign_name === $record['campaign_name'] &&
                    $existing->publisher_id == $record['publisher_id'] &&
                    $existing->sub_publisher_id == $record['sub_publisher_id'] &&
                    $existing->revenue_type === $record['revenue_type']
                ) {
                    $duplicates[] = [
                        'line' => $index + 2,
                        'message' => 'Record duplicato trovato (' . 
                            ($existing->is_published ? 'pubblicato' : 'completato') . ')'
                    ];
                    break;
                }
            }
        }

        return $duplicates;
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
       Log::channel('upload')->error('ProcessCsvUpload: Job failed', [
           'upload_id' => $this->upload->id,
           'error' => $exception->getMessage(),
           'trace' => $exception->getTraceAsString()
       ]);

       $this->handleError($exception);

       // Aggiorna lo stato dell'upload
       $this->upload->update([
           'status' => FileUpload::STATUS_ERROR,
           'error_message' => 'Job fallito: ' . $exception->getMessage(),
           'processing_stats' => array_merge(
               $this->upload->processing_stats ?? [],
               [
                   'completed_at' => now()->toDateTimeString(),
                   'job_failure' => [
                       'message' => $exception->getMessage(),
                       'class' => get_class($exception),
                       'time' => now()->toDateTimeString()
                   ]
               ]
           )
       ]);

       // Log dettagliato del fallimento
       Log::channel('upload')->error('ProcessCsvUpload: Job failure details', [
           'upload_id' => $this->upload->id,
           'exception_class' => get_class($exception),
           'message' => $exception->getMessage(),
           'file' => $exception->getFile(),
           'line' => $exception->getLine(),
           'trace' => $exception->getTraceAsString()
       ]);
   }
}