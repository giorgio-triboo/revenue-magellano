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
        // Controlla solo se esiste già un file per lo stesso anno/mese
        $this->checkExistingUpload($processDate);

        Log::info('Starting file upload process', [
            'user_id' => $userId,
            'file_name' => $file->getClientOriginalName(),
            'process_date' => $processDate
        ]);

        // Carica subito il file
        $upload = $this->storeFile($file, $userId, $processDate);

        // Dispatch del job di validazione e processing
        ProcessCsvUpload::dispatch($upload)->onQueue('csv-processing');

        return $upload;

    } catch (\Exception $e) {
        Log::error('Error in handleFileUpload', [
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
        $tmpPath = null;

        try {
            Log::channel('upload')->debug('Inizio validazione CSV', [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize()
            ]);

            if (!$file->isValid()) {
                Log::channel('upload')->error('File non valido', [
                    'error' => $file->getError(),
                    'error_message' => $file->getErrorMessage()
                ]);
                throw new \Exception('Il file caricato non è valido: ' . $file->getErrorMessage());
            }

            $path = $file->getRealPath();
            if (!file_exists($path)) {
                Log::channel('upload')->error('File non trovato', ['path' => $path]);
                throw new \Exception('File non trovato nel percorso temporaneo');
            }

            $content = file_get_contents($path);
            if ($content === false) {
                Log::channel('upload')->error('Impossibile leggere il contenuto del file');
                throw new \Exception('Impossibile leggere il contenuto del file');
            }

            // Rimuovi BOM se presente
            $bom = pack('H*', 'EFBBBF');
            $content = preg_replace("/^$bom/", '', $content);

            // Crea file temporaneo
            $tmpPath = tempnam(sys_get_temp_dir(), 'csv_');
            if ($tmpPath === false) {
                Log::channel('upload')->error('Impossibile creare file temporaneo');
                throw new \Exception('Impossibile creare file temporaneo');
            }

            if (file_put_contents($tmpPath, $content) === false) {
                Log::channel('upload')->error('Impossibile scrivere sul file temporaneo');
                throw new \Exception('Impossibile scrivere sul file temporaneo');
            }

            Log::channel('upload')->debug('File temporaneo creato', ['tmp_path' => $tmpPath]);

            // Inizializza il CSV reader
            $csv = Reader::createFromPath($tmpPath, 'r');
            $csv->setDelimiter(';');
            $csv->setHeaderOffset(0);

            // Verifica headers
            $headers = array_map('trim', $csv->getHeader());
            Log::channel('upload')->debug('Headers CSV trovati', ['headers' => $headers]);

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

            // Verifica presenza colonna quantita_validata
            $hasValidatedQuantity = in_array('quantita_validata', $headers) ||
                in_array('quantità_validata', $headers);

            if (!$hasValidatedQuantity) {
                Log::channel('upload')->error('Colonna quantita_validata mancante');
                throw new \Exception("Colonna 'quantita_validata' o 'quantità_validata' non trovata nel file");
            }

            // Verifica headers mancanti
            $missingHeaders = array_filter($requiredHeaders, function ($header) use ($headers) {
                return !in_array($header, $headers);
            });

            if (!empty($missingHeaders)) {
                Log::channel('upload')->error('Headers mancanti', ['missing' => $missingHeaders]);
                throw new \Exception('Headers mancanti: ' . implode(', ', $missingHeaders));
            }

            // Verifica record
            $duplicates = [];
            $publisherErrors = [];
            $records = $csv->getRecords();
            $recordCount = 0;

            foreach ($records as $offset => $record) {
                $recordCount++;
                $lineNumber = $offset + 2; // +2 perché l'offset parte da 0 e la prima riga è l'header

                try {
                    $this->validateBasicRecord($record, $lineNumber);

                    // Verifica publisher-subpublisher
                    $publisherId = (int) $record['publisher_id'];
                    $subPublisherId = (int) $record['sub_publisher_id'];

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

                    // Verifica duplicati
                    $duplicate = $this->checkDuplicate($record);
                    if ($duplicate) {
                        $duplicates[] = [
                            'line' => $lineNumber,
                            'status' => $duplicate['status']
                        ];
                    }
                } catch (\Exception $e) {
                    Log::channel('upload')->error('Errore validazione record', [
                        'line' => $lineNumber,
                        'error' => $e->getMessage()
                    ]);
                    throw new \Exception("Riga $lineNumber: " . $e->getMessage());
                }
            }

            Log::channel('upload')->debug('Validazione record completata', [
                'total_records' => $recordCount,
                'publisher_errors' => count($publisherErrors),
                'duplicates' => count($duplicates)
            ]);

            // Gestione errori publisher-subpublisher
            if (!empty($publisherErrors)) {
                throw new \Exception($this->formatPublisherErrors($publisherErrors));
            }

            // Gestione duplicati
            if (!empty($duplicates)) {
                throw new \Exception($this->formatDuplicateErrors($duplicates));
            }

        } catch (\Exception $e) {
            Log::channel('upload')->error('Errore durante la validazione CSV', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        } finally {
            // Pulizia file temporaneo
            if ($tmpPath && file_exists($tmpPath)) {
                unlink($tmpPath);
                Log::channel('upload')->debug('File temporaneo rimosso', ['tmp_path' => $tmpPath]);
            }
        }
    }

    private function formatPublisherErrors(array $errors): string
    {
        $errorLines = array_slice($errors, 0, 3);
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

        if (count($errors) > 3) {
            $message .= ' e altri ' . (count($errors) - 3) . ' errori';
        }

        return $message;
    }

    private function formatDuplicateErrors(array $duplicates): string
    {
        $lines = array_slice($duplicates, 0, 3);
        $message = 'Record duplicati trovati: ';

        foreach ($lines as $dup) {
            $message .= "riga {$dup['line']} ({$dup['status']}), ";
        }

        $message = rtrim($message, ', ');

        if (count($duplicates) > 3) {
            $message .= ' e altri ' . (count($duplicates) - 3) . ' record';
        }

        return $message;
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
    $uploadPath = sprintf('uploads/%s/%s', now()->year, now()->month);
    Storage::disk('private')->makeDirectory($uploadPath);

    $storedFilename = $this->generateFileName($file);
    $fullPath = Storage::disk('private')->putFileAs($uploadPath, $file, $storedFilename);

    return FileUpload::create([
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