<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $userId = auth('api')->check() ? auth('api')->id() : null;

        Log::info('CheckPermission middleware: verificando acceso a ruta', [
            'path' => $request->path(),
            'required_permission' => $permission,
            'user_id' => $userId,
            'header_permission' => $request->header('X-User-Permission')
        ]);

        if (!$userId) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $user = auth('api')->user();
        $headerPermission = $request->header('X-User-Permission');

        // Obtener todos los permisos del usuario
        $userPermissions = $user->roles
            ->flatMap(fn($role) => $role->permissions->pluck('name'))
            ->unique()
            ->values()
            ->toArray();

        Log::info('Verificando permisos para usuario', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'header_permission' => $headerPermission,
            'is_admin' => $user->hasRole('Admin'),
            'user_permissions' => $userPermissions
        ]);

        // Si el usuario es Admin, acceso completo
        if ($user->hasRole('Admin')) {
            Log::info('Usuario es Admin, concediendo acceso completo');
            $request->attributes->set('effective_permission', 'manage-products');
            return $next($request);
        }

        // Manejar múltiples permisos (separados por "|")
        $permissions = strpos($permission, '|') !== false
            ? array_map('trim', explode('|', $permission))
            : [trim($permission)];

        Log::info('Permisos requeridos', ['permissions' => $permissions]);

        // Verificar si el encabezado coincide con alguno de los permisos requeridos
        if ($headerPermission && in_array($headerPermission, $permissions) && in_array($headerPermission, $userPermissions)) {
            Log::info('Permiso concedido por encabezado', ['header_permission' => $headerPermission]);
            $request->attributes->set('effective_permission', $headerPermission);
            return $next($request);
        }

        // Verificar cada permiso individual
        foreach ($permissions as $singlePermission) {
            Log::info('Verificando permiso individual', [
                'permission' => $singlePermission,
                'has_permission' => in_array($singlePermission, $userPermissions)
            ]);

            if (in_array($singlePermission, $userPermissions)) {
                Log::info('Permiso concedido', ['permission' => $singlePermission]);
                $request->attributes->set('effective_permission', $singlePermission);
                return $next($request);
            }
        }

        // Acceso denegado
        Log::warning('Acceso denegado: sin permisos suficientes', [
            'user_id' => $user->id,
            'required_permissions' => $permissions,
            'user_permissions' => $userPermissions
        ]);

        return response()->json([
            'error' => 'Forbidden. You do not have the required permissions.',
            'required_permissions' => $permissions,
            'user_permissions' => $userPermissions
        ], 403);
    }
}
