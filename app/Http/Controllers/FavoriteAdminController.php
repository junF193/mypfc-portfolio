<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\FavoriteListService;
use Illuminate\Http\Request;
use App\Http\Resources\FavoriteResource;

class FavoriteAdminController extends Controller
{
    protected FavoriteListService $favoriteService;

    public function __construct(FavoriteListService $favoriteService)
    {
        $this->favoriteService = $favoriteService;
    }

    // 全件取得して JSON で返す or CSV をダウンロードする
    public function export(Request $request)
    {
        $userId = (int) $request->query('user_id'); // 管理者が指定する対象ユーザーID
        if (!$userId) {
            return response()->json(['message' => 'user_id is required'], 422);
        }

        // getFavoritesForUser の perPage=null を使って全件取得（Service 側で count 上限は入れても可）
        $all = $this->favoriteService->getFavoritesForUser($userId, null);
       // dd(FavoriteResource::collection($all)->toArray(request()));

        
       



        // 例：JSON で返す
        return response()->json([
        'count' => $all->count(),
        'favorites' => FavoriteResource::collection($all),
        
    ]);
    
    
        // あるいは CSV を作る処理をここに置く
    }
}
