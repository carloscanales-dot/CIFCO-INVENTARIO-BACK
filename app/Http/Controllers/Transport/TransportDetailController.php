<?php

namespace App\Http\Controllers\Transport;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Transport\Transport;
use App\Http\Controllers\Controller;
use App\Models\Product\ProductWarehouse;
use App\Models\Transport\TransportDetail;

class TransportDetailController extends Controller
{
    public function attention_exit(Request $request) {
        $transport_detail_id = $request->transport_detail_id;
        $transport_detail = TransportDetail::findOrFail($transport_detail_id);
        $transport = $transport_detail->transport;

        if($transport_detail->state != 1){
            return response()->json([
                "message" => 403,
                "message_text" => "NO SE PUEDE DAR SALIDA A ESTE PRODUCTO PORQUE YA SE ATENDIO"
            ]);
        }

        $product_warehouse = ProductWarehouse::where("product_id",$transport_detail->product_id)
                                                ->where("unit_id",$transport_detail->unit_id)
                                                ->where("warehouse_id",$transport->warehouse_start_id)
                                                ->first();
        if($product_warehouse->stock < $transport_detail->quantity){
            return response()->json([
                "message" => 403,
                "message_text" => "NO SE PUEDE ATENDER LA CANTIDAD SOLICITADA, PORQUE UNICAMENTE CONTAMOS CON ".$product_warehouse->stock
            ]);
        }
        $product_warehouse->update([
            "stock" => $product_warehouse->stock - $transport_detail->quantity,
        ]);
        date_default_timezone_set('America/Lima');
        $transport_detail->update([
            "state" => 2,
            "user_salida" => auth('api')->user()->id,
            "date_salida" => now(),
        ]);

        return response()->json([
            "message" => 200,
            "transport_detail" => [
                "id" => $transport_detail->id,
                "product_id"  => $transport_detail->product_id,
                "product" => [
                    "title" => $transport_detail->product->title,
                    "sku" => $transport_detail->product->sku,
                ],
                "unit_id"  => $transport_detail->unit_id,
                "unit" => [
                    "name" => $transport_detail->unit->name,
                ],
                "price_unit"  => $transport_detail->price_unit,
                "total"  => $transport_detail->total,
                "quantity"  => $transport_detail->quantity,
                "state"  => $transport_detail->state,
                "user_entrega"  => $transport_detail->user_entrega ? [
                    "id" => $transport_detail->user_in->id,
                    "full_name" => $transport_detail->user_in->name.' '.$transport_detail->user_in->surname,
                ]: NULL,
                "date_entrega"  => $transport_detail->date_entrega ? Carbon::parse($transport_detail->date_entrega)->format("Y-m-d") : null,
                "user_salida"  => $transport_detail->user_salida ? [
                    "id" => $transport_detail->user_out->id,
                    "full_name" => $transport_detail->user_out->name.' '.$transport_detail->user_out->surname,
                ]: NULL,
                "date_salida"  => $transport_detail->date_salida ? Carbon::parse($transport_detail->date_salida)->format("Y-m-d") : null,
                "description" => $transport_detail->description,
            ],
        ]);
    }

    public function attention_in(Request $request) {
        $transport_detail_id = $request->transport_detail_id;
        $transport_detail = TransportDetail::findOrFail($transport_detail_id);
        $transport = $transport_detail->transport;

        if($transport_detail->state == 3){
            return response()->json([
                "message" => 403,
                "message_text" => "NO SE PUEDE DAR SALIDA A ESTE PRODUCTO PORQUE YA SE ATENDIO"
            ]);
        }

        $product_warehouse = ProductWarehouse::where("product_id",$transport_detail->product_id)
                                                ->where("unit_id",$transport_detail->unit_id)
                                                ->where("warehouse_id",$transport->warehouse_end_id)
                                                ->first();

        if(!$product_warehouse){
            ProductWarehouse::create([
                "product_id" => $transport_detail->product_id,
                "warehouse_id" => $transport->warehouse_end_id,
                "unit_id" => $transport_detail->unit_id,
                "stock" => $transport_detail->quantity,
            ]);
        }else{
            $product_warehouse->update([
                "stock" => $product_warehouse->stock + $transport_detail->quantity,
            ]);
        }
        date_default_timezone_set("America/Lima");
        $transport_detail->update([
            "state" => 3,
            "user_entrega" => auth('api')->user()->id,
            "date_entrega" => now(),
        ]);
        return response()->json([
            "message" => 200,
            "transport_detail" => [
                "id" => $transport_detail->id,
                "product_id"  => $transport_detail->product_id,
                "product" => [
                    "title" => $transport_detail->product->title,
                    "sku" => $transport_detail->product->sku,
                ],
                "unit_id"  => $transport_detail->unit_id,
                "unit" => [
                    "name" => $transport_detail->unit->name,
                ],
                "price_unit"  => $transport_detail->price_unit,
                "total"  => $transport_detail->total,
                "quantity"  => $transport_detail->quantity,
                "state"  => $transport_detail->state,
                "user_entrega"  => $transport_detail->user_entrega ? [
                    "id" => $transport_detail->user_in->id,
                    "full_name" => $transport_detail->user_in->name.' '.$transport_detail->user_in->surname,
                ]: NULL,
                "date_entrega"  => $transport_detail->date_entrega ? Carbon::parse($transport_detail->date_entrega)->format("Y-m-d") : null,
                "user_salida"  => $transport_detail->user_salida ? [
                    "id" => $transport_detail->user_out->id,
                    "full_name" => $transport_detail->user_out->name.' '.$transport_detail->user_out->surname,
                ]: NULL,
                "date_salida"  => $transport_detail->date_salida ? Carbon::parse($transport_detail->date_salida)->format("Y-m-d") : null,
                "description" => $transport_detail->description,
            ],
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // transport_id
        // product
        // unit_id
        // unit
        // price_unit
        // quantity
        // total
        $product = $request->product;
        $transport = Transport::findOrFail($request->transport_id);
        if($transport->state >= 3){
            return response()->json([
                "message" => 403,
                "message_text" => "NO PUEDES AGREGAR MAS PRODUCTOS PORQUE YA SE DIO SALIDA A LA SOLICITUD"
            ]);
        }
        $transport_detail = TransportDetail::create([
            "transport_id" => $request->transport_id,
            "product_id" => $product["id"],
            "unit_id" => $request->unit_id,
            "price_unit" => $request->price_unit,
            "total" => $request->total,
            "quantity" => $request->quantity,
            "state" => 1,
        ]);

        $newImporte = round($transport->importe + $transport_detail->total,2);
        $newIgv = round($newImporte*0.18,2); 
        $newTotal = round($newImporte + $newIgv,2);
        
        $transport->update([
            "importe" => $newImporte,
            "igv" => $newIgv,
            "total" => $newTotal,
        ]);

        return response()->json([
            "message" => 200,
            "transport_detail" => [
                "id" => $transport_detail->id,
                "product_id"  => $transport_detail->product_id,
                "product" => [
                    "title" => $transport_detail->product->title,
                    "sku" => $transport_detail->product->sku,
                ],
                "unit_id"  => $transport_detail->unit_id,
                "unit" => [
                    "name" => $transport_detail->unit->name,
                ],
                "price_unit"  => $transport_detail->price_unit,
                "total"  => $transport_detail->total,
                "quantity"  => $transport_detail->quantity,
                "state"  => $transport_detail->state,
                "user_entrega"  => $transport_detail->user_entrega ? [
                    "id" => $transport_detail->user_in->id,
                    "full_name" => $transport_detail->user_in->name.' '.$transport_detail->user_in->surname,
                ]: NULL,
                "date_entrega"  => $transport_detail->date_entrega ? Carbon::parse($transport_detail->date_entrega)->format("Y-m-d") : null,
                "user_salida"  => $transport_detail->user_salida ? [
                    "id" => $transport_detail->user_out->id,
                    "full_name" => $transport_detail->user_out->name.' '.$transport_detail->user_out->surname,
                ]: NULL,
                "date_salida"  => $transport_detail->date_salida ? Carbon::parse($transport_detail->date_salida)->format("Y-m-d") : null,
                "description" => $transport_detail->description,
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
        // transport_id
        //unit_id
        // price_unit
        // quantity
        // total
        // description
        $transport_detail = TransportDetail::findOrFail($id);
        if($transport_detail->state != 1){
            if($transport_detail->quantity != $request->quantity){
                return response([
                    "message" => 403,
                    "message_text" => "NO PUEDE EDITAR LA CANTIDAD DEL DETALLADO PORQUE YA SE HA ENTREGADO EL PRODUCTO",
                ]);
            }
            if($transport_detail->unit_id != $request->unit_id){
                return response([
                    "message" => 403,
                    "message_text" => "NO PUEDE EDITAR LA UNIDAD DEL DETALLADO PORQUE YA SE HA ENTREGADO EL PRODUCTO",
                ]);
            }
        }
        $oldTotalDetail = $transport_detail->total;
        $transport_detail->update([
            "unit_id" => $request->unit_id,
            "price_unit" => $request->price_unit,
            "quantity" => $request->quantity,
            "total" => $request->total,
            "description" => $request->description,
        ]);

        $transport = Transport::findOrFail($request->transport_id);

        $newImporte = round(($transport->importe - $oldTotalDetail) + $transport_detail->total,2);
        $newIgv = round( $newImporte * 0.18,2);
        $newTotal = round($newImporte + $newIgv,2);

        $transport->update([
            "importe" => $newImporte,
            "igv" => $newIgv,
            "total" => $newTotal
        ]);

        return response()->json([
            "message" => 200,
            "transport_detail" => [
                "id" => $transport_detail->id,
                "product_id"  => $transport_detail->product_id,
                "product" => [
                    "title" => $transport_detail->product->title,
                    "sku" => $transport_detail->product->sku,
                ],
                "unit_id"  => $transport_detail->unit_id,
                "unit" => [
                    "name" => $transport_detail->unit->name,
                ],
                "price_unit"  => $transport_detail->price_unit,
                "total"  => $transport_detail->total,
                "quantity"  => $transport_detail->quantity,
                "state"  => $transport_detail->state,
                "user_entrega"  => $transport_detail->user_entrega ? [
                    "id" => $transport_detail->user_in->id,
                    "full_name" => $transport_detail->user_in->name.' '.$transport_detail->user_in->surname,
                ]: NULL,
                "date_entrega"  => $transport_detail->date_entrega ? Carbon::parse($transport_detail->date_entrega)->format("Y-m-d") : null,
                "user_salida"  => $transport_detail->user_salida ? [
                    "id" => $transport_detail->user_out->id,
                    "full_name" => $transport_detail->user_out->name.' '.$transport_detail->user_out->surname,
                ]: NULL,
                "date_salida"  => $transport_detail->date_salida ? Carbon::parse($transport_detail->date_salida)->format("Y-m-d") : null,
                "description" => $transport_detail->description,
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
        $transport_detail = TransportDetail::findOrFail($id);
        if($transport_detail->state != 1){
            return response()->json([
                "message" => 403,
                "message_text" => "NO PUEDES ELIMINAR ESTE DETALLADO PORQUE YA HA SIDO ENTREGADO POR EL ALMACEN DE ATENCIÓN"
            ]);
        }
        $transport_detail->delete();

        $transport = $transport_detail->transport;

        $newImporte = round(($transport->importe - $transport_detail->total),2);
        $newIgv = round($newImporte * 0.18,2);
        $newTotal = round($newImporte + $newIgv,2);

        $transport->update([
            "importe" => $newImporte,
            "igv" => $newIgv,
            "total" => $newTotal,
        ]);

        return response()->json([
            "message" => 200,
            "transport_detail_id" => $id,
            "importe" => $newImporte,
            "igv" => $newIgv,
            "total" => $newTotal,
        ]);
    }
}
