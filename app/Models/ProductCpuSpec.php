<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCpuSpec extends Model
{
    protected $primaryKey = 'product_id';
    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'socket',
        'cores',
        'performance',
        'tdp',
        'has_igpu',
    ];

    protected function casts(): array
    {
        return [
            'cores' => 'integer',
            'performance' => 'integer',
            'tdp' => 'integer',
            'has_igpu' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}