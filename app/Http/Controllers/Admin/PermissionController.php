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
        $permission->delete();

        return response()->json(null, 204);
    }
} 