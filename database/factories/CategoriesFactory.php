<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Categories>
 */
class CategoriesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
// database/factories/CategoriesFactory.php

    public function definition(): array
    {
        $name = $this->faker->unique()->word;

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . uniqid(), // Düzeltilmiş kısım
            'parent_category_id' => null,
        ];
    }

    /**
     * Define the state for a subcategory.
     *
     * @param  int|null  $parentId
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function asSubcategory($parentId = null): Factory
    {
        return $this->state(function (array $attributes) use ($parentId) {
            return [
                'parent_category_id' => $parentId,  // Alt kategori için parent_id ekliyoruz
            ];
        });
    }
}