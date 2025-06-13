<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Product\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function createClient(): User
    {
        $user = User::factory()->create(['type_user' => 2]);
        $role = Role::firstOrCreate(['name' => 'Usuario'], ['description' => 'Cliente']);
        $user->roles()->sync([$role->id]);
        return $user;
    }

    private function createAdmin(): User
    {
        $user = User::factory()->create(['type_user' => 1]);
        $role = Role::firstOrCreate(['name' => 'Admin'], ['description' => 'Admin']);
        $user->roles()->sync([$role->id]);
        return $user;
    }

    private function productPayload(): array
    {
        return [
            'title' => 'Prod ' . uniqid(),
            'portada' => UploadedFile::fake()->image('prod.jpg'),
        ];
    }

    public function test_client_can_create_up_to_three_products(): void
    {
        $user = $this->createClient();
        $this->actingAs($user, 'api');

        for ($i = 1; $i <= 3; $i++) {
            $response = $this->postJson('/api/admin/products', $this->productPayload());
            $response->assertStatus(200);
        }
    }

    public function test_client_cannot_create_more_than_three_products(): void
    {
        $user = $this->createClient();
        Product::factory()->count(3)->create(['user_id' => $user->id]);
        $this->actingAs($user, 'api');

        $response = $this->postJson('/api/admin/products', $this->productPayload());
        $response->assertStatus(403);
    }

    public function test_admin_has_no_product_limit(): void
    {
        $user = $this->createAdmin();
        $this->actingAs($user, 'api');

        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/admin/products', $this->productPayload());
            $response->assertStatus(200);
        }
    }
}
