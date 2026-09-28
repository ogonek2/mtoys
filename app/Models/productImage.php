<?php

namespace App\Models;

use App\Helpers\FileUploadHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'src', 'product_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getImagePath()
    {
        return FileUploadHelper::publicUrl($this->src);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray()
    {
        $array = parent::toArray();

        if (array_key_exists('src', $array)) {
            $array['src'] = FileUploadHelper::publicUrl(
                is_string($array['src'] ?? null) ? $array['src'] : $this->src
            );
        }

        return $array;
    }
}
