<?php

namespace App\Http\Controllers\Product;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product\ProductWallet;

class ProductWalletController extends Controller
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
        $product_id = $request->product_id;
        $type_client = $request->type_client;
        $unit_id = $request->unit_id;
        $sucursale_id = $request->sucursale_id;
        $price = $request->price;

        $product_wallet = ProductWallet::create([
            "product_id" => $product_id,
            "type_client" => $type_client,
            "sucursale_id" => $sucursale_id,
            "unit_id" => $unit_id,
            "price" => $price,
        ]);

        return response()->json([
            "product_wallet" => [
                "id" => $product_wallet->id,
                "type_client" => $product_wallet->type_client,
                "type_client_name" => $product_wallet->type_client == 1 ? 'Cliente Final' : 'Cliente Empresa',
                "sucursale_id" => $product_wallet->sucursale_id,
                "sucursale" => $product_wallet->sucursale ? [
                    "name" => $product_wallet->sucursale->name,
                ] : NULL,
                "unit_id" => $product_wallet->unit_id,
                "unit" => [
                    "name" => $product_wallet->unit->name,
                ],
                "price" => $product_wallet->price,
            ]
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
        $product_id = $request->product_id;
        $sucursale_id = $request->sucursale_id;
        $unit_id = $request->unit_id;
        $price = $request->price;
        $type_client = $request->type_client;

        $exits_product_wallet = ProductWallet::where("product_id",$product_id)->where("sucursale_id",$sucursale_id)
                                        ->where("unit_id",$unit_id)
                                        ->where("type_client",$type_client)
                                    ->where("id","<>",$id)->first();
        if($exits_product_wallet){
            return response()->json([
                "message" => 403,
                "message_text" => "EL precio que intentas editar ya existe"
            ]);
        }

        $product_wallet = ProductWallet::findOrFail($id);
        $product_wallet->update([
            "type_client" => $type_client,
            "sucursale_id" => $sucursale_id,
            "unit_id" => $unit_id,
            "price" => $price
        ]);

        return response()->json([
            "product_wallet" => [
                "id" => $product_wallet->id,
                "type_client" => $product_wallet->type_client,
                "type_client_name" => $product_wallet->type_client == 1 ? 'Cliente Final' : 'Cliente Empresa',
                "sucursale_id" => $product_wallet->sucursale_id,
                "sucursale" => $product_wallet->sucursale ? [
                    "name" => $product_wallet->sucursale->name,
                ] : NULL,
                "unit_id" => $product_wallet->unit_id,
                "unit" => [
                    "name" => $product_wallet->unit->name,
                ],
                "price" => $product_wallet->price,
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product_wallet = ProductWallet::findOrFail($id);
        $product_wallet->delete();

        return response()->json([
            "message" => 200,
        ]);
    }
}
