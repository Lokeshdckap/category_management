<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_bundle', function (Blueprint $table) {
            $table->id();
              // The bundle product (parent)
            $table->foreignId('bundle_id')
                  ->constrained('products')
                  ->onDelete('cascade');
            
            // The standard product that is part of the bundle (child)
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade');
            
            // Bundle-specific data
            $table->integer('quantity')->default(1); // Quantity of this product in the bundle
            $table->decimal('price', 10, 2)->default(0); 
            $table->timestamps();


             $table->unique(['bundle_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_bundle');
    }
};
