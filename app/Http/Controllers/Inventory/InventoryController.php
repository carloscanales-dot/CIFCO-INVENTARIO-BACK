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

        $products = Product::with('product_categorie')
            ->filterAdvance(
                $search,
                $categorie_id,
                $warehouse_id,
                null,
                $sucursale_id,
                null,
                null
            )
            ->orderBy("id", "desc")
            ->paginate(15);

        // Obtener stock de inventario para todos los productos
        $productIds = $products->pluck('id')->toArray();
        $inventoryStock = ProductWarehouse::whereIn('product_id', $productIds)
            ->get()
            ->map(function ($stock) {
                return [
                    'product_id' => $stock->product_id,
                    'warehouse_id' => $stock->warehouse_id,
                    'quantity' => $stock->stock,
                ];
            });

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
        $search = $request->get("search");
        $categorie_id = $request->get("product_categorie_id");
        $warehouse_id = $request->get("warehouse_id");
        $sucursale_id = $request->get("sucursale_id");

        $products = Product::filterAdvance(
            $search,
            $categorie_id,
            $warehouse_id,
            null,
            $sucursale_id,
            null,
            null
        )
            ->orderBy("id", "desc")
            ->get();

        return Excel::download(new ProductDownloadExcel($products), "inventario.xlsx");
    }
}
