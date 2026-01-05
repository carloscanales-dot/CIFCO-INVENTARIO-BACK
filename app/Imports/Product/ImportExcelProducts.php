<?php

namespace App\Imports\Product;

use App\Models\Config\Unit;
use Illuminate\Support\Str;
use App\Models\Product\Product;
use App\Models\Config\Sucursale;
use App\Models\Config\Warehouse;
use Illuminate\Support\Collection;
use App\Models\Product\ProductWallet;
use App\Models\Config\ProductCategorie;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\Product\ProductWarehouse;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ImportExcelProducts implements ToModel,WithHeadingRow,WithValidation
{
    use Importable,SkipsErrors;
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row) {


        // 1.- ES CREAR EL PRODUCTO

        $product_categorie = ProductCategorie::where("title","ilike",Str::lower($row["categoria"]))->first();

        $warehouse = Warehouse::where("name","ilike",Str::lower($row["almacen"]))->first();
        $unit = Unit::where("name","ilike",Str::lower($row["unidad_almacen"]))->first();
        $sucursale = Sucursale::where("name","ilike",Str::lower($row["sucursal"]))->first();
        $unit_price = Unit::where("name","ilike",Str::lower($row["unidad_precio"]))->first();

        $product_title = Product::where("title",$row["nombre_producto"])->first();
        $product_sku = Product::where("title",$row["sku"])->first();
        if(!$product_categorie || !$warehouse || !$unit || !$sucursale || !$unit_price || $product_title || $product_sku){
            return Product::first();
        }
        $disponibilidad = 1;
        switch (Str::upper($row["disponibilidad"])) {
            case 'ATENDER SIN STOCK':
                $disponibilidad = 1;
                break;
            case 'NO ATENDER SIN STOCK':
                $disponibilidad = 2;
                break;
            default:
                $disponibilidad = 1;
                break;
        }
        $tax_selected = 1;
        switch (Str::upper($row["tipo_impuesto"])) {
            case 'SUJETO A IMPUESTO':
                $tax_selected = 1;
                break;
            case 'LIBRE DE IMPUESTO':
                $tax_selected = 2;
                break;
            default:
                $tax_selected = 1;
                break;
        }
        $PRODUCT = Product::create([
            "title" => $row["nombre_producto"],
            "sku" => $row["sku"],
            "imagen" => $row["imagen"],
            "product_categorie_id" => $product_categorie->id,
            "price_general" => $row["precio_general"],
            "price_company" => $row["precio_empresa"],
            "description" => $row["descripcion"],
            "is_discount" => $row["descuento"] ? 2 : 1,
            "max_discount" =>$row["descuento"] ? $row["descuento"] : 0,
            "is_gift" => $row["es_regalo"] == 'SI' ? 2 : 1,
            "disponibilidad" => $disponibilidad,
            "state" => $row["estado"] == 'ACTIVO' ? 1 : 2,
            "warranty_day" => $row["dias_garantia"],
            "tax_selected" => $tax_selected,
            "importe_iva" => $row["importe_iva"],
        ]);
        // 2. CREAR LA EXISTENCIA DEL PRODUCTO
        $PRODUCT_WAREHOUSE = ProductWarehouse::create([
            "product_id" => $PRODUCT->id,
            "warehouse_id" => $warehouse->id,
            "unit_id" => $unit->id,
            "stock" => $row["stock"],
            "umbral" => $row["umbral"]
        ]);
        // 3. CREAR UN PRECIO MULTIPLE DEL PRODUCTO
        $PRODUCT_PRICE_MULTITPLE = ProductWallet::create([
            "product_id" => $PRODUCT->id,
            "type_client" => Str::upper($row["tipo_de_cliente"]) == 'CLIENTE FINAL' ? 1 : 2,
            "sucursale_id" => $sucursale->id,
            "unit_id" => $unit_price->id,
            "price" => $row["precio"]
        ]);
        return $PRODUCT;
    }

    public function  rules() : array 
    {
        return [
            "*.nombre_producto" => ['required'],
            "*.sku" => ['required'],
            "*.categoria" => ['required'],
            "*.imagen" => ['required'],
            "*.precio_general" => ['required'],
            "*.precio_empresa" => ['required'],
            "*.descripcion" => ['required'],
            "*.tipo_impuesto" => ['required'],
            "*.importe_iva" => ['required'],
            "*.estado" => ['required'],
            "*.dias_garantia" => ['required'],
            "*.disponibilidad" => ['required'],
            "*.unidad_almacen" => ['required'],
            "*.almacen" => ['required'],
            "*.stock" => ['required'],
            "*.umbral" => ['required'],
            "*.sucursal" => ['required'],
            "*.unidad_precio" => ['required'],
            "*.tipo_de_cliente" => ['required'],
            "*.precio" => ['required'],
        ];
    }
}
