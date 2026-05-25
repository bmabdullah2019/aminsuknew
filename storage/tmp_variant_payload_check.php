<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$product = App\Models\Product::query()->find(254);
if (!$product) {
    echo "Product not found\n";
    exit;
}
$payload = app(App\Services\VariantAttributeService::class)->buildProductVariantPayload($product);
echo "variants=" . count($payload['variants'] ?? []) . "\n";
foreach (($payload['variants'] ?? []) as $v) {
    echo ($v['sku_code'] ?? '') . ' | ' . ($v['color'] ?? '') . ' | ' . ($v['size'] ?? '') . ' | ' . ($v['age'] ?? '') . "\n";
}
