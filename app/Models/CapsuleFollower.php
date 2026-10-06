<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapsuleFollower extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'email',
        'milestone_id',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Creator::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }
}
