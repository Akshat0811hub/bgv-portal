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
        return view('admin.users.create');
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

        // Create the organization (if new) and the user atomically, so the
        // account is always persisted regardless of whether the email sends.
        $user = DB::transaction(function () use ($data) {
            $org = ClientOrganization::firstOrCreate(['name' => $data['client_org_name']]);

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

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        if ($user->isAdmin()) {
            return back()->with('error', 'Admin accounts cannot be deleted here.');
        }
        $user->delete();

        return back()->with('success', 'User deleted.');
    }
}
