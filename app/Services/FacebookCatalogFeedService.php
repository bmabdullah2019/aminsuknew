<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use XMLWriter;

class FacebookCatalogFeedService
{
    public const CACHE_KEY = 'feeds:facebook_catalog_xml:v4';
    private const CACHE_TTL_MINUTES = 30;
    private const CURRENCY = 'BDT';

    public function xml(): string
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_TTL_MINUTES), function () {
            return $this->buildXml();
        });
    }

    private function buildXml(): string
    {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('rss');
        $writer->writeAttribute('version', '2.0');
        $writer->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');

        $writer->startElement('channel');
        $writer->writeElement('title', config('app.name', 'Store').' Facebook Catalog');
        $writer->writeElement('link', $this->baseUrl());
        $writer->writeElement('description', 'Product catalog feed for Facebook Commerce Manager');

        $this->query()
            ->chunkById(250, function ($products) use ($writer) {
                foreach ($products as $product) {
                    $this->writeProduct($writer, $product);
                }
            });

        $writer->endElement(); // channel
        $writer->endElement(); // rss
        $writer->endDocument();

        return $writer->outputMemory();
    }

    private function query(): Builder
    {
        return Product::query()
            ->withoutGlobalScopes()
            ->where('status', 1)
            ->whereNotNull('slug')
            ->where('slug', '<>', '')
            ->whereNotNull('name')
            ->where('name', '<>', '')
            ->where('new_price', '>', 0)
            ->with(['image', 'category', 'brand', 'warehouseStocks'])
            ->select([
                'id',
                'name',
                'slug',
                'sku',
                'product_code',
                'new_price',
                'old_price',
                'description',
                'short_description',
                'category_id',
                'brand_id',
                'status',
                'updated_at',
            ])
            ->orderBy('id');
    }

    private function writeProduct(XMLWriter $writer, Product $product): void
    {
        $description = $this->description($product);
        $brand = trim((string) ($product->brand->name ?? config('app.name', 'Store')));
        $category = trim((string) ($product->category->name ?? ''));
        $stock = (float) $product->warehouseStocks->sum('available_quantity');

        $writer->startElement('item');
        $writer->writeElement('g:id', $this->productId($product));
        $writer->writeElement('g:title', Str::limit(trim((string) $product->name), 150, ''));
        $writer->writeElement('g:description', Str::limit($description, 5000, ''));
        $writer->writeElement('g:availability', $stock > 0 ? 'in stock' : 'out of stock');
        $writer->writeElement('g:condition', 'new');
        $writer->writeElement('g:price', $this->money((float) $product->new_price));
        $writer->writeElement('g:link', $this->absoluteUrl('products/'.ltrim((string) $product->slug, '/')));
        $writer->writeElement('g:image_link', $this->absoluteUrl($product->display_image));
        $writer->writeElement('g:brand', Str::limit($brand, 70, ''));

        if ($category !== '') {
            $writer->writeElement('g:product_type', Str::limit($category, 750, ''));
        }

        if ((float) $product->old_price > (float) $product->new_price) {
            $writer->writeElement('g:sale_price', $this->money((float) $product->new_price));
        }

        $writer->writeElement('g:inventory', (string) max(0, (int) floor($stock)));
        $writer->endElement(); // item
    }

    private function productId(Product $product): string
    {
        $identifier = trim((string) ($product->sku ?: $product->product_code ?: $product->id));

        return 'product-'.$identifier;
    }

    private function description(Product $product): string
    {
        $description = trim(strip_tags((string) ($product->short_description ?: $product->description)));
        $description = preg_replace('/\s+/', ' ', $description) ?: '';

        return $description !== '' ? $description : (string) $product->name;
    }

    private function money(float $amount): string
    {
        return number_format(max(0, $amount), 2, '.', '').' '.self::CURRENCY;
    }

    private function absoluteUrl(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return $this->baseUrl().'/'.ltrim($path, '/');
    }

    private function baseUrl(): string
    {
        $configuredUrl = $this->normalizedConfiguredUrl();

        if (app()->runningInConsole()) {
            return $configuredUrl;
        }

        $requestUrl = rtrim(request()->getSchemeAndHttpHost(), '/');
        $requestHost = parse_url($requestUrl, PHP_URL_HOST);

        if (! in_array($requestHost, ['localhost', '127.0.0.1'], true)) {
            return $requestUrl;
        }

        return $configuredUrl;
    }

    private function normalizedConfiguredUrl(): string
    {
        $url = trim((string) config('app.url', ''));
        if ($url === '') {
            return 'http://localhost';
        }

        if (! Str::startsWith($url, ['http://', 'https://'])) {
            $url = 'http://'.$url;
        }

        return rtrim($url, '/');
    }
}
