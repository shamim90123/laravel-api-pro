<?php
// app/Http/Controllers/Api/PermissionController.php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    /**
     * Get all permissions with pagination and search
     */
    public function index(Request $request): JsonResponse
    {
        $query = Permission::query();

        if ($request->has('q') && !empty($request->q)) {
            $search = $request->q;
            $query->where('name', 'like', "%{$search}%");
        }

        // ✅ Use paginate instead of get()
        $permissions = $query->orderBy('name', 'asc')->paginate($request->get('per_page', 10));

        return response()->json($permissions);
    }
    /**
     * Get permission details
     */
    public function show($id): JsonResponse
    {
        $permission = Permission::findOrFail($id);
        return response()->json($permission);
    }

    /**
     * Create new permission
     */
    public function store(Request $request): JsonResponse
    {
        // return 453;
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'guard_name' => 'sometimes|string|max:255'
        ]);

        $permission = Permission::create([
            'name' => $request->name,
            'guard_name' => $request->guard_name ?? 'sanctum'
        ]);

        return response()->json([
            'message' => 'Permission created successfully',
            'permission' => $permission
        ], 201);
    }

    /**
     * Update permission
     */
    public function update(Request $request, $id): JsonResponse
    {
        $permission = Permission::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $id,
            'guard_name' => 'sometimes|string|max:255'
        ]);

        $permission->update([
            'name' => $request->name,
            'guard_name' => $request->guard_name ?? $permission->guard_name
        ]);

        return response()->json([
            'message' => 'Permission updated successfully',
            'permission' => $permission
        ]);
    }

    /**
     * Delete permission
     */
    public function destroy($id): JsonResponse
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return response()->json([
            'message' => 'Permission deleted successfully'
        ]);
    }

    /**
     * Get all permissions grouped by module
     */
    public function grouped(): JsonResponse
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0] ?? 'other';
        });

        return response()->json($permissions);
    }

    /**
     * Sync permissions to role
     */
    public function syncToRole(Request $request, $roleId): JsonResponse
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $role = Role::findOrFail($roleId);
        $permissionNames = Permission::whereIn('id', $request->permissions)->pluck('name');
        
        $role->syncPermissions($permissionNames);

        return response()->json([
            'message' => 'Permissions synced to role successfully'
        ]);
    }
}