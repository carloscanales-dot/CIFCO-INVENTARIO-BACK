<?php

namespace App\Http\Controllers\Sale;

use App\Models\Sale\Sale;
use Illuminate\Http\Request;
use App\Models\Sale\SaleDetail;
use App\Http\Controllers\Controller;
use App\Http\Resources\Sale\SaleDetailResource;

class SaleDetailController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // sale_id
        // product
        // unit_id
        // unit
        // warehouse_id
        // price
        // quantity
        // discount
        // is_gift
        // igv
        // subtotal
        // total
        $product = $request->product;
        $sale_detail = SaleDetail::create([
            "sale_id" => $request->sale_id,
            "product_id" => $product["id"],
            "product_categorie_id" => $product["product_categorie_id"],
            "unit_id" => $request->unit_id,
            "warehouse_id" => $request->warehouse_id,
            "quantity" => $request->quantity,
            "price_unit" => $request->price,
            "discount" => $request->discount,
            "subtotal" => $request->subtotal,
            "igv" => $request->igv,
            "total" => $request->total,
            "quantity_pending" => $request->quantity,
        ]);

        $sale = Sale::findOrFail($request->sale_id);

        $discount =$sale->discount + ($sale_detail->discount * $sale_detail->quantity);
        $igv =  $sale->igv + ($sale_detail->igv * $sale_detail->quantity);
        $subtotal =$sale->subtotal + ($sale_detail->price_unit * $sale_detail->quantity);
        $total = $sale->total + $sale_detail->total;

        $debt = $sale->debt + $sale_detail->total;

        date_default_timezone_set('America/Lima');
        $state_payment = 1;
        $date_pay_complete = null;
        if($debt == 0){
            $state_payment = 3;
            $date_pay_complete = now();
        }else if($sale->paid_out > 0){
            $state_payment = 2;
            $date_pay_complete = null;
        }
        $state_entrega = 1;
        if($sale->state_sale == 1){
            $state_entrega = 2;
        }
        $sale->update([
            "discount" => $discount,
            "igv" => $igv,
            "subtotal" => $subtotal,
            "total" => $total ,
            "debt" => $debt,
            "state_payment" => $state_payment,
            "date_pay_complete" => $date_pay_complete,
            "state_entrega" => $state_entrega,
        ]);

        return response()->json([
            "detail" => SaleDetailResource::make($sale_detail),
            "discount" => round($discount,2),
            "igv" => round($igv,2),
            "subtotal" => round($subtotal,2),
            "total" => round($total,2),
            "debt" => round($debt,2),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //unit_id
        // price_unit
        //quantity
        // discount
        //igv
        // sale_id
        // description
        $sale_detail = SaleDetail::findOrFail($id);
        $sale = $sale_detail->sale;
        
        $paid_out = (float) $sale->paid_out;

        $discount_old = $sale_detail->discount * $sale_detail->quantity;
        $igv_old = $sale_detail->igv * $sale_detail->quantity;
        $subtotal_old = $sale_detail->subtotal;
        $total_old = $sale_detail->total;

        $subtotal_detail = ((float)$request->price_unit - (float)$request->discount + (float)$request->igv);
        $total_detail = $subtotal_detail * (int)$request->quantity;
        if((float)$request->price_unit < (float)$request->discount){
            return response()->json([
                "message" => 403,
                "message_text" => "NO PUEDES INGRESAR UN PRECIO QUE SEA MENOR AL DESCUENTO",
            ]);
        }
        if( (((float)$sale->total - (float)$sale_detail->total) + (float)$total_detail) <  $paid_out){
            return response()->json([
                "message" => 403,
                "message_text" => "NO PUEDES EDITAR ESTE DETALLE PORQUE EL MONTO SERA MENOR DE LO CANCELADO",
            ]);
        }
        $quantity_attend = (int)$sale_detail->quantity - (int)$sale_detail->quantity_pending;
        if((int)$quantity_attend > (int)$request->quantity){
            return response()->json([
                "message" => 403,
                "message_text" => "NO PUEDES INGRESAR UNA CANTIDAD MENOR A LA ENTREGADA",
            ]);
        }
        $state_attention = 1;
        if((int)$request->quantity == (int)$quantity_attend){
            $state_attention = 3;
        }else if((int)$quantity_attend > 0){
            $state_attention = 2;
        }
        $sale_detail->update([
            "unit_id" => $request->unit_id,
            "price_unit" => $request->price_unit,
            "quantity" => $request->quantity,
            "discount" => $request->discount,
            "igv" => $request->igv,
            "subtotal" => $subtotal_detail,
            "total" => $total_detail,
            "description" => $request->description,
            
            "state_attention" => $state_attention,
            "quantity_pending" => $request->quantity - $quantity_attend,
        ]);
        date_default_timezone_set('America/Lima');
        $state_payment = 1;
        $date_pay_complete = null;
        if(
            (((float)$sale->total - (float)$total_old) + (float)$sale_detail->total) == $paid_out
        ){
            $state_payment = 3;
            $date_pay_complete = now();
        }else if((float) $paid_out > 0){
            $state_payment = 2;
            $date_pay_complete = null;
        }
        $state_entrega = 1;
        $sale_detail_attention_count = SaleDetail::where("sale_id",$sale->id)->where("state_attention",3)->count();
        if($sale->sale_details->count() == $sale_detail_attention_count){
            $state_entrega = 3;
        }else if($sale_detail_attention_count > 0){
            $state_entrega = 2;
        }

        $sale->update([
            "discount" => ($sale->discount - $discount_old) + ($sale_detail->discount * $sale_detail->quantity),
            "igv" => ($sale->igv - $igv_old) + ($sale_detail->igv * $sale_detail->quantity),
            "subtotal" => ($sale->subtotal - $subtotal_old) + $sale_detail->subtotal,
            "total" => ($sale->total - $total_old) + $sale_detail->total,
            "debt" =>  ($sale->debt - $total_old) + $sale_detail->total,
            "state_payment" => $state_payment,
            "date_pay_complete" => $date_pay_complete,
            "state_entrega" => $state_entrega,
        ]);

        return response()->json([
            "detail" => SaleDetailResource::make($sale_detail),
            "discount" => round($sale->discount,2),
            "igv" => round($sale->igv,2),
            "subtotal" => round($sale->subtotal,2),
            "total" => round($sale->total,2),
            "debt" => round($sale->debt,2),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $sale_detail = SaleDetail::findOrFail($id);
        $sale = $sale_detail->sale;
        
        if($sale_detail->state_attention != 1){
            return response()->json([
                "message" => 403,
                "message_text" => "NO PUEDES ELIMINAR UN DETALLADO QUE YA TENGA UNA ENTREGA PARCIAL O COMPLETA"
            ]);
        }

        $sale_detail->delete();

        date_default_timezone_set('America/Lima');
        $paid_out = (float) $sale->paid_out;
        $state_payment = 1;
        $date_pay_complete = null;
        if(
            ($sale->total - $sale_detail->total) == $paid_out
        ){
            $state_payment = 3;
            $date_pay_complete = now();
        }else if($paid_out > 0){
            $state_payment = 2;
            $date_pay_complete = null;
        }
        $state_entrega = 1;
        $sale_detail_attention_count = SaleDetail::where("sale_id",$sale->id)->where("state_attention",3)->count();
        $sale_detail_count = SaleDetail::where("sale_id",$sale->id)->count();
        if($sale_detail_count == $sale_detail_attention_count){
            $state_entrega = 3;
        }else if($sale_detail_attention_count > 0){
            $state_entrega = 2;
        }

        $sale->update([
            "discount" => $sale->discount - ($sale_detail->dicount * $sale_detail->quantity),
            "igv" => $sale->igv - ($sale_detail->igv * $sale_detail->quantity),
            "subtotal" => $sale->subtotal - $sale_detail->subtotal,
            "total" => $sale->total - $sale_detail->total,
            "debt" =>  $sale->debt - $sale_detail->total,
            "state_payment" => $state_payment,
            "date_pay_complete" => $date_pay_complete,
            "state_entrega" => $state_entrega,
        ]);

        return response()->json([
            "message" => 200,
            "sale_detail_id" => $id,
            "discount" => round($sale->discount,2),
            "igv" => round($sale->igv,2),
            "subtotal" => round($sale->subtotal,2),
            "total" => round($sale->total,2),
            "debt" => round($sale->debt,2),
        ]);
    }
}
