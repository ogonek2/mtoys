<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategoryCatalogRelation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'category_id',
        'catalog_id',
        'sort_order',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function catalog()
    {
        return $this->belongsTo(Catalog::class);
    }
}