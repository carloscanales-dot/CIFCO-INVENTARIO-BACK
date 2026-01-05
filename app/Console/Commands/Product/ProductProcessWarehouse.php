<?php

namespace App\Console\Commands\Product;

use App\Models\Product\Product;
use Illuminate\Console\Command;

class ProductProcessWarehouse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:product-process-warehouse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analizar las existencias de los productos para saber si el umbral configurado aun no es igual o menor al stock disponible, en caso fuera asi marcaremos al producto con el estado POR AGOTAR O AGOTADO';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //OBTENEMOS LOS PRODUCTOS ACTIVOS
        $PRODUCTS = Product::where("state",1)->get();
        // ITERAMOS LA LISTA DE LOS PRODUCTOS
        foreach ($PRODUCTS as $PRODUCT) {
            // EXITENCIA DEL PRODUCTO
            $por_agotar = 0;
            $agotado = 0;
            foreach ($PRODUCT->warehouses as $warehouse) {
                // COMPARAMOS EL STOCK ACTUAL CON EL UMBRAL CONFIGURADO
                if($warehouse->stock <= $warehouse->umbral){
                    // SI EL STOCK ES MENOR O IGUAL AL UMBRAL ENTONCES LA VARIABLE POR AGOTAR ES 1
                    $por_agotar = 1;
                    $warehouse->update([
                        "state_stock" => 2, //POR AGOTAR
                    ]);
                }
                if($warehouse->stock == 0){
                    // SI EL STOCK ES IGUAL A 0 ENTONCES LA VARIABLE AGOTADO ES 1
                    $agotado = 1;
                    $warehouse->update([
                        "state_stock" => 3, //AGOTADO
                    ]);
                }
            }
            if($por_agotar == 1){
                // SI POR AGOTAR ES 1 ENTONCES EL ESTADO DE STOCK DEL PRODUCTO ES IGUAL A 2
                $PRODUCT->update([
                    "state_stock" => 2,
                ]);
            }
            if($agotado == 1){
                // SI LA VARIABLE AGOTADO ES IGUAL A 1 ENTONCES EL ESTADO DE STOCK DEL PRODUCTO ES IGUAL A 3
                $PRODUCT->update([
                    "state_stock" => 3,
                ]);
            }
        }
    }
}
