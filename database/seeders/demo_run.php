<?php 
$org = \App\Models\ClientOrganization::firstOrCreate(
    ['name' => 'Acme Technologies Pvt Ltd'],
    ['gstin' => '27AABCU9603R1ZM', 'contact_phone' => '+91 98765 43210', 'address' => 'Level 5, Cyber Tower, Hitech City, Hyderabad - 500081']
);

$clientUser = \App\Models\User::updateOrCreate(
    ['email' => 'client@orbitverify.com'],
    [
        'name' => 'Rahul Sharma (HR Manager)',
        'password' => 'password',
        'role' => 'organization',
        'is_verified' => true,
        'password_reset_required' => false,
        'client_organization_id' => $org->id,
    ]
);

if (\App\Models\Cases::count() === 0) {
    // Case 1: Completed Green
    $c1 = \App\Models\Cases::create([
        'case_number' => 'OV-2026-00001',
        'client_organization_id' => $org->id,
        'created_by' => $clientUser->id,
        'status' => 'Completed - Green (Clear)',
        'candidate_name' => 'Aarav Mehta',
        'candidate_email' => 'aarav.mehta@example.com',
        'candidate_phone' => '+91 98111 22334',
        'candidate_father_name' => 'Rajesh Mehta',
        'candidate_designation' => 'Senior Software Engineer',
        'case_start_date' => now()->subDays(4)->toDateString(),
        'report_closed_date' => now()->toDateString(),
        'admin_notes' => 'All identity, education, and employment records were verified directly with the issuing authorities and found clear.',
    ]);
    $c1->checks()->create([
        'check_type' => 'national_id_verification',
        'status' => 'Clear',
        'form_data' => ['_self' => ['aadhaar' => 'XXXX-XXXX-8492', 'pan' => 'ABCDE1234F']],
        'verified_data' => ['_self' => ['aadhaar' => 'XXXX-XXXX-8492 (Verified with UIDAI)', 'pan' => 'ABCDE1234F (Active & Matched)']],
        'comments' => 'Both PAN and Aadhaar details match government records.',
        'completed_at' => now()->subDay(),
    ]);
    $c1->checks()->create([
        'check_type' => 'education_verification',
        'status' => 'Clear',
        'form_data' => ['_self' => ['university' => 'IIT Delhi', 'degree' => 'B.Tech Computer Science', 'year' => '2020']],
        'verified_data' => ['_self' => ['university' => 'IIT Delhi', 'degree' => 'B.Tech Computer Science (First Division)', 'year' => '2020']],
        'comments' => 'Confirmed by Registrar Office via official email.',
        'completed_at' => now(),
    ]);

    // Case 2: In Progress / Insufficiency
    $c2 = \App\Models\Cases::create([
        'case_number' => 'OV-2026-00002',
        'client_organization_id' => $org->id,
        'created_by' => $clientUser->id,
        'status' => 'Insufficiency',
        'candidate_name' => 'Priya Nair',
        'candidate_email' => 'priya.nair@example.com',
        'candidate_phone' => '+91 98222 33445',
        'candidate_father_name' => 'Suresh Nair',
        'candidate_designation' => 'Product Manager',
        'case_start_date' => now()->subDays(2)->toDateString(),
    ]);
    $c2->checks()->create([
        'check_type' => 'employment_verification',
        'status' => 'Insufficiency',
        'form_data' => ['Current Employment' => ['organization' => 'Infosys Ltd', 'designation' => 'Associate Consultant', 'tenure' => '2022 - 2026']],
        'comments' => 'Insufficiency Raised: Relieving letter or last 3 months payslips required to verify tenure.',
    ]);
    $c2->uploadToken()->create([
        'token' => 'demo-candidate-upload-token-2026-orbitverify',
        'used' => false,
        'expires_at' => now()->addDays(7),
    ]);
}
echo "Demo data seeded successfully!\n";
