<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Post;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;





class FoodService
{
    /**
     * OpenFoodFactsから食品を検索
     *
     * @param string $keyword
     * @return array
     */
    public function searchOpenFoodFacts(string $keyword): array
    {
        $normalizedKeyword = mb_strtolower(trim($keyword));
        $cacheKey = 'off:' . md5($normalizedKeyword);

        // キャッシュ制御 (TTL: 300秒)
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($keyword) {
            try {
                $response = Http::timeout(5)
                    ->retry(2, 100)
                    ->withHeaders(['User-Agent' => 'MyApp/1.0'])
                    ->get('https://world.openfoodfacts.org/cgi/search.pl', [
                        'search_terms' => $keyword,
                        'search_simple' => 1,
                        'action' => 'process',
                        'json' => 1,
                        'cc' => 'jp', // 日本市場限定
                        'fields' => 'product_name,product_name_ja,nutriments,id,image_front_small_url',
                        'page_size' => 20,
                        'page' => 1,
                    ]);

                if (!$response->successful()) {
                    throw new \Exception('API status: ' . $response->status());
                }

                $products = $response->json('products', []);
                $formattedData = collect($products)->map(function ($product) {
                    return $this->formatProduct($product);
                })->values()->all();

                return [
                    'data' => $formattedData,
                    'meta' => ['source' => 'openfoodfacts'],
                ];

            } catch (\Exception $e) {
                Log::warning('off_search_failed', [
                    'q' => $keyword,
                    'error' => $e->getMessage()
                ]);

                return [
                    'data' => [],
                    'meta' => ['error' => 'external_unavailable']
                ];
            }
        });
    }

    /**
     * バーコードで食品を取得
     */
    public function getFoodByBarcode($barcode)
    {
        try {
            $response = Http::timeout(5)
                ->retry(2, 100)
                ->withHeaders(['User-Agent' => 'MyApp/1.0'])
                ->get("https://world.openfoodfacts.org/api/v2/product/{$barcode}", [
                    'fields' => 'product_name,product_name_ja,nutriments,code,image_front_small_url',
                    'cc' => 'jp',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] == 1) {
                    return $this->formatProduct($data['product']);
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
     * APIレスポンスを整形
     */
    private function formatProduct(array $product): array
    {
        $nutriments = $product['nutriments'] ?? [];
        
        // 商品名: 日本語名があればそれを優先、なければ英語名、それもなければ '不明'
        $name = $product['product_name_ja'] ?? $product['product_name'] ?? '不明';
        if (empty($name)) { 
            $name = '不明'; 
        }

        return [
            'food_name' => $name,
            'image_url' => $product['image_front_small_url'] ?? null,
            'energy_kcal_100g' => $nutriments['energy-kcal_100g'] ?? $nutriments['energy_kcal_100g'] ?? 0, // APIの揺れに対応
            'proteins_100g' => $nutriments['proteins_100g'] ?? 0,
            'fat_100g' => $nutriments['fat_100g'] ?? 0,
            'carbohydrates_100g' => $nutriments['carbohydrates_100g'] ?? 0,
            'source' => 'api',
            'code' => $product['code'] ?? $product['id'] ?? null, // ID or Code
        ];
    }
}



