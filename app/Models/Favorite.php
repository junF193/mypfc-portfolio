<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;

    // テーブル名を複数形のスネークケース（favorites）にしているので、
    // $tableプロパティの指定は不要（Laravelが自動で解決してくれる）

    protected $fillable = [
        'user_id',
        'source_food_log_id',
        'food_name',
        'energy_kcal_100g',
        'proteins_100g',
        'fat_100g',
        'carbohydrates_100g',
        'memo',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'energy_kcal_100g' => 'float',
        'proteins_100g' => 'float',
        'fat_100g' => 'float',
        'carbohydrates_100g' => 'float',
    ];

    /**
     * このお気に入りを所有するユーザーを取得
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * このお気に入りのコピー元となった食事履歴を取得（存在する場合）
     */
    public function sourceFoodLog()
    {
        return $this->belongsTo(FoodLog::class, 'source_food_log_id');
    }
}