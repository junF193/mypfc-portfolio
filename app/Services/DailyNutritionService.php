<?php
namespace App\Services;

use App\Models\FoodLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DailyNutritionService
{
    /**
     * 指定ユーザー・指定日の栄養合計を返す
     *
     * @param int $userId
     * @param string|null $date YYYY-MM-DD 省略時は今日 (Asia/Tokyo)
     * @return array
     */
    public function getDailyTotals(int $userId, ?string $date = null): array
    {
        $tz = 'Asia/Tokyo';
        $day = $date ? Carbon::createFromFormat('Y-m-d', $date, $tz) : Carbon::now($tz);

       

        

        // DB側で合計を一回で取得（multiplier null -> 1, nutritions null -> 0）
        $row = FoodLog::query()
            ->where('user_id', $userId)
            ->whereDate('consumed_at', $day->format('Y-m-d'))
            ->selectRaw('
                SUM(COALESCE(proteins_100g,0) * COALESCE(multiplier,1)) as protein_g,
                SUM(COALESCE(fat_100g,0) * COALESCE(multiplier,1)) as fat_g,
                SUM(COALESCE(carbohydrates_100g,0) * COALESCE(multiplier,1)) as carbs_g
            ')
            ->first();

        $protein_g = (float) ($row->protein_g ?? 0);
        $fat_g     = (float) ($row->fat_g ?? 0);
        $carbs_g   = (float) ($row->carbs_g ?? 0);

        if (! $row) {
    // レコードがない場合はゼロを返す
    $protein_g = $fat_g = $carbs_g = 0.0;
} else {
    // aggregate カラムは既に SUM(...) as protein_g などで用意されているはず
    $protein_g = (float) ($row->protein_g ?? 0);
    $fat_g     = (float) ($row->fat_g ?? 0);
    $carbs_g   = (float) ($row->carbs_g ?? 0);

    // optional: ログに集計行を出す（デバッグ用）
    Log::debug('daily totals row', [
        'protein_g' => $protein_g,
        'fat_g' => $fat_g,
        'carbs_g' => $carbs_g,
    ]);
}

        // kcal 計算
        $protein_kcal = $protein_g * 4.0;
        $fat_kcal     = $fat_g * 9.0;
        $carbs_kcal   = $carbs_g * 4.0;

        $calories_total = $protein_kcal + $fat_kcal + $carbs_kcal;

        if ($calories_total <= 0) {
            $p_percent = $f_percent = $c_percent = 0.0;
        } else {
            $p_percent = round($protein_kcal / $calories_total * 100, 1);
            $f_percent = round($fat_kcal / $calories_total * 100, 1);
            $c_percent = round($carbs_kcal / $calories_total * 100, 1);
        }

    

        // Collection で整形（将来の加工が楽）
        $result = collect([
            'date' => $day->format('Y-m-d'),
            'calories_total' => round($calories_total, 1),
            'protein_g' => round($protein_g, 1),
            'fat_g' => round($fat_g, 1),
            'carbs_g' => round($carbs_g, 1),
        ])->put('protein_kcal', round($protein_kcal, 1))
          ->put('fat_kcal', round($fat_kcal, 1))
          ->put('carbs_kcal', round($carbs_kcal, 1))
          ->put('pfc_percent', [
              'protein' => $p_percent,
              'fat' => $f_percent,
              'carbs' => $c_percent,
          ])
          ->put('chart', [
              'labels' => ['Protein', 'Fat', 'Carbs'],
              'data' => [round($protein_kcal, 1), round($fat_kcal, 1), round($carbs_kcal, 1)],
          ]);

        return $result->toArray();
    }
}

