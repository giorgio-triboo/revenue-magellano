<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            RoleSeeder::class,          // RoleSeeder viene eseguito per primo
            UserSeeder::class,
            // PublisherSeeder::class,
            // SubPublisherSeeder::class,
        ]);
    }
}
