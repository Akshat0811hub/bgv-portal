<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $orgId = $user->client_organization_id;

        $cases = Cases::with('checks')
            ->where('client_organization_id', $orgId)
            ->latest()
            ->limit(6)
            ->get();

        $counts = Cases::where('client_organization_id', $orgId)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $stats = [
            'total'       => (int) $counts->sum(),
            'pending'     => (int) ($counts['Pending'] ?? 0),
            'in_progress' => (int) (($counts['In Progress'] ?? 0) + ($counts['Insufficiency'] ?? 0) + ($counts['Amber'] ?? 0) + ($counts['On Hold'] ?? 0)),
            'completed'   => (int) (($counts['Completed - Green (Clear)'] ?? 0) + ($counts['Completed - Discrepancy Found'] ?? 0)),
        ];

        return view('client.dashboard', compact('cases', 'stats'));
    }
}
