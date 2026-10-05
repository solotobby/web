<?php

namespace App\Services;

use App\Models\Creator;
use App\Models\Envelope;
use App\Models\Payment;
use App\Models\Postcard;
use App\Models\Referral;
use App\Models\Stat;
use App\Support\Capsule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MintPostcardService
{
    /**
     * @param  array{
     *   name:string, location:string, teaser:string, letter:string, email:string,
     *   addressed_to:string, photo_path?:?string, predictions?:array,
     *   creator_slug?:?string, claim_token_hash?:?string, stripe_session_id?:?string
     * }  $payload
     * @return array{postcard: Postcard, claim_token: ?string}
     */
    public function mint(array $payload): array
    {
        return DB::transaction(function () use ($payload) {
            if (! empty($payload['stripe_session_id'])) {
                $existing = Payment::query()
                    ->where('stripe_session_id', $payload['stripe_session_id'])
                    ->where('status', 'paid')
                    ->whereNotNull('postcard_id')
                    ->lockForUpdate()
                    ->first();
                if ($existing?->postcard) {
                    return ['postcard' => $existing->postcard->load('envelope'), 'claim_token' => null];
                }
            }

            $stat = Stat::query()->lockForUpdate()->firstOrCreate(['id' => 1], [
                'sealed_count' => 0,
                'founding_count' => 0,
            ]);

            if ($stat->sealed_count >= Capsule::TOTAL_CAP) {
                throw new \RuntimeException('capsule_full');
            }

            $number = (int) DB::table('postcards')->max('number') + 1;
            if ($number < 1) {
                $number = 1;
            }
            if ($number > Capsule::TOTAL_CAP) {
                throw new \RuntimeException('capsule_full');
            }

            $creatorId = null;
            $slug = trim((string) ($payload['creator_slug'] ?? ''));
            if ($slug !== '') {
                $creatorId = Creator::query()->where('slug', Capsule::slugify($slug))->value('id');
            }

            $milestoneId = ! empty($payload['milestone_id']) ? $payload['milestone_id'] : null;
            if (! $milestoneId && $creatorId) {
                $milestoneId = \App\Models\Milestone::query()->where('creator_id', $creatorId)->where('is_active', true)->value('id')
                    ?? \App\Models\Milestone::query()->where('creator_id', $creatorId)->value('id');
            }

            $founding = $number <= Capsule::FOUNDING_CAP;
            $postcard = Postcard::query()->create([
                'id' => (string) Str::uuid(),
                'number' => $number,
                'name' => trim($payload['name']),
                'location' => trim($payload['location']),
                'teaser' => mb_substr(trim($payload['teaser']), 0, Capsule::MAX_TEASER),
                'addressed_to' => Capsule::clampAddressDate($payload['addressed_to'] ?? Capsule::OPENING),
                'sealed_at' => now(),
                'founding' => $founding,
                'creator_id' => $creatorId,
                'milestone_id' => $milestoneId,
                'seeded' => false,
            ]);

            Envelope::query()->create([
                'postcard_id' => $postcard->id,
                'letter' => mb_substr(trim($payload['letter']), 0, Capsule::MAX_LETTER),
                'email' => strtolower(trim($payload['email'])),
                'photo_path' => $payload['photo_path'] ?? null,
                'predictions' => $payload['predictions'] ?? [],
                'claim_token_hash' => $payload['claim_token_hash'] ?? null,
            ]);

            $stat->update([
                'sealed_count' => $stat->sealed_count + 1,
                'founding_count' => $stat->founding_count + ($founding ? 1 : 0),
            ]);

            if ($creatorId) {
                $amountCents = (int) ($payload['amount_cents'] ?? Capsule::DEFAULT_SEAL_PRICE_CENTS);
                $cutCents = Capsule::calculateCreatorCut($amountCents);
                $refStatus = ! empty($payload['stripe_session_id']) ? 'paid' : 'pending';

                Referral::query()->create([
                    'postcard_id' => $postcard->id,
                    'creator_id' => $creatorId,
                    'amount_cents' => $amountCents,
                    'cut_cents' => $cutCents,
                    'status' => $refStatus,
                ]);
            }

            if (! empty($payload['stripe_session_id'])) {
                Payment::query()
                    ->where('stripe_session_id', $payload['stripe_session_id'])
                    ->update([
                        'status' => 'paid',
                        'postcard_id' => $postcard->id,
                        'paid_at' => now(),
                    ]);
            }

            return ['postcard' => $postcard->load('envelope'), 'claim_token' => null];
        });
    }
}
