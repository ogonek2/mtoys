<?php

namespace App\Http\Middleware;

use App\Support\Database;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Remote mysql.tools / ProxySQL часто рвёт idle-соединения (2006 MySQL server has gone away).
 */
class ReconnectDatabase
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->ensureAlive();

        try {
            return $next($request);
        } catch (QueryException $e) {
            if (! $request->isMethodSafe() || ! Database::isLostConnection($e)) {
                throw $e;
            }

            Database::reconnect();

            return $next($request);
        }
    }

    private function ensureAlive(): void
    {
        try {
            DB::connection()->getPdo();
            DB::select('select 1');
        } catch (Throwable) {
            Database::reconnect();
        }
    }
}
