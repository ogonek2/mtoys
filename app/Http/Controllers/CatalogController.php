<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Services\ProductListingService;

class CatalogController extends Controller
{
    public function __construct(private readonly ProductListingService $listing)
    {
    }

    public function search(Request $request)
    {
        $results = $this->listing->autocomplete((string) $request->input('q'), 12);
        $this->listing->attachPrimaryCategory($results);

        return response()->json($results);
    }

    public function searchSubmit(Request $request)
    {
        $query = (string) $request->input('q');
        $originalQuery = $query;

        if (empty($query)) {
            return redirect()->route('home');
        }

        $cleanQuery = trim($query);
        $builder = $this->listing->queryForSearch($cleanQuery)->with('categories');
        $getProducts = $builder->paginate(12);
        $this->listing->attachPrimaryCategory($getProducts->getCollection());
        $getProducts->appends($request->query());

        $suggestions = $this->generateSearchSuggestions($query);

        return view('searchResult', [
            'query' => $query,
            'originalQuery' => $originalQuery,
            'getProducts' => $getProducts,
            'suggestions' => $suggestions,
            'usedAlternative' => false,
            'paginationData' => [
                'current_page' => $getProducts->currentPage(),
                'last_page' => $getProducts->lastPage(),
                'per_page' => $getProducts->perPage(),
                'total' => $getProducts->total(),
                'from' => $getProducts->firstItem(),
                'to' => $getProducts->lastItem(),
            ],
        ]);
    }

    private function generateSearchSuggestions($query)
    {
        $suggestions = [];
        $words = explode(' ', trim($query));

        foreach ($words as $word) {
            if (strlen($word) >= 3) {
                $similarProducts = Product::where('name', 'like', '%' . $word . '%')
                    ->where('availability', 'in_stock')
                    ->limit(3)
                    ->pluck('name')
                    ->toArray();

                $suggestions = array_merge($suggestions, $similarProducts);
            }
        }

        return array_slice(array_unique($suggestions), 0, 5);
    }

    public function getRecommendedProducts()
    {
        try {
            $recommendedProducts = $this->listing->recommended(12);
            return response()->json([
                'success' => true,
                'products' => $recommendedProducts,
                'total' => $recommendedProducts->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Ошибка при получении рекомендуемых товаров: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке рекомендуемых товаров: ' . $e->getMessage(),
                'products' => [],
            ], 500);
        }
    }
}
