<?php

namespace App\Http\Controllers\Client;

use App\Concerns\StoresCaseDocuments;
use App\Http\Controllers\Controller;
use App\Mail\CandidateInviteMail;
use App\Models\Cases;
use App\Models\Check;
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
        $user = auth()->user();
        $query = Cases::with('checks', 'createdBy')
            ->where('client_organization_id', $user->client_organization_id)
            ->latest();

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

        return view('client.cases.index', compact('cases'));
    }

    public function create()
    {
        $checks = Check::where('active', true)->get();
        $schemas = $checks->mapWithKeys(fn ($c) => [$c->slug => $c->schema ?? []]);

        return view('client.cases.create', compact('checks', 'schemas'));
    }

    public function store(Request $request)
    {
        $validCheckSlugs = Check::where('active', true)->pluck('slug')->toArray();

        $data = $request->validate([
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

        $user = auth()->user();

        $case = DB::transaction(function () use ($data, $user) {
            $case = Cases::create([
                'case_number' => Cases::generateCaseNumber(),
                'client_organization_id' => $user->client_organization_id,
                'created_by' => $user->id,
                'status' => 'Pending',
                'candidate_name' => $data['candidate_name'],
                'candidate_email' => $data['candidate_email'],
                'candidate_phone' => $data['candidate_phone'] ?? null,
                'candidate_father_name' => $data['candidate_father_name'] ?? null,
                'candidate_designation' => $data['candidate_designation'] ?? null,
                'case_start_date' => today(),
            ]);

            foreach ($data['checks'] as $checkType) {
                $case->checks()->create(['check_type' => $checkType, 'status' => 'Pending']);
            }

            return $case;
        });

        $case->load('checks', 'clientOrganization');

        // Persist any details/documents the client entered (optional in either mode).
        $this->captureCheckSubmission($request, $case, 'client');
        $case->syncStatusAndCloseDate();

        // Always generate a token so the case has an active upload link ready to copy/share anytime.
        $token = Str::random(48);
        $case->uploadToken()->create(['token' => $token, 'expires_at' => now()->addDays(7)]);
        $case->load('uploadToken');

        if (in_array($data['mode'], ['admin_upload', 'self_upload'])) {
            return redirect()->route('client.cases.show', $case)
                ->with('success', 'Case created with the details you entered.');
        }

        // send_link: email the candidate.
        try {
            Mail::to($case->candidate_email)->send(new CandidateInviteMail($case, $token));
        } catch (\Throwable $e) {
            Log::warning('Failed to send candidate invite email: '.$e->getMessage());
        }

        return redirect()->route('client.cases.show', $case)
            ->with('success', "Case created and upload link sent to {$case->candidate_email}.");
    }

    public function show(Cases $case)
    {
        if ($case->client_organization_id !== auth()->user()->client_organization_id) {
            abort(403);
        }
        $case->load('checks.documents', 'documents', 'uploadToken', 'createdBy');

        $schemas = Check::whereIn('slug', $case->checks->pluck('check_type'))->get()
            ->mapWithKeys(fn ($c) => [$c->slug => $c->schema ?? []]);

        return view('client.cases.show', compact('case', 'schemas'));
    }

    public function report(Cases $case)
    {
        if ($case->client_organization_id !== auth()->user()->client_organization_id) {
            abort(403);
        }

        return $this->caseReportPdf($case);
    }

    public function updateCandidate(Request $request, Cases $case)
    {
        if ($case->client_organization_id !== auth()->user()->client_organization_id) {
            abort(403);
        }

        $data = $request->validate([
            'candidate_name' => 'required|string|max:150',
            'candidate_email' => 'required|email|max:255',
            'candidate_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\+\-\s\(\)]+$/'],
            'candidate_father_name' => 'nullable|string|max:150',
            'candidate_designation' => 'nullable|string|max:100',
        ]);

        $case->update($data);

        return back()->with('success', 'Candidate details updated.');
    }

    public function resendLink(Cases $case)
    {
        if ($case->client_organization_id !== auth()->user()->client_organization_id) {
            abort(403);
        }

        $case->load('checks', 'clientOrganization', 'uploadToken');

        $token = optional($case->uploadToken)->isValid() ? $case->uploadToken->token : Str::random(48);

        if (! optional($case->uploadToken)->isValid()) {
            $case->uploadToken()->updateOrCreate(
                ['case_id' => $case->id],
                ['token' => $token, 'used' => false, 'expires_at' => now()->addDays(7)],
            );
            $case->load('uploadToken');
        }

        try {
            Mail::to($case->candidate_email)->send(new CandidateInviteMail($case, $token, reminder: true));
        } catch (\Throwable $e) {
            Log::warning('Failed to send candidate invite email: ' . $e->getMessage());
        }

        return back()->with('success', "Upload link re-sent to {$case->candidate_email}.");
    }

    public function uploadCheckDocuments(Request $request, Cases $case, \App\Models\CaseCheck $check)
    {
        if ($case->client_organization_id !== auth()->user()->client_organization_id) {
            abort(403);
        }
        abort_if($check->case_id !== $case->id, 404);

        $request->validate([
            'files' => 'required|array|min:1|max:5',
            'files.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,webp',
        ]);

        foreach ((array) $request->file('files', []) as $file) {
            $this->storeDocument($file, $case, $check, '_file', 'client');
        }

        if (in_array($check->status, ['Pending', 'Insufficiency'])) {
            $check->update(['status' => 'In Progress']);
            $case->syncStatusAndCloseDate();
        }

        return back()->with('success', 'Supporting documents uploaded successfully.');
    }
}
