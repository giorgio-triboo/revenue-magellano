<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Collection;

class StatementsExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    protected $statements;

    public function __construct(Collection $statements)
    {
        $this->statements = $statements;
    }

    public function collection()
    {
        return $this->statements;
    }

    public function headings(): array
    {
        return [
            'Publisher',
            'Sub Publisher',
            'Campaign',
            'Anno',
            'Mese',
            'Anno Competenza',
            'Mese Competenza',
            'Tipo Revenue',
            'Quantità',
            'Importo Unitario',
            'Importo Totale',
            'Note',
            'Data Invio'
        ];
    }

    public function map($statement): array
    {
        return [
            $statement->publisher->company_name ?? 'N/A',
            $statement->subPublisher->display_name ?? 'N/A',
            $statement->campaign_name,
            $statement->statement_year,
            $statement->statement_month,
            $statement->competence_year,
            $statement->competence_month,
            strtoupper($statement->revenue_type),
            $statement->validated_quantity,
            $statement->pay_per_unit,
            $statement->total_amount,
            $statement->notes,
            $statement->sending_date ?? ''
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Formattazione header
                $event->sheet->getStyle('A1:M1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'E2E8F0'
                        ]
                    ]
                ]);

                // Auto-size columns
                foreach (range('A', 'M') as $column) {
                    $event->sheet->getColumnDimension($column)->setAutoSize(true);
                }

                // Formattazione colonne numeriche
                $lastRow = $event->sheet->getHighestRow();
                // Formatta la colonna delle quantità (J) solo con numeri interi
                $event->sheet->getStyle('I2:I' . $lastRow)->getNumberFormat()
                    ->setFormatCode('#,##0');
                // Formatta le colonne degli importi (K-L) con due decimali
                $event->sheet->getStyle('J2:K' . $lastRow)->getNumberFormat()
                    ->setFormatCode('#,##0.00');
            }
        ];
    }
}