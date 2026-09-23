<?php

namespace App\Http\Controllers;

// Library
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

// Helper
use App\Helpers\FileUploadHelper;

// Models
use App\Models\Product;

class AdminProductsUploadController extends Controller
{
    public function upload(Request $req)
    {
        if (!$req->hasFile('image') || !$req->file('image')->isValid()) {
            return redirect()->back()->withErrors(['image' => 'Файл не загружен или повреждён']);
        }

        $publicUrl = FileUploadHelper::uploadToBunnyCDN($req->file('image'), 'products');

        if (!$publicUrl) {
            return back()->withErrors(['upload' => 'Не удалось загрузить файл на CDN.']);
        }

        Product::create([
            'name' => '123443gfgdgdfgdfg2434',
            'articule' => '12dfgdfgd13',
            'description' => 'fdfgwgwegefhefhehehegerrgfkefijfeifjheijhgiejgij',
            'price' => 15,
            'image_path' => $publicUrl,
        ]);

        return redirect()->back()->with('success', 'Продукт успешно добавлен');
    }
}
