<?php

namespace App\Http\Controllers;

use App\Services\SpaPageService;
use Illuminate\Http\Request;

class SpaShellController extends Controller
{
    public function __construct(private readonly SpaPageService $spa)
    {
    }

    public function home()
    {
        return view('spa.page', [
            'spaInitial' => [
                'name' => 'home',
                'params' => [],
                'query' => [],
                'meta' => $this->spa->homeMeta(),
                'data' => $this->spa->homePayload(),
            ],
        ]);
    }

    public function catalog(Request $request)
    {
        return view('spa.page', [
            'spaInitial' => [
                'name' => 'catalog',
                'params' => [],
                'query' => $request->query(),
                'meta' => $this->spa->catalogMeta(),
                'data' => $this->spa->catalogPayload($request),
            ],
        ]);
    }

    public function category(Request $request, string $category)
    {
        $categoryModel = \App\Models\Category::where('url', $category)->firstOrFail();

        return view('spa.page', [
            'spaInitial' => [
                'name' => 'category',
                'params' => ['category' => $category],
                'query' => $request->query(),
                'meta' => $this->spa->categoryMeta($categoryModel),
                'data' => $this->spa->categoryPayload($request, $category),
            ],
        ]);
    }

    public function product(string $category, string $product)
    {
        $data = $this->spa->productPayload($category, $product);

        return view('spa.page', [
            'spaInitial' => [
                'name' => 'product',
                'params' => ['category' => $category, 'product' => $product],
                'query' => [],
                'meta' => [
                    'title' => ($data['product']['name'] ?? 'Товар') . ' — Mtoys',
                    'description' => ! empty($data['product']['description'])
                        ? strip_tags(mb_substr($data['product']['description'], 0, 160))
                        : ($data['product']['name'] ?? 'Товар'),
                ],
                'data' => $data,
            ],
        ]);
    }

    public function cart()
    {
        return view('spa.page', [
            'spaInitial' => [
                'name' => 'cart',
                'params' => [],
                'query' => [],
                'meta' => [
                    'title' => 'Кошик — Mtoys',
                    'description' => 'Кошик покупок Mtoys',
                ],
                'data' => [],
            ],
        ]);
    }

    public function wishlist()
    {
        return view('spa.page', [
            'spaInitial' => [
                'name' => 'wishlist',
                'params' => [],
                'query' => [],
                'meta' => [
                    'title' => 'Обране — Mtoys',
                    'description' => 'Обрані товари Mtoys',
                ],
                'data' => [],
            ],
        ]);
    }
}
