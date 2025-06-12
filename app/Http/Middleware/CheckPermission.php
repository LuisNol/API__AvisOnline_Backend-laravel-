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
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        Log::info('CheckPermission middleware: verificando acceso a ruta', [
            'path' => $request->path(),
            'required_permission' => $permission,
            'user_id' => auth('api')->check() ? auth('api')->id() : null,
            'header_permission' => $request->header('X-User-Permission')
        ]);
        
        if (!auth('api')->check()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $user = auth('api')->user();
        $headerPermission = $request->header('X-User-Permission');
        
        Log::info('Verificando permisos para usuario', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'header_permission' => $headerPermission,
            'is_admin' => $user->hasRole('Admin'),
            'user_permissions' => $user->roles()->with('permissions')->get()->pluck('permissions.*.name')->flatten()->unique()->values()->toArray()
        ]);
        
        // Paso 1: Si el usuario es Admin, siempre tiene acceso
        if ($user->hasRole('Admin')) {
            Log::info('Usuario es Admin, concediendo acceso completo');
            $request->attributes->set('effective_permission', 'manage-products');
            return $next($request);
        }
        
        // Paso 2: Si se requieren varios permisos (formato: perm1|perm2)
        $permissions = [];
        if (strpos($permission, '|') !== false) {
            $permissions = explode('|', $permission);
            Log::info('Permisos múltiples requeridos', ['permissions' => $permissions]);
        } else {
            $permissions = [$permission];
            Log::info('Permiso único requerido', ['permission' => $permission]);
        }
        
        // Paso 3: Priorizar el permiso del encabezado si está en la lista de permisos requeridos
        if ($headerPermission && in_array($headerPermission, $permissions) && $user->hasPermission($headerPermission)) {
            Log::info('Permiso concedido por encabezado', ['header_permission' => $headerPermission]);
            $request->attributes->set('effective_permission', $headerPermission);
            return $next($request);
        }
        
        // Paso 4: Verificar cada permiso individual
        foreach ($permissions as $singlePermission) {
            $singlePermission = trim($singlePermission);
            
            Log::info('Verificando permiso individual', [
                'permission' => $singlePermission,
                'has_permission' => $user->hasPermission($singlePermission)
            ]);
            
            if ($user->hasPermission($singlePermission)) {
                Log::info('Permiso concedido', ['permission' => $singlePermission]);
                $request->attributes->set('effective_permission', $singlePermission);
                return $next($request);
            }
        }
        
        // Si llegamos aquí, el usuario no tiene ninguno de los permisos requeridos
        Log::warning('Acceso denegado: sin permisos suficientes', [
            'user_id' => $user->id,
            'required_permissions' => $permissions,
            'user_permissions' => $user->roles()->with('permissions')->get()->pluck('permissions.*.name')->flatten()->unique()->values()
        ]);
        
        return response()->json([
            'error' => 'Forbidden. You do not have the required permissions.',
            'required_permissions' => $permissions,
            'user_permissions' => $user->roles()->with('permissions')->get()->pluck('permissions.*.name')->flatten()->unique()->values()
        ], 403);
    }
}