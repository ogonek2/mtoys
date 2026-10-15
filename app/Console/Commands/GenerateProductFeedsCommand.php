<?php

namespace App\Console\Commands;

use App\Services\ProductFeedService;
use Illuminate\Console\Command;
use Throwable;

class GenerateProductFeedsCommand extends Command
{
    protected $signature = 'feeds:generate {--force : Пересобрать даже если нет изменений}';

    protected $description = 'Сгенерировать Google Merchant и Facebook Catalog фиды товаров';

    public function handle(): int
    {
        if (! $this->option('force') && ! cache()->get(ProductFeedService::DIRTY_CACHE_KEY) && ProductFeedService::exists('google')) {
            $this->info('Фиды актуальны, пересборка не нужна. Используйте --force для принудительной генерации.');

            return self::SUCCESS;
        }

        try {
            $meta = ProductFeedService::generate();
        } catch (Throwable $e) {
            $this->error('Ошибка генерации фидов: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Готово: {$meta['count']} товаров.");
        $this->line('Google: '.$meta['google']);
        $this->line('Facebook: '.$meta['facebook']);

        return self::SUCCESS;
    }
}
