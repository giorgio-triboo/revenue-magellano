<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class UsersExport implements FromQuery, WithHeadings, WithMapping, WithEvents
{
    protected $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
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

                // Auto-size columns
                foreach(range('A','V') as $column) {
                    $event->sheet->getColumnDimension($column)->setAutoSize(true);
                }
            }
        ];
    }
}