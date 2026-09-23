<?php

use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\indexController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CsrfTokenController;
use App\Http\Controllers\SpaApiController;
use App\Http\Controllers\SpaShellController;

use App\Http\Controllers\AdminProductsUploadController;
use App\Http\Controllers\admin\AdminMainController;

use App\Http\Controllers\admin\AdminProductsController;
use App\Http\Controllers\admin\AdminCategoryController;
use App\Http\Controllers\admin\AdminCatalogController;
use App\Http\Controllers\admin\AdminOrdersController;
use App\Filament\Resources\ProductResource\Pages\ManageGallery;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [SpaShellController::class, 'home'])->name('welcome');
Route::get('/home', [SpaShellController::class, 'home'])->name('home');
Route::get('/thank-you', function() {
    return view('thanks_you_page');
})->name('thanks');
Route::get('/uhoda-korystuvacha', function() {
    return view('information.uhoda_korystuvacha');
})->name('uhoda_korystuvacha');
Route::get('/dohovir-oferty', function() {
    return view('information.dohovir_oferty');
})->name('dohovir_oferty');
Route::get('/privacy-policy', function() {
    return view('information.privacy_policy');
})->name('privacy_policy');
Route::get('/garantiya', function() {
    return view('information.garantiya');
})->name('garantiya');
Route::get('/oplata', function() {
    return view('information.oplata');
})->name('oplata');
Route::get('/dostavka-ta-povernennia', function() {
    return view('information.dostavka_ta_povernennia');
})->name('dostavka_ta_povernennia');
Route::get('/oplata-i-dostavka', function() {
    return redirect()->route('oplata');
})->name('oplata_i_dostavka');
Route::get('/obmin-ta-povernennia', function() {
    return redirect()->route('dostavka_ta_povernennia');
})->name('obmin_ta_povernennia');
Route::get('/kontaktna-informatsiia', function() {
    return view('information.kontaktna_informatsiia');
})->name('kontaktna_informatsiia');
Route::get('/pro-kompaniiu', function() {
    return view('information.about');
})->name('pro_kompaniiu');
Route::post('/contact-request', [ContactController::class, 'submit'])->name('contact_request');
Route::get('/csrf-token', CsrfTokenController::class)->name('csrf_token');

Route::middleware('auth')->group(function () {
    Route::get('/admin/orders/{order}/print', [OrderController::class, 'print'])->name('admin.orders.print');
});

Route::get('/api/products', function () {
    return \App\Models\Product::select('id', 'name', 'articule', 'price', 'discount', 'image_path', 'availability', 'url')
        ->inRandomOrder()
        ->paginate(80);
});
Route::post('/order-submit', [OrderController::class, 'submit'])->name('order_submit');
Route::post('/save-abandoned-cart', [OrderController::class, 'saveAbandoned']);

Route::prefix('api/spa')->group(function () {
    Route::get('/home', [SpaApiController::class, 'home'])->name('api.spa.home');
    Route::get('/catalog', [SpaApiController::class, 'catalog'])->name('api.spa.catalog');
    Route::get('/category/{category}', [SpaApiController::class, 'category'])->name('api.spa.category');
    Route::get('/product/{category}/{product}', [SpaApiController::class, 'product'])->name('api.spa.product');
});

// Catalog
Route::get('/catalog', [SpaShellController::class, 'catalog'])->name('catalog');
Route::get('/catalog/categoriya', fn () => redirect()->route('catalog'))->name('catalog_category_index');
Route::get('/checkout', [indexController::class, 'checkout'])->name('checkout');
Route::get('/koshyk', [SpaShellController::class, 'cart'])->name('cart');
Route::get('/obrane', [SpaShellController::class, 'wishlist'])->name('wishlist');
Route::get('/cities', [indexController::class, 'getCities']);
Route::post('/warehouses', [indexController::class, 'getWarehouses']);

Route::group(['prefix' => 'catalog'], function () {
    Route::get('search', [CatalogController::class, 'search'])->name('catalog.search');
    Route::match(['get', 'post'], 'categoriya/{category}', [SpaShellController::class, 'category'])->name('catalog_category_page');
    Route::get('categoriya/{category}/{product}', [SpaShellController::class, 'product'])->name('catalog_product_page');
});

Route::get('/search', [CatalogController::class, 'searchSubmit'])->name('search.index');
Route::get('/api/recommended-products', [CatalogController::class, 'getRecommendedProducts'])->name('api.recommended_products');
Route::get('/test-recommended', [CatalogController::class, 'getRecommendedProducts'])->name('test.recommended_products');