<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\Payment;
use App\Models\Postcard;
use App\Models\Referral;
use App\Models\Stat;
use App\Support\Capsule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Stripe\Webhook;
use Tests\TestCase;

class StripePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected Creator $creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creator = Creator::create([
            'id' => (string) Str::uuid(),
            'name' => 'Maya Lin',
            'handle' => '@mayaintokyo',
            'slug' => 'maya-in-tokyo',
            'platform' => 'YouTube',
            'email' => 'maya@example.com',
            'min_seal_price_cents' => 500,
            'milestone_title' => '100k Subs',
            'unlock_date' => now()->addYear(),
        ]);
    }

    public function test_checkout_return_redirects_to_seal_when_session_id_missing(): void
    {
        $response = $this->get(route('checkout.return'));
        $response->assertRedirect(route('seal'));
    }

    public function test_checkout_return_redirects_to_seal_when_payment_not_found(): void
    {
        $response = $this->get(route('checkout.return', ['session_id' => 'cs_test_nonexistent']));
        $response->assertRedirect(route('seal'));
    }

    public function test_checkout_return_mints_postcard_when_valid_payment_exists(): void
    {
        $sessionId = 'cs_test_valid_123';
        $claim = Str::random(64);

        $payment = Payment::create([
            'id' => (string) Str::uuid(),
            'stripe_session_id' => $sessionId,
            'amount_cents' => 2500,
            'currency' => 'usd',
            'status' => 'created',
            'creator_slug' => $this->creator->slug,
            'draft' => [
                'name' => 'Stripe Fan',
                'location' => 'Seattle, USA',
                'letter' => 'Congratulations on the milestone! Here is my sealed letter.',
                'teaser' => 'Amazing journey!',
                'email' => 'stripefan@example.com',
                'addressed_to' => '2028-01-01',
                'photo_path' => null,
                'predictions' => [],
                'creator_slug' => $this->creator->slug,
                'milestone_id' => null,
                'amount_cents' => 2500,
                'claim_token_hash' => hash('sha256', $claim),
            ],
        ]);

        $response = $this->get(route('checkout.return', [
            'session_id' => $sessionId,
            'claim' => $claim,
        ]));

        $postcard = Postcard::where('name', 'Stripe Fan')->first();
        $this->assertNotNull($postcard);
        $response->assertRedirect(route('message', $postcard));

        $payment->refresh();
        $this->assertEquals('paid', $payment->status);
        $this->assertEquals($postcard->id, $payment->postcard_id);

        // Verify referral has paid status and 80% cut
        $referral = Referral::where('postcard_id', $postcard->id)->first();
        $this->assertNotNull($referral);
        $this->assertEquals(2500, $referral->amount_cents);
        $this->assertEquals(2000, $referral->cut_cents);
        $this->assertEquals('paid', $referral->status);
    }

    public function test_webhook_returns_503_when_webhook_secret_not_configured(): void
    {
        config(['services.stripe.webhook_secret' => null]);

        $response = $this->post(route('stripe.webhook'), [], [
            'Stripe-Signature' => 'test_sig',
        ]);

        $response->assertStatus(503);
    }

    public function test_webhook_returns_400_on_invalid_signature(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test_secret_123']);

        $response = $this->post(route('stripe.webhook'), ['type' => 'checkout.session.completed'], [
            'Stripe-Signature' => 'invalid_sig',
        ]);

        $response->assertStatus(400);
    }

    public function test_webhook_mints_postcard_on_completed_checkout_session(): void
    {
        $webhookSecret = 'whsec_test_mock_secret';
        config(['services.stripe.webhook_secret' => $webhookSecret]);

        $sessionId = 'cs_test_webhook_mint_456';
        $claim = Str::random(64);

        $payment = Payment::create([
            'id' => (string) Str::uuid(),
            'stripe_session_id' => $sessionId,
            'amount_cents' => 1000,
            'currency' => 'usd',
            'status' => 'created',
            'creator_slug' => $this->creator->slug,
            'draft' => [
                'name' => 'Webhook Fan',
                'location' => 'Berlin, Germany',
                'letter' => 'Sealed via Stripe webhook!',
                'teaser' => 'See you in 2028',
                'email' => 'webhook@example.com',
                'addressed_to' => '2028-01-01',
                'photo_path' => null,
                'predictions' => [],
                'creator_slug' => $this->creator->slug,
                'milestone_id' => null,
                'amount_cents' => 1000,
                'claim_token_hash' => hash('sha256', $claim),
            ],
        ]);

        $payload = json_encode([
            'id' => 'evt_test_123',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => $sessionId,
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_789',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $webhookSecret);
        $header = "t={$timestamp},v1={$signature}";

        $response = $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $header,
            ],
            $payload
        );

        $response->assertStatus(200);

        $postcard = Postcard::where('name', 'Webhook Fan')->first();
        $this->assertNotNull($postcard);

        $payment->refresh();
        $this->assertEquals('paid', $payment->status);
        $this->assertEquals($postcard->id, $payment->postcard_id);
        $this->assertEquals('pi_test_789', $payment->stripe_payment_intent);

        $referral = Referral::where('postcard_id', $postcard->id)->first();
        $this->assertNotNull($referral);
        $this->assertEquals(1000, $referral->amount_cents);
        $this->assertEquals(800, $referral->cut_cents);
        $this->assertEquals('paid', $referral->status);
    }
}
