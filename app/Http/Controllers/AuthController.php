<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\UserSession;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;


class AuthController extends Controller
{

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register() {
        Gate::authorize('create',User::class);

        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = new User;
        $user->name = request()->name;
        $user->email = request()->email;
        $user->password = bcrypt(request()->password);
        $user->save();

        return response()->json($user, 201);
    }


    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Registrar la sesi�n del usuario
        $this->logUserSession($token);

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        Log::info('🔴 Iniciando proceso de logout');

        // Obtener el usuario ANTES de cerrar la sesión JWT
        $user = auth()->user();

        if (!$user) {
            Log::warning('⚠️ No hay usuario autenticado para cerrar sesión');
            auth()->logout();
            return response()->json(['message' => 'No user authenticated']);
        }

        Log::info('🔍 Usuario encontrado: ' . $user->id . ' - ' . $user->email);

        // Buscar TODAS las sesiones activas del usuario
        $activeSessions = UserSession::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        if ($activeSessions->count() > 0) {
            Log::info('📝 Sesiones activas encontradas: ' . $activeSessions->count());

            // Cerrar TODAS las sesiones activas del usuario
            foreach ($activeSessions as $session) {
                Log::info('   - Cerrando sesión ID: ' . $session->id . ' (Login: ' . $session->login_at . ')');
                $session->update([
                    'is_active' => false,
                    'logout_at' => now(),
                ]);
            }

            Log::info('✅ Todas las sesiones cerradas correctamente');
        } else {
            Log::warning('⚠️ No se encontró sesión activa para el usuario ' . $user->id);
        }

        // Ahora sí invalidar el token JWT
        auth()->logout();
        Log::info('✅ Token JWT invalidado correctamente');

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $permissions = auth('api')->user()->getAllPermissions()->map(function($permission) {
            return $permission->name;
        });
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            "user" => [
                "full_name" => auth('api')->user()->name.' '.auth('api')->user()->surname,
                "email" => auth('api')->user()->email,
                //"avatar" => auth('api')->user()->avatar ? env("APP_URL")."storage/".auth('api')->user()->avatar : NULL,
                "avatar" => auth('api')->user()->avatar ? asset('storage/'.auth('api')->user()->avatar) : NULL,
                "sucursale_id" => auth('api')->user()->sucursale_id,
                "sucursale" => [
                    "name" => auth('api')->user()->sucursale->name,
                ],
                "role" => [
                    "id" => auth('api')->user()->role->id,
                    "name" => auth('api')->user()->role->name
                ],
                "permissions" => $permissions,
            ]
        ]);
    }

    /**
     * Registrar la sesi�n del usuario
     */
    protected function logUserSession($token)
    {
        $userAgent = request()->header('User-Agent') ?? '';
        $ipAddress = request()->ip();

        UserSession::create([
            'user_id' => auth()->user()->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_type' => UserSession::detectDeviceType($userAgent),
            'browser' => UserSession::detectBrowser($userAgent),
            'platform' => UserSession::detectPlatform($userAgent),
            'login_at' => now(),
            'is_active' => true,
            'token_id' => substr($token, -10),
        ]);
    }

    /**
     * Cerrar la sesi�n activa del usuario
     */
    protected function closeUserSession()
    {
        $user = auth()->user();

        if ($user) {
            UserSession::where('user_id', $user->id)
                ->where('is_active', true)
                ->orderBy('login_at', 'desc')
                ->first()
                ?->update([
                    'is_active' => false,
                    'logout_at' => now(),
                ]);
        }
    }
}
