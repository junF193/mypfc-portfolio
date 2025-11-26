<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodStoreTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected array $foodDataFromSearch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        // 「検索結果から」をデフォルトのテストデータとして用意
        $this->foodDataFromSearch = [
            'food_name' => 'バナナ',
            'source_food_number' => 'DB1000',
            'energy_kcal_100g' => 100.00,
            'proteins_100g' => 1.10,
            'fat_100g' => 0.30,
            'carbohydrates_100g' => 22.80,
            'source_type' => 'db',
            'meal_type' => 'breakfast',
        ];
    }

    /** @test */
    public function stores_food_log_from_search_successfully()
    {
        // Act: デフォルトの「検索結果」データでAPIにリクエスト
        $response = $this->actingAs($this->user)
            ->postJson('/api/food/store', $this->foodDataFromSearch);

        // Assert: 検証
        $response->assertStatus(201);
        $this->assertDatabaseHas('food_logs', $this->foodDataFromSearch);
    }
    
    /** @test */
    public function stores_manual_food_log_successfully()
    {
        // Arrange: 「手入力」用のデータに上書き
        $manualData = $this->foodDataFromSearch;
        $manualData['source_type'] = 'manual';
        $manualData['source_food_number'] = 'manual_' . time();

        // Act: 「手入力」用データでAPIにリクエスト
        $response = $this->actingAs($this->user)
            ->postJson('/api/food/store', $manualData);

        // Assert: 検証
        $response->assertStatus(201);
        $this->assertDatabaseHas('food_logs', $manualData);
    }
    /** @test */
    public function it_returns_validation_error_if_food_name_is_missing()
    {
        $invaildData = $this->foodDataFromSearch;
        unset ($invaildData['food_name']);

        $response = $this->actingAs($this->user)
            ->postJson('/api/food/store', $invaildData);

        $response->assertStatus(422);

        $response->assertJsonValidationErrorFor('food_name');


    }
    /** @test */
    public function it_returns_validation_error_if_meal_type_is_invalid()
    {
        $invaildData = $this->foodDataFromSearch;
        $invaildData['meal_type'] = 'brunch';

        $response = $this->actingAs($this->user)
            ->postJson('/api/food/store', $invaildData);

        $response->assertStatus(422);

        $response->assertJsonValidationErrorFor('meal_type');




    }
     /** @test */
    public function it_returns_validation_error_if_energy_is_not_numeric()
    {
        $invaildData = $this->foodDataFromSearch;
        $invaildData['energy_kcal_100g'] = '文字列';

        $response = $this->actingAs($this->user)
            ->postJson('/api/food/store', $invaildData);

        $response->assertStatus(422);

        $response->assertJsonValidationErrorFor('energy_kcal_100ga');





    }

}
