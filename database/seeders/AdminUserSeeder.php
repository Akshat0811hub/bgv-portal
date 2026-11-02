<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@orbitverify.com')],
            [
                'name'                    => 'Master Admin',
                'password'               => env('ADMIN_PASSWORD', 'password'),
                'role'                   => 'admin',
                'is_verified'            => true,
                'password_reset_required'=> false,
            ]
        );
    }
}
