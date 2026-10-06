<?php

namespace App\Mail;

use App\Models\Envelope;
use App\Models\Postcard;
use App\Support\Capsule;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope as MailEnvelope;
use Illuminate\Queue\SerializesModels;

class FanCapsuleSealedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Postcard $postcard,
        public Envelope $envelope,
        public ?string $claimToken = null,
        public int $amountCents = Capsule::DEFAULT_SEAL_PRICE_CENTS,
    ) {}

    public function envelope(): MailEnvelope
    {
        $creator = $this->postcard->creator;
        $num = Capsule::formatNumber($this->postcard->number);
        $subject = $creator
            ? "📬 Sealed: Fan Pass No. {$num} for {$creator->name}’s Vault"
            : "📬 Sealed: Fan Keepsake Pass No. {$num}";

        return new MailEnvelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.fan-capsule-sealed',
        );
    }
}
