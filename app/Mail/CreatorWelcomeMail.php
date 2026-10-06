<?php

namespace App\Mail;

use App\Models\Creator;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope as MailEnvelope;
use Illuminate\Queue\SerializesModels;

class CreatorWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Creator $creator,
        public string $studioUrl,
    ) {}

    public function envelope(): MailEnvelope
    {
        return new MailEnvelope(
            subject: "🎉 Welcome to FanVault, {$this->creator->name}! Your Creator Vault is Live",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.creator-welcome',
        );
    }
}
