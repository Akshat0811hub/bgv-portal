<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        try {
            if (! Schema::hasTable('settings')) {
                return $default;
            }

            return static::where('key', $key)->value('value') ?? $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Apply stored SMTP mail settings (if configured in DB) over .env defaults.
     * Returns true if an active SMTP host + username + password is configured.
     */
    public static function applyMailConfig(): bool
    {
        $mailer   = static::getValue('mail_mailer') ?: config('mail.default', 'log');
        $host     = static::getValue('mail_host') ?: config('mail.mailers.smtp.host');
        $port     = static::getValue('mail_port') ?: config('mail.mailers.smtp.port', 587);
        $enc      = static::getValue('mail_encryption') ?: env('MAIL_ENCRYPTION', 'tls');
        $username = static::getValue('mail_username') ?: config('mail.mailers.smtp.username');
        $password = static::getValue('mail_password') ?: config('mail.mailers.smtp.password');
        $fromAddr = static::getValue('mail_from_address') ?: config('mail.from.address');
        $fromName = static::getValue('mail_from_name') ?: config('mail.from.name', 'Orbit Verify');

        // Auto-correct Laravel's local default 127.0.0.1:2525 if a real email username was provided
        if (($host === '127.0.0.1' || empty($host)) && ! empty($username)) {
            if (str_ends_with(strtolower($username), '@gmail.com') || str_ends_with(strtolower($username), '@googlemail.com')) {
                $host = 'smtp.gmail.com';
                $port = 587;
                $enc  = 'tls';
            }
        }

        if ($mailer === 'smtp' && ! empty($host) && ! empty($username) && ! empty($password)) {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.url', null);
            Config::set('mail.mailers.smtp.host', $host);
            Config::set('mail.mailers.smtp.port', (int) $port);
            Config::set('mail.mailers.smtp.scheme', ($enc === 'ssl' || (int) $port === 465) ? 'smtps' : 'smtp');
            Config::set('mail.mailers.smtp.username', $username);
            Config::set('mail.mailers.smtp.password', $password);
            Config::set('mail.from.address', $fromAddr ?: $username);
            Config::set('mail.from.name', $fromName ?: 'Orbit Verify');

            try {
                \Illuminate\Support\Facades\Mail::purge('smtp');
            } catch (\Throwable $e) {
                // ignore if mail manager not resolved yet
            }

            return true;
        }

        return config('mail.default') === 'smtp' && ! empty($username) && ! empty($password);
    }
}
