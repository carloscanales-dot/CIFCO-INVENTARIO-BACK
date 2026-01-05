<?php

namespace App\Http\Controllers\Kardex;

use Carbon\Carbon;
use App\Models\Config\Unit;
use Illuminate\Http\Request;
use App\Models\Product\Product;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Kardex\ProductStockInitial;
use PhpOffice\PhpSpreadsheet\Reader\Xls\RC4;

class KardexProductController extends Controller
{
    public function kardex_products(Request $request) {
        $year = $request->year;
        $month = $request->month;
        $warehouse_id = $request->warehouse_id;
        $product_id = $request->product_id;

        $movimients_products = collect([]);
        // 1.- AGRUPAR LAS ENTRADAS Y SALIDAS DE LOS PRODUCTOS
            // ENTRADAS
            $query_purchase = DB::table("purchase_details")
                                ->whereNull("purchase_details.deleted_at")
                                ->join("purchases","purchase_details.purchase_id","=","purchases.id")
                                ->whereNull("purchases.deleted_at")
                                ->join("products","purchase_details.product_id","=","products.id")
                                ->whereNotNull("purchase_details.date_entrega")
                                ->whereYear("purchase_details.date_entrega",$year)
                                ->whereMonth("purchase_details.date_entrega",$month);
            if($warehouse_id){
                $query_purchase->where("purchases.warehouse_id",$warehouse_id);
            }
            if($product_id){
                $query_purchase->where("purchase_details.product_id",$product_id);
            }

            $query_purchase = $query_purchase->selectRaw("
                        purchase_details.product_id as product_id,
                        products.title as product_title,
                        purchase_details.unit_id as unit_id,
                        1 as type_op,
                        TO_CHAR(purchase_details.date_entrega, 'YYYY-MM-DD HH24:MI:SS') as date_entrega_format,
                        EXTRACT(EPOCH FROM purchase_details.date_entrega) as date_entrega_num,
                        purchase_details.quantity as product_quantity,
                        purchase_details.price_unit as product_price_unit,
                        ('COMPRA N° ' || purchases.id) as detalle
                    ")->get();
            
            foreach ($query_purchase as $key => $purchase) {
                $movimients_products->push($purchase);
            }
            // dd($query_purchase);
            $query_transport_entrega = DB::table("transport_details")
                                        ->whereNull("transport_details.deleted_at")
                                        ->join("transports","transport_details.transport_id","=","transports.id")
                                        ->whereNull("transports.deleted_at")
                                        ->join("products","transport_details.product_id","=","products.id")
                                        ->whereNotNull("transport_details.date_entrega")
                                        ->whereYear("transport_details.date_entrega",$year)
                                        ->whereMonth("transport_details.date_entrega",$month);
            if($warehouse_id){
                $query_transport_entrega->where("transports.warehouse_end_id",$warehouse_id);
            }
            if($product_id){
                $query_transport_entrega->where("transport_details.product_id",$product_id);
            } 

            $query_transport_entrega = $query_transport_entrega->selectRaw("
                transport_details.product_id as product_id,
                products.title as product_title,
                transport_details.unit_id as unit_id,
                1 as type_op,
                TO_CHAR(transport_details.date_entrega, 'YYYY-MM-DD HH24:MI:SS') as date_entrega_format,
                EXTRACT(EPOCH FROM transport_details.date_entrega) as date_entrega_num,
                transport_details.quantity as product_quantity,
                transport_details.price_unit as product_price_unit,
                ('TRANSPORTE N° ' || transports.id) as detalle
            ")->get();
            // dd($query_transport_entrega);
            foreach ($query_transport_entrega as $key => $transport) {
                $movimients_products->push($transport);
            }

            $query_conversions_entrega = DB::table("conversions")
                                        ->whereNull("conversions.deleted_at")
                                        ->join("products","conversions.product_id","=","products.id")
                                        ->whereYear("conversions.created_at",$year)
                                        ->whereMonth("conversions.created_at",$month);

            if($warehouse_id){
                $query_conversions_entrega->where("conversions.warehouse_id",$warehouse_id);
            }
            if($product_id){
                $query_conversions_entrega->where("conversions.product_id",$product_id);
            }   
            $query_conversions_entrega = $query_conversions_entrega->selectRaw("
                conversions.product_id as product_id,
                products.title as product_title,
                conversions.unit_end_id as unit_id,
                1 as type_op,
                TO_CHAR(conversions.created_at, 'YYYY-MM-DD HH24:MI:SS') as date_entrega_format,
                EXTRACT(EPOCH FROM conversions.created_at) as date_entrega_num,
                conversions.quantity_end as product_quantity,
                0 as product_price_unit,
                ('CONVERSION N° ' || conversions.id) as detalle
            ")->get();
            foreach ($query_conversions_entrega as $key => $conversion) {
                $movimients_products->push($conversion);
            }
            // dd($query_conversions_entrega);
            //SALIDAS

            $query_sale_details_attentions = DB::table("sale_detail_attentions")
                                            ->whereNull("sale_detail_attentions.deleted_at")
                                            ->join("products","sale_detail_attentions.product_id","=","products.id")
                                            ->join("sale_details","sale_detail_attentions.sale_detail_id","sale_details.id")
                                            ->whereYear("sale_detail_attentions.created_at",$year)
                                            ->whereMonth("sale_detail_attentions.created_at",$month);
            if($warehouse_id){
                $query_sale_details_attentions->where("sale_detail_attentions.warehouse_id",$warehouse_id);
            }
            if($product_id){
                $query_sale_details_attentions->where("sale_detail_attentions.product_id",$product_id);
            }    
            
            $query_sale_details_attentions = $query_sale_details_attentions->selectRaw("
                sale_detail_attentions.product_id as product_id,
                products.title as product_title,
                sale_detail_attentions.unit_id as unit_id,
                2 as type_op,
                TO_CHAR(sale_detail_attentions.created_at, 'YYYY-MM-DD HH24:MI:SS') as date_entrega_format,
                EXTRACT(EPOCH FROM sale_detail_attentions.created_at) as date_entrega_num,
                sale_detail_attentions.quantity as product_quantity,
                sale_details.price_unit as product_price_unit,
                ('VENTA N° ' || sale_details.sale_id) as detalle
            ")->get();

            foreach ($query_sale_details_attentions as $key => $sale_detail_attention) {
                $movimients_products->push($sale_detail_attention);
            }
            // dd($query_sale_details_attentions);

            $query_transport_salida = DB::table("transport_details")
                                        ->whereNull("transport_details.deleted_at")
                                        ->join("transports","transport_details.transport_id","=","transports.id")
                                        ->whereNull("transports.deleted_at")
                                        ->join("products","transport_details.product_id","=","products.id")
                                        ->whereNotNull("transport_details.date_salida")
                                        ->whereYear("transport_details.date_salida",$year)
                                        ->whereMonth("transport_details.date_salida",$month);
            if($warehouse_id){
                $query_transport_salida->where("transports.warehouse_start_id",$warehouse_id);
            }
            if($product_id){
                $query_transport_salida->where("transport_details.product_id",$product_id);
            } 

            $query_transport_salida = $query_transport_salida->selectRaw("
                transport_details.product_id as product_id,
                products.title as product_title,
                transport_details.unit_id as unit_id,
                2 as type_op,
                TO_CHAR(transport_details.date_salida, 'YYYY-MM-DD HH24:MI:SS') as date_entrega_format,
                EXTRACT(EPOCH FROM transport_details.date_salida) as date_entrega_num,
                transport_details.quantity as product_quantity,
                transport_details.price_unit as product_price_unit,
                ('TRANSPORTE N° ' || transports.id) as detalle
            ")->get();

            foreach ($query_transport_salida as $key => $transport) {
                $movimients_products->push($transport);
            }

            $query_conversions_salida = DB::table("conversions")
                                        ->whereNull("conversions.deleted_at")
                                        ->join("products","conversions.product_id","=","products.id")
                                        ->whereYear("conversions.created_at",$year)
                                        ->whereMonth("conversions.created_at",$month);

            if($warehouse_id){
                $query_conversions_salida->where("conversions.warehouse_id",$warehouse_id);
            }
            if($product_id){
                $query_conversions_salida->where("conversions.product_id",$product_id);
            }   
            $query_conversions_salida = $query_conversions_salida->selectRaw("
                conversions.product_id as product_id,
                products.title as product_title,
                conversions.unit_start_id as unit_id,
                2 as type_op,
                TO_CHAR(conversions.created_at, 'YYYY-MM-DD HH24:MI:SS') as date_entrega_format,
                EXTRACT(EPOCH FROM conversions.created_at) as date_entrega_num,
                conversions.quantity_start as product_quantity,
                0 as product_price_unit,
                ('CONVERSION N° ' || conversions.id) as detalle
            ")->get();

            foreach ($query_conversions_salida as $key => $conversion) {
                $movimients_products->push($conversion);
            }

            // dd($query_transport_salida);

            $query_refound_products = DB::table("refound_products")
                                        ->whereNull("refound_products.deleted_at")
                                        ->where("type",2)
                                        ->join("products","refound_products.product_id","=","products.id")
                                        ->join("sale_details","refound_products.sale_detail_id","=","sale_details.id")
                                        ->whereYear("refound_products.created_at",$year)
                                        ->whereMonth("refound_products.created_at",$month);
            if($warehouse_id){
                $query_refound_products->where("refound_products.warehouse_id",$warehouse_id);
            }
            if($product_id){
                $query_refound_products->where("refound_products.product_id",$product_id);
            }   

            $query_refound_products = $query_refound_products->selectRaw("
                refound_products.product_id as product_id,
                products.title as product_title,
                refound_products.unit_id as unit_id,
                2 as type_op,
                TO_CHAR(refound_products.created_at, 'YYYY-MM-DD HH24:MI:SS') as date_entrega_format,
                EXTRACT(EPOCH FROM refound_products.created_at) as date_entrega_num,
                refound_products.quantity as product_quantity,
                sale_details.price_unit as product_price_unit,
                ('DEVOLUCIÓN N° ' || refound_products.id) as detalle
            ")->get();

            foreach ($query_refound_products as $key => $refound_product) {
                $movimients_products->push($refound_product);
            }
            // dd($movimients_products);
        //2. AGRUPAR LOS PRODUCTOS
        $kardex_products = collect([]);
        foreach ($movimients_products->groupBy("product_id") as $key => $movimient_product) {
            // LA LISTA DE MOVIMIENTOS DE UN PRODUCTO EN ESPECIFICO
            // dd($movimient_product);
            //3. AGRUPAR POR UNIDAD DE LOS PRODUCTOS
            $movimients_for_units = collect([]);
            $units = collect([]);
            foreach ($movimient_product->groupBy("unit_id") as $movimient_unit) {
                // UNA LIDA DE MOVIMIENTOS POR UNIDAD DEL PRODUCTO
                // dd($movimient_unit[0]);
                $movimients = collect([]);

                // product_id,unit_id,warehouse,quantity,price_unit_avg
                $product_stock_initials = ProductStockInitial::where("product_id",$movimient_unit[0]->product_id)
                                    ->where("unit_id",$movimient_unit[0]->unit_id);
                if($warehouse_id){
                    $product_stock_initials->where("warehouse_id",$warehouse_id);
                }

                $product_stock_initials = $product_stock_initials->whereDate("created_at","$year-$month-01")->get();
                //LIMA CAJA - 5 - 60
                //TRUJILLO CAJA - 3 - 55
                // CAJA - 5 - 60 / CAJA - 3 - 55

                $quantity_total = $product_stock_initials->sum("stock");// 8
                $price_unit_avg = $product_stock_initials->avg("price_unit_avg");// 60 + 55 / 2
                $total = $quantity_total * $price_unit_avg;

                $quantity_anterior = $quantity_total;
                $total_anterior = $total;
                $movimients->push([
                    "fecha" => Carbon::parse($year."-".$month."-01")->format("Y-m-d")." 00:00:00",
                    "detalle" => "Stock inicial del producto",
                    "entrada" => NULL,
                    "salida" => NULL,
                    "existencia" => [
                        "quantity" => $quantity_total,
                        "price_unit" => $price_unit_avg,
                        "total" => $total
                    ],
                ]);
                //4. ORDENAR LOS MOVIMIENTOS SEGUN LA FECHA

                foreach ($movimient_unit->sortBy("date_entrega_num") as $movimient) {

                    $quantity_existencia = 0;

                    if($movimient->type_op == 1){
                        $quantity_existencia = $quantity_anterior + $movimient->product_quantity;
                        $total_existencia = $total_anterior + round((float)$movimient->product_quantity * (float)$movimient->product_price_unit,2);
                    }else{
                        $quantity_existencia = $quantity_anterior - $movimient->product_quantity;
                        $total_existencia = $total_anterior - round((float)$movimient->product_quantity * (float)$movimient->product_price_unit,2);
                    }
                    
                    $movimients->push([
                        "fecha" => $movimient->date_entrega_format,
                        "detalle" => $movimient->detalle,
                        "entrada" => $movimient->type_op == 1 ? [
                            "quantity" => $movimient->product_quantity,
                            "price_unit" => $movimient->product_price_unit,
                            "total" => round((float)$movimient->product_quantity * (float)$movimient->product_price_unit,2)
                        ]: NULL,
                        "salida" => $movimient->type_op == 2 ? [
                            "quantity" => $movimient->product_quantity,
                            "price_unit" => $movimient->product_price_unit,
                            "total" => round((float)$movimient->product_quantity * (float)$movimient->product_price_unit,2)
                        ]: NULL,
                        "existencia" => [
                            "quantity" => $quantity_existencia,
                            "price_unit" => $quantity_existencia > 0 ? round($total_existencia/$quantity_existencia,2) : 0,
                            "total" =>round($total_existencia,2),
                        ]
                    ]);

                    $quantity_anterior = $quantity_existencia;
                    $total_anterior =round($total_existencia,2);
                }
                // 5. ALMACENANDO CADA UNA DE LAS UNIDADES QUE SE ITERAN

                $movimients_for_units->push([
                    "unit_id" => $movimient_unit[0]->unit_id,
                    "movimients" => $movimients,
                ]);
                $units->push(Unit::find($movimient_unit[0]->unit_id));
            }
            // dd($movimients_for_units);
            $product = Product::findOrFail($movimient_product[0]->product_id);
            $kardex_products->push([
                "product_id" => $movimient_product[0]->product_id,
                "title" => $movimient_product[0]->product_title,
                "sku" => $product->sku,
                "categoria" => $product->product_categorie->title,
                "movimient_for_units" => $movimients_for_units,
                // 
                "unit_first" => $units->first(),
                "units" => $units,
            ]);
        }

        return response()->json([
            "kardex_products" => $kardex_products,
        ]);
    }
}
