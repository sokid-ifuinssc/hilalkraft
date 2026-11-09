<?php

// Fix Windows PHP sandbox is_writable issue on directories
namespace Illuminate\Foundation {
    if (!function_exists('Illuminate\Foundation\is_writable')) {
        function is_writable($path) {
            $isDir = is_dir($path);
            $testFile = $isDir ? rtrim($path, '/\\') . '/.write_test_' . uniqid() : $path;
            $res = @file_put_contents($testFile, '', FILE_APPEND);
            if ($res !== false) {
                if ($isDir) {
                    @unlink($testFile);
                }
                return true;
            }
            return \is_writable($path);
        }
    }
}

namespace {
    use App\Http\Middleware\RoleMiddleware;
    use Illuminate\Foundation\Application;
    use Illuminate\Foundation\Configuration\Exceptions;
    use Illuminate\Foundation\Configuration\Middleware;

    return Application::configure(basePath: dirname(__DIR__))
        ->withRouting(
            web: __DIR__.'/../routes/web.php',
            commands: __DIR__.'/../routes/console.php',
            health: '/up',
        )
        ->withMiddleware(function (Middleware $middleware) {
            $middleware->alias([
                'role' => RoleMiddleware::class,
            ]);
        })
        ->withExceptions(function (Exceptions $exceptions) {
            //
        })->create();
}

