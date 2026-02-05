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

        // Crea la directory exports se non esiste (fopen non crea le directory padre)
        $exportDir = storage_path('app/private/exports');
        if (!is_dir($exportDir)) {
            if (!mkdir($exportDir, 0755, true)) {
                Log::channel('ax_export')->error('AxExportService: Impossibile creare la directory exports.', ['path' => $exportDir]);
                throw new \Exception('Impossibile creare la directory di export');
            }
        }

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
                $period = $upload->process_date->format('Ym');
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

                        // Formatta i valori numerici e gestisce i valori nulli
                        $formattedRow = array_map(function ($value) {
                            if ($value === null) {
                                return ' ';  // Sostituisce null con spazio vuoto
                            }
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
                            return $value === '' ? ' ' : $value; // Sostituisce stringhe vuote con spazio
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
                $period,                            // Period (FIELD_TABLE_HEADER)
                $axData->ax_vend_account,           // VendAccount (FIELD_TABLE_HEADER)
                'PUB' . $period . ($axData->ax_vend_id ?? ''), // PurchId (FIELD_TABLE_HEADER_ADDITIONAL_HEADER_KEY_ROW)
                $lineNumber,                        // LineNumber (FIELD_TABLE_ROW)
                $publisher->legal_name,             // VendName (FIELD_TABLE_HEADER)
                $statement->subPublisher->ax_name,  // SiteUrl (FIELD_TABLE_HEADER_ROW)
                null,                               // Address (FIELD_TABLE_HEADER)
                $publisher->address,                // Street (FIELD_TABLE_HEADER)
                $publisher->postal_code,            // ZipCode (FIELD_TABLE_HEADER)
                $publisher->city,                   // City (FIELD_TABLE_HEADER)
                null,                               // State (FIELD_TABLE_HEADER)
                $publisher->state_id,               // CountryRegionId (FIELD_TABLE_HEADER)
                $axData->vend_group,                // VendGroup (FIELD_TABLE_HEADER)
                $axData->party_type,                // PartyType (FIELD_TABLE_HEADER)
                $axData->ax_vat_number,             // VATNum (FIELD_TABLE_HEADER)
                null,                               // FiscalCode (FIELD_TABLE_HEADER)
                $axData->payment,                   // Payment (FIELD_TABLE_HEADER)
                $axData->payment_mode,              // PaymMode (FIELD_TABLE_HEADER)
                $axData->tax_withhold_calculate,    // TaxWithholdCalculate (FIELD_TABLE_HEADER)
                $axData->item_id,                   // ItemId (FIELD_TABLE_ROW)
                1,                                  // Qty (FIELD_TABLE_ROW)
                $statement->total_amount,           // PurchPrice (FIELD_TABLE_ROW)
                null,                               // BirthPlace (FIELD_TABLE_HEADER)
                null,                               // BirthDate (FIELD_TABLE_HEADER)
                null,                               // BirthCounty (FIELD_TABLE_HEADER)
                null,                               // Gender (FIELD_TABLE_HEADER)
                $axData->email,                     // Email (FIELD_TABLE_HEADER)
                null,                               // Phone (FIELD_TABLE_HEADER)
                $publisher->iban,                   // BankIBAN (FIELD_TABLE_HEADER)
                $publisher->swift,                  // BankSWIFTCode (FIELD_TABLE_HEADER)
                $axData->cost_profit_center,        // CostProfitCenter (FIELD_TABLE_HEADER_ROW)
                $statement->subPublisher->channel_detail, // ChannelDetail (FIELD_TABLE_HEADER_ROW)
                $axData->tax_item_group,            // TaxItemGroup (FIELD_TABLE_ROW)
                null,                               // VendorOrderReference (FIELD_TABLE_HEADER)
                $axData->sales_tax_group,           // SalesTaxGroupCode (FIELD_TABLE_HEADER)
                $axData->number_sequence_group_id,  // NumberSequenceGroupId (FIELD_TABLE_HEADER)
                $axData->currency_code,             // CurrencyCode (FIELD_TABLE_HEADER)
                null,                               // Al6CompetenceDateFrom (FIELD_TABLE_HEADER)
                null,                               // Al6CompetenceDateTo (FIELD_TABLE_HEADER)
                null,                               // Al6Locked (FIELD_TABLE_HEADER)
                null,                               // AccountingDate (FIELD_TABLE_ADDITIONAL_HEADER)
                null,                               // VendorPostingProfileId (FIELD_TABLE_ADDITIONAL_HEADER)
                null,                               // Al6EInvoiceNumber (FIELD_TABLE_ADDITIONAL_HEADER)
                null,                               // Al6EInvoiceYear (FIELD_TABLE_ADDITIONAL_HEADER)
                null,                               // CostProfitCenterCode (FIELD_TABLE_ROW)
                null,                               // PartnerBrand (FIELD_TABLE_ROW)
                null                                // Geography (FIELD_TABLE_ROW)
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
