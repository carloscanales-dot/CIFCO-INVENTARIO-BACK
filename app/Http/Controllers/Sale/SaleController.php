<?php

namespace App\Http\Controllers\Sale;

use App\Models\Sale\Sale;
use Illuminate\Http\Request;
use App\Models\Client\Client;
use App\Models\Product\Product;
use App\Models\Sale\SaleDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Config\Warehouse;
use App\Models\Sale\SalePayment;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Sale\SaleDownloadExcel;
use App\Models\Product\ProductWarehouse;
use App\Models\Sale\SaleDetailAttention;
use App\Http\Resources\Sale\SaleResource;
use App\Http\Resources\Sale\SaleCollection;
use App\Http\Resources\Sale\SaleDetailResource;
use App\Http\Resources\Product\ProductCollection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize("viewAny",Sale::class);
        $search = $request->search;
        $type_client = $request->type_client;
        $search_client = $request->search_client;
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $type = $request->type;
        $state_entrega = $request->state_entrega;
        $state_payment = $request->state_payment;
        $search_product = $request->search_product;
        $user = auth('api')->user();
        $sales = Sale::filterAdvance($search,$type_client,$search_client,$start_date,$end_date,$type,$state_entrega,$state_payment,$search_product,$user)
                    ->orderBy("id","desc")
                    ->paginate(25);
        return response()->json([
            "total_page" => $sales->lastPage(),
            "sales" => SaleCollection::make($sales),
        ]);
    }

    public function config() 
    {
        $today = now()->format("Y/m/d");
        $warehouses = Warehouse::where("state",1)->orderBy("id","desc")->get();
        return response()->json([
            "today"=> $today,
            "warehouses" => $warehouses->map(function($warehouse) {
                return [
                    "id" => $warehouse->id,
                    "name" => $warehouse->name,
                    "sucursale_id" => $warehouse->sucursale_id,
                ];
            })
        ]);
    }

    public function search_client(Request $request) 
    {
        $search = $request->get("search");

        if(!$search){
            return response()->json([
                "clients" => []
            ]);
        }

        $clients = Client::where(DB::raw("clients.full_name || '' || clients.n_document || '' || clients.phone || '' || COALESCE(clients.email,'')"),"ilike","%".$search."%")
                    ->orderBy("id","desc")
                    ->where("state",1)
                    ->get();

        return response()->json([
            "clients" => $clients->map(function($client) {
                return [
                    "id" => $client->id,
                    "full_name" => $client->full_name,
                    "type_document" => $client->type_document,
                    "n_document" => $client->n_document,
                    "type_client" => $client->type_client,
                    "phone" => $client->phone,
                ];
            })
        ]);
    }

    public function search_product(Request $request){
        $search = $request->get("search");

        if(!$search){
            return response()->json([
                "products" => []
            ]);
        }

        $products = Product::where(DB::raw("products.title || '' || products.sku"),"ilike","%".$search."%")
                    ->orderBy("id","desc")
                    ->where("state",1)
                    ->get();

        return response()->json([
            "products" => ProductCollection::make($products),
        ]);
    }

    public function download_excel(Request $request) {
        $search = $request->search;
        $type_client = $request->type_client;
        $search_client = $request->search_client;
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $type = $request->type;
        $state_entrega = $request->state_entrega;
        $state_payment = $request->state_payment;
        $search_product = $request->search_product;

        $sales = Sale::filterAdvance($search,$type_client,$search_client,$start_date,$end_date,$type,$state_entrega,$state_payment,$search_product)
                    ->orderBy("id","desc")
                    ->get();
        
        return Excel::download(new SaleDownloadExcel($sales),"reporte_ventas_cotizacion.xlsx");
    }

    public function sale_pdf($id){
        $sale = Sale::findOrFail($id);
        $pdf = Pdf::loadView('sale.pdf_sale', compact('sale'));
        return $pdf->stream('sale_'.$id.'.pdf');
    }

    public function stock_attention_detail(Request $request){
        $sale_detail_id = $request->sale_detail_id;
        $SALE_DETAIL = SaleDetail::findOrFail($sale_detail_id);
         // LA DISTRUBICIÓN DE STOCK
        $warehouse_product = ProductWarehouse::where("product_id",$SALE_DETAIL->product_id)
                                                ->where("warehouse_id",$SALE_DETAIL->warehouse_id)
                                                ->where("unit_id",$SALE_DETAIL->unit_id)
                                                ->first();
        $state_attention = $SALE_DETAIL->state_attention;
        $quantity_attention = 0;
        $quantity_pending = $SALE_DETAIL->quantity_pending;
        if($warehouse_product && $warehouse_product->stock >= $SALE_DETAIL->quantity_pending){//ENTREGA COMPLETE
            $state_attention = 3;
            $quantity_attention = $SALE_DETAIL->quantity_pending;
            $quantity_pending = 0;
            $warehouse_product->update([
                "stock" => $warehouse_product->stock - $SALE_DETAIL->quantity_pending,
            ]);
        }else{
            if($warehouse_product && $warehouse_product->stock > 0 && $warehouse_product->stock < $SALE_DETAIL->quantity_pending){//ENTREGA PARCIAL
                $state_attention = 2;
                $quantity_attention = $warehouse_product->stock;
                $quantity_pending = $SALE_DETAIL->quantity_pending - $warehouse_product->stock;
                $warehouse_product->update([
                    "stock" => 0,
                ]);
            }else{
                // if($warehouse_product && $warehouse_product->stock == 0){
                //     $state_attention = 1;
                //     $quantity_pending = $SALE_DETAIL->quantity_pending;
                // }
                // if(!$warehouse_product){
                //     $state_attention = 1;
                //     $quantity_pending = $SALE_DETAIL->quantity_pending;
                // }
            }
        }

        $SALE_DETAIL->update([
            "state_attention" => $state_attention,
            "quantity_pending" => $quantity_pending,
        ]);

        if($quantity_attention > 0){
            SaleDetailAttention::create([
                "sale_detail_id" => $SALE_DETAIL->id,
                "product_id" => $SALE_DETAIL->product_id,
                "unit_id" => $SALE_DETAIL->unit_id,
                "warehouse_id" => $SALE_DETAIL->warehouse_id,
                "quantity" => $quantity_attention,
            ]);
        }

        // EL N° DE PRODUCTOS QUE HAN SIDO ENTREGADOS POR COMPLETO
        $counter_detail_complete = 0;
        // EL N° DE PRODUCTOS QUE TIENE UNA VENTA
        $counter_details = 0;
        
        $counter_detail_complete = SaleDetail::where("sale_id",$SALE_DETAIL->sale_id)->where("state_attention",3)->count();
        $counter_details = SaleDetail::where("sale_id",$SALE_DETAIL->sale_id)->count();
        $sale = $SALE_DETAIL->sale;
        $state_entrega = $sale->state_entrega;
        if($counter_detail_complete == $counter_details){
            $state_entrega = 3;
        }
        $sale->update([
            "state_entrega" => $state_entrega,
        ]);

        return response()->json([
            "sale_detail" => SaleDetailResource::make($SALE_DETAIL),
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize("create",Sale::class);
        $sale_details = $request->sale_details;
        $payments = $request->payments;
        try {
            date_default_timezone_set('America/Lima');
            DB::beginTransaction();
            $sale = Sale::create([
                "user_id" => auth('api')->user()->id,
                "sucursale_id" => auth('api')->user()->sucursale_id,
                "client_id" => $request->client_id,
                "type_client" => $request->type_client,
                "subtotal" => $request->subtotal,
                "discount" => $request->discount,
                "total" => $request->total,
                "igv" => $request->igv,
                "state_sale" => $request->state_sale,
                "state_payment" => $request->state_payment,
                "debt" => $request->debt,
                "paid_out" => $request->paid_out,
                "date_validation" => $request->state_sale == 1 ? now() : NULL,
                "date_pay_complete" => $request->state_payment == 3 ? now() : NULL,
                "description" => $request->description,
            ]);

            $state_entrega = 1;$counter_complete = 0;
            foreach ($sale_details as $key => $sale_detail) {
                $state_attention = 1;//1 PENDIENTE, 2 PARCIAL 3 COMPLETO
                $quantity = 0;//LA CANTIDAD DE ATENCIÓN
                $quantity_pending = $sale_detail["quantity"];
                if($sale->state_sale == 1){
                    // LA DISTRUBICIÓN DE STOCK
                    $warehouse_product = ProductWarehouse::where("product_id",$sale_detail["product"]["id"])
                                                            ->where("warehouse_id",$sale_detail["warehouse_id"])
                                                            ->where("unit_id",$sale_detail["unit_id"])
                                                            ->first();
                    if($warehouse_product && $warehouse_product->stock >= $sale_detail["quantity"]){//ENTREGA COMPLETE
                        $state_attention = 3;
                        $quantity = $sale_detail["quantity"];
                        $warehouse_product->update([
                            "stock" => $warehouse_product->stock - $sale_detail["quantity"],
                        ]);
                        $quantity_pending = 0;
                        $counter_complete ++;
                    }else{
                        if($warehouse_product && $warehouse_product->stock > 0 && $warehouse_product->stock < $sale_detail["quantity"]){//ENTREGA PARCIAL
                            $state_attention = 2;
                            $quantity = $warehouse_product->stock;
                            $quantity_pending = $sale_detail["quantity"] - $warehouse_product->stock;
                            $warehouse_product->update([
                                "stock" => 0,
                            ]);
                            $state_entrega = 2;
                        }else{
                            if($warehouse_product && $warehouse_product->stock == 0){
                                $state_attention = 1;
                                $quantity_pending = $sale_detail["quantity"];
                            }
                            if(!$warehouse_product){
                                $state_attention = 1;
                                $quantity_pending = $sale_detail["quantity"];
                            }
                        }
                    }
                }

                $detail_sale = SaleDetail::create([
                    "sale_id" => $sale->id,
                    "product_id" => $sale_detail["product"]["id"],
                    "product_categorie_id" => $sale_detail["product"]["product_categorie_id"],
                    "unit_id" => $sale_detail["unit_id"],
                    "warehouse_id" => $sale_detail["warehouse_id"],
                    "quantity" => $sale_detail["quantity"],//LA CANTIDAD SOLICITADA
                    "quantity_pending" => $quantity_pending, //LA CANTIDAD PENDIENTE
                    "price_unit" => $sale_detail["price"],
                    "discount" => $sale_detail["discount"],
                    "subtotal" => $sale_detail["subtotal"],
                    "igv" => $sale_detail["igv"],
                    "total" => $sale_detail["total"],
                    "state_attention" => $state_attention,
                ]);
                if($quantity > 0){
                    SaleDetailAttention::create([
                        "sale_detail_id" => $detail_sale->id,
                        "product_id" => $detail_sale->product_id,
                        "unit_id" => $detail_sale->unit_id,
                        "warehouse_id" => $detail_sale->warehouse_id,
                        "quantity" => $quantity,
                    ]);
                }
            }
            if($counter_complete == sizeof($sale_details)){
                $state_entrega = 3;
            }
            // method_payment
            // amount
            foreach ($payments as $key => $payment) {
                SalePayment::create([
                    "sale_id" => $sale->id,
                    "method_payment" => $payment["method_payment"],
                    "amount" => $payment["amount"],
                ]);
            }

            $sale->update([
                "state_entrega" => $state_entrega,
                "date_pay_complete" => $sale->state_payment == 3 ? now() : NULL,
            ]);

            DB::commit();
        } catch (\Throwable $th) {
           DB::rollBack();
           throw new HttpException(500,$th->getMessage());
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
        Gate::authorize("update",Sale::class);
        $sale = Sale::findOrFail($id);
        return response()->json([
            "sale" => SaleResource::make($sale)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Gate::authorize("update",Sale::class);
        $sale = Sale::findOrFail($id);
        if($sale->state_sale == 2 && $request->state_sale == 1){
            date_default_timezone_set('America/Lima');
            $request->request->add(["date_validation" => now()]);
        }
        $sale->update($request->all());

        return response()->json([
            "message" => 200,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize("delete",Sale::class);
        $sale = Sale::findOrFail($id);
        $sale->delete();
        return response()->json([
            "message" => 200,
        ]);
    }
}
