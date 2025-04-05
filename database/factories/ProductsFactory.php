<?php

namespace Database\Factories;

use App\Models\Brands;
use App\Models\Products;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attributes>
 */

class ProductsFactory extends Factory
{

    public function definition()
    {
        return [
            'sku' => $this->faker->unique()->bothify('???-####'),
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'brand_id' => Brands::factory(),
            'is_active' => true,
            'slug' => Str::slug($this->faker->word),
        ];
    }
}
