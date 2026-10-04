<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Milestone extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'creator_id', 'title', 'unlock_date', 'description', 'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (Milestone $milestone) {
            if (! $milestone->id) {
                $milestone->id = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'unlock_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function formattedUnlockDate(): string
    {
        if ($this->unlock_date) {
            return $this->unlock_date->format('j F Y');
        }

        return '1 January 2028';
    }

    public function daysRemaining(): ?int
    {
        if (! $this->unlock_date) {
            return null;
        }

        return max(0, (int) now()->diffInDays($this->unlock_date, false));
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Creator::class);
    }

    public function postcards(): HasMany
    {
        return $this->hasMany(Postcard::class);
    }
}
