<?php
namespace App\Services;

use App\Models\FoodLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FoodSuggestionService
{
    /**
     * ユーザーが、頻繁に、使用する、食事記録の、最新の、エントリを、取得する
     * 
     * @return Collection
     */
    public function getFrequentlyUsedFoods(int $userId): Collection
{


    $subQuery = FoodLog::query()
        ->select(
            'id',
            'user_id',
            'food_name',
            'energy_kcal_100g',
            'proteins_100g',
            'fat_100g',
            'carbohydrates_100g',
            'meal_type',
            'source_type',
            'source_food_number',
            'multiplier',
            'created_at',
            'updated_at',
            DB::raw('ROW_NUMBER() OVER (PARTITION BY food_name ORDER BY created_at DESC) as rn'),
            DB::raw('COUNT(*) OVER (PARTITION BY food_name) as frequency')
        )
        ->where('user_id', $userId);

    // main query に left join を追加して is_favorited を付与
    $sql = DB::table(DB::raw("({$subQuery->toSql()}) as sub"))
        ->mergeBindings($subQuery->getQuery())
        ->leftJoin('favorites as fav', function($join) use ($userId) {
            $join->on('sub.id', '=', 'fav.source_food_log_id')
                 ->where('fav.user_id', '=', $userId);
        })
        ->where('rn', 1)
        ->orderBy('frequency', 'desc')
        ->limit(10)
        ->select('sub.*', DB::raw('CASE WHEN fav.user_id IS NULL THEN 0 ELSE 1 END as is_favorited'), 'fav.id as favorite_id');

    return $sql->get();
}

}