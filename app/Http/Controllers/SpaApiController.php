<?php

namespace App\Http\Controllers;

use App\Services\SpaPageService;
use Illuminate\Http\Request;

class SpaApiController extends Controller
{
    public function __construct(private readonly SpaPageService $spa)
    {
    }

    public function home()
    {
        return response()->json([
            'success' => true,
            'meta' => $this->spa->homeMeta(),
            'data' => $this->spa->homePayload(),
        ]);
    }

    public function catalog(Request $request)
    {
        return response()->json([
            'success' => true,
            'meta' => $this->spa->catalogMeta(),
            'data' => $this->spa->catalogPayload($request),
        ]);
    }

    public function category(Request $request, string $category)
    {
        $categoryModel = \App\Models\Category::where('url', $category)->firstOrFail();

        return response()->json([
            'success' => true,
            'meta' => $this->spa->categoryMeta($categoryModel),
            'data' => $this->spa->categoryPayload($request, $category),
        ]);
    }

    public function product(string $category, string $product)
    {
        $data = $this->spa->productPayload($category, $product);

        return response()->json([
            'success' => true,
            'meta' => [
                'title' => ($data['product']['name'] ?? 'Товар') . ' — Mtoys',
                'description' => ! empty($data['product']['description'])
                    ? strip_tags(mb_substr($data['product']['description'], 0, 160))
                    : ($data['product']['name'] ?? 'Товар'),
            ],
            'data' => $data,
        ]);
    }
}
