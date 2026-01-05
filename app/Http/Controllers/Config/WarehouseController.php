<?php

namespace App\Http\Controllers\Config;

use Illuminate\Http\Request;
use App\Models\Config\Sucursale;
use App\Models\Config\Warehouse;
use App\Http\Controllers\Controller;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get("search");
        //
        $warehouses = Warehouse::where("name","ilike","%".$search."%")->orderBy("id","desc")->get();

        $sucursales = Sucursale::where("state",1)->get();
        return response()->json([
            "warehouses" => $warehouses->map(function($warehouse) {
                return [
                    "id" => $warehouse->id,
                    "name" => $warehouse->name,
                    "address" => $warehouse->address,
                    "sucursale_id" => $warehouse->sucursale_id,
                    "sucursal" => [
                        "id" => $warehouse->sucursal->id,
                        "name" => $warehouse->sucursal->name,
                    ],
                    "state" => $warehouse->state,
                    "created_at" => $warehouse->created_at->format("Y/m/d h:i:s"),
                ];
            }),
            "sucursales" => $sucursales->map(function($sucursal) {
                return [
                    "id" => $sucursal->id,
                    "name" => $sucursal->name,
                ];
            })
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $exist_warehouse = Warehouse::where("name",$request->name)->first();

        if($exist_warehouse){
            return response()->json([
                "message" => 403,
                "message_text" => "EL NOMBRE DEL ALMACEN YA EXISTE, INTENTE UNO NUEVO"
            ]);
        }
        // $request->all() -> name, address y state
        $warehouse = Warehouse::create($request->all());
        
        return response()->json([
            "message" => 200,
            "warehouse" => [
                "id" => $warehouse->id,
                "name" => $warehouse->name,
                "address" => $warehouse->address,
                "state" => $warehouse->state,
                "sucursale_id" => $warehouse->sucursale_id,
                "sucursal" => [
                    "id" => $warehouse->sucursal->id,
                    "name" => $warehouse->sucursal->name,
                ],
                "created_at" => $warehouse->created_at->format("Y/m/d h:i:s"),
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
        //
        $exist_warehouse = Warehouse::where("name",$request->name)->where("id","<>",$id)->first();

        if($exist_warehouse){
            return response()->json([
                "message" => 403,
                "message_text" => "EL NOMBRE DEL ALMACEN YA EXISTE, INTENTE UNO NUEVO"
            ]);
        }
        // $request->all() -> name, address y state
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->update($request->all());

        return response()->json([
            "message" => 200,
            "warehouse" => [
                "id" => $warehouse->id,
                "name" => $warehouse->name,
                "address" => $warehouse->address,
                "state" => $warehouse->state,
                "sucursale_id" => $warehouse->sucursale_id,
                "sucursal" => [
                    "id" => $warehouse->sucursal->id,
                    "name" => $warehouse->sucursal->name,
                ],
                "created_at" => $warehouse->created_at->format("Y/m/d h:i:s"),
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->delete();

        return response()->json([
            "message" => 200,
        ]);
    }
}
