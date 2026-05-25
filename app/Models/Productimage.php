<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Productimage extends Model
{
    use HasFactory;

    private const FALLBACK_IMAGE_PATH = 'public/backEnd/assets/images/products/product-1.png';

    protected $fillable = [
        'product_id',
        'image',
        'webp_image',
        'alt_text',
        'sort_order',
    ];

    public function getImageAttribute($value)
    {
        return $this->normalizePublicPath($value);
    }

    public function getWebpImageAttribute($value)
    {
        return $this->normalizePublicPath($value);
    }

    private function normalizePublicPath($value)
    {
        if (! $value) {
            return self::FALLBACK_IMAGE_PATH;
        }

        $path = str_replace('\\', '/', (string) $value);

        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        $path = ltrim($path, '/');

        // Handle storage/ prefixed paths (new uploads)
        if (Str::startsWith($path, 'storage/')) {
            // Path is like: storage/products/gallery/file.jpg
            // Public accessible path is: public/storage/products/gallery/file.jpg
            // File exists at: storage/app/public/products/gallery/file.jpg
            $filePath = str_replace('storage/', 'storage/app/public/', $path);
            $publicPath = 'public/'.$path;
            if (! is_file(base_path($filePath)) && ! is_file(base_path($publicPath))) {
                return self::FALLBACK_IMAGE_PATH;
            }

            // For web access, serve from public/storage/
            return 'public/'.$path;
        }

        // Handle legacy public/ paths (old uploads)
        if (! Str::startsWith($path, 'public/')) {
            $path = 'public/'.$path;
        }

        // Show a placeholder when DB points to a file that no longer exists.
        if (! is_file(base_path($path))) {
            return self::FALLBACK_IMAGE_PATH;
        }

        return $path;
    }
}
