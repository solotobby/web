<?php

namespace App\Mail;

use App\Models\CreatorProspect;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope as MailEnvelope;
use Illuminate\Queue\SerializesModels;

class CreatorOutreachMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CreatorProspect $prospect,
        public string $customSubject,
        public string $customBody,
        public string $replyToEmail = 'oluwatobi@getfanvault.com',
        public string $replyToName = 'Oluwatobi Solomon',
    ) {
        $this->subject = $customSubject;
    }

    public function envelope(): MailEnvelope
    {
        return new MailEnvelope(
            subject: $this->customSubject,
            replyTo: [
                new Address($this->replyToEmail, $this->replyToName),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.creator-outreach',
            text: 'mail.creator-outreach-text',
        );
    }
}
