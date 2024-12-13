<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Publisher;

class PublisherSeeder extends Seeder
{
    public function run(): void
    {
        // Inserimento di publisher con dati fissi
        $fixedPublishers = [
            [
                'vat_number' => 'IT12345678901',
                'company_name' => 'T-Direct',
                'legal_name' => 'T-Direct Srl',
                'county' => 'Milano',
                'city' => 'Milano',
                'postal_code' => '20126',
                'iban' => 'IT60X0542811101000000123456',
                'swift' => 'SWIFT12345',
                'website' => 'www.triboo.direct',
                'is_active' => true,
            ],
        ];

        foreach ($fixedPublishers as $publisher) {
            Publisher::create($publisher);
        }
    }
}