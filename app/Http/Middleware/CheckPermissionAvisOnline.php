<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissionAvisOnline
{
    /**
     * Handle an incoming request for AvisOnline.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        Log::info('CheckPermissionAvisOnline: verificando acceso', [
            'path' => $request->path(),
            'method' => $request->method(),
            'required_permission' => $permission,
            'user_id' => auth('api')->check() ? auth('api')->id() : null,
            'user_agent' => $request->userAgent()
        ]);
        
        // Verificar autenticación
        if (!auth('api')->check()) {
            Log::warning('Acceso denegado: usuario no autenticado', [
                'path' => $request->path(),
                'ip' => $request->ip()
            ]);
            return response()->json(['error' => 'No autenticado.'], 401);
        }

        $user = auth('api')->user();
        
        Log::info('Usuario autenticado verificando permisos', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'required_permission' => $permission,
            'user_roles' => $user->roles->pluck('name')->toArray()
        ]);
        
        // ====================================
        // VERIFICACIÓN DE PERMISOS ESPECÍFICOS
        // ====================================
        
        // 1. Si el usuario tiene permiso full-admin, siempre tiene acceso
        if ($user->hasPermission('full-admin')) {
            Log::info('Acceso concedido: usuario con permiso full-admin');
            $request->attributes->set('effective_permission', 'full-admin');
            $request->attributes->set('user_type', 'super_admin');
            return $next($request);
        }
        
        // 2. Parsear permisos múltiples (formato: perm1|perm2|perm3)
        $allowedPermissions = explode('|', $permission);
        $allowedPermissions = array_map('trim', $allowedPermissions);
        
        Log::info('Verificando permisos requeridos', [
            'allowed_permissions' => $allowedPermissions,
            'user_permissions' => $this->getUserPermissions($user)
        ]);
        
        // 3. Verificar cada permiso
        foreach ($allowedPermissions as $singlePermission) {
            if ($user->hasPermission($singlePermission)) {
                Log::info('Acceso concedido por permiso específico', [
                    'permission' => $singlePermission,
                    'user_id' => $user->id
                ]);
                
                $request->attributes->set('effective_permission', $singlePermission);
                $request->attributes->set('user_type', $this->getUserType($singlePermission));
                
                // Filtrar datos según el permiso específico
                $this->setupRequestFilters($request, $singlePermission, $user);
                
                return $next($request);
            }
        }
        
        // 4. Si llegamos aquí, el usuario no tiene permisos
        Log::warning('Acceso denegado: permisos insuficientes', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'required_permissions' => $allowedPermissions,
            'user_permissions' => $this->getUserPermissions($user),
            'path' => $request->path()
        ]);
        
        return response()->json([
            'error' => 'Acceso denegado. No tienes los permisos necesarios.',
            'required_permissions' => $allowedPermissions,
            'user_permissions' => $this->getUserPermissions($user),
            'message' => 'Contacta al administrador si crees que esto es un error.'
        ], 403);
    }
    
    /**
     * Obtener lista de permisos del usuario
     */
    private function getUserPermissions($user): array
    {
        return $user->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions.*.name')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();
    }
    
    /**
     * Determinar el tipo de usuario según el permiso
     */
    private function getUserType(string $permission): string
    {
        switch ($permission) {
            case 'full-admin':
                return 'super_admin';
            case 'manage-users':
            case 'manage-all-announcements':
            case 'manage-categories':
            case 'manage-sliders':
                return 'admin';
            case 'manage-own-announcements':
                return 'user';
            default:
                return 'user';
        }
    }
    
    /**
     * Configurar filtros de request según el permiso
     */
    private function setupRequestFilters(Request $request, string $permission, $user): void
    {
        switch ($permission) {
            case 'manage-own-announcements':
                // Para usuarios normales, filtrar solo sus propios anuncios
                $request->attributes->set('filter_by_user_id', $user->id);
                $request->attributes->set('can_only_view_own', true);
                Log::info('Filtro aplicado: solo anuncios propios', ['user_id' => $user->id]);
                break;
                
            case 'manage-all-announcements':
                // Para admins de anuncios, pueden ver todos
                $request->attributes->set('can_view_all_announcements', true);
                Log::info('Filtro aplicado: todos los anuncios');
                break;
                
            case 'full-admin':
                // Super admin puede ver todo
                $request->attributes->set('can_view_everything', true);
                Log::info('Filtro aplicado: acceso total');
                break;
        }
    }
    
    /**
     * Verificar si el usuario puede acceder a un recurso específico
     */
    public static function canAccessResource($user, $resourceOwnerId, $permission): bool
    {
        // Super admin siempre puede acceder
        if ($user->hasPermission('full-admin')) {
            return true;
        }
        
        // Si el permiso permite gestionar todos los recursos
        if ($user->hasPermission('manage-all-announcements') && 
            in_array($permission, ['manage-all-announcements', 'manage-own-announcements'])) {
            return true;
        }
        
        // Si solo puede gestionar sus propios recursos
        if ($user->hasPermission('manage-own-announcements') && 
            $permission === 'manage-own-announcements') {
            return $user->id == $resourceOwnerId;
        }
        
        return false;
    }
} 