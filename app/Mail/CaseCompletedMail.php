<?php

namespace App\Mail;

use App\Models\Cases;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CaseCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Cases $case,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "BGV Report Ready: {$this->case->candidate_name} ({$this->case->case_number}) - Orbit Verify"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.case-completed',
            with: [
                'caseUrl' => route('client.cases.show', $this->case),
                'reportUrl' => route('client.cases.report', $this->case),
            ],
        );
    }
}
