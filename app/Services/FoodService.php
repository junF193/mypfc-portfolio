<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Post;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;





class FoodService
{
   /**
  * @param string $searchTerm
  * @return Collection
  */

    public function searchFood($searchTerm): Collection
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'MyApp/1.0 ()'
            ])->get('https://world.openfoodfacts.org/cgi/search.pl', [
                'search_terms' => $searchTerm,
                'search_simple' => 1,
                'action' => 'process',
                'json' => 1,
                'cc' => 'jp',
                'lc' => 'ja',
                'page_size' => 20, 
                'page' => 1,
            ]);

          

            // APIリクエストが失敗した、または結果が空の場合
               if (!$response->successful() || empty($response->json('products'))) {
                   // 失敗した場合はログに記録
                    if (!$response->successful()) {
                       Log::error('Open Food Facts API request failed.', [
                          'status' => $response->status(),
                           'body' => $response->body()
                        ]);
                   }
                   // 呼び出し元を止めないように、空のCollectionを返す
                    return collect([]);
               }

               $products =collect($response->json('products'));

               $filtered = $products->filter(function ($product){
                return !empty($product['product_name_ja']);


               });

               return $filtered->values();

        } catch (\Exception $e) {
            // 例外が発生した場合はログに記録
            Log::error('Open Food Facts API request failed.', [
                'error' => $e->getMessage()
            ]);
            // 呼び出し元を止めないように、空のCollectionを返す
            return collect([]);
        }
    }

/*バーコードで食品を取得*/
    public function getFoodByBarcode($barcode)
    {
        
        try {
            $response = http::withHeaders([
            'User-Agent' => 'MyApp/1.0 (fjun5100@gmail.com)'
        ])->get("https://world.openfoodfacts.org/api/v2/product/{$barcode}", [
            'fields' => 'product_name,product_name_ja,nutriments',
            'lc' => 'ja', 
            'cc' => 'jp',
        ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] == 1) {
                    return $this->extractPFC($data);
                } else {
                    return ['error' => '商品が見つかりませんでした'];
                }
            } else {
                return ['error' => 'APIリクエストが失敗しました。ステータス: ' . $response->status()];
            }
        } catch (\Exception $e) {
            return ['error' => 'ネットワークエラー: ' . $e->getMessage()];
        }
    }
    /**
     * PFC情報を抽出
     */
private function extractPFC($apiResponse)
{
    if (isset($apiResponse['product']['nutriments'])) {
        $nutriments = $apiResponse['product']['nutriments'];
        return [
            'code' => $apiResponse['product']['code'],
            'product_name' => $apiResponse['product']['product_name'] ?? '不明',
            'product_name_ja' => $apiResponse['product']['product_name_ja'] ?? null,
            'protein' => $nutriments['proteins_100g'] ?? 0,
            'fat' => $nutriments['fat_100g'] ?? 0,
            'carbohydrates' => $nutriments['carbohydrates_100g'] ?? 0,
        ];
    }
    return null;
}
}



