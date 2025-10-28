<?php
// app/Http/Controllers/Api/RoleController.php
namespace App\Http\Controllers\Api\Leads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function __construct()
    {
        // Spatie permission gates per action
        $this->middleware('permission:roles.view')->only(['index', 'show']);
        $this->middleware('permission:roles.create')->only(['store']);
        $this->middleware('permission:roles.update')->only(['update']);
        $this->middleware('permission:roles.delete')->only(['destroy']);
    }

    private function guard(): string
    {
        return config('permission.defaults.guard') ?? config('permission.default_guard') ?? 'web';
    }

    public function permissionsIndex()
    {
        return Permission::where('guard_name', $this->guard())->orderBy('name')->pluck('name');
    }

    public function names()
    {
        return Role::where('guard_name', $this->guard())->orderBy('name')->pluck('name');
    }

    public function index(Request $r)
    {
        $q = trim((string)$r->query('q', ''));
        $perPage = (int)$r->query('per_page', 10);

        $roles = Role::query()
            ->where('guard_name', $this->guard())
            ->when($q, fn($qq) => $qq->where('name', 'like', "%{$q}%"))
            ->with(['permissions' => function ($q) {
                $q->select('permissions.id', 'name');
            }])
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'data' => $roles->items(),
            'meta' => [
                'total'      => $roles->total(),
                'per_page'   => $roles->perPage(),
                'current'    => $roles->currentPage(),
                'last_page'  => $roles->lastPage(),
            ],
        ]);
    }

    public function show(Role $role)
    {
        // ensure correct guard
        abort_unless($role->guard_name === $this->guard(), 404);
        return $role->load('permissions:id,name');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required','string','max:100', Rule::unique('roles','name')],
            'permissions'  => ['array'],
            'permissions.*'=> ['string'],
        ]);

        $guard = $this->guard();

        $role = Role::create([
            'name'       => $data['name'],
            'guard_name' => $guard,
        ]);

        $valid = Permission::where('guard_name', $guard)
            ->whereIn('name', $data['permissions'] ?? [])
            ->pluck('name')->all();

        $role->syncPermissions($valid);

        return response()->json($role->load('permissions:id,name'), 201);
    }

    public function update(Request $request, Role $role)
    {
        abort_unless($role->guard_name === $this->guard(), 404);

        $data = $request->validate([
            'name'         => ['required','string','max:100', Rule::unique('roles','name')->ignore($role->id)],
            'permissions'  => ['array'],
            'permissions.*'=> ['string'],
        ]);

        $role->update(['name' => $data['name']]);

        $valid = Permission::where('guard_name', $this->guard())
            ->whereIn('name', $data['permissions'] ?? [])
            ->pluck('name')->all();

        $role->syncPermissions($valid);

        return $role->load('permissions:id,name');
    }

    public function destroy(Role $role)
    {
        abort_unless($role->guard_name === $this->guard(), 404);

        // Optional: protect core roles
        if (in_array($role->name, ['admin'])) {
            return response()->json(['message' => 'Cannot delete core role'], 422);
        }

        $role->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
