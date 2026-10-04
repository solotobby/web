<?php

namespace App\Http\Controllers;

use App\Services\CreatorAuthService;
use Illuminate\Http\RedirectResponse;

class CreatorLoginController extends Controller
{
    public function __invoke(string $token, CreatorAuthService $auth): RedirectResponse
    {
        $creator = $auth->consumeToken($token);

        if (! $creator) {
            return redirect()
                ->route('creators.access')
                ->with('creator_login_error', 'That link has expired or already been used. Request a new one.');
        }

        if ($creator->needsOnboarding()) {
            return redirect()->route('creators.onboard');
        }

        return redirect()->route('creators.studio');
    }
}
