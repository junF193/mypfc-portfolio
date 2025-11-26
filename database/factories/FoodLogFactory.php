<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\FoodLog;
use App\Models\User;



/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FoodLog>
 */
class FoodLogFactory extends Factory
{
    protected $model = FoodLog::class;

    public function definition()
    {
        // 適度なレンジの栄養素を生成
        $energy = $this->faker->randomFloat(1, 10, 800); // kcal per 100g
        $proteins = $this->faker->randomFloat(1, 0, 50); // g per 100g
        $fat = $this->faker->randomFloat(1, 0, 50);      // g per 100g
        $carbs = max(0, $energy / 4 - $proteins - $fat); // 単純なバランス(任意)

        $mealTypes = ['breakfast', 'lunch', 'dinner', 'snack'];

        return [
             'user_id' => User::factory(),
            'food_name' => $this->faker->words(2, true),
            'energy_kcal_100g' => round($energy, 1),
            'proteins_100g' => round($proteins, 1),
            'fat_100g' => round($fat, 1),
            'carbohydrates_100g' => round($carbs, 1),
            'meal_type' => $this->faker->randomElement($mealTypes),
            'source_type' => 'manual',
            'source_food_number' => $this->faker->regexify('[A-Z]{2}[0-9]{6}'),
            // 必要なら他のカラムを追加
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * 状態ヘルパー: 指定の栄養値を与える
     */
    public function withNutrition(array $vals = [])
    {
        return $this->state(function (array $attributes) use ($vals) {
            return array_merge($attributes, $vals);
        });
    }
}
