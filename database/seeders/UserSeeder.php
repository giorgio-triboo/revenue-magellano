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
                'email_verified'=> true,
                'email_verified_at'=> now(),
                'privacy_accepted'=> true,
                'privacy_verified_at'=> now(),
                'terms_accepted'=> true,
                'terms_verified_at'=> now(),
                'terms_version'=> 1.0,
                'can_receive_email'=> true,
                'is_validated'=> true,
                'failed_login_attempts'=> 0,
                'locked_until'=> null,
            ],
            
            // Aggiungi altri utenti fissi qui
        ];

        foreach ($fixedUsers as $user) {
            User::create($user);
        }
    }
}
