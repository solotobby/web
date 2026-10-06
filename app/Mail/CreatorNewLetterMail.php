<?php

namespace App\Mail;

use App\Models\Creator;
use App\Models\Postcard;
use App\Support\Capsule;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope as MailEnvelope;
use Illuminate\Queue\SerializesModels;

class CreatorNewLetterMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Creator $creator,
        public Postcard $postcard,
        public int $amountCents = Capsule::DEFAULT_SEAL_PRICE_CENTS,
        public int $creatorCutCents = 400,
    ) {}

    public function envelope(): MailEnvelope
    {
        $earningsFormatted = '$' . number_format($this->creatorCutCents / 100, 2);

        return new MailEnvelope(
            subject: "✨ New fan letter sealed in your vault from {$this->postcard->name} (+{$earningsFormatted})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.creator-new-letter',
        );
    }
}
