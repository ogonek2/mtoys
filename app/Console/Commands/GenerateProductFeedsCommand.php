<?php

namespace App\Console\Commands;

use App\Services\ProductFeedService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Аварийная/первичная полная сборка. В обычной работе не нужна —
 * фиды правятся точечно при сохранении товаров.
 */
class GenerateProductFeedsCommand extends Command
{
    protected $signature = 'feeds:generate {--force : Пересобрать файлы с нуля}';

    protected $description = 'Первичная/аварийная полная сборка Google/Facebook фидов';

    public function handle(): int
    {
        if (! $this->option('force') && ProductFeedService::exists('google') && ProductFeedService::exists('facebook')) {
            $this->info('Файлы уже есть. Точечные правки идут через товары. --force для полной пересборки.');

            return self::SUCCESS;
        }

        try {
            $meta = ProductFeedService::generate();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("OK: {$meta['count']} / {$meta['catalog_total']}");
        $this->line($meta['google']);
        $this->line($meta['facebook']);

        return self::SUCCESS;
    }
}
