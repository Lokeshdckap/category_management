<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'uuid','name','sku','short_description','description',
        'slug','default_category_id','meta_title','meta_description','is_active'
    ];

     protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }


    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function defaultCategory()
    {
        return $this->belongsTo(Category::class, 'default_category_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
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
}
