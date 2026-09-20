<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * CSRF protection can't be exercised end-to-end from a normal feature
 * test: Laravel's own middleware (PreventRequestForgery::handle())
 * explicitly skips verification when `$app->runningUnitTests()` is true
 * - which is exactly why every other test in this suite posts forms
 * without attaching a token. That bypass is why this file asserts the
 * middleware is actually *registered* (via the "web" group every one of
 * these routes uses) instead of trying to trigger a 419 in-process.
 *
 * The end-to-end proof - a real POST against the live `php artisan
 * serve` process (not the test runner, so no bypass) with no session
 * cookie or token - was run manually via curl and confirmed a 419:
 *
 *   curl -s -o /dev/null -w "%{http_code}\n" -X POST http://localhost:8000/login \
 *     -d "email=x@example.com&password=x"
 *   → 419
 */
class CsrfProtectionTest extends TestCase
{
    public function test_state_changing_routes_run_the_csrf_middleware(): void
    {
        $webGroupMiddleware = app(Router::class)->getMiddlewareGroups()['web'] ?? [];

        $this->assertContains(
            PreventRequestForgery::class,
            $webGroupMiddleware,
            'The "web" middleware group no longer includes CSRF protection.',
        );

        $stateChangingRoutes = ['login', 'register', 'logout', 'bookmarks.store', 'bookmarks.destroy', 'categories.follow', 'categories.unfollow'];

        foreach ($stateChangingRoutes as $name) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "Route [{$name}] is not registered.");
            $this->assertContains(
                'web',
                $route->gatherMiddleware(),
                "Route [{$name}] does not run the \"web\" middleware group, so it skips CSRF protection.",
            );
        }
    }
}
