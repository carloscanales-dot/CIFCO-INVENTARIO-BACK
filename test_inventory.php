<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Simular lo que hace el controlador
$products = \App\Models\Product\Product::with('product_categorie')
    ->orderBy("id", "desc")
    ->paginate(15);

$productIds = $products->pluck('id')->toArray();

echo "Total productos en página: " . count($productIds) . PHP_EOL;
echo "IDs: " . implode(', ', array_slice($productIds, 0, 5)) . "..." . PHP_EOL;

$inventoryStock = \App\Models\Product\ProductWarehouse::whereIn('product_id', $productIds)
    ->get();

echo "Registros de stock encontrados: " . $inventoryStock->count() . PHP_EOL;

if ($inventoryStock->count() > 0) {
    echo "\nPrimeros 3 registros:" . PHP_EOL;
    foreach ($inventoryStock->take(3) as $stock) {
        echo "- Product ID: {$stock->product_id}, Warehouse ID: {$stock->warehouse_id}, Stock: {$stock->stock}" . PHP_EOL;
    }
} else {
    echo "\n⚠️ No se encontró stock para estos productos" . PHP_EOL;
    echo "Verificando si hay stock en general..." . PHP_EOL;
    $anyStock = \App\Models\Product\ProductWarehouse::take(3)->get();
    echo "Muestra de stock existente:" . PHP_EOL;
    foreach ($anyStock as $stock) {
        echo "- Product ID: {$stock->product_id}, Warehouse ID: {$stock->warehouse_id}, Stock: {$stock->stock}" . PHP_EOL;
    }
}
