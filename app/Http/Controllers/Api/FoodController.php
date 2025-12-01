<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchFoodRequest;
use App\Http\Requests\GetFoodByBarcodeRequest;
use App\Http\Resources\FoodResource;
use App\Services\FoodService;
use Illuminate\Http\Request;

class FoodController extends Controller
{
    protected $foodService;

    public function __construct(FoodService $foodService)
    {
        $this->foodService = $foodService;
    }

    public function search(SearchFoodRequest $request)
    {
        // バリデーション済みデータを取得
        $keyword = $request->validated('search');
        
        // サービス層で検索ロジック実行
        $result = $this->foodService->searchFood($keyword);

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 500);
        }

        // Resourceクラスで整形してJSONを返す (200 OK)
        // Note: FoodService returns ['products' => [...]] structure usually
        return response()->json($result['products']);
    }

    public function getFoodByBarcode(GetFoodByBarcodeRequest $request)
    {
        $barcode = $request->validated('barcode');
        $result = $this->foodService->getFoodByBarcode($barcode);

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 404);
        }

        return response()->json($result);
    }
}
