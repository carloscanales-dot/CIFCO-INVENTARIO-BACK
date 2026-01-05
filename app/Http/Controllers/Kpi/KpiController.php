<?php

namespace App\Http\Controllers\Kpi;

use Carbon\Carbon;
use App\Models\Sale\Sale;
use Illuminate\Http\Request;
use App\Models\Config\Sucursale;
use App\Models\Purchase\Purchase;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class KpiController extends Controller
{
    //
    public function information_general() {
        $year = date("Y");
        $month = date("m");

        $date_before = Carbon::parse($year."-".$month."-01")->subMonth(1);

        $total_sales_month_current = Sale::whereYear("created_at",$year)
                                        ->whereMonth("created_at",$month)
                                        ->where("state_sale",1)
                                        ->sum("total");

        $total_sales_month_before = Sale::whereYear("created_at",$date_before->format("Y"))
                                        ->whereMonth("created_at",$date_before->format("m"))
                                        ->where("state_sale",1)
                                        ->sum("total");
        $variation_percentage_total_sales = $total_sales_month_before > 0 ? (($total_sales_month_current - $total_sales_month_before)/$total_sales_month_before) * 100 : 0;
        
        $sucursales_most_sales_month_current = DB::table("sales")->whereNull("sales.deleted_at")
                                                ->join("sucursales","sales.sucursale_id","=","sucursales.id")
                                                ->whereYear("sales.created_at",$year)
                                                ->whereMonth("sales.created_at",$month)
                                                ->where("sales.state_sale",1)
                                                ->selectRaw("
                                                    sales.sucursale_id as sucursal_most_sale_id,
                                                    sucursales.name as sucursale_most_sales,
                                                    SUM(sales.total) as total_sales,
                                                    count(*) as count_sales
                                                ")
                                                ->groupBy("sucursal_most_sale_id",'sucursale_most_sales')
                                                ->orderBy("total_sales","desc")
                                                ->take(1)
                                                ->first();
        $variation_percentage_sucursal_most_sale = 0;
        if($sucursales_most_sales_month_current){
            $total_sales_month_before_sucursale_most_sales = Sale::where("sucursale_id",$sucursales_most_sales_month_current->sucursal_most_sale_id)
                                                                    ->whereYear("created_at",$date_before->format("Y"))
                                                                    ->whereMonth("created_at",$date_before->format("m"))
                                                                    ->where("state_sale",1)
                                                                    ->sum("total");
    
            $variation_percentage_sucursal_most_sale = $total_sales_month_before_sucursale_most_sales > 0 ? (($sucursales_most_sales_month_current->total_sales - $total_sales_month_before_sucursale_most_sales)/$total_sales_month_before_sucursale_most_sales) * 100 : 0;
        }
        
        $purchase_total_month_current = Purchase::whereYear("created_at",$year)
                                                    ->whereMonth("created_at",$month)
                                                    ->where("state",3)
                                                    ->sum("total");

        $purchase_total_month_before = Purchase::whereYear("created_at",$date_before->format("Y"))
                                                    ->whereMonth("created_at",$date_before->format("m"))
                                                    ->where("state",3)
                                                    ->sum("total");

        $variation_percentage_purchase = $purchase_total_month_before > 0 ? (($purchase_total_month_current - $purchase_total_month_before)/$purchase_total_month_before)*100 : 0;
        
        return response()->json([
            "variation_percentage_purchase" => round($variation_percentage_purchase,2),
            "purchase_total_month_before" => round($purchase_total_month_before,2),
            "purchase_total_month_current" => round($purchase_total_month_current,2),
            "variation_percentage_sucursal_most_sale" => round($variation_percentage_sucursal_most_sale,2),
            "sucursales_most_sales_month_current" => $sucursales_most_sales_month_current,
            "variation_percentage_total_sales" => round($variation_percentage_total_sales,2),
            "total_sales_month_current" => round($total_sales_month_current,2),
        ]);
    }

    public function asesor_most_sales(){
        $year = date("Y");
        $month = date("m");

        $date_before = Carbon::parse($year."-".$month."-01")->subMonth(1);
        // 1 - jose jaico - 100 
        // 2 - camila - 150
        // 1 - jose jaico - 150
        // 1 - jose jaico - 250
        $asesores_m_most_sales_month_current = DB::table("sales")->whereNull("sales.deleted_at")
                                                ->join("users","sales.user_id","=","users.id")
                                                ->where("sales.state_sale",1)
                                                ->whereYear("sales.created_at",$year)
                                                ->whereMonth("sales.created_at",$month)
                                                ->where("users.gender","M")
                                                ->selectRaw("
                                                    sales.user_id as asesor_id,
                                                    (users.name || ' ' ||users.surname) as asesor_full_name,
                                                    SUM(sales.total) as total_sales,
                                                    count(*) as count_sales
                                                ")
                                                ->groupBy("asesor_id","asesor_full_name")
                                                ->orderBy("total_sales","desc")
                                                ->first();
        $variation_percentage_asesor_m_most_sales = 0;
        if($asesores_m_most_sales_month_current){

            $total_sales_month_before_asesor = Sale::whereYear("created_at",$date_before->format("Y"))
                                                        ->whereMonth("created_at",$date_before->format("m"))
                                                        ->where("state_sale",1)
                                                        ->where("user_id",$asesores_m_most_sales_month_current->asesor_id)
                                                        ->sum("total");

            $variation_percentage_asesor_m_most_sales = $total_sales_month_before_asesor > 0 ? (($asesores_m_most_sales_month_current->total_sales - $total_sales_month_before_asesor)/$total_sales_month_before_asesor) * 100 : 0 ;

        }

        $asesores_f_most_sales_month_current = DB::table("sales")->whereNull("sales.deleted_at")
                                            ->join("users","sales.user_id","=","users.id")
                                            ->where("sales.state_sale",1)
                                            ->whereYear("sales.created_at",$year)
                                            ->whereMonth("sales.created_at",$month)
                                            ->where("users.gender","F")
                                            ->selectRaw("
                                                sales.user_id as asesor_id,
                                                (users.name || ' ' ||users.surname) as asesor_full_name,
                                                SUM(sales.total) as total_sales,
                                                count(*) as count_sales
                                            ")
                                            ->groupBy("asesor_id","asesor_full_name")
                                            ->orderBy("total_sales","desc")
                                            ->first();
        $variation_percentage_asesor_f_most_sales = 0;
        if($asesores_f_most_sales_month_current){

            $total_sales_month_before_asesor = Sale::whereYear("created_at",$date_before->format("Y"))
                                                        ->whereMonth("created_at",$date_before->format("m"))
                                                        ->where("state_sale",1)
                                                        ->where("user_id",$asesores_f_most_sales_month_current->asesor_id)
                                                        ->sum("total");
                                                        
            $variation_percentage_asesor_f_most_sales = $total_sales_month_before_asesor > 0 ? (($asesores_f_most_sales_month_current->total_sales -  $total_sales_month_before_asesor)/$total_sales_month_before_asesor) * 100 : 0;
        }
        return response()->json([
            "variation_percentage_asesor_f_most_sales" => round($variation_percentage_asesor_f_most_sales,2),
            "asesores_f_most_sales_month_current" => $asesores_f_most_sales_month_current,
            "variation_percentage_asesor_m_most_sales" => round($variation_percentage_asesor_m_most_sales,2),
            "asesores_m_most_sales_month_current" => $asesores_m_most_sales_month_current,
        ]);
    }

    public function sales_payment_total_pending() {
        $year = date("Y");
        $month = date("m");
        $date_before = Carbon::parse($year."-".$month."-01")->subMonth(1);

        $sale_total_payment_complete_month_current = Sale::whereYear("created_at",$year)
                                                ->whereMonth("created_at",$month)
                                                ->where("state_sale",1)
                                                ->where("state_payment",3)
                                                ->sum("total");

        $sale_total_payment_complete_month_before = Sale::whereYear("created_at",$date_before->format("Y"))
                                                ->whereMonth("created_at",$date_before->format("m"))
                                                ->where("state_sale",1)
                                                ->where("state_payment",3)
                                                ->sum("total");

        $variation_percentage_sale_total_payment_complete = $sale_total_payment_complete_month_before > 0 ? (($sale_total_payment_complete_month_current - $sale_total_payment_complete_month_before)/$sale_total_payment_complete_month_before)*100 : 0;
        
        // 27
        $num_sales_payment_total_month_current = Sale::whereYear("created_at",$year)
                                        ->whereMonth("created_at",$month)
                                        ->where("state_sale",1)
                                        ->where("state_payment",3)
                                        ->count();
        // 73
        $num_sales_payment_pending_month_current = Sale::whereYear("created_at",$year)
                                        ->whereMonth("created_at",$month)
                                        ->where("state_sale",1)
                                        ->whereIn("state_payment",[1,2])
                                        ->count();
        // 100
        $num_sales_month_current = Sale::whereYear("created_at",$year)
                                    ->whereMonth("created_at",$month)
                                    ->where("state_sale",1)
                                    ->count();

        $percentage_sale_payment_complete = ($num_sales_payment_total_month_current/$num_sales_month_current) *  100;
        $percentage_sale_pending = ($num_sales_payment_pending_month_current/$num_sales_month_current) * 100;
        return response()->json([
            "percentage_sale_pending" => round($percentage_sale_pending,2),
            "percentage_sale_payment_complete" => round($percentage_sale_payment_complete,2),
            "num_sales_month_current" => $num_sales_month_current,
            "num_sales_payment_pending_month_current" => $num_sales_payment_pending_month_current,
            "num_sales_payment_total_month_current" => $num_sales_payment_total_month_current,
            "variation_percentage_sale_total_payment_complete" => round($variation_percentage_sale_total_payment_complete,2),
            "sale_total_payment_complete_month_current" => round($sale_total_payment_complete_month_current,2),
        ]);
    }

    public function sucursales_reporte_sales() {
        $year = date("Y");
        $month = date("m");
        $date_before = Carbon::parse($year."-".$month."-01")->subMonth(1);

        $sucursales = Sucursale::all();

        $sucursale_report_sales = collect([]);
        foreach ($sucursales as $key => $sucursal) {
            
            $sales_total_sucursale = Sale::whereYear("created_at",$year)
                                            ->whereMonth("created_at",$month)
                                            ->where("state_sale",1)
                                            ->where("sucursale_id",$sucursal->id)
                                            ->sum("total");

            $sales_total_sucursale_month_before = Sale::whereYear("created_at",$date_before->format("Y"))
                                                    ->whereMonth("created_at",$date_before->format("m"))
                                                    ->where("state_sale",1)
                                                    ->where("sucursale_id",$sucursal->id)
                                                    ->sum("total");

            $variation_percentage_sucursale = $sales_total_sucursale_month_before > 0 ? (($sales_total_sucursale - $sales_total_sucursale_month_before)/$sales_total_sucursale_month_before) * 100 : 0;
            

            $num_sales_total_sucursale = Sale::whereYear("created_at",$year)
                                        ->whereMonth("created_at",$month)
                                        ->where("state_sale",1)
                                        ->where("sucursale_id",$sucursal->id)
                                        ->count();

            $num_cotizaciones_total_sucursale = Sale::whereYear("created_at",$year)
                                        ->whereMonth("created_at",$month)
                                        ->where("state_sale",2)
                                        ->where("sucursale_id",$sucursal->id)
                                        ->count();


            $amount_total_payment_sucursale = Sale::whereYear("created_at",$year)
                                        ->whereMonth("created_at",$month)
                                        ->where("state_sale",1)
                                        ->where("sucursale_id",$sucursal->id)
                                        ->sum("paid_out");
            
            $amount_total_not_payment_sucursale = Sale::whereYear("created_at",$year)
                                                    ->whereMonth("created_at",$month)
                                                    ->where("state_sale",1)
                                                    ->where("sucursale_id",$sucursal->id)
                                                    ->sum("debt");

            $sucursale_report_sales->push([
                "id" => $sucursal->id,
                "name" => $sucursal->name,
                "sales_total_sucursal" => round($sales_total_sucursale,2),
                "variation_perncetage_sale_total" => round($variation_percentage_sucursale,2),
                "n_sales" => $num_sales_total_sucursale,
                "n_cotizaciones" => $num_cotizaciones_total_sucursale,
                "amount_total_payment" => round($amount_total_payment_sucursale,2),
                "amount_total_not_payment" => round($amount_total_not_payment_sucursale,2),
            ]);
        }

        return response()->json([
            "sucursales" => $sucursale_report_sales,
        ]);
    }

    public function client_most_sales() {
        $year = date("Y");
        $month = date("m");
        $date_before = Carbon::parse($year."-".$month."-01")->subMonth(1);

        $client_most_sale = DB::table("sales")->whereNull("sales.deleted_at")
                                ->join("clients","sales.client_id","=","clients.id")
                                ->where("sales.state_sale",1)
                                ->whereYear("sales.created_at",$year)
                                ->whereMonth("sales.created_at",$month)
                                ->selectRaw("
                                    sales.client_id as client_most_sale_id,
                                    clients.full_name as client_most_sale,
                                    SUM(sales.total) as total_sales,
                                    count(*) as count_sales
                                ")
                                ->groupBy("client_most_sale_id","client_most_sale")
                                ->orderBy("total_sales","desc")
                                ->first();
        $variation_percentage_client_most_sale = 0;
        if($client_most_sale){

            $total_sales_client_month_before = Sale::whereYear("created_at",$date_before->format("Y"))
                                                    ->whereMonth("created_at",$date_before->format("m"))
                                                    ->where("state_sale",1)
                                                    ->where("client_id",$client_most_sale->client_most_sale_id)
                                                    ->sum("total");


            $variation_percentage_client_most_sale = $total_sales_client_month_before > 0 ? (($client_most_sale->total_sales - $total_sales_client_month_before)/$total_sales_client_month_before) * 100 : 0;


        }
        return response()->json([
            "variation_percentage_client_most_sale" => round($variation_percentage_client_most_sale,2),
            "client_most_sale" => $client_most_sale,
        ]);
    }

    public function sales_x_month_of_year(Request $request){
        $year = $request->year;

        $sale_x_month_of_year_current = DB::table("sales")->whereNull("sales.deleted_at")
                                                ->where("sales.state_sale",1)
                                                ->whereYear("sales.created_at",$year)
                                                ->selectRaw("
                                                    TO_CHAR(sales.created_at,'YYYY-MM') as created_at_format,
                                                    SUM(sales.total) as total_sales
                                                ")
                                                ->groupBy("created_at_format")
                                                ->get();

        $sale_x_month_of_year_before = DB::table("sales")->whereNull("sales.deleted_at")
                                                ->where("sales.state_sale",1)
                                                ->whereYear("sales.created_at",$year - 1)
                                                ->selectRaw("
                                                    TO_CHAR(sales.created_at,'YYYY-MM') as created_at_format,
                                                    SUM(sales.total) as total_sales
                                                ")
                                                ->groupBy("created_at_format")
                                                ->get();

        return response()->json([
            "sale_x_month_of_year_before" => $sale_x_month_of_year_before,
            "total_sales_year_before" => round($sale_x_month_of_year_before->sum("total_sales"),2),
            "sale_x_month_of_year_current" => $sale_x_month_of_year_current,
            "total_sales_year_current" => round($sale_x_month_of_year_current->sum("total_sales"),2)
        ]);
    }

    public function categories_most_sales(Request $request) {
        $year = $request->year;
        $month = $request->month;

        $categories_most_sales = DB::table("sale_details")->whereNull("sale_details.deleted_at")
                                    ->join("sales","sale_details.sale_id","=","sales.id")
                                    ->join("product_categories","sale_details.product_categorie_id","=","product_categories.id")
                                    ->whereNull("sales.deleted_at")
                                    ->where("sales.state_sale",1)
                                    ->whereYear("sale_details.created_at",$year)
                                    ->whereMonth("sale_details.created_at",$month)
                                    ->selectRaw("
                                        sale_details.product_categorie_id as product_categorie_id,
                                        product_categories.title as categorie,
                                        product_categories.imagen as categorie_imagen,
                                        SUM(sale_details.total) as total_sales,
                                        count(*) as count_sales
                                    ")
                                    ->groupBy("product_categorie_id","categorie","categorie_imagen")
                                    ->orderBy("total_sales","desc")
                                    ->take(4)
                                    ->get();

        $categories_products = collect([]);
        foreach ($categories_most_sales as $key => $categories_most_sale) {
            
            $product_most_sale_of_categorie = DB::table("sale_details")->whereNull("sale_details.deleted_at")
                                                ->join("sales","sale_details.sale_id","=","sales.id")
                                                ->join("products","sale_details.product_id","=","products.id")
                                                ->whereNull("sales.deleted_at")
                                                ->where("sales.state_sale",1)
                                                ->whereYear("sale_details.created_at",$year)
                                                ->whereMonth("sale_details.created_at",$month)
                                                ->where("sale_details.product_categorie_id",$categories_most_sale->product_categorie_id)
                                                ->selectRaw("
                                                    sale_details.product_id as product_id,
                                                    products.title as product_title,
                                                    products.sku as product_sku,
                                                    products.imagen as product_imagen,
                                                    products.state_stock as product_state_stock,
                                                    SUM(sale_details.total) as total_sales,
                                                    count(*) as count_sales
                                                ")
                                                ->groupBy("product_id","product_title","product_sku","product_imagen","product_state_stock")
                                                ->orderBy("total_sales","desc")
                                                ->take(4)
                                                ->get();
            
            $categories_products->push([
                "id" => $categories_most_sale->product_categorie_id,
                "name" => $categories_most_sale->categorie,
                //"imagen" => env("APP_URL")."storage/".$categories_most_sale->categorie_imagen,
                "imagen" => $categories_most_sale->categorie_imagen ? asset('storage/'.$categories_most_sale->categorie_imagen) : NULL,
                "total_sales" => round($categories_most_sale->total_sales,2),
                "count" => $categories_most_sale->count_sales,
                "products" => $product_most_sale_of_categorie->map(function($product) {
                    $link = "";
                    if($product->product_imagen){
                        if(str_contains($product->product_imagen,"https://") || str_contains($product->product_imagen,"http://")){
                            $link = $product->product_imagen;
                        }else{
                            $link =  env('APP_URL').'storage/'.$product->product_imagen;
                        }
                    }
                    $product->product_imagen = $link;
                    $product->total_sales = round($product->total_sales,2);
                    return $product;
                }),
            ]);                                  

        }
        
        return response()->json([
            "categories_products" => $categories_products,
        ]);
    }
}
