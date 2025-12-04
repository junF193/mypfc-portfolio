<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\FoodLog;
use App\Enums\Gender;
use App\Enums\ActivityLevel;
use App\Enums\DietGoal;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create User
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'height' => 170,
            'weight' => 60,
            'age' => 30,
            'gender' => Gender::Male,
            'activity_level' => ActivityLevel::Medium,
            'diet_goal' => DietGoal::Maintain,
        ]);

        // 2. Create Food Log (Breakfast: Toast)
        FoodLog::create([
            'user_id' => $user->id,
            'food_name' => 'トースト',
            'energy_kcal_100g' => 200, // Assuming 1 slice is approx 200kcal for simplicity or per 100g if multiplier is used. 
                                       // Request says "Toast (200kcal) 1 piece". 
                                       // Let's assume 200kcal/100g and multiplier 1 for simplicity, or adjust.
                                       // Usually toast is ~260kcal/100g. 6枚切り is ~60g (~160kcal).
                                       // User asked for "Toast (200kcal)". I will set 200kcal/100g and multiplier 1.
            'proteins_100g' => 5.0,
            'fat_100g' => 3.0,
            'carbohydrates_100g' => 35.0,
            'meal_type' => 'breakfast',
            'source_type' => 'manual',
            'source_food_number' => 'manual_1',
            'multiplier' => 1.0,
            'consumed_at' => Carbon::today(),
        ]);
    }
}
