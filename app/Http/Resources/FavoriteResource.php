<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'food_name' => $this->food_name,
            'energy_kcal_100g' => $this->energy_kcal_100g,
            'proteins_100g' => $this->proteins_100g,
            'fat_100g' => $this->fat_100g,
            'carbohydrates_100g' => $this->carbohydrates_100g,
            'memo' => $this->memo,
            'source_food_log_id' => $this->source_food_log_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}