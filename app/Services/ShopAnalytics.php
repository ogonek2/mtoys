<?php

namespace App\Services;

use App\Models\Orders;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShopAnalytics
{
    public const CACHE_TTL = 300;

    /**
     * @return array{orders:int, revenue:float, avg_check:float, products:int, in_stock:int, abandoned:int, orders_total:int, revenue_total:float}
     */
    public static function overview(): array
    {
        return Cache::remember('shop.analytics.overview', self::CACHE_TTL, function () {
            try {
                $monthStart = now()->startOfMonth()->copy();
                $ordersMonth = Orders::query()
                    ->where('created_at', '>=', $monthStart)
                    ->orderBy('id')
                    ->limit(2000)
                    ->get();
                $revenue = $ordersMonth->sum(fn (Orders $o) => $o->numeric_total_price);
                $count = $ordersMonth->count();

                $abandoned = 0;
                try {
                    $abandoned = (int) DB::table('abandoned_carts')->count();
                } catch (Throwable) {
                    $abandoned = 0;
                }

                $allForTotal = Orders::query()->orderBy('id')->limit(5000)->get();

                return [
                    'orders' => $count,
                    'revenue' => round((float) $revenue, 2),
                    'avg_check' => $count > 0 ? round((float) $revenue / $count, 2) : 0.0,
                    'products' => Product::query()->count(),
                    'in_stock' => Product::query()->whereRaw(ProductListingService::IN_STOCK_SQL)->count(),
                    'abandoned' => $abandoned,
                    'orders_total' => Orders::query()->count(),
                    'revenue_total' => round((float) $allForTotal->sum(fn (Orders $o) => $o->numeric_total_price), 2),
                ];
            } catch (Throwable $e) {
                Log::warning('ShopAnalytics::overview failed: '.$e->getMessage());

                return [
                    'orders' => 0,
                    'revenue' => 0.0,
                    'avg_check' => 0.0,
                    'products' => 0,
                    'in_stock' => 0,
                    'abandoned' => 0,
                    'orders_total' => 0,
                    'revenue_total' => 0.0,
                ];
            }
        });
    }

    /**
     * @return array{labels: array<int, string>, orders: array<int, int>, revenue: array<int, float>}
     */
    public static function dailySales(int $days = 30): array
    {
        $days = max(1, min(90, $days));

        return Cache::remember("shop.analytics.daily.{$days}", self::CACHE_TTL, function () use ($days) {
            try {
                $start = now()->subDays($days - 1)->startOfDay();
                $orders = Orders::query()
                    ->where('created_at', '>=', $start)
                    ->orderBy('id')
                    ->limit(5000)
                    ->get();

                $labels = [];
                $orderCounts = [];
                $revenues = [];

                for ($i = 0; $i < $days; $i++) {
                    $day = $start->copy()->addDays($i);
                    $key = $day->format('Y-m-d');
                    $labels[] = $day->format('d.m');
                    $dayOrders = $orders->filter(fn (Orders $o) => $o->created_at?->format('Y-m-d') === $key);
                    $orderCounts[] = $dayOrders->count();
                    $revenues[] = round((float) $dayOrders->sum(fn (Orders $o) => $o->numeric_total_price), 2);
                }

                return [
                    'labels' => $labels,
                    'orders' => $orderCounts,
                    'revenue' => $revenues,
                ];
            } catch (Throwable $e) {
                Log::warning('ShopAnalytics::dailySales failed: '.$e->getMessage());

                return self::emptySeries($days, 'd.m');
            }
        });
    }

    /**
     * @return array{labels: array<int, string>, orders: array<int, int>, revenue: array<int, float>}
     */
    public static function monthlySales(int $months = 6): array
    {
        $months = max(1, min(24, $months));

        return Cache::remember("shop.analytics.monthly.{$months}", self::CACHE_TTL, function () use ($months) {
            try {
                $start = now()->subMonths($months - 1)->startOfMonth();
                $orders = Orders::query()
                    ->where('created_at', '>=', $start)
                    ->orderBy('id')
                    ->limit(5000)
                    ->get();

                $labels = [];
                $orderCounts = [];
                $revenues = [];

                for ($i = 0; $i < $months; $i++) {
                    $month = $start->copy()->addMonths($i);
                    $key = $month->format('Y-m');
                    $labels[] = $month->translatedFormat('M Y');
                    $monthOrders = $orders->filter(fn (Orders $o) => $o->created_at?->format('Y-m') === $key);
                    $orderCounts[] = $monthOrders->count();
                    $revenues[] = round((float) $monthOrders->sum(fn (Orders $o) => $o->numeric_total_price), 2);
                }

                return [
                    'labels' => $labels,
                    'orders' => $orderCounts,
                    'revenue' => $revenues,
                ];
            } catch (Throwable $e) {
                Log::warning('ShopAnalytics::monthlySales failed: '.$e->getMessage());

                return self::emptySeries($months, 'M Y', true);
            }
        });
    }

    /**
     * @return Collection<int, array{id:int, name:string, articule:string, qty:int, revenue:float, orders:int}>
     */
    public static function topProducts(int $limit = 10): Collection
    {
        $limit = max(1, min(50, $limit));

        return Cache::remember("shop.analytics.top_products.{$limit}", self::CACHE_TTL, function () use ($limit) {
            try {
                $map = [];

                Orders::query()->latest('id')->limit(500)->get()->each(function (Orders $order) use (&$map) {
                    foreach ($order->cart_items as $item) {
                        $key = (string) ($item['id'] ?? $item['articule'] ?? $item['name'] ?? uniqid('p_', true));
                        if (! isset($map[$key])) {
                            $map[$key] = [
                                'name' => (string) ($item['name'] ?? 'Товар'),
                                'articule' => (string) ($item['articule'] ?? '—'),
                                'qty' => 0,
                                'revenue' => 0.0,
                                'orders' => 0,
                            ];
                        }
                        $qty = (int) ($item['quantity'] ?? 1);
                        $price = (float) ($item['price'] ?? 0);
                        $map[$key]['qty'] += $qty;
                        $map[$key]['revenue'] += $qty * $price;
                        $map[$key]['orders']++;
                    }
                });

                return collect($map)
                    ->sortByDesc('qty')
                    ->take($limit)
                    ->values()
                    ->map(function (array $row, int $index) {
                        $row['id'] = $index + 1;
                        $row['revenue'] = round($row['revenue'], 2);

                        return $row;
                    });
            } catch (Throwable $e) {
                Log::warning('ShopAnalytics::topProducts failed: '.$e->getMessage());

                return collect();
            }
        });
    }

    /**
     * @return Collection<int, array{name:string, qty:int, revenue:float}>
     */
    public static function topCategories(int $limit = 8): Collection
    {
        $limit = max(1, min(20, $limit));

        return Cache::remember("shop.analytics.top_categories.{$limit}", self::CACHE_TTL, function () use ($limit) {
            try {
                $productIds = [];
                $qtyByProduct = [];
                $revenueByProduct = [];

                Orders::query()->latest('id')->limit(500)->get()->each(function (Orders $order) use (&$productIds, &$qtyByProduct, &$revenueByProduct) {
                    foreach ($order->cart_items as $item) {
                        $id = (int) ($item['id'] ?? 0);
                        if ($id <= 0) {
                            continue;
                        }
                        $productIds[$id] = $id;
                        $qty = (int) ($item['quantity'] ?? 1);
                        $price = (float) ($item['price'] ?? 0);
                        $qtyByProduct[$id] = ($qtyByProduct[$id] ?? 0) + $qty;
                        $revenueByProduct[$id] = ($revenueByProduct[$id] ?? 0) + ($qty * $price);
                    }
                });

                if ($productIds === []) {
                    return collect();
                }

                $products = Product::query()
                    ->whereIn('id', array_values($productIds))
                    ->with(['categories:id,name'])
                    ->get(['id']);

                $map = [];
                foreach ($products as $product) {
                    $category = $product->categories->first();
                    $catName = $category?->name ?? 'Без категории';
                    if (! isset($map[$catName])) {
                        $map[$catName] = ['name' => $catName, 'qty' => 0, 'revenue' => 0.0];
                    }
                    $map[$catName]['qty'] += $qtyByProduct[$product->id] ?? 0;
                    $map[$catName]['revenue'] += $revenueByProduct[$product->id] ?? 0;
                }

                return collect($map)
                    ->sortByDesc('qty')
                    ->take($limit)
                    ->values()
                    ->map(function (array $row) {
                        $row['revenue'] = round($row['revenue'], 2);

                        return $row;
                    });
            } catch (Throwable $e) {
                Log::warning('ShopAnalytics::topCategories failed: '.$e->getMessage());

                return collect();
            }
        });
    }

    /**
     * @return array{count:int, recent:int, phones:int}
     */
    public static function abandonedCarts(): array
    {
        return Cache::remember('shop.analytics.abandoned', self::CACHE_TTL, function () {
            try {
                $count = (int) DB::table('abandoned_carts')->count();
                $recent = (int) DB::table('abandoned_carts')
                    ->where('updated_at', '>=', now()->subDays(7))
                    ->count();
                $phones = (int) DB::table('abandoned_carts')->whereNotNull('phone')->distinct('phone')->count('phone');

                return compact('count', 'recent', 'phones');
            } catch (Throwable) {
                return ['count' => 0, 'recent' => 0, 'phones' => 0];
            }
        });
    }

    /**
     * @return array{labels: array<int, string>, orders: array<int, int>, revenue: array<int, float>}
     */
    private static function emptySeries(int $count, string $format, bool $monthly = false): array
    {
        $labels = [];
        $zeros = [];
        $start = $monthly ? now()->subMonths($count - 1)->startOfMonth() : now()->subDays($count - 1)->startOfDay();

        for ($i = 0; $i < $count; $i++) {
            $point = $monthly ? $start->copy()->addMonths($i) : $start->copy()->addDays($i);
            $labels[] = $monthly ? $point->translatedFormat($format) : $point->format($format);
            $zeros[] = 0;
        }

        return [
            'labels' => $labels,
            'orders' => $zeros,
            'revenue' => array_map(static fn () => 0.0, $zeros),
        ];
    }

    public static function forgetCache(): void
    {
        Cache::forget('shop.analytics.overview');
        Cache::forget('shop.analytics.abandoned');
        foreach ([30, 14, 7] as $days) {
            Cache::forget("shop.analytics.daily.{$days}");
        }
        foreach ([6, 12] as $months) {
            Cache::forget("shop.analytics.monthly.{$months}");
        }
        foreach ([10, 12, 15] as $limit) {
            Cache::forget("shop.analytics.top_products.{$limit}");
            Cache::forget("shop.analytics.top_categories.{$limit}");
        }
    }
}
