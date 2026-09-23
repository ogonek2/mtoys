<?php

namespace App\Http\Controllers;

use App\Imports\ProductsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Jobs\ProcessProductImport;

class ProductImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:5120', // макс 5 МБ
        ]);

        // Сохраняем файл во временную папку
        $path = $request->file('excel_file')->store('imports');

        // Ставим задачу в очередь с путем к файлу
        ProcessProductImport::dispatch($path);

        return response()->json(['message' => 'Файл загружен, импорт запущен в фоне']);
    }
}
