<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\FavoriteFood;
use App\Models\User;
use App\Models\FoodLog;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FavoriteFood>
 */
class FavoriteFoodFactory extends Factory
{
    protected $model = FavoriteFood::class;

    public function definition()
    {
        return [
            // デフォルトでは関連モデルのファクトリを使って生成（テストで上書き可能）
            'user_id' => User::factory(),
            'food_log_id' => FoodLog::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * 関連の FoodLog を既成のものにする（便利なチェーンヘルパー）
     * 使い方: FavoriteFood::factory()->forFoodLog($foodLog)->create();
     */
    public function forFoodLog(FoodLog $foodLog)
    {
        return $this->state(function (array $attributes) use ($foodLog) {
            return [
                'food_log_id' => $foodLog->id,
            ];
        });
    }

    /**
     * 関連の User を既成のものにする（便利なチェーンヘルパー）
     * 使い方: FavoriteFood::factory()->forUser($user)->create();
     */
    public function forUser(User $user)
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->id,
            ];
        });
    }
}
