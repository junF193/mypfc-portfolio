<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use App\Providers\RouteServiceProvider;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // 認証処理
        $request->authenticate();

        // ✅ セッションを再生成（セキュリティ対策）
        $request->session()->regenerate();

        // ✅ Cookie/Session 認証を使用するため、API トークンは不要
        // 以下のコードを削除またはコメントアウト
        // $token = $request->user()->createToken('web-api-token')->plainTextToken;
        // session()->flash('api_token', $token);

        // ✅ デバッグログ（開発中のみ）
       Log::info('LOGIN AFTER', [
  'auth_id' => auth()->id(),
  'session_id' => session()->getId(),
  'session_cookie_in_request' => request()->cookie(config('session.cookie')),
  'session_keys' => array_keys(session()->all())
]);

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // ログアウト処理
        Auth::guard('web')->logout();

        // セッションを無効化
        $request->session()->invalidate();

        // CSRF トークンを再生成
        $request->session()->regenerateToken();

        // ✅ Sanctum トークンも削除（もし発行されていれば）
        if ($request->user()) {
            $request->user()->tokens()->delete();
        }

        return redirect('/');
    }
}