<?php

namespace Database\Factories;

use App\Models\FoodCompositions;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FoodCompositions>
 */
class FoodCompositionsFactory extends Factory
{
    protected $model = FoodCompositions::class;

    public function definition()
    {
        return [
            'food_name' => $this->faker->word(),
            // food_numberはunique制約があるので、必ずユニークな値を生成する
            'food_number' => $this->faker->unique()->numerify('##########'),
        ];
    }
}