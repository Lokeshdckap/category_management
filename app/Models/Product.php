<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'type',
        'sku',
        'slug',
        'short_description',
        'description',
        
        'price',
        'gp_percentage',
        'total_price',
        
        'bundle_subtotal',
        'bundle_gp_percentage',
        'bundle_final_price',
        
        'default_category_id',
        'meta_title',
        'meta_description',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'gp_percentage' => 'decimal:2',
        'total_price' => 'decimal:2',
        'bundle_subtotal' => 'decimal:2',
        'bundle_gp_percentage' => 'decimal:2',
        'bundle_final_price' => 'decimal:2',
    ];

    protected $appends = [
        'calculated_total_price', 
        'calculated_bundle_subtotal',
        'calculated_bundle_final_price',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->uuid)) {
                $product->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    public function defaultCategory()
    {
        return $this->belongsTo(Category::class, 'default_category_id');
    }

    public function compatibleProducts()
    {
        return $this->belongsToMany(
            Product::class,
            'compatible_products',
            'product_id',
            'compatible_product_id'
        );
    }

    public function bundleProducts()
    {
        return $this->belongsToMany(
            Product::class,
            'product_bundle',
            'bundle_id',
            'product_id'
        )
        ->withPivot('quantity', 'price')
        ->withTimestamps();
    }

    public function bundles()
    {
        return $this->belongsToMany(
            Product::class,
            'product_bundle',
            'product_id',
            'bundle_id'
        )
        ->withPivot('quantity', 'price')
        ->withTimestamps();
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeStandard($query)
    {
        return $query->where('type', 'standard');
    }

    public function scopeBundle($query)
    {
        return $query->where('type', 'bundle');
    }

    public function isBundle()
    {
        return $this->type === 'bundle';
    }

    public function isStandard()
    {
        return $this->type === 'standard';
    }


    public function getCalculatedTotalPriceAttribute()
    {
        if (!$this->isStandard() || !$this->price || !$this->gp_percentage) {
            return 0;
        }

        return $this->price + ($this->price * $this->gp_percentage / 100);
    }


    public function getCalculatedBundleSubtotalAttribute()
    {
        if (!$this->isBundle()) {
            return 0;
        }

        return $this->bundleProducts->sum(function ($product) {
            return $product->pivot->price * $product->pivot->quantity;
        });
    }

    public function getCalculatedBundleFinalPriceAttribute()
    {
        if (!$this->isBundle() || !$this->bundle_gp_percentage) {
            return $this->calculated_bundle_subtotal;
        }

        $subtotal = $this->calculated_bundle_subtotal;
        return $subtotal + ($subtotal * $this->bundle_gp_percentage / 100);
    }

 
    public function getBundleTotalQuantityAttribute()
    {
        if (!$this->isBundle()) {
            return 0;
        }

        return $this->bundleProducts->sum('pivot.quantity');
    }

    public function getFinalSellingPriceAttribute()
    {
        if ($this->isStandard()) {
            return $this->total_price ?? $this->calculated_total_price;
        }

        if ($this->isBundle()) {
            return $this->bundle_final_price ?? $this->calculated_bundle_final_price;
        }

        return 0;
    }
}