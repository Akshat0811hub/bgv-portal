<?php

namespace Tests\Feature;

use App\Models\Cases;
use App\Models\ClientOrganization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DynamicCaseFieldsTest extends TestCase
{
    use RefreshDatabase;

    private function schema(): array
    {
        return ['Current Employment' => [
            ['name' => 'company', 'label' => 'Legal employer name', 'type' => 'textarea', 'section_label' => 'Most recent employer', 'required' => true, 'placeholder' => 'Registered company name', 'help' => 'Use the name on your appointment letter.'],
            ['name' => 'salary', 'label' => 'Annual salary', 'type' => 'number', 'required' => true],
            ['name' => 'contact', 'label' => 'HR email', 'type' => 'email'],
            ['name' => 'mode', 'label' => 'Employment arrangement', 'type' => 'select', 'options' => ['Full time', 'Contract'], 'required' => true],
            ['name' => 'consent', 'label' => 'Confirm employment details', 'type' => 'checkbox', 'required' => true],
            ['name' => '_file_current', 'label' => 'Appointment evidence', 'type' => 'file', 'required' => true],
        ]];
    }

    private function makeCase(): Cases
    {
        Storage::fake('local');
        $org = ClientOrganization::create(['name' => 'Custom fields client']);
        $client = User::create(['name' => 'Client', 'email' => 'custom@example.test', 'password' => 'password123', 'role' => 'organization', 'is_verified' => true, 'client_organization_id' => $org->id]);
        $case = Cases::create(['case_number' => Cases::generateCaseNumber(), 'client_organization_id' => $org->id, 'created_by' => $client->id, 'candidate_name' => 'Candidate', 'candidate_email' => 'candidate@example.test', 'status' => 'Pending', 'case_start_date' => today()]);
        $case->checks()->create(['check_type' => 'employment_verification', 'status' => 'Pending', 'custom_schema' => $this->schema()]);
        $case->uploadToken()->create(['token' => 'dynamic-case-fields', 'expires_at' => now()->addDay()]);

        return $case->fresh('checks', 'uploadToken');
    }

    public function test_input_controls_use_renamed_labels_with_stable_data_names_and_expanded_types(): void
    {
        $html = view('components.check-fields', ['slug' => 'employment_verification', 'schema' => $this->schema(), 'data' => ['Current Employment' => ['salary' => '0', 'mode' => 'Contract']], 'enforceRequired' => true])->render();
        $this->assertStringContainsString('Most recent employer', $html);
        $this->assertStringContainsString('Legal employer name', $html);
        $this->assertStringContainsString('check_data[employment_verification][Current Employment][company]', $html);
        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('type="number"', $html);
        $this->assertStringContainsString('type="email"', $html);
        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('Use the name on your appointment letter.', $html);
        $this->assertStringContainsString('value="0"', $html);
    }

    public function test_comparisons_hide_removed_custom_fields_without_losing_zero_or_checkbox_values(): void
    {
        $schema = ['_self' => [['name' => 'enabled', 'label' => 'Consent', 'type' => 'checkbox', 'section_label' => 'Confirmation'], ['name' => 'count', 'label' => 'Count', 'type' => 'number']]];
        $data = ['_self' => ['enabled' => '0', 'count' => '0', 'removed' => 'HIDDEN LEGACY VALUE'], 'Old section' => ['old' => 'HIDDEN SECTION']];
        foreach (['components.check-comparison', 'components.check-summary'] as $component) {
            $html = view($component, ['schema' => $schema, 'stated' => $data, 'verified' => [], 'data' => $data, 'includeUnmapped' => false])->render();
            $this->assertStringContainsString('Confirmation', $html);
            $this->assertStringContainsString('No', $html);
            $this->assertStringNotContainsString('HIDDEN LEGACY VALUE', $html);
            $this->assertStringNotContainsString('HIDDEN SECTION', $html);
        }
    }

    public function test_candidate_validation_checks_required_types_options_and_files_without_consuming_token(): void
    {
        $case = $this->makeCase();
        $response = $this->post(route('candidate.upload.store', $case->uploadToken->token), ['check_data' => ['employment_verification' => ['Current Employment' => ['contact' => 'not-an-email', 'mode' => 'Unknown', 'consent' => '0']]]]);
        $response->assertSessionHasErrors([
            'check_data.employment_verification.Current Employment.company',
            'check_data.employment_verification.Current Employment.salary',
            'check_data.employment_verification.Current Employment.contact',
            'check_data.employment_verification.Current Employment.mode',
            'check_data.employment_verification.Current Employment.consent',
            'check_files.employment_verification._file_current',
        ]);
        $this->assertFalse($case->uploadToken->fresh()->used);
        $this->assertNull($case->checks->first()->fresh()->form_data);
    }

    public function test_candidate_can_submit_all_custom_types_and_multiple_required_documents(): void
    {
        $case = $this->makeCase();
        $this->post(route('candidate.upload.store', $case->uploadToken->token), [
            'check_data' => ['employment_verification' => ['Current Employment' => ['company' => "Example Ltd\nRegistered office", 'salary' => '0', 'contact' => 'hr@example.test', 'mode' => 'Contract', 'consent' => '1']]],
            'check_files' => ['employment_verification' => ['_file_current' => [UploadedFile::fake()->create('letter.pdf', 10, 'application/pdf'), UploadedFile::fake()->create('contract.pdf', 10, 'application/pdf')]]],
        ])->assertOk()->assertSee('Documents Submitted');
        $check = $case->checks->first()->fresh();
        $this->assertSame('0', $check->form_data['Current Employment']['salary']);
        $this->assertSame('Contract', $check->form_data['Current Employment']['mode']);
        $this->assertSame('1', $check->form_data['Current Employment']['consent']);
        $this->assertSame(2, $check->documents()->count());
    }

    public function test_existing_file_and_prefilled_values_satisfy_required_fields_on_a_later_submission(): void
    {
        $case = $this->makeCase();
        $check = $case->checks->first();
        $check->update(['form_data' => ['Current Employment' => ['company' => 'Existing company', 'salary' => '100', 'mode' => 'Full time', 'consent' => '1']]]);
        $check->documents()->create(['case_id' => $case->id, 'original_name' => 'existing-proof.pdf', 'stored_path' => 'cases/existing.pdf', 'mime_type' => 'application/pdf', 'file_size' => 10, 'field_key' => '_file_current', 'uploaded_by_role' => 'client']);
        $this->post(route('candidate.upload.store', $case->uploadToken->token), ['check_data' => ['employment_verification' => ['Current Employment' => ['contact' => 'new-hr@example.test']]]])->assertOk()->assertSee('Documents Submitted');
        $this->assertSame('Existing company', $check->fresh()->form_data['Current Employment']['company']);
        $this->assertSame(1, $check->documents()->count());
    }
}
