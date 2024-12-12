<?php

namespace App\Exports;

use App\Models\Publisher;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;


class PublisherExport implements WithMultipleSheets
{
    protected $publishers;

    public function __construct($publishers)
    {
        $this->publishers = $publishers;
    }

    public function sheets(): array
    {
        return [
            'Publishers' => new PublisherDataSheet($this->publishers),
            'SubPublishers' => new SubPublisherDataSheet($this->publishers),
            'AX Data' => new AxDataSheet($this->publishers),
            'Users' => new UserDataSheet($this->publishers),
        ];
    }
}

class PublisherDataSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents
{
    protected $publishers;

    public function __construct($publishers)
    {
        $this->publishers = $publishers;
    }

    public function collection()
    {
        return $this->publishers;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Partita IVA',
            'Nome Azienda',
            'Ragione Sociale',
            'Provincia',
            'Città',
            'CAP',
            'IBAN',
            'SWIFT',
            'Sito Web',
            'Stato',
            'AX Vendor Account',
            'AX Vendor ID',
            'AX Email',
            'AX Country Region ID',
            'Data Creazione',
            'Data Modifica',
            'Data Cancellazione'
        ];
    }

    public function map($publisher): array
    {
        return [
            $publisher->id,
            $publisher->vat_number,
            $publisher->company_name,
            $publisher->legal_name,
            $publisher->county,
            $publisher->city,
            $publisher->postal_code,
            $publisher->iban,
            $publisher->swift,
            $publisher->website,
            $publisher->is_active ? 'Attivo' : 'Non Attivo',
            $publisher->ax_vend_account,
            $publisher->ax_vend_id,
            $publisher->ax_email,
            $publisher->ax_countryregion_Id,
            $publisher->created_at,
            $publisher->updated_at,
            $publisher->deleted_at
        ];
    }

    public function title(): string
    {
        return 'Publishers';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Formattazione header
                $event->sheet->getStyle('A1:R1')->applyFromArray([
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
                foreach (range('A', 'R') as $column) {
                    $event->sheet->getColumnDimension($column)->setAutoSize(true);
                }
            }
        ];
    }

}

class SubPublisherDataSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents
{
    protected $publishers;

    public function __construct($publishers)
    {
        $this->publishers = $publishers;
    }

    public function collection()
    {
        return $this->publishers->load('subPublishers')->flatMap->subPublishers;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Publisher ID',
            'Nome Publisher',
            'Nome Visualizzato',
            'Gruppo Fatturazione',
            'Nome AX',
            'Dettaglio Canale',
            'Note',
            'Stato',
            'Primario',
            'Data Creazione',
            'Data Modifica',
            'Data Cancellazione'
        ];
    }

    public function map($subPublisher): array
    {
        return [
            $subPublisher->id,
            $subPublisher->publisher_id,
            $subPublisher->publisher->company_name ?? 'N/A',
            $subPublisher->display_name,
            $subPublisher->invoice_group,
            $subPublisher->ax_name,
            $subPublisher->channel_detail,
            $subPublisher->notes,
            $subPublisher->is_active ? 'Attivo' : 'Non Attivo',
            $subPublisher->is_primary ? 'Sì' : 'No',
            $subPublisher->created_at,
            $subPublisher->updated_at,
            $subPublisher->deleted_at
        ];
    }

    public function title(): string
    {
        return 'SubPublishers';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
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

                foreach (range('A', 'M') as $column) {
                    $event->sheet->getColumnDimension($column)->setAutoSize(true);
                }
            }
        ];
    }

}

class AxDataSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents
{
    protected $publishers;

    public function __construct($publishers)
    {
        $this->publishers = $publishers;
    }

    public function collection()
    {
        return $this->publishers->load('axData');
    }

    public function headings(): array
    {
        return [
            'ID',
            'ID Publisher',
            'Nome Publisher',
            'AX Vendor Account',
            'AX Vendor ID',
            'ID Paese',
            'Gruppo Vendor',
            'Tipo Entità',
            'Calcolo Ritenuta',
            'ID Item',
            'Email',
            'Centro Costo/Profitto',
            'Data Creazione',
            'Data Modifica',
            'Data Cancellazione'
        ];
    }

    public function map($publisher): array
    {
        $axData = $publisher->axData;
        return [
            $axData->id ?? '',
            $publisher->id,
            $publisher->company_name,
            $axData->ax_vend_account ?? '',
            $axData->ax_vend_id ?? '',
            $axData->country_id ?? '',
            $axData->vend_group ?? '',
            $axData->party_type ?? '',
            $axData->tax_withhold_calculate ?? '',
            $axData->item_id ?? '',
            $axData->email ?? '',
            $axData->cost_profit_center ?? '',
            $axData->created_at ?? '',
            $axData->updated_at ?? '',
            $axData->deleted_at ?? ''
        ];
    }

    public function title(): string
    {
        return 'AX Data';
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:O1')->applyFromArray([
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

                foreach (range('A', 'O') as $column) {
                    $event->sheet->getColumnDimension($column)->setAutoSize(true);
                }
            }
        ];
    }

}

class UserDataSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents
{
    protected $publishers;

    public function __construct($publishers)
    {
        $this->publishers = $publishers;
    }

    public function collection()
    {
        return $this->publishers->load('users.role')->flatMap->users;
    }

    public function headings(): array
    {
        return [
            'ID',
            'ID Ruolo',
            'Nome',
            'Cognome',
            'Email',
            'ID Publisher',
            'Stato',
            'Token Attivazione',
            'Email Verificata',
            'Data Verifica Email',
            'Privacy Accettata',
            'Data Verifica Privacy',
            'Termini Accettati',
            'Data Verifica Termini',
            'Versione Termini',
            'Può Ricevere Email',
            'Validato',
            'Tentativi Login Falliti',
            'Bloccato Fino',
            'Data Creazione',
            'Data Modifica',
            'Data Cancellazione'
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->role_id,
            $user->first_name,
            $user->last_name,
            $user->email,
            $user->publisher_id,
            $user->is_active ? 'Attivo' : 'Non Attivo',
            $user->activation_token,
            $user->email_verified ? 'Sì' : 'No',
            $user->email_verified_at,
            $user->privacy_accepted ? 'Sì' : 'No',
            $user->privacy_verified_at,
            $user->terms_accepted ? 'Sì' : 'No',
            $user->terms_verified_at,
            $user->terms_version,
            $user->can_receive_email ? 'Sì' : 'No',
            $user->is_validated ? 'Sì' : 'No',
            $user->failed_login_attempts,
            $user->locked_until,
            $user->created_at,
            $user->updated_at,
            $user->deleted_at
        ];
    }

    public function title(): string
    {
        return 'Users';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:V1')->applyFromArray([
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

                foreach (range('A', 'V') as $column) {
                    $event->sheet->getColumnDimension($column)->setAutoSize(true);
                }
            }
        ];
    }

}