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
        'contact_url',
        'recommended_priority',
        'email_subject',
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

    public function specialityBadgeClass(): string
    {
        return match (strtolower((string) $this->speciality)) {
            'gaming' => 'bg-purple-950/60 text-purple-300 border-purple-800/60',
            'technology' => 'bg-blue-950/60 text-blue-300 border-blue-800/60',
            'lifestyle' => 'bg-emerald-950/60 text-emerald-300 border-emerald-800/60',
            'travel' => 'bg-amber-950/60 text-amber-300 border-amber-800/60',
            default => 'bg-slate-900 text-slate-300 border-slate-700',
        };
    }

    public function priorityBadgeClass(): string
    {
        return match (strtoupper((string) $this->recommended_priority)) {
            'A' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
            'B' => 'bg-blue-500/10 text-blue-400 border-blue-500/30',
            default => 'bg-slate-800 text-slate-400 border-slate-700',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'Contacted' => 'bg-amber-500/10 text-amber-400 border-amber-500/30',
            'Replied' => 'bg-sky-500/10 text-sky-400 border-sky-500/30',
            'In Discussion' => 'bg-purple-500/10 text-purple-400 border-purple-500/30',
            'Onboarded' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
            'Declined', 'Passed' => 'bg-rose-500/10 text-rose-400 border-rose-500/30',
            default => 'bg-slate-800/80 text-slate-400 border-slate-700/80',
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
