<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * 簡易スタブ：実際の TrimStrings は入力のトリム処理を行うが、
 * テスト目的ではそのまま次へ渡す（副作用なし）。
 * 本番では framework の実装やより完全な app 実装に置き換えてください。
 */
class TrimStrings
{
    public function handle(Request $request, Closure $next)
    {
        // ここで本来は input の空白を trim する処理が入るが、
        // テストを通すために単にパススルーします。
        return $next($request);
    }
}
