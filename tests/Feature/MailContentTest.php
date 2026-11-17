<?php

namespace Tests\Feature;

use App\Mail\CandidateInviteMail;
use App\Mail\UserCredentialsMail;
use App\Models\Cases;
use App\Models\ClientOrganization;
use App\Models\User;
use Tests\TestCase;

class MailContentTest extends TestCase
{
    public function test_notification_renders_without_an_upload_request(): void
    {
        $case = new Cases(['candidate_name' => 'Jane', 'case_number' => 'OV-TEST']);
        $case->setRelation('clientOrganization', new ClientOrganization(['name' => 'Acme']));
        $case->setRelation('checks', collect());
        $case->setRelation('uploadToken', null);
        $mail = new CandidateInviteMail($case, 'test-token', requestDocuments: false);
        $mail->assertSeeInHtml('No upload is requested');
        $mail->assertDontSeeInHtml('Upload Your Documents Securely');
        $mail->assertSeeInText('OV-TEST');
        $mail->assertDontSeeInText('/upload/');
    }

    public function test_credentials_include_plain_text_login_details(): void
    {
        $mail = new UserCredentialsMail(new User(['name' => 'Jane', 'email' => 'jane@example.test']), 'temporary123');
        $mail->assertSeeInHtml('jane@example.test');
        $mail->assertSeeInText('temporary123');
        $mail->assertSeeInText(route('login'));
    }
}
