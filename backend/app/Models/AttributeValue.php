<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    protected $fillable = ['attribute_id', 'value', 'sort_order', 'status'];

    protected static function booted()
    {
        static::updated(function ($attributeValue) {
            if ($attributeValue->isDirty('status')) {
                if ($attributeValue->status === 'inactive') {
                    // Deactivate all variations that use this attribute value
                    $attributeValue->variations()->update(['status' => 'inactive']);
                } elseif ($attributeValue->status === 'active') {
                    // Activate all variations that use this attribute value
                    $attributeValue->variations()->update(['status' => 'active']);
                }
            }
        });
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function variations()
    {
        return $this->belongsToMany(ProductVariation::class, 'product_variation_attribute_values');
    }
}
