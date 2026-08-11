<?php

use App\Http\Middleware\AddSecurityHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

it('adds baseline security headers to application responses', function () {
    $this->get('/')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=()')
        ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
});

it('adds production content security policy and hsts to secure responses', function () {
    $originalEnvironment = app()->environment();
    app()->detectEnvironment(fn (): string => 'production');

    try {
        $request = Request::create('https://capylendar.test');
        $response = (new AddSecurityHeaders)->handle(
            $request,
            fn (): Response => new Response('ok'),
        );

        expect($response->headers->get('Content-Security-Policy'))
            ->toBe(
                "default-src 'self'; base-uri 'self'; connect-src 'self'; font-src 'self' https://fonts.bunny.net; form-action 'self'; frame-ancestors 'none'; img-src 'self' data: https:; object-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.bunny.net; upgrade-insecure-requests",
            )
            ->and($response->headers->get('Strict-Transport-Security'))
            ->toBe('max-age=31536000; includeSubDomains');
    } finally {
        app()->detectEnvironment(fn (): string => $originalEnvironment);
    }
});

it('does not add hsts to insecure responses', function () {
    $originalEnvironment = app()->environment();
    app()->detectEnvironment(fn (): string => 'production');

    try {
        $response = (new AddSecurityHeaders)->handle(
            Request::create('http://capylendar.test'),
            fn (): Response => new Response('ok'),
        );

        expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
    } finally {
        app()->detectEnvironment(fn (): string => $originalEnvironment);
    }
});
