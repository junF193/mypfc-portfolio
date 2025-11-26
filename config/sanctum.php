<?php
use Laravel\Sanctum\Sanctum;

return [
    // ✅ stateful domains: 同じドメインからのリクエストを信頼
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s%s',
        'localhost,localhost:3000,localhost:8000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort() ? ',' : '',
        Sanctum::currentApplicationUrlWithPort()
    ))),

    // ✅ guard: web (Cookie/Session を使用)
    'guard' => ['web'],

    // ✅ expiration: null (Cookie の有効期限に従う)
    'expiration' => null,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];