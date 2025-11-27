<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FoodController;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Controllers\FavoriteController; 
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;




// ✅ Sanctum の CSRF Cookie エンドポイント (オプション)
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
});
Route::middleware('auth:sanctum')->get('/debug-session', function () {
    return response()->json([
        'auth_check' => Auth::check(),
        'user_id' => Auth::id(),
        'session_id' => session()->getId(),
        'cookie_name' => config('session.cookie'),
        'cookie_domain' => config('session.domain'),
        'all_cookies' => request()->cookies->all(),
    ]);
});
// ✅ 認証確認用エンドポイント (デバッグ用)
//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
   // return $request->user();
//});
    Route::get('/favorites', function (Request $request) {
        Log::info('Favorites request', [
            'auth_check' => Auth::check(),
            'user_id' => Auth::id(),
            'session_id' => session()->getId(),
        ]);
        
        // 既存のコントローラー呼び出し
       
    });


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return response()->json([
        'id' => $request->user()->id,
        'name' => $request->user()->name,
        'email' => $request->user()->email,
    ]);
});

// お気に入りマスター (Favorite) に関するAPI
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::post('/favorites/from-history', [FavoriteController::class, 'storeFromHistory'])->name('favorites.store.from_history');
    Route::patch('/favorites/{favorite}', [FavoriteController::class, 'update'])->name('favorites.update');
    Route::delete('/favorites/{favorite}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');
    Route::delete('/favorites/by-food-log/{foodLogId}', [FavoriteController::class, 'destroyByFoodLog'])->name('favorites.destroy.by_food_log');

    // User Profile
    Route::post('/user/profile', [App\Http\Controllers\MypageController::class, 'updateProfile'])->name('user.profile.update');
});

// ✅ 将来的に追加する API エンドポイント
// Route::middleware('auth:sanctum')->group(function () {
//     Route::apiResource('food-logs', FoodLogController::class);
//     Route::get('/nutrition-summary', [NutritionController::class, 'summary']);
// });

//Route::get('/food/search', [FoodController::class, 'search'])->name('food.search');

//Route::post('/food/store', [FoodController::class, 'store'])->name('food.store');

//Route::get('/food/barcode', [FoodController::class, 'getFoodByBarcode'])->name('food.barcode');
