<?php

namespace App\Http\Controllers\Sale;

use Carbon\Carbon;
use App\Models\Sale\Sale;
use Illuminate\Http\Request;
use App\Models\Sale\SaleDetail;
use Illuminate\Support\Facades\DB;
use App\Models\Sale\RefoundProduct;
use App\Http\Controllers\Controller;
use App\Models\Product\ProductWarehouse;
use App\Http\Resources\Sale\SaleResource;
use App\Http\Resources\Refound\RefoundProductResource;
use App\Http\Resources\Refound\RefoundProductCollection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SaleRefounProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search_product = $request->search_product;
        $warehouse_id = $request->warehouse_id;
        $unit_id = $request->unit_id;
        $type = $request->type;
        $state = $request->state;
        $sale_id = $request->sale_id;
        $search_client = $request->search_client;
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $user = auth('api')->user();
        $refound_products = RefoundProduct::filterAdvance($search_product,$warehouse_id,$unit_id,$type,$state,$sale_id,$search_client,$start_date,$end_date,$user)->orderBy("id","desc")->paginate(25);

        return response()->json([
            "total_page" => $refound_products->lastPage(),
            "refound_products" => RefoundProductCollection::make($refound_products),
        ]);
    }
    public function search_sale($sale_id){
        $sale = Sale::find($sale_id);
        if(!$sale){
            return response()->json([
                "message" => 403,
                "message_text" => "EL N° DE VENTA INGRESADO NO EXISTE",
            ]);
        }
        return response()->json([
            "sale" => SaleResource::make($sale),
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $sale_detail_id = $request->sale_detail_id;
        $type = $request->type;
        $quantity = $request->quantity;
        $description = $request->description;
        $sale_id = $request->sale_id;
        $sale_detail = SaleDetail::findOrFail($sale_detail_id);
        if($sale_detail->quantity < $quantity){
            return response()->json([
                "message" => 403,
                "message_text" => "LA CANTIDAD INGRESADA ES MUCHO MAYOR A LA CANTIDAD SOLICITADA DEL PRODUCTO"
            ]);
        }
        $quantity_attend = $sale_detail->quantity - $sale_detail->quantity_pending;
        if($quantity_attend < $quantity){
            return response()->json([
                "message" => 403,
                "message_text" => "LA CANTIDAD INGRESADA ES MUCHO MAYOR A LA CANTIDAD ATENDIDA",
            ]);
        }
        $date_limite = Carbon::parse($sale_detail->created_at)->addDays((int) $sale_detail->product->warranty_day);

        if(!now()->lte($date_limite)){
            return response()->json([
                "message" => 403,
                "message_text" => "LA FECHA LIMITE DE LA DEVOLUCIÓN DEL PRODUCTO YA PASO",
            ]);
        }
        try {
            DB::beginTransaction();
            // 1.- EL TIPO REPARACIÓN
                if($type == 1){
                    $sale_detail = SaleDetail::findOrFail($sale_detail_id);
                    $sale = Sale::findOrFail($sale_id);
                    $refound_product = RefoundProduct::create([
                        "product_id" => $sale_detail->product_id ,
                        "unit_id" => $sale_detail->unit_id,
                        "warehouse_id" => $sale_detail->warehouse_id,
                        "quantity" => $quantity,
                        "sale_detail_id" => $sale_detail_id,
                        "client_id" => $sale->client_id,
                        "type" => $type,
                        "state" => 1,
                        "description" => $description,
                        "user_id" => auth('api')->user()->id,
                    ]);
                }
            // 2. EL TIPO REMPLAZO
                if($type == 2){
                    // VALIDACION DE TIPO Y CANTIDAD
                    $REFOUND_PRODUCT_V = RefoundProduct::where("sale_detail_id",$sale_detail_id)->where("type",1)->first();
                    if(!$REFOUND_PRODUCT_V){
                        return response()->json([
                            "message" => 403,
                            "message_text" => "ES NECESARIO PRIMERO PASAR POR EL PROCESO DE REPARACIÓN Y EVALUACIÓN TECNICA",
                        ]);
                    }
                    // ATENCIÓN DE STOCK ADICIONAL
                    $product_warehouse = ProductWarehouse::where("product_id",$sale_detail->product_id)
                                                            ->where("unit_id",$sale_detail->unit_id)
                                                            ->where("warehouse_id",$sale_detail->warehouse_id)
                                                            ->first();
                    if($product_warehouse->stock < $quantity){
                        return response()->json([
                            "message" => 403,
                            "message_text" => "EL PRODUCTO NO CUENTA CON STOCK DISPONIBLE PARA ATENDER EL REMPLAZO"
                        ]);
                    }
                    $product_warehouse->update([
                        "stock" => $product_warehouse->stock - $quantity
                    ]);
                    // EL REGISTRO DE LA DEVOLUCIÓN
                    $sale = Sale::findOrFail($sale_id);
                    $refound_product = RefoundProduct::create([
                        "product_id" => $sale_detail->product_id ,
                        "unit_id" => $sale_detail->unit_id,
                        "warehouse_id" => $sale_detail->warehouse_id,
                        "quantity" => $quantity,
                        "sale_detail_id" => $sale_detail_id,
                        "client_id" => $sale->client_id,
                        "type" => $type,
                        "description" => $description,
                        "user_id" => auth('api')->user()->id,
                    ]);
                }
            // 3. EL TIPO DEVOLUCIÓN
                if($type == 3){
                    // DEVOLUCIÓN DEL STOCK
                    $product_warehouse = ProductWarehouse::where("product_id",$sale_detail->product_id)
                                                            ->where("unit_id",$sale_detail->unit_id)
                                                            ->where("warehouse_id",$sale_detail->warehouse_id)
                                                            ->first();
                    if($product_warehouse){
                        $product_warehouse->update([
                            "stock" => $product_warehouse->stock + $quantity,
                        ]);
                    }
                    // EDICIÓN DEL DETALLADO Y VALIDACIÓN
                    $sale = Sale::findOrFail($sale_id);
                    $SALE_DETAIL_TOTAL_NEW = $sale_detail->subtotal * ($sale_detail->quantity - $quantity);
                    $SALE_TOTAL_NEW = ($sale->total - $sale_detail->total) + $SALE_DETAIL_TOTAL_NEW;
                    if($sale->paid_out > $SALE_TOTAL_NEW){
                        return response()->json([
                            "message" => 403,
                            "message_text" => "NO PUEDES REGISTRAR ESTA DEVOLUCIÓN, POR EL TOTAL CANCELADO DE LA VENTA, DEBIDO A QUE SERIA MAYOR, INTENTE EDITAR LOS PAGOS DE ESA VENTA"
                        ]);
                    }
                    $quantity_sol = $sale_detail->quantity;
                    $quantity_pending = $sale_detail->quantity_pending;
                    $quantity_attend = $quantity_sol - $quantity_pending;
    
                    $quantity_sol_new = $quantity_sol - $quantity;
                    $quantity_attend_new =  $quantity_attend - $quantity;
                    $quantity_pending_new = $quantity_sol_new - $quantity_attend_new;
    
                    $IGV_TOTAL_OLD = $sale_detail->igv * $sale_detail->quantity;
                    $DISCOUNT_TOTAL_OLD = $sale_detail->discount * $sale_detail->quantity;
                    $SUBTOTAL_ORIGNAL_OLD = $sale_detail->price_unit * $sale_detail->quantity;

                    $sale_detail->update([
                        "quantity" => $quantity_sol_new,
                        "total" => $SALE_DETAIL_TOTAL_NEW,
                        "quantity_pending" => $quantity_pending_new ,
                        "state_attention" => $quantity_pending_new == 0 ? 3 : 2,
                        "subtotal" => $quantity_sol_new == 0 ? 0 : $sale_detail->subtotal,
                    ]);
    
                    $IGV_TOTAL_NEW = $sale_detail->igv * $sale_detail->quantity;
                    $DISCOUNT_TOTAL_NEW = $sale_detail->discount * $sale_detail->quantity;
                    $SUBTOTAL_ORIGINAL_NEW = $sale_detail->price_unit * $sale_detail->quantity;

                    $state_payment = 1;
                    $date_pay_complete = null;
                    date_default_timezone_set('America/Lima');
                    if($sale->paid_out == $SALE_TOTAL_NEW){
                        $state_payment = 3;
                        $date_pay_complete = now();
                    }else{
                        if($sale->paid_out > 0){
                            $state_payment = 2;
                        }
                    }
                    $sale->update([
                        "igv" => ($sale->igv - $IGV_TOTAL_OLD) + $IGV_TOTAL_NEW,
                        "discount" => ($sale->discount - $DISCOUNT_TOTAL_OLD) + $DISCOUNT_TOTAL_NEW,
                        "subtotal" => ($sale->subtotal - $SUBTOTAL_ORIGNAL_OLD) + $SUBTOTAL_ORIGINAL_NEW,
                        "total" => $SALE_TOTAL_NEW,
                        "debt" => $SALE_TOTAL_NEW - $sale->paid_out,
                        "state_payment" => $state_payment,
                        "date_pay_complete" => $date_pay_complete,
                    ]);
                    // REGISTRO DE DEVOLUCIÓN
                    $refound_product = RefoundProduct::create([
                        "product_id" => $sale_detail->product_id,
                        "unit_id" => $sale_detail->unit_id,
                        "warehouse_id" => $sale_detail->warehouse_id,
                        "quantity" => $quantity,
                        "sale_detail_id" => $sale_detail_id,
                        "client_id" => $sale->client_id,
                        "type" => $type,
                        "description" => $description,
                        "user_id" => auth('api')->user()->id,
                    ]);
                }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw new HttpException(500,$th->getMessage());
        }
        return response()->json([
            "message" => 200,
            "refound_product" => RefoundProductResource::make($refound_product),
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
        $quantity = $request->quantity;
        $description = $request->description;
        $state = $request->state;
        $description_resolution = $request->description_resolution;
        
        $refound_product = RefoundProduct::findOrFail($id);
        if($refound_product->type == 1){
            $resolution_date = NULL;
            if($refound_product->resolution_date){
                $resolution_date = $refound_product->resolution_date;
            }else{
                date_default_timezone_set('America/Lima');
                if($description_resolution){
                    $resolution_date = now();
                }
            }
            $refound_product->update([
                "description" => $description,
                "quantity" => $quantity,
                "state" => $state,
                "description_resolution" => $description_resolution,
                "resolution_date" => $resolution_date,
            ]);
        }
        if($refound_product->type == 2){
            $refound_product->update([
                "description" => $description,
            ]);
        }
        if($refound_product->type == 3){
            $refound_product->update([
                "description" => $description,
            ]);
        }
        
        return response()->json([
            "refound_product" => RefoundProductResource::make($refound_product),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $refound_product = RefoundProduct::findOrFail($id);
        $refound_product->delete();

        return response()->json([
            "message" => 200,
        ]);
    }
}
