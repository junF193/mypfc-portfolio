<?php
namespace App\Services;

use App\Models\FoodLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // DBファサードをインポート

class IntegratedSearchService
{
    protected readonly FoodService $foodService;
    protected readonly LocalFoodService $localFoodService;

    public function __construct(FoodService $foodService, LocalFoodService $localFoodService)
    {
        $this->foodService = $foodService;
        $this->localFoodService = $localFoodService;
    }

    public function search(string $searchTerm): array
    {
        $errors = [];
        $apiProducts = collect();
        $dbProducts = collect();

        try {
            $apiProducts = $this->foodService->searchFood($searchTerm)
                ->map(function ($item) {
                    return [
                        'food_name' => (string)($item['product_name_ja'] ?? '不明'),
                        'food_number' => (string)($item['code'] ?? '不明'),
                        'source' => 'api',
                        'nutriments' => [
                            'energy_kcal_100g' => isset($item['nutriments']['energy-kcal_100g']) ? number_format($item['nutriments']['energy-kcal_100g'], 2, '.', '') : null,
                            'proteins_100g' => isset($item['nutriments']['proteins_100g']) ? number_format($item['nutriments']['proteins_100g'], 2, '.', '') : null,
                            'fat_100g' => isset($item['nutriments']['fat_100g']) ? number_format($item['nutriments']['fat_100g'], 2, '.', '') : null,
                            'carbohydrates_100g' => isset($item['nutriments']['carbohydrates_100g']) ? number_format($item['nutriments']['carbohydrates_100g'], 2, '.', '') : null,
                        ]
                    ];
                });
        } catch (\Exception $e) {
            Log::error('API検索エラー', ['search_term' => $searchTerm, 'error' => $e->getMessage()]);
            $errors[] = '検索結果の取得に失敗しました。お手数ですが、もう一度お試しください。';
        }

        try {
            // Eloquent Collectionを、ただの配列に変換してから、再度、汎用Collectionを、生成する
            $dbData = $this->localFoodService->searchFoodDb($searchTerm)->toArray();
            $dbProducts = collect($dbData)->map(function ($item) {
                // $itemは、オブジェクトではなく、配列なので、アクセス方法を、変更
                return [
                    'food_name' => (string)($item['food_name'] ?? '不明'),
                    'food_number' => (string)($item['food_number'] ?? '不明'),
                    'source' => 'db',
                    'nutriments' => [
                        'energy_kcal_100g' => isset($item['energy_kcal_100g']) ? number_format($item['energy_kcal_100g'], 2, '.', '') : null,
                        'proteins_100g' => isset($item['proteins_100g']) ? number_format($item['proteins_100g'], 2, '.', '') : null,
                        'fat_100g' => isset($item['fat_100g']) ? number_format($item['fat_100g'], 2, '.', '') : null,
                        'carbohydrates_100g' => isset($item['carbohydrates_100g']) ? number_format($item['carbohydrates_100g'], 2, '.', '') : null,
                    ],
                ];
            });
        } catch (\Exception $e) {
            Log::error('DB検索エラー', ['search_term' => $searchTerm, 'error' => $e->getMessage()]);
            $errors[] = 'データベースからの検索に失敗しました。';
        }

        // 2つの、汎用Collectionを、マージし、重複を、排除する
        $mergedProducts = $dbProducts->merge($apiProducts);
        $products = $mergedProducts->unique('food_name');

        // Collectionのまま、コントローラーに、返す
        return ['products' => $products->values(), 'success' => $products->isNotEmpty(), 'errors' => $errors];
    }
}