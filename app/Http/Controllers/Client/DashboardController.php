<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $cases = \App\Models\Cases::where('created_by', $user->id)->latest()->limit(5)->get();

        $stats = [
            'total'     => \App\Models\Cases::where('created_by', $user->id)->count(),
            'pending'   => \App\Models\Cases::where('created_by', $user->id)->where('status', 'Pending')->count(),
            'completed' => \App\Models\Cases::where('created_by', $user->id)
                            ->whereIn('status', ['Completed - Green (Clear)', 'Completed - Discrepancy Found'])->count(),
            'in_progress'=> \App\Models\Cases::where('created_by', $user->id)->where('status', 'In Progress')->count(),
        ];

        return view('client.dashboard', compact('cases', 'stats'));
    }
}
