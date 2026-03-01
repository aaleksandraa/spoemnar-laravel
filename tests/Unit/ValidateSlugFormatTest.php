<?php

use App\Http\Middleware\ValidateSlugFormat;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

uses(Tests\TestCase::class);

it('accepts valid slugs with dots', function () {
    $middleware = new ValidateSlugFormat();
    $validSlugs = [
        'stefan.stefanovic',
        'john.doe',
        'jean.pierre.de.la.cruz',
    ];

    foreach ($validSlugs as $slug) {
        $request = Request::create('/test/' . $slug, 'GET');
        $request->setRouteResolver(function () use ($request, $slug) {
            $route = new \Illuminate\Routing\Route('GET', '/test/{slug}', []);
            $route->bind($request);
            $route->setParameter('slug', $slug);
            return $route;
        });

        $nextCalled = false;
        $response = $middleware->handle($request, function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK', 200);
        });

        expect($nextCalled)->toBeTrue();
        expect($response->getStatusCode())->toBe(200);
    }
});

it('accepts valid slugs with hyphens', function () {
    $middleware = new ValidateSlugFormat();
    $validSlugs = [
        'john-doe',
        'mary-anne-smith',
    ];

    foreach ($validSlugs as $slug) {
        $request = Request::create('/test/' . $slug, 'GET');
        $request->setRouteResolver(function () use ($request, $slug) {
            $route = new \Illuminate\Routing\Route('GET', '/test/{slug}', []);
            $route->bind($request);
            $route->setParameter('slug', $slug);
            return $route;
        });

        $nextCalled = false;
        $response = $middleware->handle($request, function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK', 200);
        });

        expect($nextCalled)->toBeTrue();
        expect($response->getStatusCode())->toBe(200);
    }
});

it('accepts valid slugs with mixed dots and hyphens', function () {
    $middleware = new ValidateSlugFormat();
    $validSlugs = [
        'jean-pierre.de-la.cruz',
        'mary.anne-smith',
        'john-paul.doe',
    ];

    foreach ($validSlugs as $slug) {
        $request = Request::create('/test/' . $slug, 'GET');
        $request->setRouteResolver(function () use ($request, $slug) {
            $route = new \Illuminate\Routing\Route('GET', '/test/{slug}', []);
            $route->bind($request);
            $route->setParameter('slug', $slug);
            return $route;
        });

        $nextCalled = false;
        $response = $middleware->handle($request, function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK', 200);
        });

        expect($nextCalled)->toBeTrue();
        expect($response->getStatusCode())->toBe(200);
    }
});

it('rejects slugs with consecutive dots', function () {
    $middleware = new ValidateSlugFormat();
    $invalidSlugs = [
        'john..doe',
        'mary...anne',
    ];

    foreach ($invalidSlugs as $slug) {
        $request = Request::create('/test/' . $slug, 'GET');
        $request->setRouteResolver(function () use ($request, $slug) {
            $route = new \Illuminate\Routing\Route('GET', '/test/{slug}', []);
            $route->bind($request);
            $route->setParameter('slug', $slug);
            return $route;
        });

        $nextCalled = false;
        $response = $middleware->handle($request, function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK', 200);
        });

        expect($nextCalled)->toBeFalse();
        expect($response)->toBeInstanceOf(JsonResponse::class);
        expect($response->getStatusCode())->toBe(422);
    }
});

it('rejects slugs with consecutive hyphens', function () {
    $middleware = new ValidateSlugFormat();
    $invalidSlugs = [
        'john--doe',
        'mary---anne',
    ];

    foreach ($invalidSlugs as $slug) {
        $request = Request::create('/test/' . $slug, 'GET');
        $request->setRouteResolver(function () use ($request, $slug) {
            $route = new \Illuminate\Routing\Route('GET', '/test/{slug}', []);
            $route->bind($request);
            $route->setParameter('slug', $slug);
            return $route;
        });

        $nextCalled = false;
        $response = $middleware->handle($request, function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK', 200);
        });

        expect($nextCalled)->toBeFalse();
        expect($response)->toBeInstanceOf(JsonResponse::class);
        expect($response->getStatusCode())->toBe(422);
    }
});

it('rejects slugs starting with dot', function () {
    $middleware = new ValidateSlugFormat();
    $invalidSlugs = [
        '.john.doe',
        '.stefan',
    ];

    foreach ($invalidSlugs as $slug) {
        $request = Request::create('/test/' . $slug, 'GET');
        $request->setRouteResolver(function () use ($request, $slug) {
            $route = new \Illuminate\Routing\Route('GET', '/test/{slug}', []);
            $route->bind($request);
            $route->setParameter('slug', $slug);
            return $route;
        });

        $nextCalled = false;
        $response = $middleware->handle($request, function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK', 200);
        });

        expect($nextCalled)->toBeFalse();
        expect($response)->toBeInstanceOf(JsonResponse::class);
        expect($response->getStatusCode())->toBe(422);
    }
});

it('rejects slugs ending with dot', function () {
    $middleware = new ValidateSlugFormat();
    $invalidSlugs = [
        'john.doe.',
        'stefan.',
    ];

    foreach ($invalidSlugs as $slug) {
        $request = Request::create('/test/' . $slug, 'GET');
        $request->setRouteResolver(function () use ($request, $slug) {
            $route = new \Illuminate\Routing\Route('GET', '/test/{slug}', []);
            $route->bind($request);
            $route->setParameter('slug', $slug);
            return $route;
        });

        $nextCalled = false;
        $response = $middleware->handle($request, function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK', 200);
        });

        expect($nextCalled)->toBeFalse();
        expect($response)->toBeInstanceOf(JsonResponse::class);
        expect($response->getStatusCode())->toBe(422);
    }
});

it('rejects slugs starting with hyphen', function () {
    $middleware = new ValidateSlugFormat();
    $invalidSlugs = [
        '-john-doe',
        '-stefan',
    ];

    foreach ($invalidSlugs as $slug) {
        $request = Request::create('/test/' . $slug, 'GET');
        $request->setRouteResolver(function () use ($request, $slug) {
            $route = new \Illuminate\Routing\Route('GET', '/test/{slug}', []);
            $route->bind($request);
            $route->setParameter('slug', $slug);
            return $route;
        });

        $nextCalled = false;
        $response = $middleware->handle($request, function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK', 200);
        });

        expect($nextCalled)->toBeFalse();
        expect($response)->toBeInstanceOf(JsonResponse::class);
        expect($response->getStatusCode())->toBe(422);
    }
});

it('rejects slugs ending with hyphen', function () {
    $middleware = new ValidateSlugFormat();
    $invalidSlugs = [
        'john-doe-',
        'stefan-',
    ];

    foreach ($invalidSlugs as $slug) {
        $request = Request::create('/test/' . $slug, 'GET');
        $request->setRouteResolver(function () use ($request, $slug) {
            $route = new \Illuminate\Routing\Route('GET', '/test/{slug}', []);
            $route->bind($request);
            $route->setParameter('slug', $slug);
            return $route;
        });

        $nextCalled = false;
        $response = $middleware->handle($request, function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK', 200);
        });

        expect($nextCalled)->toBeFalse();
        expect($response)->toBeInstanceOf(JsonResponse::class);
        expect($response->getStatusCode())->toBe(422);
    }
});

it('rejects slugs with uppercase letters', function () {
    $middleware = new ValidateSlugFormat();
    $invalidSlugs = [
        'John.Doe',
        'STEFAN.STEFANOVIC',
        'John-Doe',
    ];

    foreach ($invalidSlugs as $slug) {
        $request = Request::create('/test/' . $slug, 'GET');
        $request->setRouteResolver(function () use ($request, $slug) {
            $route = new \Illuminate\Routing\Route('GET', '/test/{slug}', []);
            $route->bind($request);
            $route->setParameter('slug', $slug);
            return $route;
        });

        $nextCalled = false;
        $response = $middleware->handle($request, function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK', 200);
        });

        expect($nextCalled)->toBeFalse();
        expect($response)->toBeInstanceOf(JsonResponse::class);
        expect($response->getStatusCode())->toBe(422);
    }
});

it('rejects slugs with special characters', function () {
    $middleware = new ValidateSlugFormat();
    $invalidSlugs = [
        'john_doe',
        'john@doe',
        'john doe',
        'john+doe',
        'john&doe',
        'john#doe',
    ];

    foreach ($invalidSlugs as $slug) {
        $request = Request::create('/test/' . $slug, 'GET');
        $request->setRouteResolver(function () use ($request, $slug) {
            $route = new \Illuminate\Routing\Route('GET', '/test/{slug}', []);
            $route->bind($request);
            $route->setParameter('slug', $slug);
            return $route;
        });

        $nextCalled = false;
        $response = $middleware->handle($request, function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK', 200);
        });

        expect($nextCalled)->toBeFalse();
        expect($response)->toBeInstanceOf(JsonResponse::class);
        expect($response->getStatusCode())->toBe(422);
    }
});
