<?php

namespace Tests\Unit;

use App\Services\FoodService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Services\LocalFoodService;
use App\Services\IntegratedSearchService;
use Mockery;
use App\Models\FoodCompositions;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;


class FoodServiceTest extends TestCase
{
    protected $foodService;
    protected $localFoodService;
    protected $integratedSearchService; 
    protected $mockLocalFoodService;


    protected function setUp(): void
    {
        parent::setUp();
    $this->mockLocalFoodService = Mockery::mock(LocalFoodService::class);
    $this->app->instance(LocalFoodService::class, $this->mockLocalFoodService);

        $this->foodService = new FoodService();
        

    
    }
//apiテスト
protected function fakeOpenFoodFactsApi()
{
    Http::fake([
        'https://world.openfoodfacts.org/cgi/search.pl*' => Http::response([
            'products' => [
                [
                    'product_name_ja' => 'バナナ',
                    'nutriments' => [
                        'proteins_100g' => 1.1,
                        'fat_100g' => 0.3,
                        'carbohydrates_100g' => 22.8,
                    ],
                ],
                [
                    'product_name_ja' => 'アップル',
                    'nutriments' => [
                        'proteins_100g' => 0.3,
                        'fat_100g' => 0.2,
                        'carbohydrates_100g' => 14.0,
                    ],
                ],
            ],
        ], 200),
    ]);
}

/** @test */
    public function api_search_returns_products_on_success()
    {
        // モックレスポンス（正常系：検索結果に複数の商品）
        $this->fakeOpenFoodFactsApi();

        $result = $this->foodService->searchFood('バナナ');

        // 修正: 戻り値はCollectionであり、空ではないことを確認
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertNotEmpty($result);
        $this->assertCount(2, $result);
        $this->assertEquals('バナナ', $result[0]['product_name_ja']);
        $this->assertEquals('アップル', $result[1]['product_name_ja']);
    }

/** @test */
    public function it_returns_empty_products_when_no_results_found()
    {
        // モックレスポンス（異常系：空の検索結果）
        Http::fake([
            'https://world.openfoodfacts.org/cgi/search.pl*' => Http::response([
                'products' => [],
            ], 200),
        ]);

        $result = $this->foodService->searchFood('nonexistent');

        // 修正: 戻り値はCollectionであり、空であることを確認
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertEmpty($result);
    }

   /** @test */
    public function it_returns_error_on_api_failure()
    {
        // モックレスポンス（異常系：APIリクエスト失敗）
        Http::fake([
            'https://world.openfoodfacts.org/cgi/search.pl*' => Http::response([], 404),
        ]);

        $result = $this->foodService->searchFood('Banana');

        // 修正: API失敗時は空のCollectionが返ることを確認
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertEmpty($result);
    }



/** @test */
    public function it_returns_food_data_on_successful_response()
    {
        // モックレスポンスを定義（正常系）
        Http::fake([
            'https://world.openfoodfacts.org/api/v2/product/*' => Http::response([
                'status' => 1,
                'product' => [
                    'code' => '1234567890123', 
                    'product_name' => 'Apple',
                    'nutriments' => [
                        'proteins_100g' => 0.3,
                        'fat_100g' => 0.2,
                        'carbohydrates_100g' => 14.0,
                    ],
                ],
            ], 200),
        ]);

        $result = $this->foodService->getFoodByBarcode('1234567890123');

        $this->assertNotNull($result);
        $this->assertEquals('1234567890123', $result['code']);
        $this->assertEquals('Apple', $result['product_name']);
        $this->assertEquals(0.3, $result['protein']);
        $this->assertEquals(0.2, $result['fat']);
        $this->assertEquals(14.0, $result['carbohydrates']);
    }

    /** @test */
    public function it_returns_error_when_product_not_found()
    {
        // モックレスポンスを定義（商品が見つからない場合）
        Http::fake([
            'https://world.openfoodfacts.org/api/v2/product/*' => Http::response([
                'status' => 0,
            ], 200),
        ]);

        $result = $this->foodService->getFoodByBarcode('9999999999999');

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('商品が見つかりませんでした', $result['error']);
    }

    /** @test */
    public function it_returns_error_when_barcode_is_invalid()
    {
        // モックレスポンスを定義（APIリクエスト失敗）
        Http::fake([
            'https://world.openfoodfacts.org/api/v2/product/*' => Http::response([], 404),
        ]);

        $result = $this->foodService->getFoodByBarcode('1234567890123');

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('APIリクエストが失敗しました。ステータス: 404', $result['error']);
    }
     
    //DBテスト



     /** @test */
     public function db_search_returns_products_on_success()
     {
        //正常系
       $dbResponse = Mockery::mock(FoodCompositions::class);
       $dbResponse->shouldReceive('getAttribute')->with('food_name')->andReturn('バナナ');
       $dbResponse->shouldReceive('getAttribute')->with('protein')->andReturn(1.1);
       $dbResponse->shouldReceive('getAttribute')->with('fat')->andReturn(0.3);
       $dbResponse->shouldReceive('getAttribute')->with('carbohydrate')->andReturn(22.8);

       $collection = collect([$dbResponse]);

       $this->mockLocalFoodService
       ->shouldReceive('searchFoodDb')->with('果物')->andReturn($collection);


      $result = $this->mockLocalFoodService->searchFoodDb('果物');
       

        $this->assertEquals($collection,$result);
        $this->assertNotEmpty($result);
        $this->assertEquals('バナナ', $result->first()->food_name);

        

     }
     /** @test */
      public function  db_search_returns_products_not_found()
      {
        // モックレスポンス（異常系：空の検索結果）
        $this->localFoodService = Mockery::mock(LocalFoodService::class);
        $this->localFoodService
          ->shouldReceive('searchFoodDb')
            ->with('存在しない食材')
            ->once()
            ->andReturn(collect());


            $result = $this->localFoodService->searchFoodDb('存在しない食材');

        
        $this->assertTrue($result->isEmpty());



      }
       /** @test */
      public function  db_search_throws_exception_when_db_error()
      {
        // 例外処理
         $this->localFoodService = Mockery::mock(LocalFoodService::class);
         $this->localFoodService
            ->shouldReceive('searchFoodDb')
            ->with('果物')
            ->once()
            ->andThrow(new \Exception('データベースエラー: Connection failed'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('データベースエラー: Connection failed');

        
        $this->localFoodService->searchFoodDb('果物');


      }
     
    
    
     /** @test */
     public function db_api_search_returns_products_on_success()
     {
       // 正常系: 統合検索テスト
       $this->fakeOpenFoodFactsApi(); // APIからは「バナナ」「アップル」が返る

       // DBのモックデータを作成
       $dbResponse = Mockery::mock(FoodCompositions::class);
       $dbResponse->shouldReceive('getAttribute')->with('food_name')->andReturn('バナナ');
       $dbResponse->shouldReceive('getAttribute')->with('food_number')->andReturn('DB123'); // ★ food_numberのモックを追加
       $dbResponse->shouldReceive('getAttribute')->with('energy_kcal_100g')->andReturn(86);
       $dbResponse->shouldReceive('getAttribute')->with('proteins_100g')->andReturn(1.1);
       $dbResponse->shouldReceive('getAttribute')->with('fat_100g')->andReturn(0.3);
       $dbResponse->shouldReceive('getAttribute')->with('carbohydrates_100g')->andReturn(22.8);

        $dbProducts = collect([$dbResponse]);

        $this->mockLocalFoodService
        ->shouldReceive('searchFoodDb')->with('果物')->andReturn($dbProducts);

        $service = new IntegratedSearchService($this->foodService, $this->mockLocalFoodService);  
        $result = $service->search('果物');

        // --- 検証 --- 
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('products', $result);
        
        $products = collect($result['products']); // 検証しやすいようにCollectionに変換

        // 重複排除ロジックにより、DBの「バナナ」とAPIの「アップル」の2件になるはず
        $this->assertCount(2, $products);

        // 1件目はDBのバナナであることを確認
        $dbBanana = $products->firstWhere('food_name', 'バナナ');
        $this->assertNotNull($dbBanana);
        $this->assertEquals('db', $dbBanana['source']);
        $this->assertEquals('DB123', $dbBanana['food_number']);

        // 2件目はAPIのアップルであることを確認
        $apiApple = $products->firstWhere('food_name', 'アップル');
        $this->assertNotNull($apiApple);
        $this->assertEquals('api', $apiApple['source']);
    }

    /** @test */
    public function api_db_search_returns_products_not_found()
    {
        // モックレスポンス（異常系：空の検索結果）
        Http::fake([
            'https://world.openfoodfacts.org/cgi/search.pl*' => Http::response([
                'products' => [],
            ], 200),
        ]);

        

        $this->mockLocalFoodService
          ->shouldReceive('searchFoodDb')
            ->with('存在しない食材')
            ->once()
            ->andReturn(collect());


       $service = new IntegratedSearchService($this->foodService,$this->mockLocalFoodService);
       $result = $service->search('存在しない食材');
        



        $this->assertCount(0, $result['products']);
        
        $this->assertEmpty($result['products']);
        $this->assertArrayHasKey('products', $result);
      
        
        $this->assertFalse($result['success']);

       
    }

           /** @test */
      public function  api_db_search_throws_exception_when_db_error()
      {
        // モックレスポンス（異常系：例外処理）
        Http::fake([
            'https://world.openfoodfacts.org/cgi/search.pl*' => Http::response([], 404),
        ]);

        

        $this->mockLocalFoodService
          ->shouldReceive('searchFoodDb')
            ->with('果物')
            ->once()
            ->andThrow(new \Exception('データベースエラー: Connection failed'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('データベースエラー: Connection failed');

        $service = new IntegratedSearchService($this->foodService,$this->mockLocalFoodService);
        $result = $service->search('果物');

        


      }

        
    protected function tearDown(): void
    {
        restore_error_handler();
        restore_exception_handler();
        Mockery::close();
        parent::tearDown();
    }

     
}

