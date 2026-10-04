<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\MintPostcardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
