<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('roles')->get();
        
        return response()->json([
            'recordsTotal' => count($users),
            'recordsFiltered' => count($users),
            'data' => $users
        ]);
    }

    public function show($id)
    {
        $user = User::with('roles')->findOrFail($id);
        return response()->json($user);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
            ]);

            // Crea el usuario con type_user = "admin" para acceso al panel
            // pero con rol "usuario" para permisos limitados (AvisOnline)
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'type_user' => 'admin' // Permite acceso al panel de administración
            ]);

            // Asigna el rol de usuario por defecto (ajusta el nombre si es necesario)
            $defaultRole = Role::where('name', 'usuario')->first();
            if ($defaultRole) {
                $user->roles()->sync([$defaultRole->id]);
            }

            return response()->json($user->load('roles'), 201);
        } catch (\Exception $e) {
            \Log::error('Error creando usuario: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,'.$id,
            ]);

            $data = [
                'name' => $request->name,
                'email' => $request->email,
            ];

            if ($request->filled('type_user')) {
                // Convertir type_user a entero si es necesario
                $typeUserMap = [
                    'ADMIN' => 1,
                    'CLIENT' => 2
                ];
                
                $typeUser = $request->type_user;
                if (isset($typeUserMap[$typeUser])) {
                    $data['type_user'] = $typeUserMap[$typeUser];
                } else if (is_numeric($typeUser)) {
                    $data['type_user'] = (int)$typeUser;
                }
            }

            if ($request->filled('password') && !empty($request->password)) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

            // Log de depuración
            \Log::info('Roles recibidos: ', ['roles' => $request->roles]);

            if ($request->has('roles')) {
                $roles = $request->roles;
                
                // Si roles no es un array pero es una cadena JSON, intentamos decodificarlo
                if (!is_array($roles) && is_string($roles)) {
                    $roles = json_decode($roles, true);
                }
                
                // Si ahora es un array válido, procedemos
                if (is_array($roles)) {
                    // Aseguramos que todos los IDs sean enteros válidos
                    $validRoles = [];
                    foreach ($roles as $roleId) {
                        if (is_numeric($roleId)) {
                            $validRoles[] = (int)$roleId;
                        }
                    }
                    $user->roles()->sync($validRoles);
                }
            }

            return response()->json($user->load('roles'));
        } catch (\Exception $e) {
            \Log::error('Error actualizando usuario: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Verificar que no se pueda eliminar el usuario logueado
        if($user->id == auth('api')->user()->id){
            return response()->json(['error' => 'No puedes eliminarte a ti mismo'], 400);
        }
        
        // Verificar que no sea el último administrador
        if($user->hasRole('Admin')){
            $adminCount = User::whereHas('roles', function($query){
                $query->where('name', 'Admin');
            })->count();
            
            if($adminCount <= 1){
                return response()->json(['error' => 'No se puede eliminar el último administrador'], 400);
            }
        }
        
        // Desasociar roles antes de eliminar
        $user->roles()->detach();
        
        // Eliminación física permanente
        $user->forceDelete();

        return response()->json(null, 204);
    }

    public function assignRole($userId, $roleId)
    {
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($roleId);
        
        $user->roles()->syncWithoutDetaching([$roleId]);
        
        return response()->json($user->load('roles'));
    }

    public function removeRole($userId, $roleId)
    {
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($roleId);
        
        $user->roles()->detach($roleId);
        
        return response()->json($user->load('roles'));
    }
} 