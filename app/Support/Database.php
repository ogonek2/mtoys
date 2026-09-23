<?php

namespace App\Support;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Повтор запроса после обрыва соединения с mysql.tools / ProxySQL (HY000 2006/2013).
 */
final class Database
{
    public static function retry(Closure $callback, int $attempts = 3): mixed
    {
        $attempts = max(1, $attempts);
        $last = null;

        for ($i = 0; $i < $attempts; $i++) {
            try {
                return $callback();
            } catch (QueryException $e) {
                $last = $e;
                if ($i >= $attempts - 1 || ! self::isLostConnection($e)) {
                    throw $e;
                }
                self::reconnect();
                usleep(150000); // 150ms — дать ProxySQL отпустить мёртвый backend
            }
        }

        throw $last ?? new \RuntimeException('Database retry failed');
    }

    public static function reconnect(): void
    {
        try {
            DB::purge(DB::getDefaultConnection());
        } catch (Throwable) {
            // ignore
        }

        DB::reconnect();
    }

    public static function isLostConnection(QueryException|Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        foreach ([
            'server has gone away',
            'lost connection',
            'error while sending',
            'is dead or not enabled',
            'no connection to the server',
            'decryption failed or bad record mac',
            'ssl connection has been closed unexpectedly',
        ] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        if ($e instanceof QueryException) {
            $code = (string) ($e->errorInfo[1] ?? '');

            return in_array($code, ['2006', '2013', '2002'], true);
        }

        return false;
    }
}
