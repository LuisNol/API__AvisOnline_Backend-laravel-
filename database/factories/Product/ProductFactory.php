<?php

namespace Database\Factories\Product;

use App\Models\Product\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->unique()->word(),
            'slug' => $this->faker->slug(),
            'imagen' => 'products/test.jpg',
            'user_id' => User::factory(),
        ];
    }
}
