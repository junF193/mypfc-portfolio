<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Favorite;
use App\Models\FoodLog;
use Illuminate\Support\Facades\Log;
use App\Services\FavoriteListService;
use App\Http\Resources\FavoriteResource;
use App\Http\Requests\StoreFavoriteRequest;
use App\Http\Requests\UpdateFavoriteRequest;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    protected FavoriteListService $favoriteListService;

    public function __construct(FavoriteListService $favoriteListService)
    {
        $this->favoriteListService = $favoriteListService;
    }

    public function store(StoreFavoriteRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();
        $foodLogId = $validated['food_log_id'];

        // 重複チェック
        $existingFavorite = $user->favorites()->where('source_food_log_id', $foodLogId)->first();

        // 重複ok
        if ($existingFavorite) {
            return $this->success(new FavoriteResource($existingFavorite), '既にお気に入りに登録されています', 200);
        }

        try {
            $favorite = DB::transaction(function () use ($user, $foodLogId) {
                $sourceLog = FoodLog::findOrFail($foodLogId);

                // リレーション経由で作成することで、user_idが自動的に設定される
                // FoodLogからデータをコピーしてスナップショットを作成
                $fav = $user->favorites()->create([
                    'source_food_log_id' => $sourceLog->id,
                    'food_name' => $sourceLog->food_name,
                    'energy_kcal_100g' => $sourceLog->energy_kcal_100g,
                    'proteins_100g' => $sourceLog->proteins_100g,
                    'fat_100g' => $sourceLog->fat_100g,
                    'carbohydrates_100g' => $sourceLog->carbohydrates_100g,
                    'memo' => '', // 初期値は空
                ]);
                return $fav;
            });

            return $this->success(new FavoriteResource($favorite), 'お気に入りに登録しました', 201);

        } catch (\Throwable $e) {
            Log::error('Favorite create failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'food_log_id' => $foodLogId,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('お気に入りの登録に失敗しました: ' . $e->getMessage(), 500);
        }
    }

   

    

    /**
     * お気に入り削除 (Favorite ID指定)
     * Vue側から呼ばれる標準的な削除
     */
    public function destroy(Favorite $favorite)
    {
        $this->authorize('delete', $favorite);

        try {
            $favorite->delete();
            return $this->success(null, 'お気に入りから削除しました');
        } catch (\Throwable $e) {
            Log::error('Favorite delete failed: ' . $e->getMessage());
            return $this->error('お気に入りの削除に失敗しました', 500);
        }
    }

    /**
     * お気に入り削除 (FoodLog ID指定)
     * JS側 (履歴リスト) から呼ばれる
     */
    public function destroyByFoodLog(Request $request, $foodLogId)
    {
        $user = $request->user();

        try {
            $result = DB::transaction(function () use ($user, $foodLogId) {
                $favoriteFood = $user->favorites()->where('source_food_log_id', $foodLogId)->first();

                if (!$favoriteFood) {
                    return false;
                }

                return $favoriteFood->delete();
            });

            if (!$result) {
                // 既に削除されている場合も成功とみなすか、404を返すか。
                // UIの整合性を保つため、404でも良いが、メッセージは控えめに。
                return $this->error('お気に入りに登録されていません', 404);
            }

            return $this->success(['food_log_id' => (int)$foodLogId], 'お気に入りから削除しました');

        } catch (\Throwable $e) {
            Log::error('Favorite delete by food_log failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'food_log_id' => $foodLogId,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('お気に入りの削除に失敗しました', 500);
        }
    }

    /**
     * お気に入り一覧取得
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 20);
            
            $favorites = $this->favoriteListService->getFavoritesForUser($user, $perPage);

            // APIリソースコレクションを直接データとして渡す
            return $this->success(FavoriteResource::collection($favorites));

        } catch (\Throwable $e) {
            Log::error('Failed to load favorites: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('お気に入りの読み込みに失敗しました', 500);
        }
    }

    /**
     * お気に入り編集
     */

    public function update(UpdateFavoriteRequest $request, Favorite $favorite)
    {
        $this->authorize('update', $favorite);

        $validated = $request->validated();

        try{
            $favorite->update($validated);

           return $this->success(new FavoriteResource($favorite), 'お気に入りを更新しました');

         } catch (\Throwable $e) {
        // 5. 失敗時の処理（エラー内容も記録する）
        Log::error('Favorite update error: ' . $e->getMessage());
         return $this->error('お気に入りの更新に失敗しました', 500);
    }

    


}
}
