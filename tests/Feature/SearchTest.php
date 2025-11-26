<?php

namespace App\Http\Controllers;

use App\Services\FoodService;
use Illuminate\Http\Request;
use App\Services\IntegratedSearchService;
use App\Services\LocalFoodService;
use Illuminate\Support\Facades\Http;
use App\Models\FoodCompositions;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class SearchTest extends TestCase
{
    use RefreshDatabase;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // Helper for fake API
    protected function fakeOpenFoodFactsApi($products = [])
    {
        Http::fake([
            'https://world.openfoodfacts.org/cgi/search.pl*' => Http::response([
                'products' => $products,
            ], 200),
        ]);
    }

    /** @test */
    public function returns_correct_results_when_db_prioritized_on_match()
    {
        // Arrange: APIとDBで同じ名前のアイテムが存在する場合、DBが優先されることをテスト
        $this->fakeOpenFoodFactsApi([
            [
                'product_name_ja' => 'バナナ',
                'code' => 'API1000',
                'nutriments' => ['energy-kcal_100g' => 100, 'proteins_100g' => 1, 'fat_100g' => 1, 'carbohydrates_100g' => 1 ],
            ]
        ]);

        FoodCompositions::factory()->create([
            'food_name' => 'バナナ',
            'food_number' => 'DB1000',
            'energy_kcal_100g' => 90.55,
            'proteins_100g' => 1.11,
            'fat_100g' => 0.33,
            'carbohydrates_100g' => 22.88,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/food/search?search=バナナ');

        // Assert
        $response->assertStatus(200);
        // ★ 重複排除により、結果は1件になる
        $response->assertJsonCount(1, 'products');
        
        // ★ 返ってきた結果がDB由来のものであり、かつ栄養素も正しいことを確認
        $response->assertJsonFragment([
            'food_name' => 'バナナ',
            'food_number' => 'DB1000',
            'source' => 'db',
            'nutriments' => [
                'energy_kcal_100g' => '90.55',
                'proteins_100g' => '1.11',
                'fat_100g' => '0.33',
                'carbohydrates_100g' => '22.88',
            ]
        ]);
    }

    /** @test */
    public function returns_only_api_results_when_db_has_no_matches()
    {
        // Arrange: API has a result, DB is empty
        $this->fakeOpenFoodFactsApi([
            [
                'product_name_ja' => 'バナナ',
                'code' => 'API1000',
                'nutriments' => [
                    'energy-kcal_100g' => 100,
                    'proteins_100g' => 1.1,
                    'fat_100g' => 0.3,
                    'carbohydrates_100g' => 22.8,
                ],
            ]
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/food/search?search=バナナ');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'products');
        $response->assertJsonFragment([
            'food_name' => 'バナナ',
            'food_number' => 'API1000',
            'source' => 'api',
            'nutriments' => [
                'energy_kcal_100g' => '100.00',
                'proteins_100g' => '1.10',
                'fat_100g' => '0.30',
                'carbohydrates_100g' => '22.80',
            ]
        ]);
    }

    /** @test */
    public function returns_only_db_results_when_api_has_no_matches()
    {
        // Arrange: API returns no results, DB has one
        $this->fakeOpenFoodFactsApi([]); // Empty products

        FoodCompositions::factory()->create([ // Use correct factory
            'food_name' => 'バナナ',
            'food_number' => '1000',
            'energy_kcal_100g' => 100.00,
            'proteins_100g' => 1.10,
            'fat_100g' => 0.30,
            'carbohydrates_100g' => 22.80,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/food/search?search=バナナ');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'products');
        $response->assertJsonFragment([
            'food_name' => 'バナナ',
            'food_number' => '1000',
            'nutriments' => [
                'energy_kcal_100g' => '100.00',
                'proteins_100g' => '1.10',
                'fat_100g' => '0.30',
                'carbohydrates_100g' => '22.80',
            ]
        ]);
    }

    /** @test */
    public function returns_correct_results_when_both_api_and_db_return_multiple_items()
    {
        // Arrange: API has two items, DB has two different items
        $this->fakeOpenFoodFactsApi([
            [
                'product_name_ja' => 'APIバナナ',
                'code' => 'API1000',
                'nutriments' => ['energy-kcal_100g' => 89],
            ],
            [
                'product_name_ja' => 'APIリンゴ',
                'code' => 'API1001',
                'nutriments' => ['energy-kcal_100g' => 47],
            ],
        ]);

        FoodCompositions::factory()->create(['food_name' => 'DBみかん(果物)']);
        FoodCompositions::factory()->create(['food_name' => 'DBぶどう(果物)']);

        // Act
        // 検索ワードを、LocalFoodServiceのモックと合わせるため、より汎用的なものに変更
        $response = $this->actingAs($this->user)
            ->getJson('/api/food/search?search=果物'); 

        // Assert
        $response->assertStatus(200);
        // ★ API(2) + DB(2) = 4件の結果が返るはず
        $response->assertJsonCount(4, 'products');
        $response->assertJsonFragment(['food_name' => 'APIバナナ']);
        $response->assertJsonFragment(['food_name' => 'APIリンゴ']);
        $response->assertJsonFragment(['food_name' => 'DBみかん(果物)']);
        $response->assertJsonFragment(['food_name' => 'DBぶどう(果物)']);
    }

    /** @test */
    public function returns_empty_results_when_no_matches_found()
    {
        // Arrange: API and DB are empty
        $this->fakeOpenFoodFactsApi([]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/food/search?search=存在しない食材');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'products');
        $response->assertJson(['products' => []]);
    }
}