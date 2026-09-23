<?php

namespace App\Http\Controllers;

use App\Services\NovaPoshtaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class indexController extends Controller
{
    public function checkout()
    {
        return view('checkout');
    }

    public function getCities(Request $request, NovaPoshtaService $np): JsonResponse
    {
        $q = (string) $request->query('q', $request->query('term', ''));

        // Select2 ajax иногда шлёт term, иногда q
        if ($q === '' && is_string($request->input('term'))) {
            $q = $request->input('term');
        }

        return response()->json($np->searchCities($q));
    }

    public function getWarehouses(Request $request, NovaPoshtaService $np): JsonResponse
    {
        $cityRef = (string) ($request->input('cityRef') ?? $request->query('cityRef', ''));
        $q = (string) ($request->input('q') ?? $request->input('term') ?? $request->query('q', ''));

        return response()->json($np->warehousesForCity($cityRef, $q));
    }
}
