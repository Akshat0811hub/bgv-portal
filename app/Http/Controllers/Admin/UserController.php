<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\UserCredentialsMail;
use App\Models\ClientOrganization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('clientOrganization')
            ->where('role', '!=', 'admin')
            ->latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $organizations = ClientOrganization::orderBy('name')->get();
        return view('admin.users.create', compact('organizations'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role' => 'required|in:organization,individual',
            'client_org_name' => 'required|string|max:150',
        ]);

        // Create the organization (case-insensitive match if existing) and the user atomically.
        $user = DB::transaction(function () use ($data) {
            $cleanOrgName = trim($data['client_org_name']);
            $org = ClientOrganization::whereRaw('LOWER(name) = ?', [strtolower($cleanOrgName)])->first()
                ?? ClientOrganization::create(['name' => $cleanOrgName]);

            return User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
                'is_verified' => true,
                'password_reset_required' => true,
                'client_organization_id' => $org->id,
            ]);
        });

        // Send credentials AFTER the user is committed — a mail failure must not
        // undo a successful account creation.
        $emailed = true;
        try {
            \App\Models\Setting::applyMailConfig();
            if (in_array(config('mail.default'), ['log', 'array'])) {
                throw new \RuntimeException('Live email delivery is not configured.');
            }
            Mail::to($user->email)->send(new UserCredentialsMail($user, $data['password']));
        } catch (\Throwable $e) {
            $emailed = false;
            Log::warning('Failed to send user credentials email: '.$e->getMessage());
        }

        return $emailed
            ? redirect()->route('admin.users.index')
                ->with('success', "Account created for {$user->name} and credentials emailed to {$user->email}.")
            : redirect()->route('admin.users.index')
                ->with('warning', "Account created for {$user->name}, but the credentials email could not be sent. Share the temporary password manually and check your mail settings.");
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }
        if ($user->isAdmin()) {
            return back()->with('error', 'Admin accounts cannot be modified here.');
        }

        $user->update(['is_verified' => ! $user->is_verified]);
        $state = $user->is_verified ? 'activated' : 'deactivated';

        return back()->with('success', "User account {$user->name} has been {$state}.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        if ($user->isAdmin()) {
            return back()->with('error', 'Admin accounts cannot be deleted here.');
        }

        // Preserve any cases created by this user under their organization before deleting
        $fallbackUser = User::where('client_organization_id', $user->client_organization_id)
            ->where('id', '!=', $user->id)
            ->first() ?? auth()->user();

        if ($fallbackUser) {
            \App\Models\Cases::where('created_by', $user->id)->update(['created_by' => $fallbackUser->id]);
        }

        $user->delete();

        return back()->with('success', 'User deleted. Organization cases have been preserved.');
    }
}
