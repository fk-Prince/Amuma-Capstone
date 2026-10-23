<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as BaseValidateCsrfToken;
use Symfony\Component\HttpFoundation\Cookie;

class ValidateCsrfToken extends BaseValidateCsrfToken
{
    protected function newCookie($request, $config)
    {
        return new Cookie(
            self::cookieName(),
            $request->session()->token(),
            $this->availableAt(60 * $config['lifetime']),
            $config['path'],
            $config['domain'],
            $config['secure'],
            false,
            false,
            $config['same_site'] ?? null,
            $config['partitioned'] ?? false
        );
    }

    public static function serialized()
    {
        return EncryptCookies::serialized(self::cookieName());
    }

    private static function cookieName(): string
    {
        return config('session.xsrf_cookie', 'XSRF-TOKEN');
    }
}
