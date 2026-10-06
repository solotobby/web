<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreatorNomination extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'creator_name',
        'handle_or_url',
        'platform',
        'milestone_hint',
        'reason',
        'nominator_name',
        'nominator_email',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (CreatorNomination $nomination) {
            if (! $nomination->id) {
                $nomination->id = (string) Str::uuid();
            }
        });
    }
}
