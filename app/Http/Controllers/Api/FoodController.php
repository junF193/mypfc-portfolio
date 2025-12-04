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
        $result = $this->foodService->searchOpenFoodFacts($keyword);

        // サービス層が整形済みのデータを返すので、そのままJSONとして返す
        return response()->json($result);
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
