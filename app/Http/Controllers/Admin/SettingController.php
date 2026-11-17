<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SettingController extends Controller
{
    public function email()
    {
        $rawHost = Setting::getValue('mail_host', config('mail.mailers.smtp.host', 'smtp.gmail.com'));
        $rawPort = Setting::getValue('mail_port', (string) config('mail.mailers.smtp.port', 587));
        if ($rawHost === '127.0.0.1' || empty($rawHost)) {
            $rawHost = 'smtp.gmail.com';
        }
        if ($rawPort === '2525' || empty($rawPort)) {
            $rawPort = '587';
        }

        $settings = [
            'mail_mailer'       => Setting::getValue('mail_mailer', config('mail.default', 'log')),
            'mail_host'         => $rawHost,
            'mail_port'         => $rawPort,
            'mail_encryption'   => Setting::getValue('mail_encryption', env('MAIL_ENCRYPTION', 'tls')),
            'mail_username'     => Setting::getValue('mail_username', config('mail.mailers.smtp.username')),
            'mail_password'     => Setting::getValue('mail_password', config('mail.mailers.smtp.password')),
            'mail_from_address' => Setting::getValue('mail_from_address', config('mail.from.address')),
            'mail_from_name'    => Setting::getValue('mail_from_name', config('mail.from.name', 'Orbit Verify')),
        ];

        return view('admin.settings.email', compact('settings'));
    }

    public function updateEmail(Request $request)
    {
        $data = $request->validate([
            'mail_mailer'       => 'required|in:smtp,log',
            'mail_host'         => 'nullable|string|max:255',
            'mail_port'         => 'nullable|integer|min:1|max:65535',
            'mail_encryption'   => 'nullable|in:tls,ssl,none',
            'mail_username'     => 'nullable|string|max:255',
            'mail_password'     => 'nullable|string|max:255',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name'    => 'nullable|string|max:150',
        ]);

        foreach ($data as $key => $val) {
            if ($key === 'mail_password' && ($val === null || $val === '')) {
                continue;
            }
            Setting::setValue($key, (string) ($val ?? ''));
        }

        Setting::applyMailConfig();

        return back()->with('success', 'Email / SMTP settings saved successfully.');
    }

    public function testEmail(Request $request)
    {
        $data = $request->validate([
            'test_email' => 'required|email|max:255',
        ]);

        $smtpActive = Setting::applyMailConfig();
        if (! $smtpActive) {
            return back()->with('error', 'Please save valid SMTP Host, Username, and Password (and select SMTP mailer) before sending a test email.');
        }

        try {
            Mail::raw(
                "Hello!\n\nThis is a live test email from your Orbit Verify BGV Portal.\nYour SMTP email delivery is working properly.\n\nRegards,\nOrbit Verify",
                function ($msg) use ($data) {
                    $msg->to($data['test_email'])
                        ->subject('Orbit Verify — Live SMTP Email Test');
                }
            );

            return back()->with('success', "Test email sent successfully to {$data['test_email']}! Check your inbox/spam.");
        } catch (\Throwable $e) {
            return back()->with('error', 'SMTP Error: ' . $e->getMessage());
        }
    }
}
