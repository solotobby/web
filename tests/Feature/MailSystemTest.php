<?php

namespace Tests\Feature;

use App\Mail\CreatorLoginLink;
use App\Mail\CreatorNewLetterMail;
use App\Mail\FanCapsuleSealedMail;
use App\Models\Creator;
use App\Models\Milestone;
use App\Models\Postcard;
use App\Services\MintPostcardService;
use App\Support\Capsule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class MailSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_login_link_renders_clean_html_with_door_and_magic_url(): void
    {
        $creator = Creator::create([
            'id' => (string) Str::uuid(),
            'name' => 'Sarah Connor',
            'handle' => '@sarahc',
            'slug' => 'sarahconnor',
            'email' => 'sarah@example.com',
            'platform' => 'YouTube',
            'milestone_title' => '100K Stream',
            'unlock_date' => Carbon::parse('2027-01-01'),
        ]);

        $mailable = new CreatorLoginLink($creator, 'https://getfanvault.com/creators/login/secret123');
        $html = $mailable->render();

        $this->assertStringContainsString('Open Your Creator Studio', $html);
        $this->assertStringContainsString('Sarah Connor', $html);
        $this->assertStringContainsString('https://getfanvault.com/creators/login/secret123', $html);
        $this->assertStringContainsString('sarahconnor', $html);
        $this->assertEquals('Your FanVault Creator Studio Magic Link', $mailable->envelope()->subject);
    }

    public function test_fan_capsule_sealed_mail_renders_pass_details_and_receipt(): void
    {
        $creator = Creator::create([
            'id' => (string) Str::uuid(),
            'name' => 'Tech Guy',
            'handle' => '@techguy',
            'slug' => 'techguy',
            'email' => 'tech@example.com',
            'platform' => 'YouTube',
            'milestone_title' => '500K Stream',
            'unlock_date' => Carbon::parse('2027-05-01'),
        ]);

        $milestone = Milestone::create([
            'creator_id' => $creator->id,
            'title' => '500K Milestone Stream',
            'slug' => '500k-milestone-stream',
            'target_count' => 500000,
            'unlock_date' => Carbon::parse('2027-05-01'),
            'is_active' => true,
        ]);

        $mint = app(MintPostcardService::class);
        $result = $mint->mint([
            'name' => 'Charlie Fan',
            'location' => 'Austin, TX',
            'teaser' => 'Proud to support you!',
            'letter' => 'Here is my full secret letter for your stream.',
            'email' => 'charlie@example.com',
            'creator_slug' => $creator->slug,
            'milestone_id' => $milestone->id,
            'amount_cents' => 1000,
            'claim_token' => 'sample_token_xyz',
            'claim_token_hash' => hash('sha256', 'sample_token_xyz'),
        ]);

        $postcard = $result['postcard'];
        $envelope = $postcard->envelope;

        $mailable = new FanCapsuleSealedMail($postcard, $envelope, 'sample_token_xyz', 1000);
        $html = $mailable->render();

        $this->assertStringContainsString('Your Letter is Officially Sealed!', $html);
        $this->assertStringContainsString('Charlie Fan', $html);
        $this->assertStringContainsString('Tech Guy', $html);
        $this->assertStringContainsString('500K Milestone Stream', $html);
        $this->assertStringContainsString('$10.00 USD', $html);
        $this->assertStringContainsString('Proud to support you!', $html);
        $this->assertStringContainsString('sample_token_xyz', $html);
        $this->assertStringContainsString(Capsule::formatNumber($postcard->number), $mailable->envelope()->subject);
    }

    public function test_creator_new_letter_mail_renders_fan_teaser_and_earnings(): void
    {
        $creator = Creator::create([
            'id' => (string) Str::uuid(),
            'name' => 'Gamer Star',
            'handle' => '@gamerstar',
            'slug' => 'gamerstar',
            'email' => 'gamer@example.com',
            'platform' => 'Twitch',
            'milestone_title' => '100k Stream',
            'unlock_date' => Carbon::parse('2027-10-10'),
        ]);

        $postcard = Postcard::create([
            'id' => (string) Str::uuid(),
            'name' => 'SuperFan Dave',
            'location' => 'London, UK',
            'teaser' => 'GG on reaching 100k!',
            'number' => 777,
            'creator_id' => $creator->id,
            'addressed_to' => Carbon::parse('2027-10-10'),
            'sealed_at' => now(),
            'founding' => false,
            'seeded' => false,
        ]);

        $mailable = new CreatorNewLetterMail(
            creator: $creator,
            postcard: $postcard,
            amountCents: 2000,
            creatorCutCents: 1600,
        );
        $html = $mailable->render();

        $this->assertStringContainsString('New Contribution Sealed in Your Time Capsule!', $html);
        $this->assertStringContainsString('SuperFan Dave', $html);
        $this->assertStringContainsString('London, UK', $html);
        $this->assertStringContainsString('GG on reaching 100k!', $html);
        $this->assertStringContainsString('$16.00 USD', $html);
        $this->assertStringContainsString('+$16.00', $mailable->envelope()->subject);
    }

    public function test_mint_service_dispatches_emails_to_fan_and_creator(): void
    {
        Mail::fake();

        $creator = Creator::create([
            'id' => (string) Str::uuid(),
            'name' => 'Streamer Pro',
            'handle' => '@streamerpro',
            'slug' => 'streamer',
            'email' => 'streamer@example.com',
            'platform' => 'YouTube',
            'milestone_title' => '1M Subscribers Stream',
            'unlock_date' => Carbon::parse('2027-12-31'),
        ]);

        $mint = app(MintPostcardService::class);
        $mint->mint([
            'name' => 'Loyal Viewer',
            'location' => 'Seattle, WA',
            'teaser' => 'Watching since day one!',
            'letter' => 'Secret message inside.',
            'email' => 'viewer@example.com',
            'creator_slug' => $creator->slug,
            'amount_cents' => 500,
            'claim_token' => 'token_123',
            'claim_token_hash' => hash('sha256', 'token_123'),
        ]);

        Mail::assertSent(FanCapsuleSealedMail::class, function ($mail) {
            return $mail->hasTo('viewer@example.com') && $mail->claimToken === 'token_123';
        });

        Mail::assertSent(CreatorNewLetterMail::class, function ($mail) {
            return $mail->hasTo('streamer@example.com') && $mail->creatorCutCents === 400;
        });
    }

    public function test_claim_token_authenticates_fan_from_email_link(): void
    {
        $mint = app(MintPostcardService::class);
        $claim = 'fan_secret_key_999';
        $result = $mint->mint([
            'name' => 'Secret Fan',
            'location' => 'Toronto',
            'teaser' => 'Hidden message',
            'letter' => 'My heartfelt private note.',
            'email' => 'fan@example.com',
            'claim_token' => $claim,
            'claim_token_hash' => hash('sha256', $claim),
        ]);

        $postcard = $result['postcard'];

        // Accessing without claim token or session does NOT show full letter
        $response1 = $this->get(route('message', $postcard));
        $response1->assertOk();
        $response1->assertSee('Hidden message');
        $response1->assertDontSee('My heartfelt private note.');

        // Accessing WITH the claim token from the email link authenticates and shows the full letter!
        $response2 = $this->get(route('message', ['postcard' => $postcard->id, 'claim' => $claim]));
        $response2->assertOk();
        $response2->assertSee('My heartfelt private note.');
        $response2->assertSessionHas('authored', [$postcard->id]);
    }

    public function test_creator_welcome_mail_renders_door_link_platforms_and_studio_cta(): void
    {
        $creator = Creator::create([
            'id' => (string) Str::uuid(),
            'name' => 'Kofi Kingston',
            'handle' => '@kofik',
            'slug' => 'kofik',
            'email' => 'kofi@example.com',
            'platform' => 'YouTube, Twitch, TikTok',
            'milestone_title' => '250K Milestone Celebration',
            'unlock_date' => Carbon::parse('2027-08-15'),
        ]);

        $mailable = new \App\Mail\CreatorWelcomeMail($creator, 'https://getfanvault.com/creators/studio');
        $html = $mailable->render();

        $this->assertStringContainsString('Welcome to FanVault, Kofi Kingston!', $html);
        $this->assertStringContainsString('/with/kofik', $html);
        $this->assertStringContainsString('YouTube', $html);
        $this->assertStringContainsString('Twitch', $html);
        $this->assertStringContainsString('TikTok', $html);
        $this->assertStringContainsString('Open Your Creator Studio ➔', $html);
        $this->assertStringContainsString('250K Milestone Celebration', $html);
        $this->assertEquals("🎉 Welcome to FanVault, Kofi Kingston! Your Creator Vault is Live", $mailable->envelope()->subject);
    }

    public function test_artisan_mail_test_command_executes_successfully(): void
    {
        $this->artisan('mail:test', [
            'email' => 'check@example.com',
            '--type' => 'all',
            '--mailer' => 'log',
        ])
        ->expectsOutputToContain('FanVault Transactional Email Test Runner')
        ->expectsOutputToContain('SUCCESS: 4 test email(s) dispatched successfully')
        ->assertExitCode(0);
    }
}
