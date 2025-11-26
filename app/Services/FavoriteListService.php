<?php
namespace App\Services;

use App\Models\Favorite;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class FavoriteListService
{
    /**
     * ユーザーのお気に入りIDを食事履歴IDのリストから取得する
     */
    public function getFavoriteSourceIdsByUserAndLogs(User $user, array $foodLogIds): array
    {
        return $user->favorites()
            ->whereIn('source_food_log_id', $foodLogIds)
            ->pluck('source_food_log_id')
            ->toArray();
    }

    /**
     * ユーザーのお気に入り一覧を取得する
     */
    public function getFavoritesForUser(User $user, ?int $perPage = 20)
    {
        $query = $user->favorites()->orderBy('created_at', 'desc');

        if ($perPage === null) {
            // 管理者専用経路など、全件取得時の過負荷を防ぐ安全装置
            $count = $query->count();
            $MAX_ALL = config('favorites.max_all', 5000);
            if ($count > $MAX_ALL) {
                throw new \RuntimeException("全件取得の上限を超えました。 (limit: {$MAX_ALL}, actual: {$count})");
            }
            return $query->get();
        }

        return $query->simplePaginate($perPage);
    }
}
