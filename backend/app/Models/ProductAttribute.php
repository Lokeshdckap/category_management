<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    protected $fillable = ['product_id', 'attribute_id', 'is_variation', 'sort_order'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function assignedValues()
    {
        return $this->hasMany(ProductAttributeValue::class)->orderBy('sort_order');
    }
}
