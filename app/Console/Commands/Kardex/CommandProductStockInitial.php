<?php

namespace App\Console\Commands\Kardex;

use Illuminate\Console\Command;
use App\Models\Purchase\PurchaseDetail;
use App\Models\Product\ProductWarehouse;
use App\Models\Kardex\ProductStockInitial;

class CommandProductStockInitial extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:command-product-stock-initial';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'El objetivo es guardar el stock inicial del producto a inicio de mes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $product_warehouses = ProductWarehouse::all();

        foreach ($product_warehouses as $key => $product_warehouse) {
            
            $date_before = now()->subMonth(1);
            $price_unit_avg = PurchaseDetail::where("product_id",$product_warehouse->product_id)
                                            ->where("unit_id",$product_warehouse->unit_id)
                                            ->whereHas("purchase",function($q) use($product_warehouse){
                                                $q->where("warehouse_id",$product_warehouse->warehouse_id);
                                            })->whereYear("date_entrega",$date_before->format("Y"))
                                            ->whereMonth("date_entrega",$date_before->format("m"))
                                            ->avg("price_unit");

            if(!$price_unit_avg){
                $product_stock_initial_final = ProductStockInitial::where("product_id",$product_warehouse->product_id)
                                                                    ->where("unit_id",$product_warehouse->unit_id)
                                                                    ->where("warehouse_id",$product_warehouse->warehouse_id)
                                                                    ->where("price_unit_avg",">",0)
                                                                    ->orderBy("id","desc")
                                                                    ->first();
                 if($product_stock_initial_final){
                    $price_unit_avg = $product_stock_initial_final->price_unit_avg;
                 }                                       
            }
            // 75 76 70.50
            ProductStockInitial::create([
                "product_id" => $product_warehouse->product_id,
                "unit_id" => $product_warehouse->unit_id,
                "warehouse_id" => $product_warehouse->warehouse_id,
                "stock" => $product_warehouse->stock,
                "price_unit_avg" => $price_unit_avg ?? 0,
                "created_at" => now()->format("Y-m")."-01 00:00:00",//2025-02-01
            ]);
        }
    }
}
