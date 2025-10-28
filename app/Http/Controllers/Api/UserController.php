<?php

// app/Http/Controllers/Api/UserController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;


class UserController extends Controller
{

    public function __construct()
    {
        // Spatie permission gates per action
        $this->middleware('permission:products.view')->only(['index', 'show']);
        $this->middleware('permission:products.create')->only(['store']);
        $this->middleware('permission:products.update')->only(['update']);
        $this->middleware('permission:products.delete')->only(['destroy']);
        $this->middleware('permission:products.toggle-status')->only(['toggleStatus']);
    }

    public function index(Request $request)
    {
        $query = User::query()
            ->select(['id','name','email','created_at'])
            ->with('roles:id,name') // eager-load to avoid N+1
            ->when($request->q, function ($q) use ($request) {
                $q->where(function ($qq) use ($request) {
                    $qq->where('name','like',"%{$request->q}%")
                    ->orWhere('email','like',"%{$request->q}%");
                });
            })
            ->orderByDesc('id');

        $page = $query->paginate($request->integer('per_page', 10));

        // map roles to simple arrays for FE convenience
        $page->getCollection()->transform(function ($u) {
            return [
                'id'         => $u->id,
                'name'       => $u->name,
                'email'      => $u->email,
                'created_at' => $u->created_at,
                'roles'      => $u->roles->pluck('name')->values(), // ['admin', ...]
                'primary_role' => $u->roles->pluck('name')->first(), // optional
            ];
        });

        return $page;
    }

    public function userList()
    {
        $users = User::orderBy('name', 'asc')->get();

        return [
            'data' => $users
        ];
    }


    public function show(User $user)
    {
        $user->load('roles:id,name');

        return [
            'id'           => $user->id,
            'name'         => $user->name,
            'email'        => $user->email,
            'created_at'   => $user->created_at,
            'updated_at'   => $user->updated_at,
            'roles'        => $user->roles->pluck('name')->values(),
            'primary_role' => $user->roles->pluck('name')->first(),
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:6'],

            // NEW: accept either `role` (string) or `roles` (array)
            'role'     => ['sometimes','string', Rule::exists('roles','name')->where('guard_name','web')],
            'roles'    => ['sometimes','array'],
            'roles.*'  => ['string', Rule::exists('roles','name')->where('guard_name','web')],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Map `role` -> `roles[]`
        $roles = $data['roles'] ?? (isset($data['role']) ? [$data['role']] : []);
        if (!empty($roles)) {
            $user->syncRoles($roles);
        }

        return response()->json([
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'roles'       => $user->getRoleNames(),                         // array
            'permissions' => $user->getAllPermissions()->pluck('name'),     // optional
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'password' => ['nullable','string','min:6'],

            // NEW: accept either `role` (string) or `roles` (array)
            'role'     => ['sometimes','string', Rule::exists('roles','name')->where('guard_name','web')],
            'roles'    => ['sometimes','array'],
            'roles.*'  => ['string', Rule::exists('roles','name')->where('guard_name','web')],
        ]);

        $user->fill([
            'name'  => $data['name'],
            'email' => $data['email'],
        ]);
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        if (array_key_exists('roles', $data) || array_key_exists('role', $data)) {
            $roles = $data['roles'] ?? (isset($data['role']) ? [$data['role']] : []);
            $user->syncRoles($roles);
        }

        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
        ];
    }

    public function destroy(User $user)
    {
        try {
            // prevent deleting yourself (optional)
            if (auth()->id() === $user->id) {
                return response()->json(['message' => 'You cannot delete your own account.'], 403);
            }

            $user->delete();

            return response()->json(['message' => 'User deleted successfully.'], 200);
            // or return response()->noContent();  // HTTP 204, if you prefer empty body
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to delete user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
