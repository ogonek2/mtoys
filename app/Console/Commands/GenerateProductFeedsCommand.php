<?php

namespace App\Console\Commands;

use App\Services\ProductFeedService;
use Illuminate\Console\Command;
use Throwable;

class GenerateProductFeedsCommand extends Command
{
    protected $signature = 'feeds:generate {--force : Принудительно}';

    protected $description = 'Собрать Google/Facebook фиды из таблицы products';

    public function handle(): int
    {
        if (
            ! $this->option('force')
            && ! cache()->get(\App\Services\Feeds\FeedBuilder::DIRTY_KEY)
            && ProductFeedService::exists('google')
        ) {
            $this->info('Фиды актуальны. --force для пересборки.');

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
