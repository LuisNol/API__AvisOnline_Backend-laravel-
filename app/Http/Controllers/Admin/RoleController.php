<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::with(['permissions', 'users'])->get();
        
        return response()->json([
            'recordsTotal' => count($roles),
            'recordsFiltered' => count($roles),
            'data' => $roles
        ]);
    }

    public function show($id)
    {
        $role = Role::with(['permissions', 'users'])->findOrFail($id);
        return response()->json($role);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        if ($request->has('permissions') && is_array($request->permissions)) {
            $role->permissions()->sync($request->permissions);
        }

        return response()->json($role->load('permissions'), 201);
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$id,
        ]);

        $role->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        if ($request->has('permissions') && is_array($request->permissions)) {
            $role->permissions()->sync($request->permissions);
        }

        return response()->json($role->load('permissions'));
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        
        // Verificar que no sea un rol crítico del sistema
        if(in_array($role->name, ['Admin', 'usuario'])){
            return response()->json(['error' => 'No se puede eliminar un rol del sistema'], 400);
        }
        
        // Verificar si tiene usuarios asignados
        if($role->users()->count() > 0){
            return response()->json(['error' => 'No se puede eliminar un rol que tiene usuarios asignados'], 400);
        }
        
        // Desasociar permisos antes de eliminar
        $role->permissions()->detach();
        
        // Eliminación física permanente
        $role->forceDelete();

        return response()->json(null, 204);
    }

    public function getUsers($id, Request $request)
    {
        $role = Role::findOrFail($id);
        $users = $role->users;
        
        return response()->json([
            'recordsTotal' => count($users),
            'recordsFiltered' => count($users),
            'data' => $users
        ]);
    }

    public function deleteUser($role_id, $user_id)
    {
        $role = Role::findOrFail($role_id);
        $user = User::findOrFail($user_id);
        
        $role->users()->detach($user_id);
        
        return response()->json(null, 204);
    }
} 