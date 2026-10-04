<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Envelope extends Model
{
    protected $primaryKey = 'postcard_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'postcard_id', 'letter', 'email', 'photo_path',
        'predictions', 'claim_token_hash', 'author_user_id',
    ];

    protected function casts(): array
    {
        return [
            'predictions' => 'array',
        ];
    }

    public function postcard(): BelongsTo
    {
        return $this->belongsTo(Postcard::class);
    }
}
