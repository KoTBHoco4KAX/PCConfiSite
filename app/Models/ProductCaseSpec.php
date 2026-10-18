<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCaseSpec extends Model
{
    protected $primaryKey = 'product_id';
    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'form_factor',
        'color',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}