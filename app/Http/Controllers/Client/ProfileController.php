<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit()
    {
        $organization = auth()->user()->clientOrganization;
        abort_if(! $organization, 404, 'No organization associated with your account.');

        return view('client.profile', compact('organization'));
    }

    public function update(Request $request)
    {
        $organization = auth()->user()->clientOrganization;
        abort_if(! $organization, 404);

        $data = $request->validate([
            'gstin' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'contact_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\+\-\s\(\)]+$/'],
        ]);

        $organization->update($data);

        return back()->with('success', 'Company profile updated.');
    }
}
