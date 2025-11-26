<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Foods extends Model
{
    use HasFactory;
    protected $fillable = [
        'food_name',
        'food_number',
        'source_type',
        'energy_kcal_100g',
        'proteins_100g',
        'fat_100g',
        'carbohydrates_100g',
    ];
}
