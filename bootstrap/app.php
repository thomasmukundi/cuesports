<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Temporarily remove middleware for testing
            Route::prefix('api/admin')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
        ]);
        
        // Apply Inertia middleware only to non-admin routes
        $middleware->group('web-inertia', [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
        
        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // Run tournament checks based on configured interval
        $checkInterval = env('TOURNAMENT_CHECK_INTERVAL', 5);
        
        // Use appropriate scheduling method based on interval
        switch ($checkInterval) {
            case 1:
                $schedule->command('tournaments:check')->everyMinute();
                break;
            case 5:
                $schedule->command('tournaments:check')->everyFiveMinutes();
                break;
            case 10:
                $schedule->command('tournaments:check')->everyTenMinutes();
                break;
            case 15:
                $schedule->command('tournaments:check')->everyFifteenMinutes();
                break;
            case 30:
                $schedule->command('tournaments:check')->everyThirtyMinutes();
                break;
            default:
                // For custom intervals, use cron expression
                $schedule->command('tournaments:check')->cron("*/{$checkInterval} * * * *");
                break;
        }
    })
    ->create();
