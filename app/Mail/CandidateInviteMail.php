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
        public bool $requestDocuments = true,
    ) {
        $this->reminder = $reminder;
    }

    public function envelope(): Envelope
    {
        $org = $this->case->clientOrganization->name ?? 'Orbit Verify';
        $caseNo = $this->case->case_number;

        $subject = $this->reminder
            ? "Reminder: Background verification for {$org} [{$caseNo}]"
            : "Background verification for {$org} [{$caseNo}]";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $checkNames = collect($this->case->checks)
            ->map(fn ($c) => ucwords(str_replace('_', ' ', $c->check_type)))
            ->values();

        // Honor Laravel's forced HTTPS scheme when running behind a proxy.
        $baseUrl = route('candidate.upload.show', $this->token);
        $uploadUrl = $baseUrl . '?ctx=' . \App\Models\UploadToken::encodeSnapshot($this->case);

        return new Content(
            view: 'emails.candidate-invite',
            text: 'emails.text.candidate-invite',
            with: [
                'case' => $this->case,
                'uploadUrl' => $uploadUrl,
                'organizationName' => $this->case->clientOrganization->name ?? 'Your Hiring Organization',
                'checkNames' => $checkNames,
                'expiresAt' => optional($this->case->uploadToken)->expires_at,
            ],
        );
    }
}
