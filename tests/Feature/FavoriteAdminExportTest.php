<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\FavoriteFood;
use App\Models\FoodLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
 // もし auth:sanctum を使うなら

class FavoriteAdminExportTest extends TestCase
{
    use RefreshDatabase;

     protected function setUp(): void
    {
        parent::setUp();
        //\Illuminate\Support\Facades\Artisan::call('route:list');
        \Illuminate\Support\Facades\Log::debug("ROUTE LIST:\n" . \Illuminate\Support\Facades\Artisan::output());

        $router = app('router');
        \Illuminate\Support\Facades\Log::debug('Router middleware map: ' . print_r($router->getMiddleware(), true));

        \Illuminate\Support\Facades\Log::debug('EnsureAdmin exists: ' . (class_exists(\App\Http\Middleware\EnsureAdmin::class) ? 'yes' : 'no'));
    }

    public function test_non_admin_cannot_export_favorites()
{
    $this->withoutExceptionHandling();

    $user = User::factory()->create(['is_admin' => false]);
    $target = User::factory()->create();
    FavoriteFood::factory()->count(3)->create(['user_id' => $target->id]);

    Sanctum::actingAs($user);  // 変更
    $this->getJson("/api/admin/favorites/export?user_id={$target->id}")
         ->assertStatus(403);
         
}

public function test_admin_can_export_favorites_returns_expected_json()
{
    $admin = User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create();

    // FoodLog を明示的に作成 (meal_type をセット)
    $foodLogs = FoodLog::factory()->count(3)->create([
        'meal_type' => 'breakfast',  // faker ではなく明示的にセットしてテスト
    ]);

    // FavoriteFood を FoodLog とリンク
    $favorites = [];
    foreach ($foodLogs as $foodLog) {
        $favorites[] = FavoriteFood::factory()->create([
            'user_id' => $target->id,
            'food_log_id' => $foodLog->id,
        ]);
    }

    Sanctum::actingAs($admin);
    $this->getJson("/api/admin/favorites/export?user_id={$target->id}")
         ->assertOk()
         ->assertJsonStructure([
             'count',
             'favorites' => [
                 '*' => ['favorite_id', 'food_log_id', 'food_name', 'energy', 'proteins', 'fat', 'carbs', 'meal_type']
             ]
         ])
         ->assertJson(['count' => 3]);
}
    public function test_service_throws_exception_when_full_fetch_exceeds_limit()
    {
        $target = User::factory()->create();

        // 閾値を小さくする（テスト用）
        config(['favorites.max_all' => 5]);

        // 作成数 = 6 で閾値を超える
        FavoriteFood::factory()->count(6)->create(['user_id' => $target->id]);
        $this->assertDatabaseCount('favorite_foods', 6);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('exceeds limit');

        // Service を直接呼ぶ（DI するか app() から取得）
        $service = app(\App\Services\FavoriteListService::class);
        $service->getFavoritesForUser($target->id, null);
    }
}
