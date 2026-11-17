<?php

namespace Tests\Feature;

use App\Models\CaseCheck;
use App\Models\Cases;
use App\Models\Check;
use App\Models\ClientOrganization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SchemaCustomizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $client;

    private Cases $case;

    private CaseCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $org = ClientOrganization::create(['name' => 'Schema Client']);
        $this->admin = User::create([
            'name' => 'Administrator', 'email' => 'schema-admin@example.test', 'password' => 'password123',
            'role' => 'admin', 'is_verified' => true,
        ]);
        $this->client = User::create([
            'name' => 'HR', 'email' => 'schema-client@example.test', 'password' => 'password123',
            'role' => 'organization', 'is_verified' => true, 'client_organization_id' => $org->id,
        ]);
        $this->case = Cases::create([
            'case_number' => 'OV-SCHEMA-TEST', 'client_organization_id' => $org->id,
            'created_by' => $this->client->id, 'candidate_name' => 'Candidate',
            'candidate_email' => 'candidate@example.test', 'status' => 'Pending', 'case_start_date' => today(),
        ]);
        Check::create([
            'name' => 'Employment Verification', 'slug' => 'employment_verification', 'active' => true,
            'schema' => ['Current Employment' => [
                ['name' => 'organization', 'label' => 'Organization Name'],
                ['name' => '_file_current', 'label' => 'Current Employment Proof', 'type' => 'file'],
            ]],
        ]);
        $this->check = $this->case->checks()->create([
            'check_type' => 'employment_verification', 'status' => 'In Progress',
            'form_data' => ['Current Employment' => ['organization' => 'Example Ltd']],
            'verified_data' => ['Current Employment' => ['organization' => 'Example Limited']],
        ]);
        $this->check->documents()->create([
            'case_id' => $this->case->id, 'original_name' => 'employment.pdf',
            'stored_path' => 'case/employment.pdf', 'mime_type' => 'application/pdf', 'file_size' => 25,
            'field_key' => '_file_current', 'uploaded_by_role' => 'candidate', 'file_content' => base64_encode('proof'),
        ]);
    }

    private function payload(): array
    {
        return [
            'check_name' => 'Career and employment history',
            'sections' => [[
                'key' => 'Current Employment', 'label' => 'Most recent employer',
                'fields' => [
                    ['name' => '_file_current', 'label' => 'Appointment and experience letters', 'type' => 'file'],
                    ['name' => 'organization', 'label' => 'Legal employer name', 'type' => 'text', 'required' => true, 'placeholder' => 'Company registered name'],
                    ['name' => 'field_reference', 'label' => 'Reference notes', 'type' => 'textarea', 'help' => 'Include the HR response.'],
                    ['name' => 'field_contract', 'label' => 'Contract type', 'type' => 'select', 'options' => ['Permanent', 'Contract']],
                ],
            ]],
        ];
    }

    public function test_admin_can_rename_sections_and_fields_reorder_and_add_fields_without_losing_evidence(): void
    {
        $stated = $this->check->form_data;
        $verified = $this->check->verified_data;
        $document = $this->check->documents()->first()->toArray();
        $defaults = Check::first()->schema;
        $this->actingAs($this->admin)->put(route('admin.cases.checks.schema', [$this->case, $this->check]), $this->payload())
            ->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->check->refresh();
        $this->assertSame('Career and employment history', $this->check->custom_name);
        $this->assertSame('employment_verification', $this->check->check_type);
        $fields = $this->check->custom_schema['Current Employment'];
        $this->assertSame(['_file_current', 'organization', 'field_reference', 'field_contract'], array_column($fields, 'name'));
        $this->assertSame('Most recent employer', $fields[1]['section_label']);
        $this->assertSame('Legal employer name', $fields[1]['label']);
        $this->assertTrue($fields[1]['required']);
        $this->assertSame(['Permanent', 'Contract'], $fields[3]['options']);
        $this->assertSame($stated, $this->check->form_data);
        $this->assertSame($verified, $this->check->verified_data);
        $this->assertSame($document, $this->check->documents()->first()->toArray());
        $this->assertSame($defaults, Check::first()->schema);
    }

    public function test_client_can_customize_own_case_with_json_payload_and_new_field_types(): void
    {
        $payload = $this->payload();
        $payload['sections'][] = [
            'key' => 'section_contact', 'label' => 'Employer contact',
            'fields' => [
                ['name' => 'field_email', 'label' => 'HR email', 'type' => 'email'],
                ['name' => 'field_phone', 'label' => 'HR phone', 'type' => 'tel'],
                ['name' => 'field_date', 'label' => 'Response date', 'type' => 'date'],
                ['name' => 'field_months', 'label' => 'Months employed', 'type' => 'number'],
                ['name' => 'field_consent', 'label' => 'Contact approved', 'type' => 'checkbox'],
                ['name' => 'field_result', 'label' => 'Response', 'type' => 'radio', 'options' => 'Matched, Needs review'],
            ],
        ];
        $sections = $payload['sections'];
        unset($payload['sections']);
        $payload['sections_payload'] = json_encode($sections, JSON_THROW_ON_ERROR);
        $this->actingAs($this->client)->put(route('client.cases.checks.schema', [$this->case, $this->check]), $payload)
            ->assertRedirect()->assertSessionHasNoErrors();
        $schema = $this->check->fresh()->custom_schema;
        $this->assertCount(2, $schema);
        $this->assertSame(['email', 'tel', 'date', 'number', 'checkbox', 'radio'], array_column($schema['section_contact'], 'type'));
        $this->assertSame(['Matched', 'Needs review'], $schema['section_contact'][5]['options']);
        $this->assertSame('In Progress', $this->check->fresh()->status);
    }

    public function test_client_cannot_customize_a_case_from_another_organization(): void
    {
        $org = ClientOrganization::create(['name' => 'Other Organization']);
        $other = User::create([
            'name' => 'Other HR', 'email' => 'other-schema@example.test', 'password' => 'password123',
            'role' => 'organization', 'is_verified' => true, 'client_organization_id' => $org->id,
        ]);
        $this->actingAs($other)->put(route('client.cases.checks.schema', [$this->case, $this->check]), $this->payload())->assertForbidden();
        $this->assertNull($this->check->fresh()->custom_schema);
    }

    public function test_check_must_belong_to_the_case_for_admin_and_client(): void
    {
        $otherCase = $this->case->replicate();
        $otherCase->case_number = 'OV-SCHEMA-OTHER';
        $otherCase->save();
        foreach (['admin' => $this->admin, 'client' => $this->client] as $role => $user) {
            $this->actingAs($user)->put(route($role.'.cases.checks.schema', [$otherCase, $this->check]), $this->payload())->assertNotFound();
        }
        $this->assertNull($this->check->fresh()->custom_schema);
    }

    public function test_missing_sections_cannot_accidentally_erase_a_schema(): void
    {
        $this->check->update(['custom_schema' => ['_self' => [['name' => 'saved', 'label' => 'Saved']]]]);
        $this->actingAs($this->admin)->put(route('admin.cases.checks.schema', [$this->case, $this->check]), ['check_name' => 'Title'])
            ->assertRedirect()->assertSessionHasErrors('sections');
        $this->assertArrayHasKey('_self', $this->check->fresh()->custom_schema);
    }

    public function test_explicit_empty_schema_and_reset_preserve_all_saved_values_and_files(): void
    {
        $stated = $this->check->form_data;
        $verified = $this->check->verified_data;
        $route = route('client.cases.checks.schema', [$this->case, $this->check]);
        $this->actingAs($this->client)->put($route, ['check_name' => 'New title', 'sections_payload' => '[]'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([], $this->check->fresh()->custom_schema);
        $this->assertSame('New title', $this->check->fresh()->custom_name);
        $this->put($route, ['reset_default' => true])->assertRedirect();
        $this->check->refresh();
        $this->assertNull($this->check->custom_schema);
        $this->assertNull($this->check->custom_name);
        $this->assertSame($stated, $this->check->form_data);
        $this->assertSame($verified, $this->check->verified_data);
        $this->assertSame(1, $this->check->documents()->count());
    }

    public function test_invalid_editor_payload_options_types_and_duplicate_identifiers_are_rejected(): void
    {
        $route = route('admin.cases.checks.schema', [$this->case, $this->check]);
        $this->actingAs($this->admin)->put($route, ['sections_payload' => '{broken'])
            ->assertRedirect()->assertSessionHasErrors('sections_payload');

        $payload = $this->payload();
        $payload['sections'][0]['fields'][3]['options'] = [];
        $this->put($route, $payload)->assertRedirect()->assertSessionHasErrors('sections.0.fields.3.options');
        $payload = $this->payload();
        $payload['sections'][0]['fields'][0]['type'] = 'script';
        $this->put($route, $payload)->assertRedirect()->assertSessionHasErrors('sections.0.fields.0.type');
        $payload = $this->payload();
        $payload['sections'][0]['fields'][2]['name'] = 'organization';
        $this->put($route, $payload)->assertRedirect()->assertSessionHasErrors('sections.0.fields.2.name');
        $payload = $this->payload();
        $payload['sections'][] = $payload['sections'][0];
        $this->put($route, $payload)->assertRedirect()->assertSessionHasErrors('sections.1.key');
        $payload = $this->payload();
        $payload['sections'][] = ['key' => 'Previous Employment', 'label' => 'Previous Employer', 'fields' => [$payload['sections'][0]['fields'][0]]];
        $this->put($route, $payload)->assertRedirect()->assertSessionHasErrors('sections.1.fields.0.name');
        $this->assertNull($this->check->fresh()->custom_schema);
    }

    public function test_duplicate_text_keys_in_different_sections_retain_independent_storage(): void
    {
        $payload = $this->payload();
        $payload['sections'][] = [
            'key' => 'Previous Employment', 'label' => 'Previous Employer',
            'fields' => [['name' => 'organization', 'label' => 'Previous company', 'type' => 'text']],
        ];
        $this->actingAs($this->client)->put(route('client.cases.checks.schema', [$this->case, $this->check]), $payload)
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('organization', $this->check->fresh()->custom_schema['Previous Employment'][0]['name']);
    }

    public function test_schema_field_limit_is_enforced_for_json_editor_payloads(): void
    {
        $sections = [];
        for ($section = 0; $section < 5; $section++) {
            $fields = [];
            for ($field = 0; $field < 50; $field++) {
                $fields[] = ['name' => 'field_'.$field, 'label' => 'Field '.$field, 'type' => 'text'];
            }
            $sections[] = ['key' => 'section_'.$section, 'label' => 'Section '.$section, 'fields' => $fields];
        }
        $this->actingAs($this->client)->put(route('client.cases.checks.schema', [$this->case, $this->check]), [
            'sections_payload' => json_encode($sections, JSON_THROW_ON_ERROR),
        ])->assertRedirect()->assertSessionHasErrors('sections');
    }

    public function test_client_can_edit_selected_check_stated_details_and_files_without_changing_agency_findings(): void
    {
        Storage::fake('local');
        $other = $this->case->checks()->create([
            'check_type' => 'education_verification', 'status' => 'Pending',
            'form_data' => ['_self' => ['degree' => 'Original degree']],
        ]);
        $findings = $this->check->verified_data;
        $this->actingAs($this->client)->patch(route('client.cases.checks.details', [$this->case, $this->check]), [
            'check_data' => [
                'employment_verification' => ['Current Employment' => ['field_reference' => 'Candidate supplied notes']],
                'education_verification' => ['_self' => ['degree' => 'Unexpected overwrite']],
            ],
            'check_files' => ['employment_verification' => ['field_letters' => [UploadedFile::fake()->create('letter.txt', 1, 'text/plain')]]],
            'verified_data' => ['Current Employment' => ['organization' => 'Forged finding']],
            'status' => 'Clear',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->check->refresh();
        $this->assertSame('Candidate supplied notes', $this->check->form_data['Current Employment']['field_reference']);
        $this->assertSame('Example Ltd', $this->check->form_data['Current Employment']['organization']);
        $this->assertSame($findings, $this->check->verified_data);
        $this->assertSame('In Progress', $this->check->status);
        $this->assertSame('Original degree', $other->fresh()->form_data['_self']['degree']);
        $this->assertSame('Pending', $other->fresh()->status);
        $this->assertSame(2, $this->check->documents()->count());
        $this->assertSame('client', $this->check->documents()->latest('id')->first()->uploaded_by_role);
    }

    public function test_client_details_editor_enforces_case_access_and_finalized_report_lock(): void
    {
        $otherCase = $this->case->replicate();
        $otherCase->case_number = 'OV-SCHEMA-FOREIGN-CHECK';
        $otherCase->save();
        $otherCheck = $otherCase->checks()->create(['check_type' => 'education_verification', 'status' => 'Pending']);
        $this->actingAs($this->client)->patch(route('client.cases.checks.details', [$this->case, $otherCheck]), [])->assertNotFound();
        $otherOrg = ClientOrganization::create(['name' => 'Blocked Organization']);
        $otherUser = User::create([
            'name' => 'Blocked HR', 'email' => 'blocked-schema@example.test', 'password' => 'password123',
            'role' => 'organization', 'is_verified' => true, 'client_organization_id' => $otherOrg->id,
        ]);
        $this->actingAs($otherUser)->patch(route('client.cases.checks.details', [$this->case, $this->check]), [])->assertForbidden();
        $this->case->update(['status' => 'Completed - Green (Clear)']);
        $this->actingAs($this->client)->patch(route('client.cases.checks.details', [$this->case, $this->check]), [])->assertForbidden();
        $this->assertSame('Example Ltd', $this->check->fresh()->form_data['Current Employment']['organization']);
        // Finalized report layout can still be customized, without editing its stated values.
        $this->put(route('client.cases.checks.schema', [$this->case, $this->check]), $this->payload())
            ->assertRedirect()->assertSessionHasNoErrors();
    }
}
