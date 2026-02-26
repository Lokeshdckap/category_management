<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = ['name', 'sort_order', 'status'];

    protected static function booted()
    {
        static::updated(function ($attribute) {
            if ($attribute->isDirty('status')) {
                if ($attribute->status === 'inactive') {
                    // Deactivate all values for this attribute
                    $attribute->values()->update(['status' => 'inactive']);

                    // Also deactivate all variations using these values
                    $valueIds = $attribute->values()->pluck('id');
                    \DB::table('product_variations')
                        ->whereIn('id', function($query) use ($valueIds) {
                            $query->select('product_variation_id')
                                ->from('product_variation_attribute_values')
                                ->whereIn('attribute_value_id', $valueIds);
                        })
                        ->update(['status' => 'inactive']);
                } elseif ($attribute->status === 'active') {
                    // Activate all values for this attribute
                    $attribute->values()->update(['status' => 'active']);

                    // Also activate all variations using these values
                    $valueIds = $attribute->values()->pluck('id');
                    \DB::table('product_variations')
                        ->whereIn('id', function($query) use ($valueIds) {
                            $query->select('product_variation_id')
                                ->from('product_variation_attribute_values')
                                ->whereIn('attribute_value_id', $valueIds);
                        })
                        ->update(['status' => 'active']);
                }
            }
        });
    }

    public function values()
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order');
    }
}
