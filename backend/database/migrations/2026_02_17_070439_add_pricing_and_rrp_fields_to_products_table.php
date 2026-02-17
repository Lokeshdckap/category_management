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
            $table->enum('cost_mode', ['default', 'average'])->default('default')->after('type');
            $table->decimal('override_shipping_cost', 10, 2)->nullable()->after('cost_mode');
            $table->decimal('rrp_cost', 10, 2)->default(0)->after('override_shipping_cost');
            $table->decimal('override_rrp_cost', 10, 2)->nullable()->after('rrp_cost');
            $table->decimal('product_cost', 10, 2)->default(0)->after('override_rrp_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['cost_mode', 'override_shipping_cost', 'rrp_cost', 'override_rrp_cost', 'product_cost']);
        });
    }
};
