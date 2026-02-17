<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductDefaultSupplierTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Create an admin user
        $this->user = User::factory()->create();
        
        try {
             $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
             $this->user->assignRole($role);
        } catch (\Exception $e) {
            // Role might probably exist or table not ready if migrations run differently.
            // Using RefreshDatabase should handle it.
        }
    }

    public function test_can_create_product_with_default_supplier()
    {
        $supplier1 = Supplier::create(['name' => 'Supplier 1', 'status' => 'active', 'price' => 100]);
        $supplier2 = Supplier::create(['name' => 'Supplier 2', 'status' => 'active', 'price' => 200]);
        $category = Category::create(['name' => 'Category 1', 'slug' => 'cat-1', 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'slug_url' => 'cat-1']);

        $productData = [
            'name' => 'Test Product',
            'sku' => 'TEST-SKU-001',
            'status' => 'active',
            'type' => 'standard',
            'default_category_id' => $category->id,
            'categories' => [$category->id],
            'price' => 150,
            'gp_percentage' => 10,
            'default_supplier_id' => $supplier2->id,
            'suppliers' => [
                ['id' => $supplier1->id, 'price' => 100],
                ['id' => $supplier2->id, 'price' => 200]
            ]
        ];

        $response = $this->actingAs($this->user)
                         ->postJson('/api/admin/products', $productData);

        $response->assertStatus(201); // or 201

        $this->assertDatabaseHas('products', [
            'sku' => 'TEST-SKU-001',
            'default_supplier_id' => $supplier2->id
        ]);
    }

    public function test_can_update_product_default_supplier()
    {
        $supplier1 = Supplier::create(['name' => 'Supplier 1', 'status' => 'active', 'price' => 100]);
        $supplier2 = Supplier::create(['name' => 'Supplier 2', 'status' => 'active', 'price' => 200]);
        $category = Category::create(['name' => 'Category 1', 'slug' => 'cat-1', 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'slug_url' => 'cat-1']);

        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-SKU-002',
            'status' => 'active',
            'type' => 'standard',
            'default_category_id' => $category->id,
            'price' => 100,
            'gp_percentage' => 10,
            'default_supplier_id' => $supplier1->id
        ]);
        
        // Attach suppliers
        $product->suppliers()->attach($supplier1->id, ['price' => 100]);
        $product->suppliers()->attach($supplier2->id, ['price' => 200]);

        $updateData = [
            'name' => 'Test Product Updated',
            'sku' => 'TEST-SKU-002',
            'status' => 'active',
            'type' => 'standard',
            'default_category_id' => $category->id,
            'categories' => [$category->id],
            'price' => 150,
            'gp_percentage' => 15,
            'default_supplier_id' => $supplier2->id,
             'suppliers' => [
                ['id' => $supplier1->id, 'price' => 100],
                ['id' => $supplier2->id, 'price' => 200]
            ]
        ];
        
        $uuid = $product->uuid ?? $product->id; // Fallback if no uuid

        $response = $this->actingAs($this->user)
                         ->putJson("/api/admin/products/{$uuid}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'default_supplier_id' => $supplier2->id
        ]);
    }

    public function test_validates_invalid_default_supplier()
    {
        $category = Category::create(['name' => 'Category 1', 'slug' => 'cat-1', 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'slug_url' => 'cat-1']);

        $productData = [
            'name' => 'Test Product',
            'sku' => 'TEST-SKU-003',
            'status' => 'active',
            'type' => 'standard',
            'default_category_id' => $category->id,
            'categories' => [$category->id],
            'price' => 150,
            'gp_percentage' => 10,
            'default_supplier_id' => 99999, // Non-existent ID
        ];

        $response = $this->actingAs($this->user)
                         ->postJson('/api/admin/products', $productData);

        $response->assertStatus(422); // Validation error
        $response->assertJsonValidationErrors(['default_supplier_id']);
    }
}
