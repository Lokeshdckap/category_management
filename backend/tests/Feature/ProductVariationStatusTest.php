<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\ProductVariation;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;

class ProductVariationStatusTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $category;
    protected $attribute;
    protected $value1;
    protected $value2;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);
        
        $this->category = Category::forceCreate([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Test Category',
            'slug' => 'test-category',
            'slug_url' => 'test-category',
            'status' => true
        ]);

        $this->attribute = Attribute::create(['name' => 'Color', 'status' => 'active']);
        $this->value1 = $this->attribute->values()->create(['value' => 'Red', 'status' => 'active']);
        $this->value2 = $this->attribute->values()->create(['value' => 'Green', 'status' => 'active']);

        $this->product = Product::forceCreate([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Status Product',
            'type' => 'combination',
            'sku' => 'STATUS-PROD',
            'default_category_id' => $this->category->id,
            'price' => 100,
            'gp_percentage' => 20,
            'total_price' => 120,
            'status' => 'active',
            'slug' => 'status-product'
        ]);
        $this->product->categories()->attach($this->category->id);

        // Create variations
        $this->var1 = $this->product->variations()->create([
            'sku' => 'VAR-RED',
            'price' => 110,
            'status' => 'active'
        ]);
        $this->var1->attributeValues()->attach($this->value1->id);

        $this->var2 = $this->product->variations()->create([
            'sku' => 'VAR-GREEN',
            'price' => 120,
            'status' => 'active'
        ]);
        $this->var2->attributeValues()->attach($this->value2->id);
    }

    public function test_inactive_variation_is_hidden_from_frontend()
    {
        // Initially visible
        $response = $this->getJson("/api/shop/products/{$this->product->slug}");
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('variations'));

        // Disable variation
        $this->var1->update(['status' => 'inactive']);

        $response = $this->getJson("/api/shop/products/{$this->product->slug}");
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('variations'));
        $this->assertEquals('VAR-GREEN', $response->json('variations.0.sku') ?? $this->var2->sku); // Checking SKU might be tricky if not in response, but we have ID
        $this->assertEquals($this->var2->id, $response->json('variations.0.id'));
    }

    public function test_disabling_attribute_value_cascades_to_variations()
    {
        // Disable attribute value "Red"
        $this->value1->update(['status' => 'inactive']);

        // Variation 1 should now be inactive
        $this->var1->refresh();
        $this->assertEquals('inactive', $this->var1->status);

        // Frontend should only show variation 2
        $response = $this->getJson("/api/shop/products/{$this->product->slug}");
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('variations'));
        $this->assertEquals($this->var2->id, $response->json('variations.0.id'));
    }

    public function test_disabling_attribute_cascades_to_values_and_variations()
    {
        // Disable attribute "Color"
        $this->attribute->update(['status' => 'inactive']);

        // All values and variations should be inactive
        $this->value1->refresh();
        $this->value2->refresh();
        $this->var1->refresh();
        $this->var2->refresh();

        $this->assertEquals('inactive', $this->value1->status);
        $this->assertEquals('inactive', $this->value2->status);
        $this->assertEquals('inactive', $this->var1->status);
        $this->assertEquals('inactive', $this->var2->status);

        // Frontend should show 0 variations
        $response = $this->getJson("/api/shop/products/{$this->product->slug}");
        $response->assertStatus(200);
        $this->assertCount(0, $response->json('variations'));
    }

    public function test_attribute_update_does_not_break_variation_associations()
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'name' => 'Color Updated',
            'status' => 'active',
            'values' => [
                ['id' => $this->value1->id, 'value' => 'Ruby Red', 'status' => 'active'],
                ['id' => $this->value2->id, 'value' => 'Emerald Green', 'status' => 'active'],
                ['value' => 'New Color', 'status' => 'active']
            ]
        ];

        $response = $this->putJson("/api/admin/attributes/{$this->attribute->id}", $payload);
        $response->assertStatus(200);

        // Verify that variations still have their attribute values
        $this->var1->refresh();
        $this->assertCount(1, $this->var1->attributeValues);
        $this->assertEquals('Ruby Red', $this->var1->attributeValues->first()->value);

        $this->var2->refresh();
        $this->assertCount(1, $this->var2->attributeValues);
        $this->assertEquals('Emerald Green', $this->var2->attributeValues->first()->value);
    }

    public function test_variation_override_price_is_preserved_on_update()
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');
        
        $category = Category::forceCreate([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Test Cat V2',
            'slug' => 'test-cat-v2',
            'slug_url' => 'test-cat-v2',
            'status' => true
        ]);
        $attribute = Attribute::create(['name' => 'Size-v2', 'status' => 'active']);
        $valS = $attribute->values()->create(['value' => 'S-v2', 'status' => 'active']);
        
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TP-V2',
            'type' => 'combination',
            'price' => 100,
            'gp_percentage' => 20,
            'total_price' => 120,
            'slug' => 'test-product-v2',
            'default_category_id' => $category->id,
            'status' => 'active'
        ]);

        $productAttr = $product->productAttributes()->create([
            'attribute_id' => $attribute->id,
            'is_variation' => true
        ]);
        $productAttr->assignedValues()->create(['attribute_value_id' => $valS->id]);

        $variation = $product->variations()->create([
            'sku' => 'TP-V2-S',
            'price' => 150,
            'override_price' => true,
            'status' => 'active'
        ]);
        $variation->attributeValues()->sync([$valS->id]);

        $this->assertEquals(150, $product->fresh()->variations[0]->price);

        // Update product, changing name but keeping variations with ID
        $response = $this->putJson("/api/admin/products/{$product->uuid}", [
            'name' => 'Updated Product Name',
            'type' => 'combination',
            'sku' => 'TP-V2',
            'price' => 100,
            'gp_percentage' => 20,
            'categories' => [$category->id],
            'default_category_id' => $category->id,
            'status' => 'active',
            'combination_attributes' => [
                [
                    'attribute_id' => $attribute->id,
                    'is_variation' => true,
                    'values' => [
                        ['attribute_value_id' => $valS->id]
                    ]
                ]
            ],
            'variations' => [
                [
                    'id' => $variation->id,
                    'sku' => 'TP-V2-S',
                    'price' => 150,
                    'override_price' => true,
                    'status' => 'active',
                    'attribute_values' => [
                        ['attribute_value_id' => $valS->id]
                    ]
                ]
            ]
        ]);

        $response->assertStatus(200);
        $product->refresh();
        
        $this->assertEquals('Updated Product Name', $product->name);
        $this->assertCount(1, $product->variations);
        $this->assertEquals($variation->id, $product->variations[0]->id);
        $this->assertEquals(150, $product->variations[0]->price);
        $this->assertTrue($product->variations[0]->override_price);
    }

    public function test_attribute_is_hidden_if_all_values_are_inactive()
    {
        $category = Category::forceCreate([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Test Cat V3',
            'slug' => 'test-cat-v3',
            'slug_url' => 'test-cat-v3',
            'status' => true
        ]);
        $attribute = Attribute::create(['name' => 'Material', 'status' => 'active']);
        $valCotton = $attribute->values()->create(['value' => 'Cotton', 'status' => 'inactive']);
        
        $product = Product::forceCreate([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Material Product',
            'sku' => 'MAT-PROD',
            'type' => 'combination',
            'default_category_id' => $category->id,
            'price' => 100,
            'gp_percentage' => 20,
            'total_price' => 120,
            'status' => 'active',
            'slug' => 'material-product'
        ]);

        $productAttr = $product->productAttributes()->create([
            'attribute_id' => $attribute->id,
            'is_variation' => false
        ]);
        $productAttr->assignedValues()->create(['attribute_value_id' => $valCotton->id]);

        // Fetch from frontend
        $response = $this->getJson("/api/shop/products/material-product");
        $response->assertStatus(200);
        
        // Assert that 'product_attributes' does not contain 'Material'
        $attributes = collect($response->json('data.product_attributes'));
        $this->assertFalse($attributes->contains('name', 'Material'));
    }

    public function test_enabling_attribute_value_cascades_to_variations()
    {
        $this->value1->update(['status' => 'inactive']);
        $this->product->variations[0]->refresh();
        $this->assertEquals('inactive', $this->product->variations[0]->status);

        // Enable it back
        $this->value1->update(['status' => 'active']);
        $this->product->variations[0]->refresh();
        $this->assertEquals('active', $this->product->variations[0]->status);
    }
}
