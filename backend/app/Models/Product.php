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
        
        'cost_mode',
        'override_shipping_cost',
        'rrp_cost',
        'override_rrp_cost',
        'product_cost',
        
        'bundle_subtotal',
        'bundle_gp_percentage',
        'bundle_final_price',
        
        'default_category_id',
        'default_supplier_id',
        'meta_title',
        'meta_description',
        'status',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'gp_percentage' => 'decimal:2',
        'total_price' => 'decimal:2',
        'override_shipping_cost' => 'decimal:2',
        'rrp_cost' => 'decimal:2',
        'override_rrp_cost' => 'decimal:2',
        'product_cost' => 'decimal:2',
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

    public function defaultSupplier()
    {
        return $this->belongsTo(Supplier::class, 'default_supplier_id');
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
        return $this->hasMany(ProductImage::class)->orderBy('is_primary', 'desc')->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->ofMany([
            'is_primary' => 'max',
            'sort_order' => 'min',
        ], 'id');
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

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'product_supplier')
            ->withPivot('price')
            ->withTimestamps();
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

        $componentsSubtotal = $this->bundleProducts->sum(function ($product) {
            $price = $product->pivot->price > 0 ? $product->pivot->price : ($product->total_price ?? 0);
            return $price * $product->pivot->quantity;
        });

        return $componentsSubtotal + ($this->price ?? 0);
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