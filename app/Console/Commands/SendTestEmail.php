<?php

namespace App\Console\Commands;

use App\Mail\CreatorLoginLink;
use App\Mail\CreatorNewLetterMail;
use App\Mail\FanCapsuleSealedMail;
use App\Models\Creator;
use App\Models\Envelope;
use App\Models\Postcard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendTestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test 
                            {email : Destination email address}
                            {--type=all : Email type: all, fan, creator, login, or welcome}
                            {--mailer= : Optional mailer override (e.g. smtp, ses, log, resend)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send test transactional emails to verify FanVault email deliverability';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $type = (string) $this->option('type');
        $mailer = $this->option('mailer');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email address: {$email}");
            return self::FAILURE;
        }

        if ($mailer) {
            config(['mail.default' => $mailer]);
        }

        $activeMailer = config('mail.default');
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');

        $this->info("==========================================");
        $this->info("  FanVault Transactional Email Test Runner");
        $this->info("==========================================");
        $this->line("  Active Transport : <comment>{$activeMailer}</comment>");
        $this->line("  From             : <comment>{$fromName} <{$fromAddress}></comment>");
        $this->line("  Recipient        : <comment>{$email}</comment>");
        $this->line("  Test Type        : <comment>{$type}</comment>");
        $this->info("------------------------------------------");

        $creator = new Creator([
            'id' => 99999,
            'name' => 'Jordan Sparks',
            'handle' => '@jordansparks',
            'slug' => 'jordansparks',
            'email' => $email,
            'platform' => 'YouTube',
            'milestone_title' => '1,000,000 Subscribers Stream',
            'unlock_date' => now()->addMonths(6),
        ]);

        $postcard = new Postcard([
            'id' => (string) Str::uuid(),
            'number' => 1420,
            'name' => 'Alex Rivera',
            'location' => 'San Francisco, CA',
            'teaser' => 'Can’t wait for the 1M stream! Here since day one.',
            'addressed_to' => now()->addMonths(6),
            'founding' => true,
            'sealed_at' => now(),
        ]);

        $envelope = new Envelope([
            'postcard_id' => $postcard->id,
            'letter' => 'Congratulations on the milestone! Keep creating amazing content.',
            'email' => $email,
        ]);

        $postcard->setRelation('envelope', $envelope);
        $postcard->setRelation('creator', $creator);

        $sentCount = 0;

        try {
            if (in_array($type, ['all', 'fan'], true)) {
                $this->line("Sending Fan Capsule Confirmation email...");
                Mail::to($email)->send(new FanCapsuleSealedMail(
                    postcard: $postcard,
                    envelope: $envelope,
                    claimToken: 'test_claim_token_preview_hash',
                    amountCents: 500,
                ));
                $this->info("  ✓ Fan Capsule Confirmation sent.");
                $sentCount++;
            }

            if (in_array($type, ['all', 'creator'], true)) {
                $this->line("Sending Creator New Letter Alert email...");
                Mail::to($email)->send(new CreatorNewLetterMail(
                    creator: $creator,
                    postcard: $postcard,
                    amountCents: 500,
                    creatorCutCents: 400,
                ));
                $this->info("  ✓ Creator New Letter Alert sent.");
                $sentCount++;
            }

            if (in_array($type, ['all', 'login'], true)) {
                $this->line("Sending Creator Magic Login Link email...");
                Mail::to($email)->send(new CreatorLoginLink(
                    creator: $creator,
                    loginUrl: route('creators.access'),
                ));
                $this->info("  ✓ Creator Magic Login Link sent.");
                $sentCount++;
            }

            if (in_array($type, ['all', 'welcome'], true)) {
                $this->line("Sending Creator Welcome Onboarding email...");
                Mail::to($email)->send(new \App\Mail\CreatorWelcomeMail(
                    creator: $creator,
                    studioUrl: route('creators.studio'),
                ));
                $this->info("  ✓ Creator Welcome email sent.");
                $sentCount++;
            }

            $this->info("------------------------------------------");
            $this->info("SUCCESS: {$sentCount} test email(s) dispatched successfully via [{$activeMailer}].");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("------------------------------------------");
            $this->error("EMAIL DELIVERY FAILED!");
            $this->error("Error: " . $e->getMessage());
            $this->line("Driver: " . $activeMailer);
            if ($activeMailer === 'smtp') {
                $this->line("Host: " . config('mail.mailers.smtp.host') . ":" . config('mail.mailers.smtp.port'));
            }
            return self::FAILURE;
        }
    }
}
