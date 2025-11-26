<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventRequestsDuringMaintenance
{
    /**
     * テスト環境では簡易的にパススルーする（本番でメンテを扱うなら本物に差し替えてください）。
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
