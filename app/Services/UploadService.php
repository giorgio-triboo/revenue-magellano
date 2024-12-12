<?php

namespace App\Services;

use App\Models\FileUpload;
use App\Models\Statement;
use App\Models\SubPublisher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Csv\Reader;
use App\Jobs\ProcessCsvUpload;
use Carbon\Carbon;
use App\Jobs\MonitorCsvProcessing;

class UploadService
{
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

    public function handleFileUpload(UploadedFile $file, $userId, $processDate): FileUpload
    {
        try {
            // Controlla se esiste già un file per lo stesso anno/mese
            $this->checkExistingUpload($processDate);

            // Validazione del file CSV
            $this->validateCsvFile($file);

            // Procedi con l'upload
            return $this->storeFile($file, $userId, $processDate);

        } catch (\Exception $e) {
            Log::error('Errore durante la validazione del file CSV', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);
            throw $e;
        }
    }

    protected function checkExistingUpload($processDate): void
    {
        $existingUpload = FileUpload::where('process_date', $processDate)
            ->whereIn('status', [
                FileUpload::STATUS_PENDING,
                FileUpload::STATUS_PROCESSING,
                FileUpload::STATUS_COMPLETED,
                FileUpload::STATUS_PUBLISHED
            ])
            ->first();

        if ($existingUpload) {
            $month = Carbon::parse($processDate)->format('F Y');

            Log::warning('Tentativo di upload duplicato', [
                'process_date' => $processDate,
                'existing_upload_id' => $existingUpload->id,
                'existing_upload_status' => $existingUpload->status
            ]);

            throw new \Exception(
                "Esiste già un file caricato per il mese di {$month}. " .
                "Stato attuale: " . $this->getStatusDescription($existingUpload->status)
            );
        }
    }

    protected function getStatusDescription(string $status): string
    {
        return match ($status) {
            FileUpload::STATUS_PENDING => 'In attesa di elaborazione',
            FileUpload::STATUS_PROCESSING => 'In elaborazione',
            FileUpload::STATUS_COMPLETED => 'Elaborazione completata',
            FileUpload::STATUS_PUBLISHED => 'Pubblicato',
            FileUpload::STATUS_ERROR => 'Errore',
            default => $status
        };
    }

    protected function validateCsvFile(UploadedFile $file): void
{
    try {
        $path = $file->getRealPath();
        $content = file_get_contents($path);
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);

        $tmpPath = tempnam(sys_get_temp_dir(), 'csv_');
        file_put_contents($tmpPath, $content);

        $csv = Reader::createFromPath($tmpPath, 'r');
        $csv->setHeaderOffset(0);
        $csv->setDelimiter(';');

        $headers = array_map('trim', $csv->getHeader());

        $requiredHeaders = [
            'anno_consuntivo',
            'mese_consuntivo',
            'anno_competenza',
            'mese_competenza',
            'nome_campagna_HO',
            'publisher_id',
            'sub_publisher_id',
            'tipologia_revenue',
            'pay',
            'importo',
            'note',
            'data_invio'
        ];

        $hasValidatedQuantity = in_array('quantita_validata', $headers) ||
            in_array('quantità_validata', $headers);

        if (!$hasValidatedQuantity) {
            throw new \Exception("Colonna 'quantita_validata' o 'quantità_validata' non trovata nel file");
        }

        $missingHeaders = array_filter($requiredHeaders, function ($header) use ($headers) {
            return !in_array($header, $headers);
        });

        if (!empty($missingHeaders)) {
            throw new \Exception('Headers mancanti: ' . implode(', ', $missingHeaders));
        }

        $duplicates = [];
        $publisherErrors = [];
        $records = $csv->getRecords();

        foreach ($records as $offset => $record) {
            $lineNumber = $offset + 2;
            try {
                $this->validateBasicRecord($record, $lineNumber);
                
                // Verifica coerenza publisher-subpublisher
                $publisherId = (int)$record['publisher_id'];
                $subPublisherId = (int)$record['sub_publisher_id'];
                
                $isValid = SubPublisher::where('id', $subPublisherId)
                    ->where('publisher_id', $publisherId)
                    ->exists();
                
                if (!$isValid) {
                    $publisherErrors[] = [
                        'line' => $lineNumber,
                        'publisher_id' => $publisherId,
                        'sub_publisher_id' => $subPublisherId
                    ];
                }
                
                $duplicate = $this->checkDuplicate($record);
                if ($duplicate) {
                    $duplicates[] = [
                        'line' => $lineNumber,
                        'status' => $duplicate['status']
                    ];
                }
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }

        // Gestione errori di associazione publisher-subpublisher
        if (!empty($publisherErrors)) {
            $errorLines = array_slice($publisherErrors, 0, 3);
            $message = 'Errore di associazione publisher-subpublisher nelle righe: ';
            foreach ($errorLines as $error) {
                $message .= sprintf(
                    "riga %d (publisher_id: %d, sub_publisher_id: %d), ",
                    $error['line'],
                    $error['publisher_id'],
                    $error['sub_publisher_id']
                );
            }
            $message = rtrim($message, ', ');

            if (count($publisherErrors) > 3) {
                $message .= ' e altri ' . (count($publisherErrors) - 3) . ' errori';
            }

            throw new \Exception($message);
        }

        if (!empty($duplicates)) {
            $lines = array_slice($duplicates, 0, 3);
            $message = 'Record duplicati trovati: ';
            foreach ($lines as $dup) {
                $message .= "riga {$dup['line']} ({$dup['status']}), ";
            }
            $message = rtrim($message, ', ');

            if (count($duplicates) > 3) {
                $message .= ' e altri ' . (count($duplicates) - 3) . ' record';
            }

            throw new \Exception($message);
        }

        unlink($tmpPath);

    } catch (\Exception $e) {
        if (file_exists($tmpPath)) {
            unlink($tmpPath);
        }
        throw new \Exception('Errore nella validazione del file CSV: ' . $e->getMessage());
    }
}

    protected function validateBasicRecord(array $record, int $lineNumber): void
    {
        foreach (['anno_consuntivo', 'anno_competenza'] as $yearField) {
            if (
                !isset($record[$yearField]) || !is_numeric($record[$yearField]) ||
                $record[$yearField] < 2000 || $record[$yearField] > 2100
            ) {
                throw new \Exception("Anno non valido nel campo $yearField alla riga $lineNumber, utilizzare formato AAAA.");
            }
        }

        foreach (['mese_consuntivo', 'mese_competenza'] as $monthField) {
            if (
                !isset($record[$monthField]) || !is_numeric($record[$monthField]) ||
                $record[$monthField] < 1 || $record[$monthField] > 12
            ) {
                throw new \Exception("Mese non valido nel campo $monthField alla riga $lineNumber");
            }
        }

        if (!isset($record['publisher_id']) || !is_numeric($record['publisher_id']) || $record['publisher_id'] <= 0) {
            throw new \Exception("Publisher ID non valido alla riga $lineNumber");
        }

        if (!isset($record['sub_publisher_id']) || !is_numeric($record['sub_publisher_id']) || $record['sub_publisher_id'] <= 0) {
            throw new \Exception("Sub Publisher ID non valido alla riga $lineNumber");
        }

        if (
            !isset($record['tipologia_revenue']) ||
            !in_array(strtolower($record['tipologia_revenue']), ['cpl', 'cpc', 'cpm', 'tmk', 'crg', 'cpa', 'sms'])
        ) {
            throw new \Exception("Tipologia revenue non valida alla riga $lineNumber");
        }

        $quantityField = isset($record['quantita_validata']) ? 'quantita_validata' : 'quantità_validata';
        if (isset($record[$quantityField])) {
            $value = str_replace(',', '.', $record[$quantityField]);
            if (!is_numeric($value) || (float) $value < 0) {
                throw new \Exception("Quantità non valida alla riga $lineNumber");
            }
        }

        if (
            !isset($record['pay']) || !is_numeric(str_replace(',', '.', $record['pay'])) ||
            (float) str_replace(',', '.', $record['pay']) < 0
        ) {
            throw new \Exception("Pay non valido alla riga $lineNumber");
        }

        if (
            !isset($record['importo']) || !is_numeric(str_replace(',', '.', $record['importo'])) ||
            (float) str_replace(',', '.', $record['importo']) < 0
        ) {
            throw new \Exception("Importo non valido alla riga $lineNumber");
        }

        // Note e data_invio sono campi testuali, non richiedono validazione particolare
    }

    protected function checkDuplicate(array $record): ?array
    {
        $existingRecord = Statement::where([
            'statement_year' => (int) $record['anno_consuntivo'],
            'statement_month' => (int) $record['mese_consuntivo'],
            'competence_year' => (int) $record['anno_competenza'],
            'competence_month' => (int) $record['mese_competenza'],
            'campaign_name' => trim($record['nome_campagna_HO']),
            'publisher_id' => (int) $record['publisher_id'],
            'sub_publisher_id' => (int) $record['sub_publisher_id'],
            'revenue_type' => strtolower($record['tipologia_revenue'])
        ])
            ->where(function ($query) {
                $query->where('is_published', true)
                    ->orWhereHas('fileUpload', function ($q) {
                        $q->whereIn('status', [FileUpload::STATUS_COMPLETED, FileUpload::STATUS_PUBLISHED]);
                    });
            })
            ->first();

        if ($existingRecord) {
            return [
                'existing_id' => $existingRecord->id,
                'status' => $existingRecord->is_published ? 'pubblicato' : 'completato'
            ];
        }

        return null;
    }

    protected function storeFile(UploadedFile $file, $userId, $processDate): FileUpload
    {
        Log::info('Starting file upload process', [
            'user_id' => $userId,
            'file_name' => $file->getClientOriginalName(),
            'process_date' => $processDate
        ]);

        try {
            $uploadPath = sprintf('uploads/%s/%s', now()->year, now()->month);
            Storage::disk('private')->makeDirectory($uploadPath);

            $storedFilename = $this->generateFileName($file);
            $fullPath = Storage::disk('private')->putFileAs($uploadPath, $file, $storedFilename);

            Log::info('File stored successfully', ['path' => $fullPath]);

            $upload = FileUpload::create([
                'user_id' => $userId,
                'original_filename' => $file->getClientOriginalName(),
                'stored_filename' => $fullPath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'process_date' => $processDate,
                'status' => FileUpload::STATUS_PENDING,
                'processed_records' => 0,
                'progress_percentage' => 0,
                'processing_stats' => [
                    'start_time' => now()->toDateTimeString(),
                    'success' => 0,
                    'errors' => 0,
                    'error_details' => []
                ]
            ]);

            Log::info('Upload record created', [
                'upload_id' => $upload->id,
                'stored_path' => $fullPath
            ]);

            // Dispatch del job di processing
            ProcessCsvUpload::dispatch($upload)->onQueue('csv-processing');

            return $upload;

        } catch (\Exception $e) {
            Log::error('Error in file upload process', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

    }

    private function generateFileName(UploadedFile $file): string
    {
        return sprintf(
            '%s_%s.%s',
            Str::random(32),
            now()->format('YmdHis'),
            $file->getClientOriginalExtension()
        );
    }
}