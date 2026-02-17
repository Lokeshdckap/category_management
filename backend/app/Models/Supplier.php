<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
   protected $fillable = [
    'name',
    'description',
    'status',
    'is_default',
    'duty_percentage',
    'shipping_cost'
   ];

   protected $casts = [
    'is_default' => 'boolean',
    'duty_percentage' => 'decimal:2',
    'shipping_cost' => 'decimal:2'
   ];

   public function products()
   {
       return $this->belongsToMany(Product::class, 'product_supplier')
           ->withPivot('price')
           ->withTimestamps();
   }
}
