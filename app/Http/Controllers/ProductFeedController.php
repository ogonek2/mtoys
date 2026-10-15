<?php

namespace App\Http\Controllers;

use App\Services\ProductFeedService;
use Symfony\Component\HttpFoundation\Response;

class ProductFeedController extends Controller
{
    public function google(): Response
    {
        $body = ProductFeedService::content('google');

        if ($body === null || $body === '') {
            abort(503, 'Google feed is not ready');
        }

        return response($body, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    public function facebook(): Response
    {
        $body = ProductFeedService::content('facebook');

        if ($body === null || $body === '') {
            abort(503, 'Facebook feed is not ready');
        }

        return response($body, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="facebook-catalog.csv"',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
