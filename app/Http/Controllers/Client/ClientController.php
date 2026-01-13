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
     * Debug endpoint to check authentication and permissions
     */
    public function debug(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        if(!$user) {
            return response()->json([
                "authenticated" => false,
                "message" => "Usuario no autenticado"
            ]);
        }

        $canView = $user->can("list_client");
        $totalClients = Client::count();

        $clientsQuery = Client::query();

        if($user->role_id != 1){
            if($user->role_id == 2){
                if($user->sucursale_id) {
                    $clientsQuery->where("sucursale_id",$user->sucursale_id);
                }
            }else{
                $clientsQuery->where("user_id",$user->id);
            }
        }

        $filteredClients = $clientsQuery->count();

        return response()->json([
            "authenticated" => true,
            "user" => [
                "id" => $user->id,
                "name" => $user->name,
                "role_id" => $user->role_id,
                "sucursale_id" => $user->sucursale_id,
            ],
            "permissions" => [
                "can_list_client" => $canView,
                "has_list_client_permission" => $user->hasPermissionTo("list_client"),
            ],
            "clients" => [
                "total_in_db" => $totalClients,
                "available_for_user" => $filteredClients,
            ],
        ]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize("viewAny",Client::class);
        $search = $request->get("search") ?? '';
        $user = auth('api')->user();

        $clients = Client::where(function($query) use($search) {
                        if($search) {
                            $query->where(DB::raw("CONCAT(COALESCE(clients.full_name,''), COALESCE(clients.n_document,''), COALESCE(clients.phone,''), COALESCE(clients.email,''))"),"ilike","%".$search."%");
                        }
                    })
                    ->where(function($query) use($user){
                        if($user->role_id != 1){
                            if($user->role_id == 2){
                                if($user->sucursale_id) {
                                    $query->where("sucursale_id",$user->sucursale_id);
                                }
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
