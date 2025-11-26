<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class FoodLog extends Model
{
    use HasFactory;


    protected $fillable = [
        'food_name',
        'user_id',
        'energy_kcal_100g',
        'proteins_100g',
        'fat_100g',
        'carbohydrates_100g',
        'meal_type',
        'source_type',
        'source_food_number',
        'multiplier',
        'consumed_at',
    ];

    protected $casts = [
        'energy_kcal_100g' => 'float',
        'proteins_100g' => 'float',
        'fat_100g' => 'float',
        'carbohydrates_100g' => 'float',
        'multiplier' => 'float', 
        'meal_type' => 'string',
    ];

    /**
     * この食事記録を所有するユーザーを取得
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
