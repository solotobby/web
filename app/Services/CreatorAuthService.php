<?php

namespace App\Services;

use App\Mail\CreatorLoginLink;
use App\Models\Creator;
use App\Models\CreatorLoginToken;
use App\Support\Capsule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreatorAuthService
{
    public const TOKEN_TTL_MINUTES = 30;

    public function login(Creator $creator): void
    {
        session([
            'creator_id' => $creator->id,
            'creator_slug' => $creator->slug,
        ]);
    }

    public function logout(): void
    {
        session()->forget(['creator_id', 'creator_slug']);
    }

    public function current(): ?Creator
    {
        $id = session('creator_id');
        if (! $id) {
            return null;
        }

        return Creator::query()->find($id);
    }

    /**
     * @return array{sent: bool, is_new?: bool, login_url?: string}
     */
    public function sendLoginLink(string $email): array
    {
        $email = strtolower(trim($email));
        $creator = Creator::query()->where('email', $email)->first();

        $isNew = false;
        if (! $creator) {
            $isNew = true;
            $prefix = explode('@', $email)[0];
            $baseSlug = Capsule::slugify($prefix) ?: 'creator';
            $slug = $baseSlug;
            $i = 1;
            while (Creator::query()->where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . (++$i);
            }

            $creator = Creator::query()->create([
                'id' => (string) Str::uuid(),
                'email' => $email,
                'name' => '',
                'handle' => '',
                'slug' => $slug,
                'platform' => 'YouTube',
                'milestone_title' => null,
                'unlock_date' => null,
                'bio' => null,
                'joined_at' => now(),
            ]);
        }

        CreatorLoginToken::query()
            ->where('creator_id', $creator->id)
            ->whereNull('used_at')
            ->delete();

        $plain = Str::random(64);
        CreatorLoginToken::query()->create([
            'creator_id' => $creator->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addMinutes(self::TOKEN_TTL_MINUTES),
        ]);

        $url = route('creators.login', ['token' => $plain]);

        Mail::to($creator->email)->send(new CreatorLoginLink($creator, $url));

        $result = ['sent' => true, 'is_new' => $isNew];
        if (app()->environment('local') || config('app.debug')) {
            $result['login_url'] = $url;
        }

        return $result;
    }

    public function consumeToken(string $plain): ?Creator
    {
        $hash = hash('sha256', $plain);
        $token = CreatorLoginToken::query()
            ->where('token_hash', $hash)
            ->with('creator')
            ->first();

        if (! $token || ! $token->isValid() || ! $token->creator) {
            return null;
        }

        $token->update(['used_at' => now()]);
        CreatorLoginToken::query()
            ->where('creator_id', $token->creator_id)
            ->whereNull('used_at')
            ->where('id', '!=', $token->id)
            ->delete();

        $this->login($token->creator);

        return $token->creator;
    }
}
