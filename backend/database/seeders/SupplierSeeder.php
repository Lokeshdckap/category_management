<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Supplier::updateOrCreate(
            ['name' => 'Default Supplier'],
            [
                'description' => 'System created default supplier.',
                'status' => 'active',
                'is_default' => true
            ]
        );
    }
}
