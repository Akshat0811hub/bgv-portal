<?php 
$org = \App\Models\ClientOrganization::firstOrCreate(['name' => 'Orbitouch Outsourcing Pvt Ltd']);
$admin = \App\Models\User::where('role', 'admin')->first();

$m = \App\Models\Cases::updateOrCreate(
    ['case_number' => 'OV-2026-00012'],
    [
        'client_organization_id' => $org->id,
        'created_by' => $admin->id,
        'status' => 'Pending',
        'candidate_name' => 'Monica',
        'candidate_email' => 'shivani@orbitverify.com',
        'candidate_phone' => '+91 98765 00012',
        'candidate_designation' => 'Associate',
        'case_start_date' => now()->toDateString(),
    ]
);

foreach (['address_verification', 'education_verification', 'national_id_verification'] as $slug) {
    $m->checks()->firstOrCreate(['check_type' => $slug], ['status' => 'Pending']);
}

$m->uploadToken()->updateOrCreate(
    ['case_id' => $m->id],
    [
        'token' => 'monica-upload-token-2026',
        'used' => false,
        'expires_at' => now()->addDays(7),
    ]
);
echo "Monica case ready: ID=" . $m->id . "\n";
