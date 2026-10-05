<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\MintPostcardService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, MintPostcardService $mint): Response
    {
        $secret = config('services.stripe.webhook_secret');
        $payload = $request->getContent();
        $sig = $request->header('Stripe-Signature');

        if (! $secret) {
            return response('webhook not configured', 503);
        }

        try {
            $event = Webhook::constructEvent($payload, (string) $sig, $secret);
        } catch (UnexpectedValueException|SignatureVerificationException) {
            return response('invalid signature', 400);
        }

        if (in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            $session = $event->data->object;
            $sessionId = (string) ($session->id ?? '');
            if ($sessionId === '') {
                return response('ok', 200);
            }

            if (($session->payment_status ?? '') !== 'paid') {
                return response('payment not completed', 200);
            }

            $payment = Payment::query()->where('stripe_session_id', $sessionId)->first();
            if ($payment && is_array($payment->draft) && ! $payment->postcard_id) {
                $draft = $payment->draft;
                $draft['stripe_session_id'] = $sessionId;
                $draft['amount_cents'] = $payment->amount_cents ?? \App\Support\Capsule::DEFAULT_SEAL_PRICE_CENTS;
                if (! empty($session->payment_intent)) {
                    $payment->update(['stripe_payment_intent' => (string) $session->payment_intent]);
                }
                $mint->mint($draft);
            }
        }

        return response('ok', 200);
    }
}
