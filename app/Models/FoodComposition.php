<?php
// app/Models/FoodComposition.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodCompositions extends Model
{
    use HasFactory;

    protected $fillable = [
        'food_group',
        'food_number',
        'index_number',
        'food_name',
        'refuse_rate',
        'energy_kj',
        'energy_kcal',
        'water',
        'protein',
        'protein_amino_acid',
        'fat',
        'triglyceride',
        'cholesterol',
        'carbohydrate',
        'available_carb_monosaccharide',
        'available_carb_mass',
        'available_carb_subtraction',
        'dietary_fiber',
        'sugar_alcohol',
        'organic_acid',
        'ash',
        'sodium',
        'potassium',
        'calcium',
        'magnesium',
        'phosphorus',
        'iron',
        'zinc',
        'copper',
        'manganese',
        'iodine',
        'selenium',
        'chromium',
        'molybdenum',
        'retinol_activity_equivalent',
        'retinol',
        'alpha_carotene',
        'beta_carotene',
        'beta_cryptoxanthin',
        'beta_carotene_equivalent',
        'vitamin_d',
        'alpha_tocopherol',
        'beta_tocopherol',
        'gamma_tocopherol',
        'delta_tocopherol',
        'vitamin_k',
        'vitamin_b1',
        'vitamin_b2',
        'niacin',
        'niacin_equivalent',
        'vitamin_b6',
        'vitamin_b12',
        'folate',
        'pantothenic_acid',
        'biotin',
        'vitamin_c',
        'alcohol',
        'salt_equivalent',
        'remarks'
    ];

    protected $casts = [
        'refuse_rate' => 'decimal:2',
        'energy_kj' => 'integer',
        'energy_kcal' => 'integer',
        'water' => 'decimal:1',
        'protein' => 'decimal:1',
        'protein_amino_acid' => 'decimal:1',
        'fat' => 'decimal:1',
        'triglyceride' => 'decimal:1',
        'cholesterol' => 'integer',
        'carbohydrate' => 'decimal:1',
        'available_carb_monosaccharide' => 'decimal:1',
        'available_carb_mass' => 'decimal:1',
        'available_carb_subtraction' => 'decimal:1',
        'dietary_fiber' => 'decimal:1',
        'sugar_alcohol' => 'decimal:1',
        'organic_acid' => 'decimal:1',
        'ash' => 'decimal:1',
        'sodium' => 'integer',
        'potassium' => 'integer',
        'calcium' => 'integer',
        'magnesium' => 'integer',
        'phosphorus' => 'integer',
        'iron' => 'decimal:1',
        'zinc' => 'decimal:1',
        'copper' => 'decimal:2',
        'manganese' => 'decimal:2',
        'iodine' => 'integer',
        'selenium' => 'integer',
        'chromium' => 'integer',
        'molybdenum' => 'integer',
        'retinol_activity_equivalent' => 'integer',
        'retinol' => 'integer',
        'alpha_carotene' => 'integer',
        'beta_carotene' => 'integer',
        'beta_cryptoxanthin' => 'integer',
        'beta_carotene_equivalent' => 'integer',
        'vitamin_d' => 'decimal:1',
        'alpha_tocopherol' => 'decimal:1',
        'beta_tocopherol' => 'decimal:1',
        'gamma_tocopherol' => 'decimal:1',
        'delta_tocopherol' => 'decimal:1',
        'vitamin_k' => 'integer',
        'vitamin_b1' => 'decimal:2',
        'vitamin_b2' => 'decimal:2',
        'niacin' => 'decimal:1',
        'niacin_equivalent' => 'decimal:1',
        'vitamin_b6' => 'decimal:2',
        'vitamin_b12' => 'decimal:1',
        'folate' => 'integer',
        'pantothenic_acid' => 'decimal:2',
        'biotin' => 'decimal:1',
        'vitamin_c' => 'integer',
        'alcohol' => 'decimal:1',
        'salt_equivalent' => 'decimal:1',
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

// =============================================
// database/migrations/xxxx_xx_xx_create_food_compositions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration