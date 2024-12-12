<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'name' => 'Administrator',
                'code' => 'admin',
                'description' => 'Full system access'
            ],
            [
                'name' => 'Publisher',
                'code' => 'publisher',
                'description' => 'Publisher access'
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}