<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\ClientOrganization;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $counts = Cases::selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        $stats = [
            'pending' => (int) ($counts['Pending'] ?? 0),
            'insufficiency' => (int) ($counts['Insufficiency'] ?? 0),
            'amber' => (int) ($counts['Amber'] ?? 0),
            'discrepancy' => (int) ($counts['Completed - Discrepancy Found'] ?? 0),
            'green' => (int) ($counts['Completed - Green (Clear)'] ?? 0),
            'on_hold' => (int) ($counts['On Hold'] ?? 0),
        ];

        // Filterable case list (search / status / organization).
        $query = Cases::with('clientOrganization', 'createdBy')
            ->withCount('checks')
            ->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('candidate_name', 'like', "%$s%")
                ->orWhere('case_number', 'like', "%$s%")
                ->orWhere('candidate_email', 'like', "%$s%")
                ->orWhere('candidate_phone', 'like', "%$s%")
                ->orWhereHas('clientOrganization', fn ($o) => $o->where('name', 'like', "%$s%"))
            );
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('org')) {
            $query->where('client_organization_id', $request->org);
        }

        $cases = $query->paginate(10)->withQueryString();
        $organizations = ClientOrganization::orderBy('name')->get();

        return view('admin.dashboard', compact('stats', 'cases', 'organizations'));
    }
}
