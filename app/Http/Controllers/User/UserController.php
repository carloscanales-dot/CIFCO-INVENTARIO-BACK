<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Config\Sucursale;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize("viewAny",User::class);
        $search = $request->get("search");

        $users = User::where(DB::raw("users.name || ' ' || COALESCE(users.surname,'') || ' ' || users.email || ' ' || COALESCE(users.phone,'')"),"ilike","%".$search."%")->orderBy("id","desc")->get();

        return response()->json([
            "users" => $users->map(function($user) {
                return [
                    "id" => $user->id,
                    "name" => $user->name,
                    "surname" => $user->surname,
                    "full_name" => $user->name . ' ' . $user->surname,
                    "email" => $user->email,
                    "role_id" => (int) $user->role_id,
                    "role" => [
                        "name" => $user->role?->name,
                    ],
                    "phone" => $user->phone,
                    "state" => $user->state,
                    "sucursale_id" => (int) $user->sucursale_id,
                    "sucursale" => [
                        "name" => $user->sucursale?->name,
                    ],
                    //"avatar" => $user->avatar ? env("APP_URL")."storage/".$user->avatar : NULL,
                    "avatar" => $user->avatar ? asset('storage/'.$user->avatar) : NULL,
                    "type_document" =>$user->type_document,
                    "n_document" =>$user->n_document,
                    "gender" =>$user->gender,
                    "created_at" => $user->created_at->format("Y-m-d h:i A"),
                ];
            }),
        ]);
    }

    public function config(){
        $sucursales = Sucursale::all();
        $roles = Role::all();

        return response()->json([
            "sucursales" => $sucursales->map(function($sucursal) {
                return [
                    "id" => $sucursal->id,
                    "name" => $sucursal->name,
                ];
            }),
            "roles" => $roles->map(function($rol) {
                return [
                    "id" => $rol->id,
                    "name" => $rol->name,
                ];
            }),
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize("create",User::class);
        $is_user_exists = User::where("email",$request->email)->first();
        if($is_user_exists){
            return response()->json([
                "message" => 403,
                "message_text" => "EL USUARIO YA EXISTE"
            ]);
        }

        if($request->hasFile("imagen")){
            $path = Storage::putFile("users",$request->file("imagen"));
            $request->request->add(["avatar" => $path]);
        }
        if($request->password){
            $request->request->add(["password" => bcrypt($request->password)]);
        }
        $user = User::create($request->all());
        $role = Role::findOrFail($request->role_id);
        $user->assignRole($role);

        return response()->json([
            "message" => 200,
            "user" => [
                "id" => $user->id,
                "name" => $user->name,
                "surname" => $user->surname,
                "full_name" => $user->name . ' ' . $user->surname,
                "email" => $user->email,
                "role_id" => (int) $user->role_id,
                "state" => $user->state,
                "role" => [
                    "name" => $user->role?->name,
                ],
                "phone" => $user->phone,
                "sucursale_id" => (int) $user->sucursale_id,
                "sucursale" => [
                    "name" => $user->sucursale?->name,
                ],
                //"avatar" => $user->avatar ? env("APP_URL")."storage/".$user->avatar : NULL,
                "avatar" => $user->avatar ? asset('storage/'.$user->avatar) : NULL,
                "type_document" =>$user->type_document,
                "n_document" =>$user->n_document,
                "gender" =>$user->gender,
                "created_at" => $user->created_at->format("Y-m-d h:i A"),
            ],
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
        Gate::authorize("update",User::class);
        // laravest@gmail.com
        // Jaico Mendoza
        // Jose
        $is_user_exists = User::where("email",$request->email)->where("id","<>",$id)->first();//
        if($is_user_exists){
            return response()->json([
                "message" => 403,
                "message_text" => "EL USUARIO YA EXISTE"
            ]);
        }
        $user = User::findOrFail($id);

        // Guardar el role_id anterior ANTES de actualizar
        $old_role_id = $user->role_id;

        if($request->hasFile("imagen")){
            if($user->avatar){
                Storage::delete($user->avatar);
            }
            $path = Storage::putFile("users",$request->file("imagen"));
            $request->request->add(["avatar" => $path]);
        }
        if($request->password){
            $request->request->add(["password" => bcrypt($request->password)]);
        }
        $user->update($request->all());

        // Sincronizar con Spatie Permission si el rol cambió
        if($request->role_id && $request->role_id != $old_role_id){
            // Remover rol anterior
            $user->syncRoles([]); // Limpia todos los roles

            // Asignar nuevo rol
            $role_new = Role::findOrFail($request->role_id);
            $user->assignRole($role_new);

            // Limpiar caché de permisos
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        }

        return response()->json([
            "message" => 200,
            "user" => [
                "id" => $user->id,
                "name" => $user->name,
                "surname" => $user->surname,
                "full_name" => $user->name . ' ' . $user->surname,
                "email" => $user->email,
                "role_id" => (int) $user->role_id,
                "role" => [
                    "name" => $user->role?->name,
                ],
                "phone" => $user->phone,
                "state" => $user->state,
                "sucursale_id" => (int) $user->sucursale_id,
                "sucursale" => [
                    "name" => $user->sucursale?->name,
                ],
                //"avatar" => $user->avatar ? env("APP_URL")."storage/".$user->avatar : NULL,
                "avatar" => $user->avatar ? asset('storage/'.$user->avatar) : NULL,
                "type_document" =>$user->type_document,
                "n_document" =>$user->n_document,
                "gender" =>$user->gender,
                "created_at" => $user->created_at->format("Y-m-d h:i A"),
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize("delete",User::class);
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json([
            "message" => 200
        ]);
    }
}
