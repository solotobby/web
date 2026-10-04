<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stat extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id', 'sealed_count', 'founding_count',
    ];
}
