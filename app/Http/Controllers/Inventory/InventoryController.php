<?php

namespace App\Http\Controllers\Inventory;

use App\Models\Product\Product;
use App\Models\Product\ProductWarehouse;
use App\Models\Config\Warehouse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\Product\ProductCollection;
use App\Exports\Product\ProductDownloadExcel;
use App\Exports\Product\InventoryDownloadExcel;
use Maatwebsite\Excel\Facades\Excel;

class InventoryController extends Controller
{
    /**
     * Obtener lista de inventario con stock por almacén
     */
    public function list(Request $request)
    {
        Gate::authorize("viewAny", Product::class);

        $search = $request->search;
        $categorie_id = $request->product_categorie_id;
        $warehouse_id = $request->warehouse_id;
        $sucursale_id = $request->sucursale_id;
        $only_with_stock = $request->only_with_stock;

        $query = Product::with('product_categorie')
            ->filterAdvance(
                $search,
                $categorie_id,
                $warehouse_id,
                null,
                $sucursale_id,
                null,
                null
            );

        // Filtrar solo productos con stock
        if ($only_with_stock) {
            $query->whereHas('warehouses', function($q) {
                $q->where('stock', '>', 0);
            });
        }

        $products = $query->orderBy("id", "desc")->paginate(15);

        // Obtener stock de inventario para TODOS los productos (no solo los de la página actual)
        // Para el dashboard necesitamos el stock completo de todos los productos
        $inventoryStock = ProductWarehouse::select('product_id', 'warehouse_id', 'stock')
            ->whereHas('product') // Solo productos que existan
            ->get()
            ->map(function ($stock) {
                return [
                    'product_id' => $stock->product_id,
                    'warehouse_id' => $stock->warehouse_id,
                    'quantity' => (int) $stock->stock,
                ];
            })
            ->values()
            ->toArray();

        return response()->json([
            "total" => $products->total(),
            "total_page" => $products->lastPage(),
            "products" => ProductCollection::make($products),
            "inventory" => $inventoryStock,
        ]);
    }

    /**
     * Descargar inventario en Excel
     */
    public function download_excel(Request $request)
    {
        Gate::authorize("viewAny", Product::class);

        $search = $request->search;
        $categorie_id = $request->product_categorie_id;
        $warehouse_id = $request->warehouse_id;
        $sucursale_id = $request->sucursale_id;
        $only_with_stock = $request->only_with_stock;

        // Obtener productos filtrados
        $query = Product::filterAdvance(
                $search,
                $categorie_id,
                $warehouse_id,
                null,
                $sucursale_id,
                null,
                null
            );

        // Filtrar solo productos con stock
        if ($only_with_stock) {
            $query->whereHas('warehouses', function($q) {
                $q->where('stock', '>', 0);
            });
        }

        $products = $query->orderBy("id", "desc")->get();

        // Cargar la relación de categorías
        $products->load('product_categorie');

        // Obtener todos los almacenes
        $warehouses = Warehouse::orderBy('name')->get();

        // Obtener stock de inventario para los productos filtrados
        $productIds = $products->pluck('id')->toArray();
        $inventoryStock = ProductWarehouse::select('product_id', 'warehouse_id', 'stock')
            ->whereIn('product_id', $productIds)
            ->get()
            ->map(function ($stock) {
                return [
                    'product_id' => $stock->product_id,
                    'warehouse_id' => $stock->warehouse_id,
                    'quantity' => (int) $stock->stock,
                ];
            })
            ->toArray();

        return Excel::download(
            new InventoryDownloadExcel($products, $warehouses, $inventoryStock),
            "inventario_" . date('Y-m-d_His') . ".xlsx"
        );
    }
}
