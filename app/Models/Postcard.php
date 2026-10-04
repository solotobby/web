<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Postcard extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'number', 'name', 'location', 'teaser', 'addressed_to',
        'sealed_at', 'founding', 'creator_id', 'milestone_id', 'seeded',
    ];

    protected function casts(): array
    {
        return [
            'addressed_to' => 'date',
            'sealed_at' => 'datetime',
            'founding' => 'boolean',
            'seeded' => 'boolean',
            'number' => 'integer',
        ];
    }

    public function envelope(): HasOne
    {
        return $this->hasOne(Envelope::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Creator::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function referral(): HasOne
    {
        return $this->hasOne(Referral::class);
    }
}
