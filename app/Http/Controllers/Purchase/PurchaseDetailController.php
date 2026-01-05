<?php

namespace App\Http\Controllers\Purchase;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Purchase\Purchase;
use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseDetail;
use App\Models\Product\ProductWarehouse;

class PurchaseDetailController extends Controller
{

    public function attention_detail(Request $request) {
        $purchase_id = $request->purchase_id;
        $purchase_detail_id = $request->purchase_detail_id;

        $purchase = Purchase::findOrFail($purchase_id);
        $purchase_detail = PurchaseDetail::findOrFail($purchase_detail_id);
        date_default_timezone_set('America/Lima');
        $purchase_detail->update([
            "state" => 2,
            "user_entrega" => auth('api')->user()->id,
            "date_entrega" => now(),
        ]);

        $product_warehouse = ProductWarehouse::where("product_id",$purchase_detail->product_id)
                                                ->where("warehouse_id",$purchase->warehouse_id)
                                                ->where("unit_id",$purchase_detail->unit_id)
                                                ->first();
        if($product_warehouse){
            $product_warehouse->update([
                "stock" => $product_warehouse->stock + $purchase_detail->quantity,
            ]);                      
        }else{
            ProductWarehouse::create([
                "product_id" => $purchase_detail->product_id,
                "warehouse_id" => $purchase->warehouse_id,
                "unit_id" => $purchase_detail->unit_id,
                "stock" => $purchase_detail->quantity,
            ]);
        }

        $state = 1;
        $n_details = PurchaseDetail::where("purchase_id",$purchase_id)->count();
        $n_max_detail_attends = PurchaseDetail::where("purchase_id",$purchase_id)->where("state",2)->count();

        if($n_max_detail_attends == $n_details){
            $state = 3;
        }else{
            $state = 2;
        }

        $purchase->update([
            "state" => $state,
        ]);

        return response()->json([
            "purchase_detail" => [
                "id" => $purchase_detail->id,
                "product_id"  => $purchase_detail->product_id,
                "product" => [
                    "title" => $purchase_detail->product->title,
                    "sku" => $purchase_detail->product->sku,
                ],
                "unit_id"  => $purchase_detail->unit_id,
                "unit" => [
                    "name" => $purchase_detail->unit->name,
                ],
                "price_unit"  => $purchase_detail->price_unit,
                "total"  => $purchase_detail->total,
                "quantity"  => $purchase_detail->quantity,
                "state"  => $purchase_detail->state,
                "user_entrega"  => $purchase_detail->user_entrega ? [
                    "id" => $purchase_detail->user->id,
                    "full_name" => $purchase_detail->user->name.' '.$purchase_detail->user->surname,
                ]: NULL,
                "date_entrega"  => $purchase_detail->date_entrega ? Carbon::parse($purchase_detail->date_entrega)->format("Y-m-d") : null,
                "description" => $purchase_detail->description,
            ],
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // purchase_id
        // product
        // unit_id
        // unit
        // price_unit
        // quantity
        // total
        $product = $request->product;

        $purchase_detail = PurchaseDetail::create([
            "purchase_id" => $request->purchase_id,
            "product_id" => $product["id"],
            "unit_id" => $request->unit_id,
            "price_unit" => $request->price_unit,
            "total" => $request->total,
            "quantity" => $request->quantity,
        ]);

        $purchase = Purchase::findOrFail($request->purchase_id);
        $newImporte = round($purchase->importe + $purchase_detail->total,2);
        $newIgv = round($newImporte*0.18,2); 
        $newTotal = round($newImporte + $newIgv,2);

        $state = 1;
        if($purchase->state == 3){
            $state = 2;
        }
        if($purchase->state == 2){
            $state = 2;
        }
        if($purchase->state == 1){
            $state = 1;
        }
        $purchase->update([
            "importe" => $newImporte,
            "igv" => $newIgv,
            "total" => $newTotal,
            "state" => $state,
        ]);

        return response()->json([
            "message" => 200,
            "purchase_detail" => [
                "id" => $purchase_detail->id,
                "product_id"  => $purchase_detail->product_id,
                "product" => [
                    "title" => $purchase_detail->product->title,
                    "sku" => $purchase_detail->product->sku,
                ],
                "unit_id"  => $purchase_detail->unit_id,
                "unit" => [
                    "name" => $purchase_detail->unit->name,
                ],
                "price_unit"  => $purchase_detail->price_unit,
                "total"  => $purchase_detail->total,
                "quantity"  => $purchase_detail->quantity,
                "state"  => $purchase_detail->state,
                "user_entrega"  => $purchase_detail->user_entrega ? [
                    "id" => $purchase_detail->user->id,
                    "full_name" => $purchase_detail->user->name.' '.$purchase_detail->user->surname,
                ]: NULL,
                "date_entrega"  => $purchase_detail->date_entrega ? Carbon::parse($purchase_detail->date_entrega)->format("Y-m-d") : null,
                "description" => $purchase_detail->description,
            ],
            "total" => $newTotal,
            "importe" => $newImporte,
            "igv" => $newIgv,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // purchase_id
        //unit_id
        // price_unit
        // quantity
        // total
        // description
        $purchase_detail = PurchaseDetail::findOrFail($id);
        if($purchase_detail->state != 1){
            if($purchase_detail->quantity != $request->quantity){
                return response([
                    "message" => 403,
                    "message_text" => "NO PUEDE EDITAR LA CANTIDAD DEL DETALLADO PORQUE YA SE HA ENTREGADO EL PRODUCTO",
                ]);
            }
            if($purchase_detail->unit_id != $request->unit_id){
                return response([
                    "message" => 403,
                    "message_text" => "NO PUEDE EDITAR LA UNIDAD DEL DETALLADO PORQUE YA SE HA ENTREGADO EL PRODUCTO",
                ]);
            }
        }
        $oldTotalDetail = $purchase_detail->total;
        $purchase_detail->update([
            "unit_id" => $request->unit_id,
            "price_unit" => $request->price_unit,
            "quantity" => $request->quantity,
            "total" => $request->total,
            "description" => $request->description,
        ]);

        $purchase = Purchase::findOrFail($request->purchase_id);

        $newImporte = round(($purchase->importe - $oldTotalDetail) + $purchase_detail->total,2);
        $newIgv = round( $newImporte * 0.18,2);
        $newTotal = round($newImporte + $newIgv,2);

        $purchase->update([
            "importe" => $newImporte,
            "igv" => $newIgv,
            "total" => $newTotal
        ]);

        return response()->json([
            "message" => 200,
            "purchase_detail" => [
                "id" => $purchase_detail->id,
                "product_id"  => $purchase_detail->product_id,
                "product" => [
                    "title" => $purchase_detail->product->title,
                    "sku" => $purchase_detail->product->sku,
                ],
                "unit_id"  => $purchase_detail->unit_id,
                "unit" => [
                    "name" => $purchase_detail->unit->name,
                ],
                "price_unit"  => $purchase_detail->price_unit,
                "total"  => $purchase_detail->total,
                "quantity"  => $purchase_detail->quantity,
                "state"  => $purchase_detail->state,
                "user_entrega"  => $purchase_detail->user_entrega ? [
                    "id" => $purchase_detail->user->id,
                    "full_name" => $purchase_detail->user->name.' '.$purchase_detail->user->surname,
                ]: NULL,
                "date_entrega"  => $purchase_detail->date_entrega ? Carbon::parse($purchase_detail->date_entrega)->format("Y-m-d") : null,
                "description" => $purchase_detail->description,
            ],
            "total" => $newTotal,
            "importe" => $newImporte,
            "igv" => $newIgv,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $purchase_detail = PurchaseDetail::findOrFail($id);
        if($purchase_detail->state != 1){
            return response()->json([
                "message" => 403,
                "message_text" => "NO PUEDES ELIMINAR ESTE DETALLADO PORQUE YA HA SIDO ENTREGADO POR EL PROVEEDOR"
            ]);
        }
        $purchase_detail->delete();

        $purchase = $purchase_detail->purchase;

        $newImporte = round(($purchase->importe - $purchase_detail->total),2);
        $newIgv = round($newImporte * 0.18,2);
        $newTotal = round($newImporte + $newIgv,2);

        $purchase->update([
            "importe" => $newImporte,
            "igv" => $newIgv,
            "total" => $newTotal,
        ]);

        return response()->json([
            "message" => 200,
            "purchase_detail_id" => $id,
            "importe" => $newImporte,
            "igv" => $newIgv,
            "total" => $newTotal,
        ]);
    }
}
