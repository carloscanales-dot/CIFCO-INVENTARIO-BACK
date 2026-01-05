<?php
 
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Kpi\KpiController;
use App\Http\Controllers\Sale\SaleController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\Config\UnitController;
use App\Http\Controllers\Client\ClientController;
use App\Http\Controllers\Config\ProviderController;
use App\Http\Controllers\Config\SucursalController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Sale\SaleDetailController;
use App\Http\Controllers\Config\WarehouseController;
use App\Http\Controllers\Sale\SalePaymentController;
use App\Http\Controllers\Purchase\PurchaseController;
use App\Http\Controllers\Product\ConversionController;
use App\Http\Controllers\Transport\TransportController;
use App\Http\Controllers\Kardex\KardexProductController;
use App\Http\Controllers\Config\UnitConversionController;
use App\Http\Controllers\Product\ProductWalletController;
use App\Http\Controllers\Sale\SaleRefounProductController;
use App\Http\Controllers\Config\ProductCategorieController;
use App\Http\Controllers\Purchase\PurchaseDetailController;
use App\Http\Controllers\Product\ProductWarehouseController;
use App\Http\Controllers\Transport\TransportDetailController;
 
Route::group([
    // 'middleware' => 'api',
    'prefix' => 'auth',
    // 'middleware' => ['auth:api','permission:delete articles'],
], function ($router) {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api')->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api')->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->middleware('auth:api')->name('me');
});

Route::group([
    "middleware" => ["auth:api"]
],function($router) {
    
    Route::resource("role",RoleController::class);

    Route::get("users/config",[UserController::class,'config']);
    Route::post("users/{id}",[UserController::class,'update']);
    Route::resource("users",UserController::class);

    Route::group(['middleware' => ['permission:settings']],function() {
        Route::resource("sucursales",SucursalController::class);
        Route::resource("warehouses",WarehouseController::class);
        Route::post("categories/{id}",[ProductCategorieController::class,'update']);
        Route::resource("categories",ProductCategorieController::class);
        Route::post("providers/{id}",[ProviderController::class,'update']);
        Route::resource("providers",ProviderController::class);
        Route::resource("units",UnitController::class);
        Route::resource("unit-conversions",UnitConversionController::class);
    });


    Route::get("products/config",[ProductController::class,"config"]);
    Route::post("products/index",[ProductController::class,"index"]);
    Route::post("products/import-excel",[ProductController::class,'import_excel']);
    Route::post("products/{id}",[ProductController::class,'update']);
    Route::resource("products",ProductController::class);

    Route::group(['middleware' => ['permission:show_inventory_product']],function() {
        Route::resource("product-warehouse",ProductWarehouseController::class);
    });
    Route::group(['middleware' => ['permission:show_wallet_price_product']],function() {
        Route::resource("product-wallet",ProductWalletController::class);
    });

    Route::resource("clients",ClientController::class);

    Route::get("sales/config",[SaleController::class,"config"]);
    Route::get("sales/search_client",[SaleController::class,"search_client"]);
    Route::get("sales/search_product",[SaleController::class,"search_product"]);
    Route::post("sales/index",[SaleController::class,"index"]);
    Route::post("stock_attention_detail",[SaleController::class,"stock_attention_detail"]);
    Route::resource("sales",SaleController::class);
    Route::resource("sale_details",SaleDetailController::class);
    Route::resource("sale_payments",SalePaymentController::class);

    Route::group(['middleware' => ['permission:return']],function() {
        Route::post("refound_products/index",[SaleRefounProductController::class,"index"]);
        Route::get("refound_products/search-sale/{sale_id}",[SaleRefounProductController::class,"search_sale"]);
        Route::resource("refound_products",SaleRefounProductController::class);
    });

    Route::get("purchase/config",[PurchaseController::class,"config"]);
    Route::post("purchase/index",[PurchaseController::class,"index"]);
    Route::resource("purchase",PurchaseController::class);
    Route::post("purchase_details/attention",[PurchaseDetailController::class,"attention_detail"]);
    Route::resource("purchase_details",PurchaseDetailController::class);

    Route::get("transports/config",[TransportController::class,"config"]);
    Route::post("transports/index",[TransportController::class,"index"]);
    Route::resource("transports",TransportController::class);
    Route::post("transport_details/attention_exit",[TransportDetailController::class,"attention_exit"]);
    Route::post("transport_details/attention_in",[TransportDetailController::class,"attention_in"]);
    Route::resource("transport_details",TransportDetailController::class);

    Route::group(['middleware' => ['permission:conversions']],function() {
        Route::post("conversions/index",[ConversionController::class,"index"]);
        Route::resource("conversions",ConversionController::class);
    });

    Route::group(['middleware' => ['permission:kardex']],function() {
        Route::post("kardex_products",[KardexProductController::class,"kardex_products"]);
    });


    Route::group(["prefix" => "kpi",'middleware' => ['permission:dashboard']],function(){
        Route::post("information_general",[KpiController::class,"information_general"]);
        Route::post("asesor_most_sales",[KpiController::class,"asesor_most_sales"]);
        Route::post("sales_payment_total_pending",[KpiController::class,"sales_payment_total_pending"]);
        Route::post("sucursales_reporte_sales",[KpiController::class,"sucursales_reporte_sales"]);
        Route::post("client_most_sales",[KpiController::class,"client_most_sales"]);
        Route::post("sales_x_month_of_year",[KpiController::class,"sales_x_month_of_year"]);
        Route::post("categories_most_sales",[KpiController::class,"categories_most_sales"]);
    });
});
Route::get("/products-excel",[ProductController::class,'download_excel']);
Route::get("/sales-excel",[SaleController::class,'download_excel']);
Route::get("/sales-pdf/{id}",[SaleController::class,'sale_pdf']);
Route::get("/purchase-pdf/{id}",[PurchaseController::class,'sale_pdf']);
Route::get("transport-pdf/{id}",[TransportController::class,'transport_pdf']);