<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'sku',
        'price',
        'stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function cpuSpec(): HasOne
    {
        return $this->hasOne(ProductCpuSpec::class, 'product_id');
    }

    public function gpuSpec(): HasOne
    {
        return $this->hasOne(ProductGpuSpec::class, 'product_id');
    }

    public function motherboardSpec(): HasOne
    {
        return $this->hasOne(ProductMotherboardSpec::class, 'product_id');
    }

    public function ramSpec(): HasOne
    {
        return $this->hasOne(ProductRamSpec::class, 'product_id');
    }

    public function storageSpec(): HasOne
    {
        return $this->hasOne(ProductStorageSpec::class, 'product_id');
    }

    public function caseSpec(): HasOne
    {
        return $this->hasOne(ProductCaseSpec::class, 'product_id');
    }

    public function coolingSpec(): HasOne
    {
        return $this->hasOne(ProductCoolingSpec::class, 'product_id');
    }

    public function psuSpec(): HasOne
    {
        return $this->hasOne(ProductPsuSpec::class, 'product_id');
    }

    public function getSnapshotData(): array
    {
        $specs = match ($this->category?->slug) {
            'cpu' => $this->cpuSpec?->toArray(),
            'gpu' => $this->gpuSpec?->toArray(),
            'motherboard' => $this->motherboardSpec?->toArray(),
            'ram' => $this->ramSpec?->toArray(),
            'storage' => $this->storageSpec?->toArray(),
            'pc_case' => $this->caseSpec?->toArray(),
            'cooling' => $this->coolingSpec?->toArray(),
            'psu' => $this->psuSpec?->toArray(),
            default => [],
        };

        return [
            'product_id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'price' => (float) $this->price,
            'brand' => $this->brand?->name,
            'category' => $this->category?->name,
            'specs' => $specs ?? [],
        ];
    }
}