<?php
// app/Models/FoodComposition.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodCompositions extends Model
{
    use HasFactory;
    
 protected $fillable = [
        'food_name',
        'food_number',
        'source',
        'energy_kcal_100g',
        'proteins_100g',
        'fat_100g',
        'carbohydrates_100g',
    ];
 

    // 食品群での検索
    public function scopeByFoodGroup($query, $group)
    {
        return $query->where('food_group', $group);
    }

    // 食品名での部分一致検索
    public function scopeSearchByName($query, $name)
    {
        return $query->where('food_name', 'LIKE', "%{$name}%");
    }

    // エネルギー値での範囲検索
    public function scopeByEnergyRange($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('energy_kcal', '>=', $min);
        }
        if ($max !== null) {
            $query->where('energy_kcal', '<=', $max);
        }
        return $query;
    }

    // 高タンパク質食品の検索（タンパク質10g以上）
    public function scopeHighProtein($query, $minProtein = 10)
    {
        return $query->where('protein', '>=', $minProtein);
    }

    // 低脂質食品の検索（脂質5g以下）
    public function scopeLowFat($query, $maxFat = 5)
    {
        return $query->where('fat', '<=', $maxFat);
    }

    // 食物繊維豊富な食品（食物繊維3g以上）
    public function scopeHighFiber($query, $minFiber = 3)
    {
        return $query->where('dietary_fiber', '>=', $minFiber);
    }

}
