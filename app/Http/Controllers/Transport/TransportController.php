<?php

namespace App\Http\Controllers\Transport;

use App\Models\Config\Unit;
use Illuminate\Http\Request;
use App\Models\Config\Warehouse;
use App\Models\Transport\Transport;
use App\Http\Controllers\Controller;
use App\Models\Transport\TransportDetail;
use App\Http\Resources\Transport\TransportResource;
use App\Http\Resources\Transport\TransportCollection;
use Barryvdh\DomPDF\Facade\PDF;

class TransportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;
        $warehouse_start_id = $request->warehouse_start_id;
        $warehouse_end_id = $request->warehouse_end_id;
        $unit_id = $request->unit_id;
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $search_product = $request->search_product;

        $transports = Transport::filterAdvance($search,$warehouse_start_id,$warehouse_end_id,$unit_id,$start_date,$end_date,$search_product)->orderBy("id","desc")->paginate(25);

        return response()->json([
            "total_page" => $transports->lastPage(),
            "transports" => TransportCollection::make($transports),
        ]);
    }

    public function config() {
        $warehouses = Warehouse::where("state",1)->get();
        $units = Unit::select("id","name")->where("state",1)->get();
        date_default_timezone_set("America/Lima");
        return response()->json([
            "warehouses" => $warehouses->map(function($warehouse) {
                return [
                    "id" => $warehouse->id,
                    "name" => $warehouse->name,
                    "sucursale_id" => $warehouse->sucursale_id
                ];
            }),
            "units" => $units,
            "today" => now()->format("Y-m-d")
        ]);
    }

    public function transport_pdf($id){

        $transport = Transport::findOrFail($id);

        $pdf = PDF::loadView("transport.pdf_transport",compact('transport'));

        return $pdf->stream("transport-".$transport->id.".pdf");
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $transport = Transport::create([
            "warehouse_start_id"  => $request->warehouse_start_id,
            "warehouse_end_id"  => $request->warehouse_end_id,
            "user_id" => auth('api')->user()->id,
            "date_emision" => $request->date_emision,
            "total" => $request->total,
            "importe" => $request->importe,
            "igv" => $request->igv,
            "description" => $request->description,
        ]);

        $transport_details = $request->transport_details;

        foreach ($transport_details as $key => $transport_detail) {
            TransportDetail::create([
                "transport_id" => $transport->id,
                "product_id" => $transport_detail["product"]["id"],
                "unit_id" => $transport_detail["unit_id"],
                "price_unit" => $transport_detail["price_unit"],
                "total" => $transport_detail["total"],
                "quantity" => $transport_detail["quantity"],
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
        $transport = Transport::findOrFail($id);
        return response()->json([
            "transport" => TransportResource::make($transport),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $transport = Transport::findOrFail($id);
        if($request->state >= 3 && $transport->state < 3){
            $n_details = TransportDetail::where("transport_id",$id)->count();
            $n_state_salida = TransportDetail::where("transport_id",$id)->where("state",2)->count();

            if($n_details != $n_state_salida){
                return response()->json([
                    "message" => 403,
                    "message_text" => "No puedes cambiar de estado porque aun tus productos se encuentran en pendiente",
                ]);
            }
        }
        date_default_timezone_set('America/Lima');
        if($transport->state < 3 && $request->state == 3){
            $transport->update([
                "date_salida" => now(),
            ]);
        }
        if($transport->state < 6 && $request->state == 6){
            $transport->update([
                "date_entrega" => now(),
            ]);
        }
        if($request->state == 6){
            $n_details = TransportDetail::where("transport_id",$id)->count();
            $n_state_entrega = TransportDetail::where("transport_id",$id)->where("state",3)->count();

            if($n_details != $n_state_entrega){
                return response()->json([
                    "message" => 403,
                    "message_text" => "No puedes cambiar de estado porque aun tus productos se encuentran en salida",
                ]);
            }
        }
        if($transport->state >= 3){
            if($transport->warehouse_start_id != $request->warehouse_start_id){
                return response()->json([
                    "message" => 403,
                    "message_text" => "No puedes cambiar el almacen de atención"
                ]);
            }
            if($transport->warehouse_end_id != $request->warehouse_end_id){
                return response()->json([
                    "message" => 403,
                    "message_text" => "No puedes cambiar el almacen de recepción"
                ]);
            }
        }
        $transport->update([
            "warehouse_start_id" => $request->warehouse_start_id,
            "warehouse_end_id" => $request->warehouse_end_id,
            "description" => $request->description,
            "state" => $request->state,
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
        $transport = Transport::findOrFail($id);
        if($transport->state >= 3){
            return response()->json([
                "message" => 403,
                "message_text" => "LA SOLICITUD DE TRANSPORTE NO SE PUEDE ELIMINAR PORQUE YA HA INICIADO SU PROCESO DE ENTREGA"
            ]);
        }
        $transport->delete();
        return response()->json([
            "message" => 200,
        ]);
    }
}
