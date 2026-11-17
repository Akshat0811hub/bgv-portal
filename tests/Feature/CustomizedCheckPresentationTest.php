<?php

namespace Tests\Feature;

use App\Models\Cases;
use App\Models\ClientOrganization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomizedCheckPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_names_labels_and_active_fields_are_used_in_report_and_case(): void
    {
        $org = ClientOrganization::create(['name' => 'Acme']);
        $client = User::create(['name' => 'HR', 'email' => 'hr@example.test', 'password' => 'password123',
            'role' => 'organization', 'is_verified' => true, 'client_organization_id' => $org->id]);
        $case = Cases::create(['case_number' => 'OV-CUSTOM', 'candidate_name' => 'Candidate', 'candidate_email' => 'candidate@example.test', 'status' => 'Pending',
            'created_by' => $client->id, 'client_organization_id' => $org->id, 'case_start_date' => today()]);
        $schema = ['Current Employment' => [
            ['name' => 'organization', 'label' => 'Employer legal name', 'type' => 'text', 'section_label' => 'Latest employer'],
            ['name' => 'confirmed', 'label' => 'Confirmation received', 'type' => 'checkbox', 'section_label' => 'Latest employer'],
        ]];
        $check = $case->checks()->create(['check_type' => 'employment_verification', 'custom_name' => 'Employment history review',
            'status' => 'Pending', 'custom_schema' => $schema,
            'form_data' => ['Current Employment' => ['organization' => 'Acme Pvt Ltd', 'confirmed' => '0', 'old_field' => 'Archived answer']],
            'verified_data' => ['Current Employment' => ['organization' => 'Acme Pvt Ltd', 'confirmed' => '1']]]);
        $check->load('documents');
        $report = view('reports.check', ['case' => $case, 'check' => $check, 'schema' => $schema, 'number' => 1, 'documentResults' => collect()])->render();
        $this->assertStringContainsString('Employment history review', $report);
        $this->assertStringContainsString('Latest employer', $report);
        $this->assertStringContainsString('Employer legal name', $report);
        $this->assertStringContainsString('Acme Pvt Ltd', $report);
        $this->assertStringNotContainsString('Archived answer', $report);
        $this->assertSame('Archived answer', $check->fresh()->form_data['Current Employment']['old_field']);
        $this->actingAs($client)->get(route('client.cases.show', $case))->assertOk()
            ->assertSee('Customize fields')->assertSee('Employment history review')->assertSee('Latest employer')
            ->assertDontSee('Archived answer');
    }
}
