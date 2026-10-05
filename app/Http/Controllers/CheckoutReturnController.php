<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\MintPostcardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class CheckoutReturnController extends Controller
{
    public function __invoke(Request $request, MintPostcardService $mint): RedirectResponse
    {
        $sessionId = (string) $request->query('session_id', '');
        $claim = (string) $request->query('claim', '');

        if ($sessionId === '') {
            return redirect()->route('seal');
        }

        $payment = Payment::query()->where('stripe_session_id', $sessionId)->first();
        if (! $payment) {
            return redirect()->route('seal');
        }

        $stripeSecret = config('services.stripe.secret');
        if ($stripeSecret) {
            try {
                Stripe::setApiKey($stripeSecret);
                $session = StripeSession::retrieve($sessionId);
                if (($session->payment_status ?? '') !== 'paid') {
                    return redirect()->route('seal')->with('error', 'Payment was not completed. Please try again.');
                }
                if (! empty($session->payment_intent) && empty($payment->stripe_payment_intent)) {
                    $payment->update(['stripe_payment_intent' => (string) $session->payment_intent]);
                }
            } catch (\Throwable $e) {
                Log::warning('Stripe session retrieval exception in return controller: ' . $e->getMessage());
                if ($payment->status !== 'paid' && ! $payment->postcard_id) {
                    return redirect()->route('seal')->with('error', 'Unable to verify payment status with Stripe.');
                }
            }
        }

        $postcard = $payment->postcard;
        if (! $postcard && is_array($payment->draft)) {
            $draft = $payment->draft;
            $draft['stripe_session_id'] = $sessionId;
            $draft['amount_cents'] = $payment->amount_cents ?? \App\Support\Capsule::DEFAULT_SEAL_PRICE_CENTS;
            $result = $mint->mint($draft);
            $postcard = $result['postcard'];
        }

        if (! $postcard) {
            return redirect()->route('seal');
        }

        $authored = session('authored', []);
        $authored[] = $postcard->id;
        session([
            'authored' => array_values(array_unique($authored)),
            "claim.{$postcard->id}" => $claim,
        ]);

        return redirect()->route('message', $postcard);
    }
}
