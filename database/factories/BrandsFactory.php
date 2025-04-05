<?php
namespace Database\Factories;

use App\Models\Brands;
use Illuminate\Database\Eloquent\Factories\Factory;

class BrandsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Brands::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->company, // Rastgele bir şirket ismi
            'logo_url' => $this->faker->imageUrl(100, 100, 'business', true), // Rastgele bir logo URL'si
        ];
    }
}
