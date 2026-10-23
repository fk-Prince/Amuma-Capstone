<?php

namespace Tests\Feature;

use App\Http\Middleware\ValidateCsrfToken;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Crypt;
use Tests\Concerns\UsesTempDatabase;
use Tests\TestCase;

class XsrfCookieNameTest extends TestCase
{
    use UsesTempDatabase;

    private const ORIGIN = 'https://sandbox.amuma.tech';

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['sandbox.amuma.tech']]);
    }

    private function csrfCookie(): ?string
    {
        $response = $this->withHeaders(['Origin' => self::ORIGIN, 'Referer' => self::ORIGIN . '/'])
            ->get('/sanctum/csrf-cookie');

        foreach ($response->headers->getCookies() as $cookie) {
            $names[] = $cookie->getName();
        }

        return implode(',', $names ?? []);
    }

    public function test_live_keeps_the_default_xsrf_cookie_name(): void
    {
        $this->assertStringContainsString('XSRF-TOKEN', $this->csrfCookie());
    }

    public function test_the_sandbox_can_rename_the_xsrf_cookie(): void
    {
        config(['session.xsrf_cookie' => 'XSRF-TOKEN-SANDBOX']);

        $names = explode(',', $this->csrfCookie());

        $this->assertContains('XSRF-TOKEN-SANDBOX', $names);
        $this->assertNotContains('XSRF-TOKEN', $names);
    }

    private function postWithXsrfHeader(string $cookieName, bool $matchingToken)
    {
        config(['session.xsrf_cookie' => 'XSRF-TOKEN-SANDBOX']);

        $middleware = new class(app(), app('encrypter')) extends ValidateCsrfToken {
            protected function runningUnitTests()
            {
                return false;
            }
        };

        $session = app('session')->driver();
        $session->start();

        $request = Request::create('/api/auth/login', 'POST');
        $request->setLaravelSession($session);

        $token = $matchingToken ? $session->token() : 'not-the-session-token';
        $header = Crypt::encrypt(CookieValuePrefix::create($cookieName, app('encrypter')->getKey()) . $token, false);
        $request->headers->set('X-XSRF-TOKEN', $header);

        return $middleware->handle($request, fn() => response('ok'));
    }

    public function test_a_token_from_the_renamed_cookie_passes_csrf(): void
    {
        $response = $this->postWithXsrfHeader('XSRF-TOKEN-SANDBOX', true);

        $this->assertSame('ok', $response->getContent());
        $this->assertContains(
            'XSRF-TOKEN-SANDBOX',
            array_map(fn($cookie) => $cookie->getName(), $response->headers->getCookies())
        );
    }

    public function test_a_wrong_token_is_still_rejected(): void
    {
        $this->expectException(TokenMismatchException::class);

        $this->postWithXsrfHeader('XSRF-TOKEN-SANDBOX', false);
    }
}
