<?php

namespace App\Http\Controllers\Client;

use Illuminate\Http\Request;
use App\Models\Client\Client;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\Cliente\ClienteResource;
use App\Http\Resources\Cliente\ClienteCollection;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize("viewAny",Client::class);
        $search = $request->get("search");
        $user = auth('api')->user();
        $clients = Client::where(DB::raw("clients.full_name || '' || clients.n_document || '' || clients.phone || '' || COALESCE(clients.email,'')"),"ilike","%".$search."%")
                    ->where(function($query) use($user){
                        if($user->role_id != 1){
                            if($user->role_id == 2){
                                $query->where("sucursale_id",$user->surcursale_id);
                            }else{
                                $query->where("user_id",$user->id);
                            }
                        }
                    })
                    ->orderBy("id","desc")
                    ->paginate(15);

        return response()->json([
            "total_page" => $clients->lastPage(),
            "clients" => ClienteCollection::make($clients),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize("create",Client::class);
        $exits_client_full_name = Client::where("full_name",$request->full_name)->first();
        if($exits_client_full_name){
            return response()->json([
                "message" => 403,
                "message_text" => "EL CLIENTE YA EXISTE. CAMBIAR EL NOMBRE"
            ]);
        }
        $exits_client_n_document = Client::where("n_document",$request->n_document)->first();
        if($exits_client_n_document){
            return response()->json([
                "message" => 403,
                "message_text" => "EL CLIENTE YA EXISTE. CAMBIAR EL N° DE DOCUMENTO"
            ]);
        }

        $request->request->add(["user_id" => auth('api')->user()->id]);
        $request->request->add(["sucursale_id" => auth('api')->user()->sucursale_id]);
        $client = Client::create($request->all());

        return response()->json([
            "message" => 200,
            "client" => ClienteResource::make($client),
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
        Gate::authorize("update",Client::class);
        $exits_client_full_name = Client::where("full_name",$request->full_name)->where("id","<>",$id)->first();
        if($exits_client_full_name){
            return response()->json([
                "message" => 403,
                "message_text" => "EL CLIENTE YA EXISTE. CAMBIAR EL NOMBRE"
            ]);
        }
        $exits_client_n_document = Client::where("n_document",$request->n_document)->where("id","<>",$id)->first();
        if($exits_client_n_document){
            return response()->json([
                "message" => 403,
                "message_text" => "EL CLIENTE YA EXISTE. CAMBIAR EL N° DE DOCUMENTO"
            ]);
        }

        $client = Client::findOrFail($id);
        $client->update($request->all());

        return response()->json([
            "message" => 200,
            "client" => ClienteResource::make($client),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize("delete",Client::class);
        $client = Client::findOrFail($id);
        $client->delete();
        return response()->json([
            "message" => 200,
        ]);
    }
}
