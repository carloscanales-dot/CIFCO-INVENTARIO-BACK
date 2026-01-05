<?php

namespace App\Http\Controllers\Product;

use App\Models\Config\Unit;
use Illuminate\Http\Request;
use App\Models\Product\Product;
use App\Imports\ImportExcelDemo;
use App\Models\Config\Sucursale;
use App\Models\Config\Warehouse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Product\ProductWallet;
use App\Models\Config\ProductCategorie;
use Illuminate\Support\Facades\Storage;
use App\Models\Product\ProductWarehouse;
use App\Imports\Product\ImportExcelProducts;
use App\Exports\Product\ProductDownloadExcel;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\Product\ProductCollection;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize("viewAny", Product::class);
        $search = $request->search;
        $categorie_id = $request->product_categorie_id;
        $warehouse_id = $request->warehouse_id;
        $unit_id = $request->unit_id;
        $sucursale_id = $request->sucursale_id;
        $disponibilidad = $request->disponibilidad;
        $is_gift = $request->is_gift;

        $products = Product::filterAdvance(
            $search,
            $categorie_id,
            $warehouse_id,
            $unit_id,
            $sucursale_id,
            $disponibilidad,
            $is_gift
        )
            ->orderBy("id", "desc")
            ->paginate(15);

        return response()->json([
            "total" => $products->total(),
            "total_page" => $products->lastPage(),
            "products" => ProductCollection::make($products),
        ]);
    }

    public function config()
    {
        $sucursales = Sucursale::where("state", 1)->get();
        $warehouses = Warehouse::where("state", 1)->get();
        $units = Unit::where("state", 1)->get();
        $categories = ProductCategorie::where("state", 1)->get();

        return response()->json([
            "sucursales" => $sucursales->map(function ($sucursale) {
                return [
                    "id" => $sucursale->id,
                    "name" => $sucursale->name
                ];
            }),
            "warehouses" => $warehouses->map(function ($warehouse) {
                return [
                    "id" => $warehouse->id,
                    "name" => $warehouse->name
                ];
            }),
            "units" => $units->map(function ($unit) {
                return [
                    "id" => $unit->id,
                    "name" => $unit->name,
                    "conversions" => $unit->conversions->map(function ($conversion) {
                        return [
        "id" => optional($conversion->unit_to)->id,
        "name" => optional($conversion->unit_to)->name,
                        ];
                    })
                ];
            }),
            "categories" => $categories->map(function ($categorie) {
                return [
                    "id" => $categorie->id,
                    "name" => $categorie->title,
                ];
            })
        ]);
    }

    public function download_excel(Request $request)
    {
        $search = $request->get("search");
        $categorie_id = $request->get("product_categorie_id");
        $warehouse_id = $request->get("warehouse_id");
        $unit_id = $request->get("unit_id");
        $sucursale_id = $request->get("sucursale_id");
        $disponibilidad = $request->get("disponibilidad");
        $is_gift = $request->get("is_gift");

        $products = Product::filterAdvance(
            $search,
            $categorie_id,
            $warehouse_id,
            $unit_id,
            $sucursale_id,
            $disponibilidad,
            $is_gift
        )
            ->orderBy("id", "desc")
            ->get();

        return Excel::download(new ProductDownloadExcel($products), "lista_products.xlsx");
    }

    public function import_excel(Request $request)
    {
        $request->validate([
            "excel" => 'required|file|mimes:xls,xlsx,csv'
        ]);
        $path = $request->file("excel");
        Excel::import(new ImportExcelProducts, $path);
        return response()->json([
            "message" => 200
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize("create", Product::class);
        $is_product_exits = Product::where("title", $request->title)->first();
        if ($is_product_exits) {
            return response()->json([
                "message" => 403,
                "message_text" => "EL NOMBRE DEL PRODUCTO YA EXISTE"
            ]);
        }

        $is_product_sku_exits = Product::where("sku", $request->sku)->first();
        if ($is_product_sku_exits) {
            return response()->json([
                "message" => 403,
                "message_text" => "EL SKU DEL PRODUCTO YA EXISTE"
            ]);
        }

        $product = Product::create($request->all());

        $product_warehouses = json_decode($request->product_warehouses, true);
        foreach ($product_warehouses as $product_warehouse) {
            ProductWarehouse::create([
                "product_id" => $product->id,
                "warehouse_id" => $product_warehouse["warehouse_id"],
                "unit_id" => $product_warehouse["unit_id"],
                "stock" => $product_warehouse["stock"],
                "umbral" => $product_warehouse["umbral"]
            ]);
        }

        $product_wallets = json_decode($request->product_wallets, true);
        foreach ($product_wallets as $product_wallet) {
            ProductWallet::create([
                "product_id" => $product->id,
                "type_client" => $product_wallet["type_client"],
                "sucursale_id" => $product_wallet["sucursale_id"],
                "unit_id" => $product_wallet["unit_id"],
                "price" => $product_wallet["price"]
            ]);
        }

        // 👇 Nuevo bloque para almacenamiento local
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $product->update([
                'imagen' => $path,
            ]);
        }

        return response()->json([
            "message" => 200,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        Gate::authorize("update", Product::class);
        $product = Product::findOrFail($id);

        return response()->json([
            "product" => ProductResource::make($product),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Gate::authorize("update", Product::class);

        $is_product_exits = Product::where("title", $request->title)->where("id", "<>", $id)->first();
        if ($is_product_exits) {
            return response()->json([
                "message" => 403,
                "message_text" => "EL NOMBRE DEL PRODUCTO YA EXISTE"
            ]);
        }

        $is_product_sku_exits = Product::where("sku", $request->sku)->where("id", "<>", $id)->first();
        if ($is_product_sku_exits) {
            return response()->json([
                "message" => 403,
                "message_text" => "EL SKU DEL PRODUCTO YA EXISTE"
            ]);
        }

        $product = Product::findOrFail($id);
        $product->update($request->all());

        // 👇 Versión local limpia
        if ($request->hasFile('image')) {
            if ($product->imagen) {
                Storage::disk('public')->delete($product->imagen);
            }

            $path = $request->file('image')->store('products', 'public');
            $product->update([
                'imagen' => $path,
            ]);
        }

        return response()->json([
            "message" => 200,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize("delete", Product::class);
        $product = Product::findOrFail($id);

        // 🗑️ Eliminar la imagen del disco local si existe
        if ($product->imagen) {
            Storage::disk('public')->delete($product->imagen);
        }

        $product->delete();

        return response()->json([
            "message" => 200,
        ]);
    }
}
