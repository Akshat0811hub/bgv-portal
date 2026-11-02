<?php

namespace App\Http\Controllers\Client;

use App\Concerns\StoresCaseDocuments;
use App\Http\Controllers\Controller;
use App\Mail\CandidateInviteMail;
use App\Models\Cases;
use App\Models\Check;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CaseController extends Controller
{
    use StoresCaseDocuments;

    public function index(Request $request)
    {
        $query = Cases::where('created_by', auth()->id())->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('candidate_name', 'like', "%$s%")
                ->orWhere('case_number', 'like', "%$s%")
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
            'mode' => 'required|in:send_link,admin_upload',
            'checks' => 'required|array|min:1|max:20',
            'checks.*' => ['required', 'string', 'in:'.implode(',', $validCheckSlugs)],
            'check_files' => 'nullable|array',
            'check_files.*.*.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,webp',
        ]);

        $user = auth()->user();

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
        $case->load('checks', 'clientOrganization');

        // Persist any details/documents the client entered (optional in either mode).
        $this->captureCheckSubmission($request, $case, 'client');
        $case->update(['status' => $case->recalculateStatus()]);

        if ($data['mode'] === 'admin_upload') {
            return redirect()->route('client.cases.show', $case)
                ->with('success', 'Case created with the details you entered.');
        }

        // send_link: generate token + email the candidate.
        $token = Str::random(48);
        $case->uploadToken()->create(['token' => $token, 'expires_at' => now()->addDays(7)]);
        $case->load('uploadToken');

        try {
            Mail::to($case->candidate_email)->send(new CandidateInviteMail($case, $token));
        } catch (\Throwable $e) {
            Log::warning('Failed to send candidate invite email: '.$e->getMessage());
        }

        return redirect()->route('client.cases.show', $case)
            ->with('success', 'Case created and upload link sent to the candidate.');
    }

    public function show(Cases $case)
    {
        if ($case->created_by !== auth()->id()) {
            abort(403);
        }
        $case->load('checks.documents', 'documents', 'uploadToken');

        $schemas = Check::whereIn('slug', $case->checks->pluck('check_type'))->get()
            ->mapWithKeys(fn ($c) => [$c->slug => $c->schema ?? []]);

        return view('client.cases.show', compact('case', 'schemas'));
    }

    public function report(Cases $case)
    {
        if ($case->created_by !== auth()->id()) {
            abort(403);
        }

        return $this->caseReportPdf($case);
    }
}
