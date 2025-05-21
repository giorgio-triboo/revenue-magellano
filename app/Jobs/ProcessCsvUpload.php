<?php
// ProcessCsvUpload.php

namespace App\Jobs;

use App\Events\FileUploadProcessed;
use App\Models\FileUpload;
use App\Models\Statement;
use App\Models\SubPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class ProcessCsvUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 3600;
    public $backoff = [60, 180, 300];
    protected $upload;
    protected $batchSize = 100;

    protected array $headerMapping = [
        'anno_consuntivo' => 'statement_year',
        'mese_consuntivo' => 'statement_month',
        'anno_competenza' => 'competence_year',
        'mese_competenza' => 'competence_month',
        'nome_campagna_HO' => 'campaign_name',
        'publisher_id' => 'publisher_id',
        'sub_publisher_id' => 'sub_publisher_id',
        'tipologia_revenue' => 'revenue_type',
        'quantita_validata' => 'validated_quantity',
        'pay' => 'pay_per_unit',
        'importo' => 'total_amount',
        'note' => 'notes',
        'data_invio' => 'sending_date'
    ];

    public function __construct(FileUpload $upload)
    {
        $this->upload = $upload;
    }

    public function handle()
    {
        $startTime = microtime(true);
        Log::channel('upload')->info('Starting CSV processing job', [
            'upload_id' => $this->upload->id,
            'memory_usage' => $this->formatBytes(memory_get_usage(true))
        ]);

        try {
            // Fase 1: Validazione iniziale del file
            $validationResult = $this->validateFile();
            if (!$validationResult['valid']) {
                $this->handleValidationError($validationResult);
                return;
            }

            // Fase 2: Processing dei record
            $this->processRecords();

            $timeTaken = (microtime(true) - $startTime) * 1000;
            Log::channel('upload')->info('CSV processing completed', [
                'upload_id' => $this->upload->id,
                'time_taken_ms' => round($timeTaken, 2),
                'peak_memory' => $this->formatBytes(memory_get_peak_usage(true))
            ]);

        } catch (\Exception $e) {
            Log::channel('upload')->error('Error in CSV processing', [
                'upload_id' => $this->upload->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'memory_usage' => $this->formatBytes(memory_get_usage(true))
            ]);

            $this->handleError($e);
            throw $e;
        }
    }

    protected function validateFile(): array
    {
        $startTime = microtime(true);

        try {
            $csv = $this->loadCsv();

            // Validazione headers
            $headers = array_map('trim', $csv->getHeader());
            $requiredHeaders = array_keys($this->headerMapping);
            $missingHeaders = array_diff($requiredHeaders, $headers);

            if (!empty($missingHeaders)) {
                return [
                    'valid' => false,
                    'message' => 'Headers mancanti: ' . implode(', ', $missingHeaders),
                    'errors' => [['line' => 0, 'message' => 'Headers mancanti']]
                ];
            }

            $timeTaken = (microtime(true) - $startTime) * 1000;
            Log::channel('upload')->debug('File validation completed', [
                'upload_id' => $this->upload->id,
                'time_taken_ms' => round($timeTaken, 2)
            ]);

            return ['valid' => true];

        } catch (\Exception $e) {
            Log::channel('upload')->error('File validation failed', [
                'upload_id' => $this->upload->id,
                'error' => $e->getMessage()
            ]);

            return [
                'valid' => false,
                'message' => 'Errore nella validazione del file: ' . $e->getMessage(),
                'errors' => [['line' => 0, 'message' => $e->getMessage()]]
            ];
        }
    }

    protected function processRecords(): void
    {
        Log::channel('upload')->info('Starting processRecords method', [
            'upload_id' => $this->upload->id,
            'current_status' => $this->upload->status
        ]);

        try {
            $csv = $this->loadCsv();
            Log::channel('upload')->debug('CSV file loaded successfully', [
                'upload_id' => $this->upload->id,
                'file_path' => $this->upload->stored_filename
            ]);

            $records = iterator_to_array($csv->getRecords());
            $totalRecords = count($records);
            
            Log::channel('upload')->info('Records loaded from CSV', [
                'upload_id' => $this->upload->id,
                'total_records' => $totalRecords
            ]);

            if ($totalRecords === 0) {
                Log::channel('upload')->warning('No records found in CSV file', [
                    'upload_id' => $this->upload->id
                ]);
                $this->upload->update([
                    'status' => FileUpload::STATUS_ERROR,
                    'error_message' => 'Il file CSV non contiene record da processare'
                ]);
                return;
            }

            $processed = 0;
            $batch = [];

            Log::channel('upload')->info('Updating upload status to processing', [
                'upload_id' => $this->upload->id,
                'total_records' => $totalRecords
            ]);

            $this->upload->update([
                'total_records' => $totalRecords,
                'status' => FileUpload::STATUS_PROCESSING
            ]);

            DB::beginTransaction();
            try {
                foreach ($records as $index => $record) {
                    try {
                        $validatedData = $this->validateRecord($record, $index + 2);
                        $transformedData = $this->transformRecord($validatedData);
                        $batch[] = $transformedData;

                        if (count($batch) >= $this->batchSize) {
                            $this->saveBatch($batch);
                            $batch = [];
                        }

                        $processed++;
                        $this->updateProgress($processed, $totalRecords);

                    } catch (\Exception $e) {
                        Log::channel('upload')->warning('Error processing record', [
                            'upload_id' => $this->upload->id,
                            'line' => $index + 2,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                // Salva gli ultimi record rimanenti
                if (!empty($batch)) {
                    $this->saveBatch($batch);
                }

                DB::commit();
                Log::channel('upload')->info('Transaction committed successfully', [
                    'upload_id' => $this->upload->id,
                    'processed_records' => $processed
                ]);

                Log::channel('upload')->info('Updating upload status to completed', [
                    'upload_id' => $this->upload->id,
                    'processed_records' => $processed,
                    'total_records' => $totalRecords
                ]);

                $this->upload->update([
                    'status' => FileUpload::STATUS_COMPLETED,
                    'processed_records' => $processed,
                    'progress_percentage' => 100
                ]);

                event(new FileUploadProcessed($this->upload));

            } catch (\Exception $e) {
                DB::rollBack();
                Log::channel('upload')->error('Error during transaction', [
                    'upload_id' => $this->upload->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        } catch (\Exception $e) {
            Log::channel('upload')->error('Error in processRecords', [
                'upload_id' => $this->upload->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function loadCsv(): Reader
    {
        $path = Storage::disk('private')->path($this->upload->stored_filename);

        if (!file_exists($path)) {
            throw new \Exception("File CSV non trovato: {$path}");
        }

        $content = file_get_contents($path);
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);

        $csv = Reader::createFromString($content);
        $csv->setHeaderOffset(0);
        $csv->setDelimiter(';');

        return $csv;
    }

    protected function validateRecord(array $record, int $lineNumber): array
    {
        $startTime = microtime(true);

        // Validazione campi numerici
        foreach (['anno_consuntivo', 'anno_competenza'] as $field) {
            if (
                !isset($record[$field]) || !is_numeric($record[$field]) ||
                $record[$field] < 2000 || $record[$field] > 2100
            ) {
                throw new \Exception("Anno non valido nel campo $field");
            }
        }

        foreach (['mese_consuntivo', 'mese_competenza'] as $field) {
            if (
                !isset($record[$field]) || !is_numeric($record[$field]) ||
                $record[$field] < 1 || $record[$field] > 12
            ) {
                throw new \Exception("Mese non valido nel campo $field");
            }
        }

        // Validazione publisher e sub_publisher
        foreach (['publisher_id', 'sub_publisher_id'] as $field) {
            if (!isset($record[$field]) || !is_numeric($record[$field]) || $record[$field] <= 0) {
                throw new \Exception("$field non valido");
            }
        }

        // Verifica associazione publisher-subpublisher
        $isValid = SubPublisher::where('id', (int) $record['sub_publisher_id'])
            ->where('publisher_id', (int) $record['publisher_id'])
            ->exists();

        if (!$isValid) {
            throw new \Exception("Associazione publisher-subpublisher non valida");
        }

        // Validazione tipologia revenue
        if (
            !isset($record['tipologia_revenue']) ||
            !in_array(strtolower($record['tipologia_revenue']), ['cpl', 'cpc', 'cpm', 'tmk', 'crg', 'cpa', 'sms'])
        ) {
            throw new \Exception("Tipologia revenue non valida");
        }

        // Validazione campi numerici con decimali
        foreach (['quantita_validata', 'pay', 'importo'] as $field) {
            if (isset($record[$field])) {
                $value = str_replace(',', '.', $record[$field]);
                if (!is_numeric($value) || (float) $value < 0) {
                    throw new \Exception("Valore non valido per il campo $field");
                }
            }
        }

        $timeTaken = (microtime(true) - $startTime) * 1000;
        Log::channel('upload')->debug('Record validation completed', [
            'upload_id' => $this->upload->id,
            'line' => $lineNumber,
            'time_taken_ms' => round($timeTaken, 2)
        ]);

        return $record;
    }

    protected function transformRecord(array $record): array
    {
        $transformed = [];

        foreach ($this->headerMapping as $csvHeader => $dbColumn) {
            if (isset($record[$csvHeader])) {
                $value = $record[$csvHeader];

                $transformed[$dbColumn] = match ($dbColumn) {
                    'statement_year', 'competence_year',
                    'statement_month', 'competence_month' => (int) $value,
                    'campaign_name' => trim($value),
                    'publisher_id', 'sub_publisher_id' => (int) $value,
                    'revenue_type' => strtolower($value),
                    'validated_quantity' => (int) str_replace(',', '.', $value),
                    'pay_per_unit', 'total_amount' => (float) str_replace(',', '.', $value),
                    default => $value,
                };
            }
        }

        return $transformed;
    }

    protected function saveBatch(array $batch): void
    {
        $startTime = microtime(true);

        foreach ($batch as $record) {
            Statement::create([
                'file_upload_id' => $this->upload->id,
                'is_published' => false,
                'raw_data' => $record,
                ...$record
            ]);
        }

        $timeTaken = (microtime(true) - $startTime) * 1000;
        Log::channel('upload')->debug('Batch saved', [
            'upload_id' => $this->upload->id,
            'batch_size' => count($batch),
            'time_taken_ms' => round($timeTaken, 2)
        ]);
    }

    protected function updateProgress(int $processed, int $total): void
    {
        $progress = ($processed / $total) * 100;

        $this->upload->update([
            'processed_records' => $processed,
            'progress_percentage' => round($progress, 2)
        ]);

        if ($processed % $this->batchSize === 0) {
            Log::channel('upload')->info('Processing progress', [
                'upload_id' => $this->upload->id,
                'processed' => $processed,
                'total' => $total,
                'percentage' => round($progress, 2),
                'memory_usage' => $this->formatBytes(memory_get_usage(true))
            ]);
        }
    }

    protected function handleValidationError(array $validationResult): void
    {
        $this->upload->update([
            'status' => FileUpload::STATUS_ERROR,
            'error_message' => $validationResult['message'],
            'processing_stats' => [
                'completed_at' => now()->toDateTimeString(),
                'error_details' => $validationResult['errors']
            ]
        ]);

        Log::channel('upload')->error('Validation failed', [
            'upload_id' => $this->upload->id,
            'errors' => $validationResult['errors']
        ]);
    }

    protected function handleError(\Exception $e): void
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

    private function formatBytes($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}