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
                'first_name'=> 'admin',
                'last_name'=> 'admin',
                'email'=> 'admin@admin.it',
                'password'=> 'admin',
                'is_active'=> 1,
                'activation_token'=> null,
                'email_verified'=> 1,
                'email_verified_at'=> null,
                'privacy_accepted'=> 1,
                'privacy_verified_at'=> null,
                'terms_accepted'=> 1,
                'terms_verified_at'=> null,
                'terms_version'=> 1.0,
                'can_receive_email'=> 1,
                'is_validated'=> 1,
                'failed_login_attempts'=> null,
                'locked_until'=> null,
            ],
            
            // Aggiungi altri utenti fissi qui
        ];

        foreach ($fixedUsers as $user) {
            User::create($user);
        }
    }
}
