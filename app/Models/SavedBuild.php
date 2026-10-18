<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedBuild extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'total_price',
        'snapshot',
        'is_public',
    ];

    protected $casts = [
        'snapshot'   => 'array',
        'is_public'  => 'boolean',
        'total_price'=> 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}