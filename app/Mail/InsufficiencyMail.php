<?php

namespace App\Mail;

use App\Models\Cases;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class InsufficiencyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Cases $case,
        public string $token,
        public string $checkName,
        public ?string $reason = null,
        public ?Carbon $expiresAt = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Action Required: Insufficiency in Your Background Verification');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.insufficiency',
            with: [
                'uploadUrl' => route('candidate.upload.show', $this->token),
            ],
        );
    }
}
