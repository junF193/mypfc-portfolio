<?php
namespace App\Services;


use App\Models\Post;
use Illuminate\Support\Collection;
use App\Models\FoodCompositions;

class LocalFoodService 
{
    public function __construct(private FoodCompositions $FoodCompositions)  {}
    
    public function searchFoodDb(string $searchTerm): Collection 
    {
        return $this->FoodCompositions
            ->where('food_name', 'like', "%{$searchTerm}%")
            ->limit(20)
            ->select(['food_name', 'food_number', 'energy_kcal_100g','proteins_100g', 'fat_100g', 'carbohydrates_100g',])
            ->get();
    }

}

