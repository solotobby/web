<?php

namespace App\Mail;

use App\Models\Creator;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CreatorLoginLink extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Creator $creator,
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your FanVault Creator Studio Magic Link',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.creator-login',
        );
    }
}
