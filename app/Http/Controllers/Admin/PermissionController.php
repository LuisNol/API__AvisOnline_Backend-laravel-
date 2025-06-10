<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $permissions = Permission::all();
        
        return response()->json([
            'recordsTotal' => count($permissions),
            'recordsFiltered' => count($permissions),
            'data' => $permissions
        ]);
    }

    public function show($id)
    {
        $permission = Permission::findOrFail($id);
        return response()->json($permission);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions',
        ]);

        $permission = Permission::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json($permission, 201);
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,'.$id,
        ]);

        $permission->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json($permission);
    }

    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        
        // Verificar que no sea un permiso crítico del sistema
        $criticalPermissions = ['manage-users', 'manage-products', 'manage-own-products'];
        if(in_array($permission->name, $criticalPermissions)){
            return response()->json(['error' => 'No se puede eliminar un permiso crítico del sistema'], 400);
        }
        
        // Verificar si está asignado a algún rol
        if($permission->roles()->count() > 0){
            return response()->json(['error' => 'No se puede eliminar un permiso que está asignado a roles'], 400);
        }
        
        // Eliminación física permanente
        $permission->forceDelete();

        return response()->json(null, 204);
    }
} 