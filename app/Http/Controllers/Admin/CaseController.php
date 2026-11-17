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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CaseController extends Controller
{
    use StoresCaseDocuments;

    public function index(Request $request)
    {
        $query = Cases::with('clientOrganization', 'createdBy', 'checks')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('org')) {
            $query->where('client_organization_id', $request->org);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('candidate_name', 'like', "%$s%")
                ->orWhere('case_number', 'like', "%$s%")
                ->orWhere('candidate_email', 'like', "%$s%")
                ->orWhere('candidate_phone', 'like', "%$s%")
                ->orWhereHas('clientOrganization', fn ($o) => $o->where('name', 'like', "%$s%"))
            );
        }

        $cases = $query->paginate(15)->withQueryString();
        $organizations = ClientOrganization::orderBy('name')->get();

        return view('admin.cases.index', compact('cases', 'organizations'));
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
            'mode' => 'required|in:send_link,admin_upload,self_upload',
            'checks' => 'required|array|min:1|max:20',
            'checks.*' => ['required', 'string', 'in:'.implode(',', $validCheckSlugs)],
            'check_files' => 'nullable|array',
            'check_files.*.*.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,webp',
        ]);

        $case = DB::transaction(function () use ($data) {
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

            return $case;
        });

        $case->load('checks', 'clientOrganization');

        // Persist any details / documents the admin entered (optional in either mode).
        $this->captureCheckSubmission($request, $case, 'admin');
        $case->syncStatusAndCloseDate();

        // Always generate a token so the case has an active upload link ready to copy/share anytime.
        $token = Str::random(48);
        $case->uploadToken()->create(['token' => $token, 'expires_at' => now()->addDays(7)]);
        $case->load('uploadToken');

        if (in_array($data['mode'], ['admin_upload', 'self_upload'])) {
            return redirect()->route('admin.cases.show', $case)
                ->with('success', 'Case created with the details you entered.');
        }

        // send_link: email the candidate.
        $this->sendInvite($case, $token);

        return redirect()->route('admin.cases.show', $case)
            ->with('success', "Case created and verification link sent to {$case->candidate_email}.");
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
        abort_if($check->case_id !== $case->id, 404);
        $data = $request->validate(['reason' => 'nullable|string|max:1000']);

        $check->update([
            'status' => 'Insufficiency',
            'comments' => $data['reason'] ? ('Insufficiency Raised: ' . $data['reason']) : $check->comments,
            'completed_at' => null,
        ]);
        $case->syncStatusAndCloseDate();
        $case->load('uploadToken');

        // Issue / refresh a token so the candidate can re-upload, preserving existing valid token so earlier emails stay valid.
        $token = optional($case->uploadToken)->isValid() ? $case->uploadToken->token : Str::random(48);
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

        $allChecks = Check::where('active', true)->get();
        $schemas = $allChecks->mapWithKeys(fn ($c) => [$c->slug => $c->schema ?? []]);

        return view('admin.cases.show', compact('case', 'schemas', 'allChecks'));
    }

    public function updateCandidate(Request $request, Cases $case)
    {
        $data = $request->validate([
            'candidate_name' => 'required|string|max:150',
            'candidate_email' => 'required|email|max:255',
            'candidate_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\+\-\s\(\)]+$/'],
            'candidate_father_name' => 'nullable|string|max:150',
            'candidate_designation' => 'nullable|string|max:100',
        ]);

        $case->update($data);

        return back()->with('success', 'Candidate profile updated.');
    }

    public function addCheck(Request $request, Cases $case)
    {
        $validSlugs = Check::where('active', true)->pluck('slug')->toArray();
        $data = $request->validate([
            'check_type' => ['required', 'string', 'in:' . implode(',', $validSlugs)],
        ]);

        if ($case->checks()->where('check_type', $data['check_type'])->exists()) {
            return back()->with('warning', 'That check is already assigned to this case.');
        }

        $case->checks()->create([
            'check_type' => $data['check_type'],
            'status' => 'Pending',
        ]);

        $case->syncStatusAndCloseDate();

        return back()->with('success', 'Check added to the case.');
    }

    public function destroyCheck(Cases $case, CaseCheck $check)
    {
        abort_if($check->case_id !== $case->id, 404);

        if ($case->checks()->count() <= 1) {
            return back()->with('error', 'A case must have at least one verification check.');
        }

        foreach ($check->documents as $doc) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($doc->stored_path);
            $doc->delete();
        }

        $check->delete();
        $case->syncStatusAndCloseDate();

        return back()->with('success', 'Check removed from the case.');
    }

    public function updateCheck(Request $request, Cases $case, CaseCheck $check)
    {
        abort_if($check->case_id !== $case->id, 404);
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
            $update['completed_at'] = $check->completed_at ?? now();
        } else {
            $update['completed_at'] = null;
        }

        $check->update($update);

        // Admin-uploaded verified documents.
        foreach ((array) $request->file('verified_files', []) as $file) {
            $this->storeDocument($file, $case, $check, 'verified', 'admin');
        }

        $case->syncStatusAndCloseDate();

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
            'admin_notes' => $data['admin_notes'] ?? $case->admin_notes,
        ]);
        $case->syncStatusAndCloseDate($data['status']);

        return back()->with('success', 'Case status and notes updated.');
    }
}
