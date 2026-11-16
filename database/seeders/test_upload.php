<?php 
$request = \Illuminate\Http\Request::create('/upload/demo-candidate-upload-token-2026-orbitverify', 'POST', [
    'check_data' => [
        'employment_verification' => [
            'Current Employment' => [
                'organization' => 'Infosys Ltd',
                'designation' => 'Associate Consultant',
                'tenure' => '2022 - 2026',
            ],
            'Previous Employment' => [
                'organization' => '',
                'designation' => '',
                'tenure' => '',
            ],
        ],
    ],
]);
$ctrl = app(\App\Http\Controllers\Candidate\UploadController::class);
$res = $ctrl->store($request, 'demo-candidate-upload-token-2026-orbitverify');
dump(get_class($res), $res instanceof \Illuminate\Http\RedirectResponse ? $res->getSession()->get('errors')?->all() : 'VIEW RETURNED!');
