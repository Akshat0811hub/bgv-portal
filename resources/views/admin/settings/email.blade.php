<x-layouts.app title="Email & SMTP Settings">

<div class="page-header">
    <div>
        <h1 class="page-title">Email / SMTP Settings</h1>
        <p class="page-subtitle">Configure email delivery for client credentials and candidate case notifications.</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 card">
        <div class="card-header"><h2 class="font-semibold text-[#0f2044]">SMTP Mail Server Configuration</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.settings.email.update') }}" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Mail Driver</label>
                        <select name="mail_mailer" class="form-select">
                            <option value="smtp" @selected($settings['mail_mailer'] === 'smtp')>SMTP (Live Email Delivery)</option>
                            <option value="log" @selected($settings['mail_mailer'] === 'log')>Log Only (Local Testing)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">SMTP Host</label>
                        <input type="text" name="mail_host" value="{{ $settings['mail_host'] }}" placeholder="smtp.gmail.com or smtp.hostinger.com" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">SMTP Port</label>
                        <input type="number" name="mail_port" value="{{ $settings['mail_port'] ?: 587 }}" placeholder="587 (TLS) or 465 (SSL)" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Encryption</label>
                        <select name="mail_encryption" class="form-select">
                            <option value="tls" @selected($settings['mail_encryption'] === 'tls')>TLS (Port 587 — Gmail / Brevo)</option>
                            <option value="ssl" @selected($settings['mail_encryption'] === 'ssl')>SSL (Port 465 — Hostinger)</option>
                            <option value="none" @selected($settings['mail_encryption'] === 'none')>None</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">SMTP Username / Email</label>
                        <input type="text" name="mail_username" value="{{ $settings['mail_username'] }}" placeholder="yourname@gmail.com or info@orbitverify.in" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">SMTP Password / App Password</label>
                        <input type="password" name="mail_password" value="" placeholder="{{ !empty($settings['mail_password']) ? '•••••••••••• (leave blank to keep)' : '16-char Gmail App Password' }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">From Email Address</label>
                        <input type="email" name="mail_from_address" value="{{ $settings['mail_from_address'] }}" placeholder="noreply@orbitverify.in" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">From Display Name</label>
                        <input type="text" name="mail_from_name" value="{{ $settings['mail_from_name'] }}" placeholder="Orbit Verify" class="form-input">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="btn-teal">Save SMTP Settings</button>
                </div>
            </form>
        </div>
    </div>

    <div class="space-y-4">
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-[#0f2044]">Send Test Email</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.settings.email.test') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="form-label">Recipient Email Address</label>
                        <input type="email" name="test_email" required placeholder="candidate@gmail.com" class="form-input">
                    </div>
                    <button type="submit" class="btn-primary w-full justify-center">Send Test Email Now</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-[#0f2044]">Inbox Delivery</h3></div>
            <div class="card-body text-xs text-[#64748b] space-y-2.5 leading-relaxed">
                <p>Use a From address authorized by your email provider. For Gmail SMTP, use your authenticated mailbox or a verified sending alias.</p>
                <p>For a custom domain, publish your provider's SPF and DKIM records, then configure DMARC. The From domain must align with SPF or DKIM. Do not add a second SPF record.</p>
                <p>Use your production HTTPS portal URL. Send a test to Gmail and inspect Show original: SPF, DKIM and DMARC should pass. SMTP acceptance does not guarantee inbox or Primary-tab placement.</p>
                <a href="https://support.google.com/mail/answer/81126" target="_blank" rel="noopener noreferrer" class="underline">Google email sender guidelines</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-[#0f2044]">Quick Setup Guide</h3></div>
            <div class="card-body text-xs text-[#64748b] space-y-2.5 leading-relaxed">
                <div>
                    <strong class="text-[#0f2044]">Option 1: Gmail SMTP (Free)</strong><br>
                    Host: <span class="font-mono">smtp.gmail.com</span> · Port: <span class="font-mono">587</span> · TLS<br>
                    Use a 16-digit Google <em>App Password</em> (under Google Account → Security → 2-Step Verification → App Passwords).
                </div>
                <div class="pt-2 border-t border-slate-100">
                    <strong class="text-[#0f2044]">Option 2: Hostinger Email</strong><br>
                    Host: <span class="font-mono">smtp.hostinger.com</span> · Port: <span class="font-mono">465</span> · SSL<br>
                    Use your Hostinger email (e.g. <span class="font-mono">info@orbitverify.in</span>) and its mailbox password.
                </div>
            </div>
        </div>
    </div>
</div>

</x-layouts.app>
