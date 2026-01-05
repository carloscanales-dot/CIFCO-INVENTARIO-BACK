<?php

namespace App\Http\Controllers\Product;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product\ProductWarehouse;

class ProductWarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // CAMPOS QUE VIENE DESDE EL FRONTED
        $product_id = $request->product_id;
        $warehouse_id = $request->warehouse_id;
        $unit_id = $request->unit_id;
        $stock = $request->stock;
        $umbral = $request->umbral;

        // EL MODELO PARA CREAR EL NUEVO REGISTRO
        $product_warehouse = ProductWarehouse::create([
            "product_id" => $product_id,
            "warehouse_id" => $warehouse_id,
            "unit_id" => $unit_id,
            "stock" => $stock,
            "umbral" => $umbral,
            "state_stock" => 1,
        ]);

        return response()->json([
            "product_warehouse" => [
                "id" => $product_warehouse->id,
                "warehouse_id" => $product_warehouse->warehouse_id,
                "warehouse" => [
                    "name" => $product_warehouse->warehouse->name,
                ],
                "unit_id" => $product_warehouse->unit_id,
                "unit" => [
                    "name" => $product_warehouse->unit->name,
                ],
                "stock" => $product_warehouse->stock,
                "umbral" => $product_warehouse->umbral,
                "state_stock" => $product_warehouse->state_stock,
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product_id = $request->product_id;
        $warehouse_id = $request->warehouse_id;
        $unit_id = $request->unit_id;
        $stock = $request->stock;
        $umbral = $request->umbral;

        $exits_product_warehouse = ProductWarehouse::where("product_id",$product_id)->where("id","<>",$id)->where("warehouse_id",$warehouse_id)->where("unit_id",$unit_id)->first();
        if($exits_product_warehouse){
            return response()->json([
                "message" => 403,
                "message_text" => "La existencia que quiere editar ya existe",
            ]);
        }

        $product_warehouse = ProductWarehouse::findOrFail($id);
        $product_warehouse->update([
            "warehouse_id" => $warehouse_id,
            "unit_id" => $unit_id,
            "stock" => $stock,
            "umbral" => $umbral,
        ]);

        return response()->json([
            "product_warehouse" => [
                "id" => $product_warehouse->id,
                "warehouse_id" => $product_warehouse->warehouse_id,
                "warehouse" => [
                    "name" => $product_warehouse->warehouse->name,
                ],
                "unit_id" => $product_warehouse->unit_id,
                "unit" => [
                    "name" => $product_warehouse->unit->name,
                ],
                "stock" => $product_warehouse->stock,
                "umbral" => $product_warehouse->umbral,
                "state_stock" => $product_warehouse->state_stock,
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product_warehouse = ProductWarehouse::findOrFail($id);
        $product_warehouse->delete();

        return response()->json(["message" => 200]);
    }
}
