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
        Log::info('Starting CSV processing', ['upload_id' => $this->fileUpload->id]);

        try {
            DB::beginTransaction();

            $csv = $this->loadCsv();
            $this->validateHeaders($csv);
            $records = $this->readRecords($csv);
            
            $result = [
                'total_records' => count($records),
                'processed_records' => 0,
                'success' => 0,
                'errors' => 0,
                'error_details' => []
            ];

            foreach ($records as $index => $record) {
                try {
                    $transformedData = $this->validateAndPreTransformRecord($record, $index + 2);
                    $this->createStatement($transformedData, $record);
                    $result['success']++;
                } catch (\Exception $e) {
                    $result['errors']++;
                    $result['error_details'][] = [
                        'line' => $index + 2,
                        'error' => $e->getMessage()
                    ];
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
            }

            // Aggiornamento finale dello stato
            $finalStatus = !empty($result['error_details']) ? FileUpload::STATUS_ERROR : FileUpload::STATUS_COMPLETED;
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
            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            
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
            $path = Storage::disk('private')->path($this->fileUpload->stored_filename);

            if (!file_exists($path)) {
                throw new \Exception("File CSV non trovato: {$path}");
            }

            $content = file_get_contents($path);
            $bom = pack('H*', 'EFBBBF');
            $content = preg_replace("/^$bom/", '', $content);

            $tmpPath = tempnam(sys_get_temp_dir(), 'csv_');
            file_put_contents($tmpPath, $content);

            $csv = Reader::createFromPath($tmpPath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(';');

            return $csv;

        } catch (\Exception $e) {
            throw new \Exception('Errore nel caricamento del file CSV: ' . $e->getMessage());
        }
    }

    protected function validateHeaders(Reader $csv): void
    {
        $headers = array_map('trim', $csv->getHeader());
        Log::debug('CSV Headers found:', [
            'headers' => $headers,
            'file_name' => $this->fileUpload->stored_filename
        ]);

        $missingHeaders = array_diff($this->requiredHeaders, $headers);
        if (!empty($missingHeaders)) {
            throw new \Exception('Headers mancanti: ' . implode(', ', $missingHeaders));
        }
    }

    protected function readRecords(Reader $csv): array
    {
        $records = iterator_to_array($csv->getRecords());
        $totalRecords = count($records);

        $this->fileUpload->update([
            'total_records' => $totalRecords,
            'processed_records' => 0,
            'progress_percentage' => 0
        ]);

        return $records;
    }

    protected function validateAndPreTransformRecord(array $record, int $lineNumber): array
    {
        $this->validateRecord($record, $lineNumber);
        return $this->transformRecord($record);
    }

    protected function validateRecord(array $record, int $lineNumber): void
    {
        foreach (['anno_consuntivo', 'anno_competenza'] as $yearField) {
            if (!isset($record[$yearField]) || !is_numeric($record[$yearField]) ||
                $record[$yearField] < 2000 || $record[$yearField] > 2100) {
                throw new \Exception("Anno non valido nel campo $yearField alla riga $lineNumber");
            }
        }

        foreach (['mese_consuntivo', 'mese_competenza'] as $monthField) {
            if (!isset($record[$monthField]) || !is_numeric($record[$monthField]) ||
                $record[$monthField] < 1 || $record[$monthField] > 12) {
                throw new \Exception("Mese non valido nel campo $monthField alla riga $lineNumber");
            }
        }

        if (!isset($record['publisher_id']) || !is_numeric($record['publisher_id']) || $record['publisher_id'] <= 0) {
            throw new \Exception("Publisher ID non valido alla riga $lineNumber");
        }

        if (!isset($record['sub_publisher_id']) || !is_numeric($record['sub_publisher_id']) || $record['sub_publisher_id'] <= 0) {
            throw new \Exception("Sub Publisher ID non valido alla riga $lineNumber");
        }

        if (!isset($record['tipologia_revenue']) || 
            !in_array(strtolower($record['tipologia_revenue']), ['cpl', 'cpc', 'cpm', 'tmk', 'crg', 'cpa', 'sms'])) {
            throw new \Exception("Tipologia revenue non valida alla riga $lineNumber");
        }

        $quantityField = isset($record['quantita_validata']) ? 'quantita_validata' : 'quantità_validata';
        if (isset($record[$quantityField])) {
            $value = str_replace(',', '.', $record[$quantityField]);
            if (!is_numeric($value) || (float)$value < 0) {
                throw new \Exception("Quantità non valida alla riga $lineNumber");
            }
        }

        if (!isset($record['pay']) || !is_numeric(str_replace(',', '.', $record['pay'])) || 
            (float)str_replace(',', '.', $record['pay']) < 0) {
            throw new \Exception("Pay non valido alla riga $lineNumber");
        }

        if (!isset($record['importo']) || !is_numeric(str_replace(',', '.', $record['importo'])) || 
            (float)str_replace(',', '.', $record['importo']) < 0) {
            throw new \Exception("Importo non valido alla riga $lineNumber");
        }
    }

    protected function transformRecord(array $record): array
    {
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

        return $transformed;
    }

    protected function createStatement(array $transformedData, array $rawRecord): void
    {
        Statement::create([
            'file_upload_id' => $this->fileUpload->id,
            'is_published' => false,
            'raw_data' => $rawRecord,
            ...$transformedData
        ]);
    }
}