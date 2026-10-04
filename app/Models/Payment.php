<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'stripe_session_id', 'stripe_payment_intent', 'amount_cents',
        'currency', 'status', 'postcard_id', 'draft', 'creator_slug', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'draft' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (! $payment->id) {
                $payment->id = (string) Str::uuid();
            }
        });
    }

    public function postcard(): BelongsTo
    {
        return $this->belongsTo(Postcard::class);
    }
}
