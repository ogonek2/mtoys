<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAdminLocale
{
    /**
     * Переключает язык только внутри админки, чтобы витрина
     * осталась на своей локали.
     */
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(config('admin.locale', 'ru'));

        return $next($request);
    }
}
