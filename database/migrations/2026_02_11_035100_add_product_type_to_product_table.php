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
        Schema::table('products', function (Blueprint $table) {
             $table->enum('type', ['standard', 'bundle'])
                  ->default('standard')
                  ->after('name');

             $table->decimal('price', 10, 2)
                  ->nullable()
                  ->after('description');
            
            $table->decimal('gp_percentage', 5, 2)
                  ->nullable()
                  ->after('price');
            $table->decimal('total_price', 10, 2)->nullable();

            $table->decimal('bundle_subtotal', 10, 2)->nullable(); 
            $table->decimal('bundle_gp_percentage', 5, 2)->nullable(); 
            $table->decimal('bundle_final_price', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['type', 'price', 'gp_percentage']);
        });
    }
};
