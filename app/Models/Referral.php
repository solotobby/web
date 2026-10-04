<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'postcard_id', 'creator_id', 'amount_cents', 'cut_cents', 'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (Referral $referral) {
            if (! $referral->id) {
                $referral->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function postcard(): BelongsTo
    {
        return $this->belongsTo(Postcard::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Creator::class);
    }
}
