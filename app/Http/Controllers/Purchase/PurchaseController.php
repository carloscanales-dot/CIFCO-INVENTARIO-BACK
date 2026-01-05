<?php

namespace App\Http\Controllers\Purchase;

use App\Models\Config\Unit;
use Illuminate\Http\Request;
use App\Models\Config\Provider;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Config\Warehouse;
use App\Models\Purchase\Purchase;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use App\Models\Purchase\PurchaseDetail;
use App\Http\Resources\Purchase\PurchaseResource;
use App\Http\Resources\Purchase\PurchaseCollection;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize("viewAny",Purchase::class);
        $search = $request->search;
        $warehouse_id = $request->warehouse_id;
        $unit_id = $request->unit_id;
        $provider_id = $request->provider_id;
        $type_comprobant = $request->type_comprobant;
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $search_product = $request->search_product;
        $user = auth('api')->user();
        $purchases = Purchase::filterAdvance($search,$warehouse_id,$unit_id,$provider_id,$type_comprobant,$start_date,$end_date,$search_product,$user)->orderBy("id","desc")->paginate(25);

        return response()->json([
            "total_page" => $purchases->lastPage(),
            "purchases" => PurchaseCollection::make($purchases),
        ]);
    }
    
    public function config() {
        $warehouses = Warehouse::where("state",1)->get();
        $units = Unit::select("id","name")->where("state",1)->get();
        $providers = Provider::where("state",1)->get();
        date_default_timezone_set("America/Lima");
        return response()->json([
            "warehouses" => $warehouses->map(function($warehouse) {
                return [
                    "id" => $warehouse->id,
                    "name" => $warehouse->name,
                    "sucursale_id" => $warehouse->sucursale_id
                ];
            }),
            "providers" => $providers->map(function($provider) {
                return [
                    "id" => $provider->id,
                    "full_name" => $provider->full_name,
                    "ruc" => $provider->ruc,
                ];
            }),
            "units" => $units,
            "today" => now()->format("Y-m-d")
        ]);
    }

    public function sale_pdf($id) {
        $purchase = Purchase::findOrFail($id);

        //$pdf = PDF::loadView("purchase.pdf_purchase",compact('purchase'));
        $pdf = Pdf::loadView("purchase.pdf_purchase", compact('purchase'));

        return $pdf->stream("purchase".$purchase->id.".pdf");
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // warehouse_id
        // provider_id
        // date_emision
        // type_comprobant
        // n_comprobant
        // purchase_details
        // description
        // total
        // importe
        // igv
        Gate::authorize("create",Purchase::class);
        $purchase = Purchase::create([
            "warehouse_id" => $request->warehouse_id,
            "user_id" => auth('api')->user()->id,
            "sucursale_id" => auth('api')->user()->sucursale_id,
            "date_emision" => $request->date_emision,
            "type_comprobant" => $request->type_comprobant,
            "n_comprobant" => $request->n_comprobant,
            "provider_id" => $request->provider_id,
            "total" => $request->total,
            "importe" => $request->importe,
            "igv" => $request->igv,
            "description" => $request->description,
        ]);

        $purchase_details = $request->purchase_details;

        foreach ($purchase_details as $key => $purchase_detail) {
            PurchaseDetail::create([
                "purchase_id" => $purchase->id,
                "product_id" => $purchase_detail["product"]["id"],
                "unit_id" => $purchase_detail["unit_id"],
                "price_unit" => $purchase_detail["price_unit"],
                "total" => $purchase_detail["total"],
                "quantity" => $purchase_detail["quantity"],
            ]);
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
        Gate::authorize("update",Purchase::class);
        $purchase = Purchase::findOrFail($id);

        return response()->json([
            "purchase" => PurchaseResource::make($purchase),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Gate::authorize("update",Purchase::class);
        $purchase = Purchase::findOrFail($id);

        $purchase->update([
            "provider_id" => $request->provider_id,
            "type_comprobant" => $request->type_comprobant,
            "n_comprobant" => $request->n_comprobant,
            "description" => $request->description,
        ]);

        return response()->json([
            "message" => 200
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize("delete",Purchase::class);
        $purchase = Purchase::findOrFail($id);
        if($purchase->state != 1){
            return response()->json([
                "message" => 403,
                "message_text" => "LA COMPRA NO SE PUEDE ELIMINAR PORQUE YA HA INICIADO SU PROCESO DE ENTREGA"
            ]);
        }
        $purchase->delete();
        return response()->json([
            "message" => 200,
        ]);
    }
}
