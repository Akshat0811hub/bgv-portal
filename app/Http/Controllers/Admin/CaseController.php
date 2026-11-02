<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\StoresCaseDocuments;
use App\Http\Controllers\Controller;
use App\Mail\CandidateInviteMail;
use App\Mail\InsufficiencyMail;
use App\Models\CaseCheck;
use App\Models\Cases;
use App\Models\Check;
use App\Models\ClientOrganization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CaseController extends Controller
{
    use StoresCaseDocuments;

    public function index(Request $request)
    {
        $query = Cases::with('clientOrganization', 'createdBy')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('candidate_name', 'like', "%$s%")
                ->orWhere('case_number', 'like', "%$s%")
                ->orWhere('candidate_email', 'like', "%$s%")
            );
        }

        $cases = $query->paginate(15)->withQueryString();

        return view('admin.cases.index', compact('cases'));
    }

    public function create()
    {
        $checks = Check::where('active', true)->get();
        $organizations = ClientOrganization::orderBy('name')->get();
        $schemas = $checks->mapWithKeys(fn ($c) => [$c->slug => $c->schema ?? []]);

        return view('admin.cases.create', compact('checks', 'organizations', 'schemas'));
    }

    public function store(Request $request)
    {
        $validCheckSlugs = Check::where('active', true)->pluck('slug')->toArray();

        $data = $request->validate([
            'client_organization_id' => 'required|exists:client_organizations,id',
            'candidate_name' => 'required|string|max:150',
            'candidate_email' => 'required|email|max:255',
            'candidate_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\+\-\s\(\)]+$/'],
            'candidate_father_name' => 'nullable|string|max:150',
            'candidate_designation' => 'nullable|string|max:100',
            'mode' => 'required|in:send_link,admin_upload',
            'checks' => 'required|array|min:1|max:20',
            'checks.*' => ['required', 'string', 'in:'.implode(',', $validCheckSlugs)],
            'check_files' => 'nullable|array',
            'check_files.*.*.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,webp',
        ]);

        $case = Cases::create([
            'case_number' => Cases::generateCaseNumber(),
            'client_organization_id' => $data['client_organization_id'],
            'created_by' => auth()->id(),
            'status' => 'Pending',
            'candidate_name' => $data['candidate_name'],
            'candidate_email' => $data['candidate_email'],
            'candidate_phone' => $data['candidate_phone'] ?? null,
            'candidate_father_name' => $data['candidate_father_name'] ?? null,
            'candidate_designation' => $data['candidate_designation'] ?? null,
            'case_start_date' => today(),
        ]);

        foreach ($data['checks'] as $slug) {
            $case->checks()->create(['check_type' => $slug, 'status' => 'Pending']);
        }
        $case->load('checks', 'clientOrganization');

        // Persist any details / documents the admin entered (optional in either mode).
        $this->captureCheckSubmission($request, $case, 'admin');
        $case->update(['status' => $case->recalculateStatus()]);

        if ($data['mode'] === 'admin_upload') {
            return redirect()->route('admin.cases.show', $case)
                ->with('success', 'Case created with the details you entered.');
        }

        // send_link: also generate a token + email the candidate.
        $token = Str::random(48);
        $case->uploadToken()->create(['token' => $token, 'expires_at' => now()->addDays(7)]);
        $case->load('uploadToken');

        $this->sendInvite($case, $token);

        return redirect()->route('admin.cases.show', $case)
            ->with('success', 'Case created and upload link sent to the candidate.');
    }

    public function resendLink(Cases $case)
    {
        $case->load('checks', 'clientOrganization', 'uploadToken');

        // Reuse a still-valid token, otherwise issue a fresh one.
        $token = optional($case->uploadToken)->isValid() ? $case->uploadToken->token : Str::random(48);

        if (! optional($case->uploadToken)->isValid()) {
            $case->uploadToken()->updateOrCreate(
                ['case_id' => $case->id],
                ['token' => $token, 'used' => false, 'expires_at' => now()->addDays(7)],
            );
            $case->load('uploadToken');
        }

        $this->sendInvite($case, $token, reminder: true);

        return back()->with('success', "Upload link re-sent to {$case->candidate_email}.");
    }

    public function triggerInsufficiency(Request $request, Cases $case, CaseCheck $check)
    {
        $data = $request->validate(['reason' => 'nullable|string|max:1000']);

        $check->update(['status' => 'Insufficiency']);
        $case->update(['status' => $case->recalculateStatus()]);

        // Issue / refresh a token so the candidate can re-upload.
        $token = Str::random(48);
        $case->uploadToken()->updateOrCreate(
            ['case_id' => $case->id],
            ['token' => $token, 'used' => false, 'expires_at' => now()->addDays(7)],
        );
        $case->load('uploadToken');

        try {
            Mail::to($case->candidate_email)->send(new InsufficiencyMail(
                case: $case,
                token: $token,
                checkName: ucwords(str_replace('_', ' ', $check->check_type)),
                reason: $data['reason'] ?? null,
                expiresAt: $case->uploadToken->expires_at,
            ));
        } catch (\Throwable $e) {
            Log::warning('Failed to send insufficiency email: '.$e->getMessage());
        }

        return back()->with('success', 'Insufficiency raised and the candidate has been notified.');
    }

    private function sendInvite(Cases $case, string $token, bool $reminder = false): void
    {
        try {
            Mail::to($case->candidate_email)->send(new CandidateInviteMail($case, $token, $reminder));
        } catch (\Throwable $e) {
            Log::warning('Failed to send candidate invite email: '.$e->getMessage());
        }
    }

    public function show(Cases $case)
    {
        $case->load('checks.documents', 'documents', 'clientOrganization', 'createdBy', 'uploadToken');

        $schemas = Check::whereIn('slug', $case->checks->pluck('check_type'))->get()
            ->mapWithKeys(fn ($c) => [$c->slug => $c->schema ?? []]);

        return view('admin.cases.show', compact('case', 'schemas'));
    }

    public function updateCheck(Request $request, Cases $case, CaseCheck $check)
    {
        $data = $request->validate([
            'status' => 'required|in:Pending,In Progress,Insufficiency,Amber,Discrepancy Found,Clear,Failed,Completed',
            'comments' => 'nullable|string|max:5000',
            'verified_data' => 'nullable|array',
            'verified_files' => 'nullable|array|max:10',
            'verified_files.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,webp',
        ]);

        $update = [
            'status' => $data['status'],
            'comments' => $data['comments'] ?? null,
        ];

        if ($request->has('verified_data')) {
            $update['verified_data'] = $this->pruneEmpty($request->input('verified_data', []));
        }

        if (in_array($data['status'], ['Clear', 'Completed', 'Discrepancy Found', 'Failed', 'Amber'])) {
            $update['completed_at'] = now();
        }

        $check->update($update);

        // Admin-uploaded verified documents.
        foreach ((array) $request->file('verified_files', []) as $file) {
            $this->storeDocument($file, $case, $check, 'verified', 'admin');
        }

        $case->update(['status' => $case->recalculateStatus()]);

        return back()->with('success', 'Check updated successfully.');
    }

    public function report(Cases $case)
    {
        return $this->caseReportPdf($case);
    }

    public function updateStatus(Request $request, Cases $case)
    {
        $data = $request->validate([
            'status' => 'required|in:Pending,In Progress,Insufficiency,Amber,Discrepancy Found,On Hold,Completed - Green (Clear),Completed - Discrepancy Found',
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        $case->update([
            'status' => $data['status'],
            'admin_notes' => $data['admin_notes'] ?? $case->admin_notes,
        ]);

        return back()->with('success', 'Case updated.');
    }
}
