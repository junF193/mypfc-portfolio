<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManualFoodRequest;
use App\Http\Requests\StoreFromHistoryFoodRequest;
use App\Services\FoodSuggestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FoodLogController extends Controller
{
    protected $foodSuggestionService;

    public function __construct(FoodSuggestionService $foodSuggestionService)
    {
        $this->foodSuggestionService = $foodSuggestionService;
    }

    public function getSuggestions()
    {
        $suggestions = $this->foodSuggestionService->getFrequentlyUsedFoods();
        return response()->json($suggestions);
    }

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

            return response()->json([
                'message' => 'メニューが登録されました',
                'data' => $foodLog
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Failed to create manual food log: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'request_data' => $validated,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'メニューの登録に失敗しました'], 500);
        }
    }

    public function storeFromHistory(StoreFromHistoryFoodRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        $consumedAt = $validated['date'] ?? Carbon::now('Asia/Tokyo')->toDateString();

        $existingHistory = $user->foodLogs()->where('id', $validated['from_history_id'])->first();
               
        if (!$existingHistory) {
            return response()->json(['message' => '履歴が見つからないか権限がありません'], 404);
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
            return response()->json([
                'message' => '履歴から登録しました',
                'data' => $newFoodLog
            ], 201);

        } catch (\Throwable $e) {
             Log::error('Failed to create food log from history: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'from_history_id' => $validated['from_history_id'],
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'サーバーエラーが発生しました'], 500);
        }
    }
}
