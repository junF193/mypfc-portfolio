<?php

namespace App\Http\Controllers;

use App\Services\FoodSuggestionService;
use App\Services\DailyNutritionService;
use App\Services\FavoriteListService;
use Illuminate\Http\Request;
use App\Models\FavoriteFood;
use Illuminate\Support\Facades\Log;
use App\Services\TDEEService;
use App\Http\Requests\UpdateProfileRequest;

class MypageController extends Controller
{
    /**
     * FoodSuggestionServiceの、インスタンス
     */
    protected FoodSuggestionService $foodSuggestionService;
    protected DailyNutritionService $dailyNutritionService;
    protected FavoriteListService $favoriteListService;
    protected TDEEService $tdeeService;

    /**
     * 新しい、コントローラーの、インスタンスを、生成する
     */
    public function __construct(
        FoodSuggestionService $foodSuggestionService,
        DailyNutritionService $dailyNutritionService,
        FavoriteListService $favoriteListService,
        TDEEService $tdeeService
    ) {
        $this->foodSuggestionService = $foodSuggestionService;
        $this->dailyNutritionService = $dailyNutritionService;
        $this->favoriteListService = $favoriteListService;
        $this->tdeeService = $tdeeService;
    }

    /**
     * Mypageを、表示する
     */
    public function index(Request $request)
    {
         $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => '認証が必要です'
            ], 401);
        }

        // サービスを、呼び出し、推薦リストを、取得する
        $suggestions = $this->foodSuggestionService->getFrequentlyUsedFoods($user->id);

         $suggestions = collect($suggestions ?? []);

        $suggestionIds = $suggestions->pluck('id')->filter()->unique()->values();

        $favoriteIds = [];

            if ($suggestionIds->isNotEmpty()) {
                $favoriteIds = $this->favoriteListService->getFavoriteSourceIdsByUserAndLogs($user, $suggestionIds->all());
            }

        // 取得した、データを、ビューに、渡す
        return view('mypage.index', [
            'suggestions' => $suggestions,
            'favoriteIds' => $favoriteIds,
            'user' => $user, // Pass user for profile data
        ]);
    }

    public function history(Request $request)
    {
        $user = $request->user();
        $history = $this->foodSuggestionService->getFrequentlyUsedFoods($user->id) ?? [];
        return response()->json($history);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'message' => 'プロフィールを更新しました',
            'user' => $user,
        ]);
    }

    public function dailyNutrition(Request $request)
    {
        $validated = $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        $user = $request->user();
        $date = $validated['date'] ?? null;

        try {
            $data = $this->dailyNutritionService->getDailyTotals($user->id, $date);

            // Calculate Targets
            $tdee = $this->tdeeService->calculateTDEE($user);
            $targets = $this->tdeeService->calculateTargetPFC($tdee);

            $data['goal'] = [
                'calories' => $tdee,
                'protein' => $targets['protein'],
                'fat' => $targets['fat'],
                'carbs' => $targets['carbs'],
            ];
            
            return response()->json($data);
        } catch (\Throwable $e) {
            Log::error('dailyNutrition error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'データ取得中にエラーが発生しました'], 500);
        }
    }
}

