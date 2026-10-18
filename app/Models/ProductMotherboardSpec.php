<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMotherboardSpec extends Model
{
    protected $primaryKey = 'product_id';
    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'socket',
        'chipset',
        'form_factor',
        'ram_type',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}