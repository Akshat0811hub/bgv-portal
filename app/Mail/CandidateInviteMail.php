<?php

namespace App\Mail;

use App\Models\Cases;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CandidateInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public bool $reminder;

    public function __construct(
        public Cases $case,
        public string $token,
        bool $reminder = false,
    ) {
        $this->reminder = $reminder;
    }

    public function envelope(): Envelope
    {
        $subject = $this->reminder
            ? 'Reminder: Upload Your Documents - Orbit Verify'
            : 'Action Required: Upload Your Documents - Orbit Verify';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $checkNames = collect($this->case->checks)
            ->map(fn ($c) => ucwords(str_replace('_', ' ', $c->check_type)))
            ->values();

        return new Content(
            view: 'emails.candidate-invite',
            with: [
                'uploadUrl' => route('candidate.upload.show', $this->token),
                'organizationName' => $this->case->clientOrganization->name ?? 'Your employer',
                'checkNames' => $checkNames,
                'expiresAt' => optional($this->case->uploadToken)->expires_at,
            ],
        );
    }
}
