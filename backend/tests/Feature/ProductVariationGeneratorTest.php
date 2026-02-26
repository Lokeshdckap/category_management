<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;

class ProductVariationGeneratorTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_preserves_variation_settings_on_regeneration()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $user = User::factory()->create();
        $user->assignRole($adminRole);
        Sanctum::actingAs($user);

        // Existing variations (Before adding Color)
        $existingVariations = [
            [
                'name' => 'Small',
                'sku' => 'SKU-S',
                'price' => 100,
                'override_price' => true,
                'is_default' => true,
                'status' => 'active',
                'attribute_values' => [
                    ['attribute_value_id' => 10],
                    ['attribute_value_id' => 99] // Extra non-variation attribute
                ]
            ],
            [
                'name' => 'Large',
                'sku' => 'SKU-L',
                'price' => 200,
                'override_price' => true,
                'is_default' => false,
                'status' => 'active',
                'attribute_values' => [
                    ['attribute_value_id' => 11],
                    ['attribute_value_id' => 99] // Extra non-variation attribute
                ]
            ]
        ];

        // New attributes (Size + Color)
        $attributes = [
            [
                'attribute_id' => 1,
                'name' => 'Size',
                'values' => [
                    ['attribute_value_id' => 10, 'value' => 'Small'],
                    ['attribute_value_id' => 11, 'value' => 'Large']
                ]
            ],
            [
                'attribute_id' => 2,
                'name' => 'Color',
                'values' => [
                    ['attribute_value_id' => 20, 'value' => 'Red'],
                    ['attribute_value_id' => 21, 'value' => 'Green']
                ]
            ]
        ];

        $response = $this->postJson('/api/admin/products/generate-variations', [
            'attributes' => $attributes,
            'existing_variations' => $existingVariations
        ]);

        $response->assertStatus(200);
        $variations = $response->json('variations');

        // Total variations should be 4 (2 sizes * 2 colors)
        $this->assertCount(4, $variations);

        // Check Small, Red (Order depends on cartesian product, but usually first matched)
        $smallRed = collect($variations)->where('name', 'Small, Red')->first();
        $this->assertEquals(100, $smallRed['price']);
        $this->assertTrue($smallRed['override_price']);
        $this->assertEquals('SKU-S', $smallRed['sku']);

        // Check Small, Green (Should have 0 price)
        $smallGreen = collect($variations)->where('name', 'Small, Green')->first();
        $this->assertEquals(0, $smallGreen['price']);
        $this->assertFalse($smallGreen['override_price']);

        // Check Large, Red (Should have 200 price)
        $largeRed = collect($variations)->where('name', 'Large, Red')->first();
        $this->assertEquals(200, $largeRed['price']);
        $this->assertTrue($largeRed['override_price']);

        // Check Large, Green (Should have 0 price)
        $largeGreen = collect($variations)->where('name', 'Large, Green')->first();
        $this->assertEquals(0, $largeGreen['price']);
    }
}
