<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Inserimento di utenti con dati fissi
        $fixedUsers = [
            [
                'role_id' => 1,
                'publisher_id' => 1,
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin',
                'email_verified_at' => now(),
                'password' => ('admin'),
                'remember_token' => '1',
                'is_active' => now(),
                'activation_token' => now(),
            ],

            [
                'role_id' => 1,
                'publisher_id' => 1,
                'first_name' => 'Publisher',
                'last_name' => 'User',
                'email' => 'publisher',
                'email_verified_at' => now(),
                'password' => ('publisher'),
                'remember_token' => '1',
                'is_active' => now(),
                'activation_token' => now(),
            ],
            
            // Aggiungi altri utenti fissi qui
        ];

        foreach ($fixedUsers as $user) {
            User::create($user);
        }
    }
}
