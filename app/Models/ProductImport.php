<?php

namespace App\Models;

use App\Services\Products\ProductImportResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Задача импорта товаров: файл нарезается на порции, каждую порцию
 * обрабатывает своя задача в очереди, а прогресс собирается здесь.
 */
class ProductImport extends Model
{
    use HasFactory;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    /** Сколько записей о проблемах храним, чтобы не раздувать строку. */
    private const PROBLEMS_LIMIT = 100;

    protected $fillable = [
        'user_id',
        'file_name',
        'source',
        'status',
        'batch_id',
        'directory',
        'chunk_size',
        'chunks_total',
        'chunks_done',
        'total_rows',
        'processed_rows',
        'created_count',
        'updated_count',
        'skipped_count',
        'categories_created',
        'images_created',
        'options',
        'recognized_fields',
        'unknown_headers',
        'problems',
        'error',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'options' => 'array',
        'recognized_fields' => 'array',
        'unknown_headers' => 'array',
        'problems' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_PROCESSING], true);
    }

    public function isFinished(): bool
    {
        return ! $this->isActive();
    }

    public function progress(): int
    {
        if ($this->isFinished()) {
            return 100;
        }

        if ($this->total_rows < 1) {
            return 0;
        }

        return (int) min(99, floor($this->processed_rows / $this->total_rows * 100));
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_QUEUED => 'В очереди',
            self::STATUS_PROCESSING => 'Выполняется',
            self::STATUS_COMPLETED => 'Готово',
            self::STATUS_FAILED => 'Ошибка',
            self::STATUS_CANCELLED => 'Отменено',
            default => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_QUEUED => 'gray',
            self::STATUS_PROCESSING => 'info',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'warning',
            default => 'gray',
        };
    }

    /** Короткая сводка для панели задач. */
    public function summary(): string
    {
        $parts = [];

        if ($this->isActive()) {
            $parts[] = "{$this->processed_rows} из {$this->total_rows} строк";
            $parts[] = "{$this->chunks_done} из {$this->chunks_total} порций";
        } else {
            $parts[] = "строк: {$this->processed_rows}";
        }

        if ($this->created_count > 0) {
            $parts[] = "создано: {$this->created_count}";
        }

        if ($this->updated_count > 0) {
            $parts[] = "обновлено: {$this->updated_count}";
        }

        if ($this->skipped_count > 0) {
            $parts[] = "пропущено: {$this->skipped_count}";
        }

        if ($this->error) {
            $parts[] = $this->error;
        }

        return implode(' · ', $parts);
    }

    /** Длительность выполнения в секундах. */
    public function duration(): ?int
    {
        if ($this->started_at === null) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->finished_at ?? now(), absolute: true);
    }

    public function markProcessing(): void
    {
        if ($this->status !== self::STATUS_QUEUED) {
            return;
        }

        $this->newQuery()
            ->whereKey($this->getKey())
            ->where('status', self::STATUS_QUEUED)
            ->update([
                'status' => self::STATUS_PROCESSING,
                'started_at' => $this->started_at ?? now(),
                'updated_at' => now(),
            ]);

        $this->refresh();
    }

    /**
     * Складывает итоги одной порции. Счётчики увеличиваем запросом к БД —
     * порции могут обрабатываться несколькими воркерами одновременно.
     */
    public function applyChunkResult(ProductImportResult $result): void
    {
        DB::transaction(function () use ($result): void {
            $fresh = $this->newQuery()->whereKey($this->getKey())->lockForUpdate()->first();

            if ($fresh === null) {
                return;
            }

            $problems = array_slice(
                array_values(array_unique(array_merge($fresh->problems ?? [], $result->problems))),
                0,
                self::PROBLEMS_LIMIT,
            );

            $this->newQuery()->whereKey($this->getKey())->update([
                'chunks_done' => DB::raw('chunks_done + 1'),
                'processed_rows' => DB::raw('processed_rows + '.(int) $result->rows),
                'created_count' => DB::raw('created_count + '.(int) $result->created),
                'updated_count' => DB::raw('updated_count + '.(int) $result->updated),
                'skipped_count' => DB::raw('skipped_count + '.(int) $result->skipped),
                'categories_created' => DB::raw('categories_created + '.(int) $result->categoriesCreated),
                'images_created' => DB::raw('images_created + '.(int) $result->imagesCreated),
                'problems' => json_encode($problems, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
        });

        $this->refresh();
    }

    public function addProblem(string $problem): void
    {
        $problems = $this->problems ?? [];
        $problems[] = $problem;

        $this->update([
            'problems' => array_slice(array_values(array_unique($problems)), 0, self::PROBLEMS_LIMIT),
        ]);
    }

    public function finish(string $status, ?string $error = null): void
    {
        $this->update([
            'status' => $status,
            'error' => $error,
            'started_at' => $this->started_at ?? now(),
            'finished_at' => now(),
        ]);

        $this->deleteChunks();
    }

    public function cancel(): void
    {
        if ($this->batch_id !== null) {
            Bus::findBatch($this->batch_id)?->cancel();
        }

        if ($this->isActive()) {
            $this->finish(self::STATUS_CANCELLED);
        }
    }

    /** Удаляет нарезанные порции — после завершения они уже не нужны. */
    public function deleteChunks(): void
    {
        if ($this->directory !== null && File::isDirectory($this->directory)) {
            File::deleteDirectory($this->directory);
        }
    }

    protected static function booted(): void
    {
        static::deleting(function (self $import): void {
            $import->deleteChunks();
        });
    }
}
