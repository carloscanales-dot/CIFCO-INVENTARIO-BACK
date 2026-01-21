<?php

namespace App\Http\Controllers;

use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\UserSessionResource;

class UserSessionController extends Controller
{
    /**
     * Obtener el listado de sesiones de usuarios
     * Solo accesible para administradores
     */
    public function index(Request $request)
    {
        // Verificar que el usuario tenga el permiso (solo administradores)
        $user = auth()->user();
        $roleName = $user->role ? $user->role->name : null;

        if (!in_array($roleName, ['Super-Admin', 'Admin'])) {
            return response()->json([
                'message' => 'No tienes permisos para acceder a esta información'
            ], 403);
        }

        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $status = $request->input('status'); // 'active' o 'all'

        $query = UserSession::with('user')
            ->orderBy('login_at', 'desc');

        // Filtrar por estado
        if ($status === 'active') {
            $query->active();
        }

        // Búsqueda por nombre de usuario o email
        if ($search) {
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        $sessions = $query->paginate($perPage);

        return UserSessionResource::collection($sessions);
    }

    /**
     * Obtener estadísticas de sesiones
     */
    public function stats()
    {
        $user = auth()->user();
        $roleName = $user->role ? $user->role->name : null;

        if (!in_array($roleName, ['Super-Admin', 'Admin'])) {
            return response()->json([
                'message' => 'No tienes permisos para acceder a esta información'
            ], 403);
        }

        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        $stats = [
            'active_sessions' => UserSession::active()->count(),
            'today_logins' => UserSession::where('login_at', '>=', $today)->count(),
            'week_logins' => UserSession::where('login_at', '>=', $thisWeek)->count(),
            'month_logins' => UserSession::where('login_at', '>=', $thisMonth)->count(),
            'total_sessions' => UserSession::count(),
            'top_browsers' => UserSession::selectRaw('browser, COUNT(*) as count')
                ->groupBy('browser')
                ->orderByDesc('count')
                ->limit(5)
                ->get(),
            'top_platforms' => UserSession::selectRaw('platform, COUNT(*) as count')
                ->groupBy('platform')
                ->orderByDesc('count')
                ->limit(5)
                ->get(),
            'device_types' => UserSession::selectRaw('device_type, COUNT(*) as count')
                ->groupBy('device_type')
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Cerrar sesión manualmente (marcar como inactiva)
     */
    public function closeSession($id)
    {
        $user = auth()->user();
        $roleName = $user->role ? $user->role->name : null;

        if (!in_array($roleName, ['Super-Admin', 'Admin'])) {
            return response()->json([
                'message' => 'No tienes permisos para realizar esta acción'
            ], 403);
        }

        $session = UserSession::findOrFail($id);
        $session->update([
            'is_active' => false,
            'logout_at' => now(),
        ]);

        return response()->json([
            'message' => 'Sesión cerrada exitosamente',
            'session' => new UserSessionResource($session)
        ]);
    }
}
