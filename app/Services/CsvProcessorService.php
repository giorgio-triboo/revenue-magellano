<?php

namespace App\Services;

use App\Models\FileUpload;
use App\Models\Statement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Statement as CsvStatement;

class CsvProcessorService
{
    protected FileUpload $fileUpload;
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

    protected array $requiredHeaders = [
        'anno_consuntivo',
        'mese_consuntivo',
        'anno_competenza',
        'mese_competenza',
        'nome_campagna_HO',
        'publisher_id',
        'sub_publisher_id',
        'tipologia_revenue',
        'quantita_validata',
        'pay',
        'importo',
        'note',
        'data_invio'
    ];

    public function __construct(FileUpload $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    public function process(): array
    {
        Log::info('Starting CSV processing', [
            'upload_id' => $this->fileUpload->id,
            'file_path' => $this->fileUpload->stored_filename,
            'memory_usage' => $this->formatBytes(memory_get_usage(true))
        ]);

        try {
            DB::beginTransaction();
            Log::debug('Database transaction started');

            Log::debug('Loading CSV file');
            $csv = $this->loadCsv();
            Log::debug('CSV file loaded successfully');

            Log::debug('Validating CSV headers');
            $this->validateHeaders($csv);
            Log::debug('CSV headers validation completed');

            Log::debug('Reading CSV records');
            $records = $this->readRecords($csv);
            Log::info('CSV records read successfully', [
                'total_records' => count($records)
            ]);
            
            $result = [
                'total_records' => count($records),
                'processed_records' => 0,
                'success' => 0,
                'errors' => 0,
                'error_details' => []
            ];

            Log::info('Starting records processing', [
                'total_records' => $result['total_records']
            ]);

            foreach ($records as $index => $record) {
                try {
                    Log::debug('Processing record', [
                        'record_index' => $index + 2,
                        'current_progress' => round(($index / $result['total_records']) * 100, 2)
                    ]);

                    $transformedData = $this->validateAndPreTransformRecord($record, $index + 2);
                    $this->createStatement($transformedData, $record);
                    $result['success']++;
                    
                    Log::debug('Record processed successfully', [
                        'record_index' => $index + 2,
                        'success_count' => $result['success']
                    ]);
                } catch (\Exception $e) {
                    $result['errors']++;
                    $result['error_details'][] = [
                        'line' => $index + 2,
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error('Error processing record', [
                        'record_index' => $index + 2,
                        'error' => $e->getMessage(),
                        'error_count' => $result['errors']
                    ]);
                }
                
                $result['processed_records']++;
                
                // Aggiorna il progresso dopo ogni record
                $progress = ($result['processed_records'] / $result['total_records']) * 100;
                $this->fileUpload->update([
                    'processed_records' => $result['processed_records'],
                    'progress_percentage' => $progress,
                    'processing_stats' => [
                        'processed_records' => $result['processed_records'],
                        'total_records' => $result['total_records'],
                        'success' => $result['success'],
                        'errors' => $result['errors'],
                        'error_details' => $result['error_details']
                    ]
                ]);

                if ($index % 100 === 0) {
                    Log::info('Processing progress update', [
                        'processed_records' => $result['processed_records'],
                        'total_records' => $result['total_records'],
                        'progress_percentage' => round($progress, 2),
                        'success_count' => $result['success'],
                        'error_count' => $result['errors']
                    ]);
                }
            }

            // Aggiornamento finale dello stato
            $finalStatus = !empty($result['error_details']) ? FileUpload::STATUS_ERROR : FileUpload::STATUS_COMPLETED;
            Log::info('Processing completed, updating final status', [
                'final_status' => $finalStatus,
                'total_processed' => $result['processed_records'],
                'success_count' => $result['success'],
                'error_count' => $result['errors']
            ]);

            $this->fileUpload->update([
                'status' => $finalStatus,
                'error_message' => !empty($result['error_details']) ? sprintf(
                    'Errori trovati in %d record. Controllare le righe: %s',
                    count($result['error_details']),
                    implode(', ', array_column($result['error_details'], 'line'))
                ) : null,
                'processing_stats' => [
                    'completed_at' => now()->toDateTimeString(),
                    'processed_records' => $result['processed_records'],
                    'total_records' => $result['total_records'],
                    'success' => $result['success'],
                    'errors' => $result['errors'],
                    'error_details' => $result['error_details']
                ]
            ]);

            DB::commit();
            Log::info('Database transaction committed successfully');

            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CSV processing, rolling back transaction', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->fileUpload->update([
                'status' => FileUpload::STATUS_ERROR,
                'error_message' => $e->getMessage(),
                'processing_stats' => [
                    'completed_at' => now()->toDateTimeString(),
                    'error' => $e->getMessage(),
                    'error_details' => [['line' => 0, 'error' => $e->getMessage()]],
                    'processed_records' => $result['processed_records'] ?? 0,
                    'total_records' => $result['total_records'] ?? 0
                ]
            ]);

            throw $e;
        }
    }

    protected function loadCsv(): Reader
    {
        try {
            Log::debug('Loading CSV file from storage', [
                'file_path' => $this->fileUpload->stored_filename
            ]);

            $path = Storage::disk('private')->path($this->fileUpload->stored_filename);

            if (!file_exists($path)) {
                Log::error('CSV file not found in storage', [
                    'path' => $path
                ]);
                throw new \Exception("File CSV non trovato: {$path}");
            }

            Log::debug('Reading file contents');
            $content = file_get_contents($path);
            
            Log::debug('Removing BOM if present');
            $bom = pack('H*', 'EFBBBF');
            $content = preg_replace("/^$bom/", '', $content);

            Log::debug('Creating temporary file');
            $tmpPath = tempnam(sys_get_temp_dir(), 'csv_');
            file_put_contents($tmpPath, $content);

            Log::debug('Initializing CSV reader');
            $csv = Reader::createFromPath($tmpPath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(';');

            Log::info('CSV file loaded successfully', [
                'file_size' => $this->formatBytes(strlen($content)),
                'temp_path' => $tmpPath
            ]);

            return $csv;

        } catch (\Exception $e) {
            Log::error('Error loading CSV file', [
                'error' => $e->getMessage(),
                'file_path' => $this->fileUpload->stored_filename,
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Errore nel caricamento del file CSV: ' . $e->getMessage());
        }
    }

    protected function validateHeaders(Reader $csv): void
    {
        Log::debug('Starting CSV headers validation');
        
        $headers = array_map('trim', $csv->getHeader());
        Log::debug('CSV Headers found:', [
            'headers' => $headers,
            'file_name' => $this->fileUpload->stored_filename,
            'header_count' => count($headers)
        ]);

        $missingHeaders = array_diff($this->requiredHeaders, $headers);
        if (!empty($missingHeaders)) {
            Log::error('Missing required headers', [
                'missing_headers' => $missingHeaders,
                'found_headers' => $headers
            ]);

            $errorMessage = "Headers mancanti nel file CSV: " . implode(', ', $missingHeaders) . ".\n";
            $errorMessage .= "Per risolvere:\n";
            $errorMessage .= "1. Apri il file CSV\n";
            $errorMessage .= "2. Verifica che la prima riga contenga tutte le intestazioni richieste\n";
            $errorMessage .= "3. Le intestazioni richieste sono: " . implode(', ', $this->requiredHeaders);
            
            $this->fileUpload->update([
                'status' => FileUpload::STATUS_ERROR,
                'error_message' => $errorMessage,
                'processing_stats' => [
                    'error' => $errorMessage,
                    'error_details' => [[
                        'line' => 1,
                        'error' => 'Headers mancanti: ' . implode(', ', $missingHeaders)
                    ]]
                ]
            ]);

            throw new \Exception($errorMessage);
        }

        Log::debug('CSV headers validation completed successfully');
    }

    protected function readRecords(Reader $csv): array
    {
        Log::debug('Starting CSV records reading');
        
        $records = iterator_to_array($csv->getRecords());
        $totalRecords = count($records);

        Log::info('CSV records read successfully', [
            'total_records' => $totalRecords,
            'memory_usage' => $this->formatBytes(memory_get_usage(true))
        ]);

        $this->fileUpload->update([
            'total_records' => $totalRecords,
            'processed_records' => 0,
            'progress_percentage' => 0
        ]);

        return $records;
    }

    protected function validateAndPreTransformRecord(array $record, int $lineNumber): array
    {
        Log::debug('Validating and transforming record', [
            'line_number' => $lineNumber
        ]);

        $this->validateRecord($record, $lineNumber);
        Log::debug('Record validation passed', [
            'line_number' => $lineNumber
        ]);

        $transformedData = $this->transformRecord($record);
        Log::debug('Record transformation completed', [
            'line_number' => $lineNumber
        ]);

        return $transformedData;
    }

    protected function validateRecord(array $record, int $lineNumber): void
    {
        Log::debug('Starting record validation', [
            'line_number' => $lineNumber
        ]);

        foreach (['anno_consuntivo', 'anno_competenza'] as $yearField) {
            if (!isset($record[$yearField]) || !is_numeric($record[$yearField]) ||
                $record[$yearField] < 2000 || $record[$yearField] > 2100) {
                Log::error('Invalid year in record', [
                    'line_number' => $lineNumber,
                    'field' => $yearField,
                    'value' => $record[$yearField] ?? 'not set'
                ]);
                throw new \Exception("Anno non valido nel campo $yearField alla riga $lineNumber");
            }
        }

        foreach (['mese_consuntivo', 'mese_competenza'] as $monthField) {
            if (!isset($record[$monthField]) || !is_numeric($record[$monthField]) ||
                $record[$monthField] < 1 || $record[$monthField] > 12) {
                Log::error('Invalid month in record', [
                    'line_number' => $lineNumber,
                    'field' => $monthField,
                    'value' => $record[$monthField] ?? 'not set'
                ]);
                throw new \Exception("Mese non valido nel campo $monthField alla riga $lineNumber");
            }
        }

        if (!isset($record['publisher_id']) || !is_numeric($record['publisher_id']) || $record['publisher_id'] <= 0) {
            Log::error('Invalid publisher_id in record', [
                'line_number' => $lineNumber,
                'value' => $record['publisher_id'] ?? 'not set'
            ]);
            throw new \Exception("Publisher ID non valido alla riga $lineNumber");
        }

        if (!isset($record['sub_publisher_id']) || !is_numeric($record['sub_publisher_id']) || $record['sub_publisher_id'] <= 0) {
            Log::error('Invalid sub_publisher_id in record', [
                'line_number' => $lineNumber,
                'value' => $record['sub_publisher_id'] ?? 'not set'
            ]);
            throw new \Exception("Sub Publisher ID non valido alla riga $lineNumber");
        }

        if (!isset($record['tipologia_revenue']) || 
            !in_array(strtolower($record['tipologia_revenue']), ['cpl', 'cpc', 'cpm', 'tmk', 'crg', 'cpa', 'sms'])) {
            Log::error('Invalid revenue type in record', [
                'line_number' => $lineNumber,
                'value' => $record['tipologia_revenue'] ?? 'not set'
            ]);
            throw new \Exception("Tipologia revenue non valida alla riga $lineNumber");
        }

        $quantityField = isset($record['quantita_validata']) ? 'quantita_validata' : 'quantità_validata';
        if (isset($record[$quantityField])) {
            $value = str_replace(',', '.', $record[$quantityField]);
            if (!is_numeric($value) || (float)$value < 0) {
                Log::error('Invalid quantity in record', [
                    'line_number' => $lineNumber,
                    'field' => $quantityField,
                    'value' => $record[$quantityField] ?? 'not set'
                ]);
                throw new \Exception("Quantità non valida alla riga $lineNumber");
            }
        }

        if (!isset($record['pay']) || !is_numeric(str_replace(',', '.', $record['pay'])) || 
            (float)str_replace(',', '.', $record['pay']) < 0) {
            Log::error('Invalid pay in record', [
                'line_number' => $lineNumber,
                'value' => $record['pay'] ?? 'not set'
            ]);
            throw new \Exception("Pay non valido alla riga $lineNumber");
        }

        if (!isset($record['importo']) || !is_numeric(str_replace(',', '.', $record['importo'])) || 
            (float)str_replace(',', '.', $record['importo']) < 0) {
            Log::error('Invalid amount in record', [
                'line_number' => $lineNumber,
                'value' => $record['importo'] ?? 'not set'
            ]);
            throw new \Exception("Importo non valido alla riga $lineNumber");
        }

        Log::debug('Record validation completed successfully', [
            'line_number' => $lineNumber
        ]);
    }

    protected function transformRecord(array $record): array
    {
        Log::debug('Starting record transformation');
        
        $transformed = [];

        foreach ($this->headerMapping as $csvHeader => $dbColumn) {
            if (isset($record[$csvHeader])) {
                $value = $record[$csvHeader];

                switch ($dbColumn) {
                    case 'statement_year':
                    case 'competence_year':
                        $transformed[$dbColumn] = (int) $value;
                        break;
                    case 'statement_month':
                    case 'competence_month':
                        $transformed[$dbColumn] = (int) $value;
                        break;
                    case 'campaign_name':
                        $transformed[$dbColumn] = trim($value);
                        break;
                    case 'publisher_id':
                    case 'sub_publisher_id':
                        $transformed[$dbColumn] = (int) $value;
                        break;
                    case 'revenue_type':
                        $transformed[$dbColumn] = strtolower($value);
                        break;
                    case 'validated_quantity':
                        $transformed[$dbColumn] = (int) str_replace(',', '.', $value);
                        break;
                    case 'pay_per_unit':
                    case 'total_amount':
                        $transformed[$dbColumn] = (float) str_replace(',', '.', $value);
                        break;
                    case 'notes':
                    case 'sending_date':
                        $transformed[$dbColumn] = $value;
                        break;
                    default:
                        $transformed[$dbColumn] = $value;
                }
            }
        }

        // Gestione esplicita dei campi note e sending_date
        if (isset($record['note'])) {
            $transformed['notes'] = $record['note'];
        }
        if (isset($record['data_invio'])) {
            $transformed['sending_date'] = $record['data_invio'];
        }

        Log::debug('Record transformation completed', [
            'transformed_fields' => array_keys($transformed)
        ]);

        return $transformed;
    }

    protected function createStatement(array $transformedData, array $rawRecord): void
    {
        Log::debug('Creating statement record');
        
        Statement::create([
            'file_upload_id' => $this->fileUpload->id,
            'is_published' => false,
            'raw_data' => $rawRecord,
            ...$transformedData
        ]);

        Log::debug('Statement record created successfully');
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