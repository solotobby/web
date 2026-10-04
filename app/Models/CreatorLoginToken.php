<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CreatorLoginToken extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'creator_id', 'token_hash', 'expires_at', 'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CreatorLoginToken $token) {
            if (! $token->id) {
                $token->id = (string) Str::uuid();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Creator::class);
    }

    public function isValid(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }
}
