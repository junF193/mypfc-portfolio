<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\CsvImportController;
use App\Http\Controllers\MypageController;
use App\Http\Controllers\FavoriteFoodController; // ★ 追加
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\AuthenticatedSessionController;





Route::get('/debug-auth', function () {
    return response()->json([
        'auth_check' => Auth::check(),
        'auth_id' => Auth::id(),
        'user' => Auth::user(),
        'session_id' => session()->getId(),
        'session_driver' => config('session.driver'),
        'web_middleware' => [
            'StartSession' => class_exists(\Illuminate\Session\Middleware\StartSession::class),
        ],
        'latest_session' => DB::table('sessions')->latest('last_activity')->first(),
    ]);
})->middleware('web');


Route::get('/debug-csrf', function () {
    return [
        'csrf_token_function' => csrf_token(),
        'request_cookie' => request()->cookie('XSRF-TOKEN'),
        'php_supercookie' => $_COOKIE['XSRF-TOKEN'] ?? null,
    ];
})->middleware('web');

Route::get('/debug-cookie', function () {
    return response()->json([
        'config_domain' => config('session.domain'),
        'config_path' => config('session.path'),
        'config_secure' => config('session.secure'),
        'config_same_site' => config('session.same_site'),
        'session_id' => session()->getId(),
        'cookies_sent' => request()->cookies->all(),
    ]);
})->middleware('web');


Route::get('/', function () {
    return view('welcome');
});

Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login');
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/test-storage', function () {
    // ... (省略)
});
require __DIR__.'/auth.php';



Route::middleware('auth')->group(function () {
    Route::get('/mypage',[MypageController::class, 'index'])->name('mypage.index');
    Route::get('/mypage/daily-nutrition', [MypageController::class, 'dailyNutrition'])->name('mypage.daily-nutrition');
    Route::get('/food/search', [FoodController::class, 'search'])->name('food.search');
    Route::get('/food/log/create', [FoodController::class, 'create'])->name('food-log.create');
    Route::get('/food/barcode', [FoodController::class, 'getFoodByBarcode'])->name('food.barcode');
    Route::post('/food/store', [FoodController::class, 'store'])->name('food.store');
    Route::get('/food-suggestions', [FoodController::class, 'getSuggestions'])->name('food-suggestions.index');
    Route::post('/food-logs', [FoodController::class, 'storeManual'])->name('food-logs.store');


    
    // ★★★ お気に入り機能の、ルート ★★★

});

// API (JavaScriptからの、非同期通信用)
//Route::middleware('auth:sanctum')->group(function () {
    //Route::get('/api/food-suggestions', [FoodController::class, 'getSuggestions'])->name('food-suggestions.index');
    //Route::post('/api/food-logs', [FoodController::class, 'store'])->name('food-logs.store');

     // ★★★ お気に入り機能の、ルート ★★★
   // Route::get('/api/favorites', [FavoriteFoodController::class, 'apiIndex'])->name('favorites.apiIndex');
    // Route::post('/favorites', [FavoriteFoodController::class, 'store'])->name('favorites.store');
   // Route::delete('/favorites/{food_log}', [FavoriteFoodController::class, 'destroy'])->name('favorites.destroy');
    //Route::get('/favorites', [FavoriteFoodController::class, 'index'])->name('favorites.index');

    
    
//});



Route::get('/process-csv', [CsvImportController::class, 'processCsv']);