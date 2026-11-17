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

        $org = \App\Models\ClientOrganization::firstOrCreate(
            ['name' => 'Acme Technologies Pvt Ltd'],
            [
                'gstin'         => '27AABCU9603R1ZM',
                'contact_phone' => '+91 98765 43210',
                'address'       => 'Level 5, Cyber Tower, Hitech City, Hyderabad - 500081',
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => env('CLIENT_EMAIL', 'client@orbitverify.com')],
            [
                'name'                    => 'Rahul Sharma (HR Manager)',
                'password'               => env('CLIENT_PASSWORD', 'password'),
                'role'                   => 'organization',
                'is_verified'            => true,
                'password_reset_required'=> false,
                'client_organization_id' => $org->id,
            ]
        );
    }
}
