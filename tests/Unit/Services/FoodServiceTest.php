<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FoodService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FoodServiceTest extends TestCase
{
    private FoodService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FoodService();
    }

    public function test_search_open_food_facts_normal_case()
    {
        Http::fake([
            'world.openfoodfacts.org/*' => Http::response([
                'products' => [
                    [
                        'product_name_ja' => 'テスト食品',
                        'image_front_small_url' => 'https://example.com/image.jpg',
                        'nutriments' => [
                            'energy-kcal_100g' => 100,
                            'proteins_100g' => 10,
                            'fat_100g' => 5,
                            'carbohydrates_100g' => 20,
                        ],
                        'id' => '123456',
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->searchOpenFoodFacts('test');

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('テスト食品', $result['data'][0]['food_name']);
        $this->assertEquals('https://example.com/image.jpg', $result['data'][0]['image_url']);
        $this->assertEquals('openfoodfacts', $result['meta']['source']);
    }

    public function test_search_open_food_facts_caching()
    {
        Http::fake([
            'world.openfoodfacts.org/*' => Http::response(['products' => []], 200)
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->with('off:' . md5('test'), 300, \Closure::class)
            ->andReturn(['data' => [], 'meta' => ['source' => 'cache']]);

        $result = $this->service->searchOpenFoodFacts('test');
        $this->assertEquals('cache', $result['meta']['source']);
    }

    public function test_search_open_food_facts_api_error()
    {
        Http::fake([
            'world.openfoodfacts.org/*' => Http::response(null, 500)
        ]);

        Log::shouldReceive('warning')->once();

        $result = $this->service->searchOpenFoodFacts('error_case');

        $this->assertEmpty($result['data']);
        $this->assertEquals('external_unavailable', $result['meta']['error']);
    }

    public function test_search_open_food_facts_fallback_name()
    {
        Http::fake([
            'world.openfoodfacts.org/*' => Http::response([
                'products' => [
                    [
                        'product_name' => 'English Name',
                        'nutriments' => [],
                        'id' => '123',
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->searchOpenFoodFacts('fallback');

        $this->assertEquals('English Name', $result['data'][0]['food_name']);
    }
}
