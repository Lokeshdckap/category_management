<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;

class ProductVariationPersistenceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $user = User::factory()->create();
        $user->assignRole($adminRole);
        Sanctum::actingAs($user);
    }

    public function test_it_persists_variation_price_overrides()
    {
        $category = Category::forceCreate([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Test Category',
            'slug' => 'test-category'
        ]);
        $attr = Attribute::create([
            'name' => 'Size'
        ]);
        $val = AttributeValue::create([
            'attribute_id' => $attr->id,
            'value' => 'Large'
        ]);

        $payload = [
            'name' => 'Test Product',
            'type' => 'combination',
            'sku' => 'TEST-SKU-123',
            'categories' => [$category->id],
            'default_category_id' => $category->id,
            'price' => 100,
            'gp_percentage' => 20,
            'combination_attributes' => [
                [
                    'attribute_id' => $attr->id,
                    'is_variation' => true,
                    'values' => [
                        ['attribute_value_id' => $val->id]
                    ]
                ]
            ],
            'variations' => [
                [
                    'sku' => 'VAR-SKU-1',
                    'price' => 150.50,
                    'override_price' => '1', // String '1' as sent by FormData
                    'is_default' => '1',
                    'status' => 'active',
                    'attribute_values' => [
                        ['attribute_value_id' => $val->id]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/admin/products', $payload);

        if ($response->status() !== 201) {
            dump($response->json());
        }

        $response->assertStatus(201);
        
        $product = Product::where('sku', 'TEST-SKU-123')->first();
        $this->assertCount(1, $product->variations);
        
        $variation = $product->variations->first();
        $this->assertEquals('VAR-SKU-1', $variation->sku);
        $this->assertEquals(150.50, (float)$variation->price);
        $this->assertTrue($variation->override_price);
        $this->assertTrue($variation->is_default);
    }

    public function test_it_updates_variation_price_overrides()
    {
        $category = Category::forceCreate([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Update Category',
            'slug' => 'update-category'
        ]);
        $product = Product::forceCreate([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Update Product',
            'type' => 'combination',
            'sku' => 'UPDATE-SKU',
            'default_category_id' => $category->id,
            'price' => 100,
            'gp_percentage' => 20
        ]);
        $product->categories()->attach($category->id);

        $attr = Attribute::create([
            'name' => 'Size'
        ]);
        $val = AttributeValue::create([
            'attribute_id' => $attr->id,
            'value' => 'Large'
        ]);

        $payload = [
            'name' => $product->name,
            'type' => 'combination',
            'sku' => $product->sku,
            'categories' => [$category->id],
            'default_category_id' => $category->id,
            'price' => 100,
            'gp_percentage' => 20,
            'combination_attributes' => [
                [
                    'attribute_id' => $attr->id,
                    'is_variation' => true,
                    'values' => [
                        ['attribute_value_id' => $val->id]
                    ]
                ]
            ],
            'variations' => [
                [
                    'sku' => 'UPDATED-VAR-SKU',
                    'price' => 199.99,
                    'override_price' => true,
                    'is_default' => true,
                    'status' => 'active',
                    'attribute_values' => [
                        ['attribute_value_id' => $val->id]
                    ]
                ]
            ]
        ];

        // API uses POST for update because of multipart/form-data and _method=PUT
        $payload['_method'] = 'PUT';
        $response = $this->postJson("/api/admin/products/{$product->uuid}", $payload);

        if ($response->status() !== 200) {
            dump($response->json());
        }
        
        $response->assertStatus(200);
        
        $product->refresh();
        $this->assertCount(1, $product->variations);
        
        $variation = $product->variations->first();
        $this->assertEquals('UPDATED-VAR-SKU', $variation->sku);
        $this->assertEquals(199.99, (float)$variation->price);
        $this->assertTrue($variation->override_price);
    }
}
