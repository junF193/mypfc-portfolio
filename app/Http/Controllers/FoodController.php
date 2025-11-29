<?php

namespace App\Http\Controllers;

use App\Http\Resources\HistoryResource;
use App\Services\FoodService;
use Illuminate\Http\Request;
use App\Services\IntegratedSearchService;
use App\Services\FoodSuggestionService;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use App\Models\FoodLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreManualFoodRequest;
use App\Http\Requests\StoreFromHistoryFoodRequest;


class FoodController extends Controller
{
    protected $foodService;
    protected $integratedSearchService;
    protected $foodSuggestionService;

    public function __construct(
        FoodService $foodService,
        IntegratedSearchService $integratedSearchService, 
        FoodSuggestionService $foodSuggestionService
    )
    {
        $this->foodService = $foodService;
        $this->integratedSearchService = $integratedSearchService;
        $this->foodSuggestionService = $foodSuggestionService;
    }

    /* 食品をキーワードで検索 */
    // 修正理由: バリデーションロジックをFormRequestに分離し、コントローラーを軽量化。
    // Early Returnを採用し、ネストを浅くすることで可読性を向上。
    public function searchFood(SearchFoodRequest $request)
    {
        // バリデーションはFormRequestで自動的に行われるため、ここでは不要

        $searchTerm = $request->input('search');
        $result = $this->foodService->searchFood($searchTerm);

        if (isset($result['error'])) {
            return view('food.search', ['results' => [], 'error' => $result['error']]);
        }
       
        return response()
            ->view('food.search', ['results' => $result['products']])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /* バーコードで食品を取得 */
    // 修正理由: SearchFoodRequestと同様に、バリデーションの分離と可読性の向上。
    public function getFoodByBarcode(GetFoodByBarcodeRequest $request)
    {
        $barcode = $request->input('barcode');
        $result = $this->foodService->getFoodByBarcode($barcode);

        if (isset($result['error'])) {
            return view('food.search', ['results' => [], 'error' => $result['error']]);
        }

       return response()
            ->view('food.search', ['results' => $result])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /* apiとdbの統合検索 */
    // 修正理由: 複雑なバリデーションルールをIntegratedSearchRequestに移動。
    // 変数名を明確にし、ロジックの流れを整理。
    public function search(IntegratedSearchRequest $request)
    {
        // バリデーション済みのデータを取得
        $validated = $request->validated();

        $searchTerm = $validated['search'] ?? null;
        $results = collect();

        if ($searchTerm) {
            $searchResult = $this->integratedSearchService->search($searchTerm);
            $results = $searchResult['products'];
        }

        return view('food.search', [
            'results' => $results,
            'meal_type' => $validated['meal_type'],
            'search_term' => $searchTerm, 
        ]);
    }

    /* メニューの登録 */
    // 修正理由: 変数名をスネークケース($new_food_log)からキャメルケース($newFoodLog)に変更し、Laravelの命名規則に準拠。
    // 不要な改行やスペースを削除し、コードの密度を適正化。
    // トランザクション内のロジックを整理。
    public function storeFromHistory(StoreFromHistoryFoodRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        $consumedAt = $validated['date'] ?? Carbon::now('Asia/Tokyo')->toDateString();

        $existingHistory = $user->foodLogs()->where('id', $validated['from_history_id'])->first();
               
        if (!$existingHistory) {
            return $this->error('履歴が見つからないか権限がありません', 404);
        }

        $multiplier = $validated['multiplier'];

        try {
            $newFoodLog = DB::transaction(function () use ($user, $validated, $consumedAt, $multiplier, $existingHistory) {
                return $user->foodLogs()->create([
                    'food_name' => $existingHistory->food_name,
                    'energy_kcal_100g' => $existingHistory->energy_kcal_100g,
                    'proteins_100g' => $existingHistory->proteins_100g,
                    'fat_100g' => $existingHistory->fat_100g,
                    'carbohydrates_100g' => $existingHistory->carbohydrates_100g,
                    'meal_type' => $validated['meal_type'], 
                    'source_type' => 'history',
                    'source_food_number' => $existingHistory->id,
                    'multiplier' => $multiplier,
                    'consumed_at' => $consumedAt,
                ]);
            });
            return $this->success($newFoodLog, '履歴から登録しました', 201);

        } catch (\Throwable $e) {
             Log::error('Failed to create food log from history: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'from_history_id' => $validated['from_history_id'],
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('サーバーエラーが発生しました', 500);
        }
    } 

    /* 手入力でメニューを登録 */
    // 修正理由: 既にFormRequestが使用されていたが、可読性向上のためコメントを追加。
    public function storeManual(StoreManualFoodRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        try {
            $foodLog = DB::transaction(function () use ($user, $validated) {
                return $user->foodLogs()->create([
                    'food_name' => $validated['food_name'],
                    'energy_kcal_100g' => $validated['energy_kcal_100g'] ?? null,
                    'proteins_100g' => $validated['proteins_100g'] ?? null,
                    'fat_100g' => $validated['fat_100g'] ?? null,
                    'carbohydrates_100g' => $validated['carbohydrates_100g'] ?? null,
                    'meal_type' => $validated['meal_type'],
                    'multiplier' => $validated['multiplier'] ?? 1.0,
                    'consumed_at' => $validated['consumed_at'] ?? Carbon::now('Asia/Tokyo')->toDateString(),
                    'source_type' => 'manual',
                    'source_food_number' => 'manual',
                ]);
            });

            return $this->success($foodLog, 'メニューが登録されました', 201);

        } catch (\Throwable $e) {
            Log::error('Failed to create manual food log: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'request_data' => $validated,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('メニューの登録に失敗しました', 500);
        }
    }

    /* メニューの登録ページの表示 */
    public function create(Request $request)
    {
         return view('food.foodLog');
    }

    /* 食事履歴の推薦を取得 */
    public function getSuggestions()
    {
        $suggestions = $this->foodSuggestionService->getFrequentlyUsedFoods();

        return response()->json($suggestions);
    }
}
