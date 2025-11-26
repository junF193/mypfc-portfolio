<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
{
    $foodLog = $this->foodLog ?? null;

    return [
        'history_id' => $this->id,
        'food_log_id' => $foodLog ? $foodLog->id : $this->food_log_id,
        'food_name' => $foodLog ? $foodLog->food_name : ($this->food_name ?? null),
        'energy' => $foodLog ? $foodLog->energy_kcal_100g : ($this->energy_kcal_100g ?? null),
        'proteins' => $foodLog ? $foodLog->proteins_100g : ($this->proteins_100g ?? null),
        'fat' => $foodLog ? $foodLog->fat_100g : ($this->fat_100g ?? null),
        'carbs' => $foodLog ? $foodLog->carbohydrates_100g : ($this->carbohydrates_100g ?? null),
        'meal_type' => $foodLog ? $foodLog->meal_type : ($this->meal_type ?? null),
        'multiplier' => $this->multiplier,
        'source_type' => $this->source_type,
        'source_food_number' => $this->source_food_number,
        'created_at' => $this->created_at?->toIso8601String(),
        'updated_at' => $this->updated_at?->toIso8601String(),
        'consumed_at' => $this->consumed_at?->toIso8601String(),
    ];
}



            
            
            



    }

