<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubPublisher;

class SubPublisherSeeder extends Seeder
{
    public function run(): void
    {
        
        $fixedSubpulisher = [
            [
                'publisher_id' => 1,
                'display_name' => 'HTML',
                'invoice_group' => 'T-Direct',
                'notes' => 'Test',
                'is_active' => 1,
                'is_primary' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'publisher_id' => 1,
                'display_name' => 'PMI',
                'invoice_group' => 'T-Direct',
                'notes' => 'Test',
                'is_active' => 1,
                'is_primary' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'publisher_id' => 1,
                'display_name' => 'WSI',
                'invoice_group' => 'T-Direct',
                'notes' => 'Test',
                'is_active' => 1,
                'is_primary' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'publisher_id' => 2,
                'display_name' => 'JTT',
                'invoice_group' => 'T-Mediahouse',
                'notes' => 'Test',
                'is_active' => 1,
                'is_primary' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'publisher_id' => 2,
                'display_name' => 'NLA',
                'invoice_group' => 'T-Mediahouse',
                'notes' => 'Test',
                'is_active' => 1,
                'is_primary' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'publisher_id' => 2,
                'display_name' => 'TBM',
                'invoice_group' => 'T-Mediahouse',
                'notes' => 'Test',
                'is_active' => 1,
                'is_primary' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            
        ];
        
    }
}

