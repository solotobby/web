<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Creator extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'name', 'handle', 'slug', 'platform', 'email',
        'bio', 'min_seal_price_cents', 'milestone_title', 'unlock_date', 'avatar_url',
        'stripe_connect_id', 'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'unlock_date' => 'date',
            'min_seal_price_cents' => 'integer',
        ];
    }

    public function minPriceDollars(): string
    {
        return (string) ((int) round(($this->min_seal_price_cents ?? \App\Support\Capsule::DEFAULT_SEAL_PRICE_CENTS) / 100));
    }

    public function formattedUnlockDate(): string
    {
        $active = $this->activeMilestone();
        if ($active && $active->unlock_date) {
            return $active->unlock_date->format('j F Y');
        }

        if ($this->unlock_date) {
            return $this->unlock_date->format('j F Y');
        }

        return '1 January 2028';
    }

    public function needsOnboarding(): bool
    {
        return empty(trim($this->name ?? ''))
            || empty(trim($this->handle ?? ''))
            || (empty(trim($this->milestone_title ?? '')) && $this->milestones()->count() === 0);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('unlock_date');
    }

    public function activeMilestone(): ?Milestone
    {
        return $this->milestones()->where('is_active', true)->first()
            ?? $this->milestones()->first();
    }

    public function postcards(): HasMany
    {
        return $this->hasMany(Postcard::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function loginTokens(): HasMany
    {
        return $this->hasMany(CreatorLoginToken::class);
    }
}
