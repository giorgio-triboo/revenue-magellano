<?php

namespace App\Services;

use App\Models\FileUpload;
use App\Models\Statement;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AxExportService
{
    public function generateTsvExport(FileUpload $upload)
    {
        Log::channel('ax_export')->debug('AxExportService: Avvio generazione file TSV.', [
            'upload_id' => $upload->id,
        ]);

        $fileName = sprintf(
            'TBA_ODA_editori_%s%s.tsv',
            $upload->process_date->format('Y'),
            $upload->process_date->format('m')
        );

        $path = storage_path('app/private/exports/' . $fileName);
        Log::channel('ax_export')->debug('AxExportService: Path generazione file TSV.', ['path' => $path]);

        if (!$handle = fopen($path, 'w')) {
            Log::channel('ax_export')->error('AxExportService: Impossibile aprire il file per scrittura.', ['path' => $path]);
            throw new \Exception('Impossibile creare il file di export');
        }

        try {
            Log::channel('ax_export')->debug('AxExportService: Inizio elaborazione statements.', [
                'upload_id' => $upload->id,
            ]);

            // Recupera gli statements
            $statements = Statement::with(['publisher.axData', 'subPublisher'])
                ->where('file_upload_id', $upload->id)
                ->whereHas('publisher', function ($query) {
                    $query->where('id', '!=', 1);
                })
                ->orderBy('publisher_id')
                ->get();

            Log::channel('ax_export')->debug('Totale Statements Recuperati', ['count' => $statements->count()]);

            // Raggruppa per PurchId
            $statements = $statements->groupBy(function ($statement) use ($upload) {
                $period = $upload->process_date->format('Ym'); // Modificato da 'ym' a 'Ym'
                return 'PUB' . $period . ($statement->publisher->axData->ax_vend_id ?? '');
            });

            Log::channel('ax_export')->debug('Totale Gruppi PurchId Dopo il Raggruppamento', ['count' => $statements->count()]);

            // Itera attraverso ogni gruppo PurchId
            foreach ($statements as $purchId => $groupedStatements) {
                Log::channel('ax_export')->debug('Elaborazione PurchId', [
                    'PurchId' => $purchId, 
                    'Numero Statements' => $groupedStatements->count()
                ]);
                
                // Inizializza il numero di riga per questo gruppo PurchId
                $lineNumber = 1;
                
                foreach ($groupedStatements as $statement) {
                    Log::channel('ax_export')->debug('Elaborazione Statement', [
                        'Statement ID' => $statement->id,
                        'PurchId' => $purchId,
                        'Numero Riga' => $lineNumber
                    ]);

                    if (!$statement->publisher->axData) {
                        Log::channel('ax_export')->error('AxExportService: Dati AX mancanti per Statement', [
                            'Statement ID' => $statement->id
                        ]);
                        continue;
                    }

                    $row = $this->formatRow($statement, $upload, $lineNumber);
                    if ($row) {
                        // Imposta il periodo
                        $row[0] = $upload->process_date->format('ym');

                        // Formatta i valori numerici
                        $formattedRow = array_map(function ($value) {
                            if (is_numeric($value)) {
                                // Converte il valore in float per il confronto
                                $floatValue = (float)$value;
                                // Verifica se il numero è effettivamente un intero
                                if ($floatValue == floor($floatValue)) {
                                    return (int)$floatValue;
                                }
                                // Se ha decimali, mantiene due decimali
                                return number_format($floatValue, 2, '.', '');
                            }
                            return $value;
                        }, $row);

                        fwrite($handle, implode("\t", $formattedRow) . PHP_EOL);
                        
                        // Incrementa il numero di riga solo dopo una scrittura riuscita
                        $lineNumber++;
                    } else {
                        Log::channel('ax_export')->warning('AxExportService: Riga non valida, saltata.', [
                            'statement_id' => $statement->id,
                        ]);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::channel('ax_export')->error('AxExportService: Errore durante l\'elaborazione degli statements.', [
                'error' => $e->getMessage(),
            ]);
            fclose($handle);
            if (file_exists($path)) {
                unlink($path);
            }
            throw $e;
        }

        fclose($handle);
        Log::channel('ax_export')->info('AxExportService: File TSV generato con successo.', ['fileName' => $fileName]);

        $upload->ax_export_path = 'private/exports/' . $fileName;
        $upload->save();

        return $fileName;
    }

    protected function formatRow($statement, $upload, $lineNumber)
    {
        Log::channel('ax_export')->debug('formatRow: Inizio formattazione riga.', [
            'Statement ID' => $statement->id,
            'LineNumber' => $lineNumber
        ]);

        try {
            $publisher = $statement->publisher;
            Log::channel('ax_export')->debug('formatRow: Publisher ottenuto.', [
                'Publisher ID' => $publisher->id,
                'Publisher Name' => $publisher->legal_name
            ]);

            $axData = $publisher->axData;

            if (!$axData) {
                Log::channel('ax_export')->error('formatRow: Dati AX mancanti per il publisher.', [
                    'Publisher ID' => $publisher->id
                ]);
                return null;
            }

            Log::channel('ax_export')->debug('formatRow: Dati AX trovati.', [
                'ax_vend_account' => $axData->ax_vend_account,
                'ax_vend_id' => $axData->ax_vend_id
            ]);

            $period = $upload->process_date->format('Ym'); // Modificato da 'Ym' a 'Ym'
            Log::channel('ax_export')->debug('formatRow: Calcolato il periodo.', ['Period' => $period]);

            $formattedRow = [
                $period,                             // Period
                $axData->ax_vend_account,           // VendAccount
                'PUB' . $period . ($axData->ax_vend_id ?? ''), // PurchId
                $lineNumber,                        // LineNumber
                $publisher->legal_name,             // VendName
                $statement->subPublisher->ax_name,  // SiteUrl
                null,                               // Address
                null,                               // Street
                null,                               // ZipCode
                null,                               // City
                null,                               // State
                $axData->country_id,                // CountryRegionId
                $axData->vend_group,                // VendGroup
                $axData->party_type,                // PartyType
                $publisher->vat_number,             // VATNum
                null,                               // FiscalCode
                null,                               // Payment
                null,                               // PaymMode
                $axData->tax_withhold_calculate,    // TaxWithholdCalculate
                $axData->item_id,                   // ItemId
                1,                                  // Qty
                $statement->total_amount,           // PurchPrice
                null,                               // BirthPlace
                null,                               // BirthDate
                null,                               // BirthCounty
                null,                               // Gender
                $axData->email,                     // Email
                null,                               // Phone
                $publisher->iban,                   // BankIBAN
                null,                               // BankSWIFTCode
                $axData->cost_profit_center,        // CostProfitCenter
                $statement->subPublisher->channel_detail // ChannelDetail
            ];

            Log::channel('ax_export')->debug('formatRow: Righe formattate correttamente.', [
                'Formatted Row' => $formattedRow
            ]);

            return $formattedRow;
        } catch (\Exception $e) {
            Log::channel('ax_export')->error('formatRow: Eccezione durante la formattazione della riga.', [
                'Statement ID' => $statement->id,
                'Error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
