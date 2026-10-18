<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCoolingSpec extends Model
{
    protected $primaryKey = 'product_id';
    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'type',
        'tdp',
    ];

    protected function casts(): array
    {
        return [
            'tdp' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}