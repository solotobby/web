<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreatorProspect extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'prospect_number',
        'creator',
        'speciality',
        'primary_outreach_angle',
        'contact_type',
        'public_email',
        'email_status',
        'email_type',
        'email_source',
        'outreach_readiness',
        'contact_url',
        'recommended_priority',
        'email_subject',
        'personalised_email',
        'status',
        'personalisation_note',
        'email',
        'internal_notes',
        'last_contacted_at',
    ];

    protected $casts = [
        'prospect_number' => 'integer',
        'last_contacted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (CreatorProspect $prospect) {
            if (! $prospect->id) {
                $prospect->id = (string) Str::uuid();
            }
        });
    }

    public function effectiveEmail(): ?string
    {
        return $this->email ?: $this->public_email;
    }

    public function hasEmail(): bool
    {
        return ! empty($this->effectiveEmail());
    }

    public function defaultEmailBody(): string
    {
        if (! empty($this->personalised_email)) {
            return $this->personalised_email;
        }

        $firstName = explode(' ', $this->creator)[0];

        return "Hi {$firstName},\n\n"
            . "I’m reaching out because your community is already built around shared moments, milestones and live reactions.\n\n"
            . "We’re building FanVault — The Creator Time Capsule.\n\n"
            . "The idea is simple: you set a future milestone or special moment, and your community leaves messages, memories, photos and predictions for you today. The vault stays sealed, so you don’t see what people wrote until the moment arrives.\n\n"
            . "Then, when you hit the milestone, you open it live with your community.\n\n"
            . "Instead of another “thank you for the support” post, you get a real moment:\n"
            . "“{$this->creator} just hit the milestone. 438 people left something behind. Let’s see what they wrote.”\n\n"
            . "That can become a live-stream segment, a YouTube video, Shorts/Reels/TikTok clips, and eventually a permanent part of your creator timeline.\n\n"
            . "We’d love to partner with you and set one up completely free of charge.\n\n"
            . "No setup cost, no subscription and no technical work required from you. We’ll handle the setup and work with you to make the capsule fit your community.\n\n"
            . "If you’re open to it, I’d love to create a FanVault for your next milestone and let you try it at zero cost.\n\n"
            . "FanVault: https://getfanvault.com/\n\n"
            . "Would you be open to giving it a try?\n\n"
            . "Best,\n"
            . "Oluwatobi\n"
            . "Founder, FanVault\n"
            . "The Creator Time Capsule\n";
    }

    public function specialityBadgeClass(): string
    {
        return match (strtolower((string) $this->speciality)) {
            'gaming' => 'bg-purple-50 text-purple-700 border-purple-200',
            'technology' => 'bg-blue-50 text-blue-700 border-blue-200',
            'lifestyle' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'travel' => 'bg-amber-50 text-amber-800 border-amber-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }

    public function priorityBadgeClass(): string
    {
        return match (strtoupper((string) $this->recommended_priority)) {
            'A' => 'bg-emerald-50 text-emerald-700 border-emerald-200 font-semibold',
            'B' => 'bg-blue-50 text-blue-700 border-blue-200 font-semibold',
            default => 'bg-slate-100 text-slate-600 border-slate-200 font-semibold',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'Contacted' => 'bg-blue-50 text-blue-700 border-blue-200',
            'Replied' => 'bg-sky-50 text-sky-700 border-sky-200',
            'In Discussion' => 'bg-purple-50 text-purple-700 border-purple-200',
            'Onboarded' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'Declined', 'Passed' => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-slate-100 text-slate-600 border-slate-200',
        };
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        if (trim($term) === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('creator', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('public_email', 'like', "%{$term}%")
                ->orWhere('primary_outreach_angle', 'like', "%{$term}%")
                ->orWhere('email_subject', 'like', "%{$term}%");
        });
    }
}
