<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attribute;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class AttributeEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $user = User::factory()->create();
        $user->assignRole($role);
        Sanctum::actingAs($user);
    }

    public function test_it_prevents_duplicate_attribute_names_case_insensitive()
    {
        Attribute::create(['name' => 'Size', 'status' => 'active']);

        $response = $this->postJson('/api/admin/attributes', [
            'name' => 'size',
            'values' => [['value' => 'S']]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    public function test_it_prevents_duplicate_attribute_values_case_insensitive()
    {
        $response = $this->postJson('/api/admin/attributes', [
            'name' => 'Color',
            'values' => [
                ['value' => 'Red'],
                ['value' => 'red']
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('values');
    }

    public function test_it_filters_attributes_by_status()
    {
        Attribute::create(['name' => 'Active Attr', 'status' => 'active']);
        Attribute::create(['name' => 'Inactive Attr', 'status' => 'inactive']);

        $response = $this->getJson('/api/admin/attributes?status=active');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertTrue(collect($data)->contains('name', 'Active Attr'));
        $this->assertFalse(collect($data)->contains('name', 'Inactive Attr'));
    }

    public function test_it_saves_attribute_status()
    {
        $response = $this->postJson('/api/admin/attributes', [
            'name' => 'Material',
            'status' => 'inactive',
            'values' => [['value' => 'Cotton']]
        ]);

        $response->assertStatus(201);
        $this->assertEquals('inactive', $response->json('status'));
        
        $attribute = Attribute::find($response->json('id'));
        $this->assertEquals('inactive', $attribute->status);
    }
}
