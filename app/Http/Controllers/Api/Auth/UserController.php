<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Helpers\UploadHelper;
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
            ->select(['id','name','email','image_url','created_at'])
            ->with('roles:id,name')
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
                'image_url'  => $u->image_url,
                'created_at' => $u->created_at,
                'roles'      => $u->roles->pluck('name')->values(),
                'primary_role' => $u->roles->pluck('name')->first(),
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
            'image_url'    => $user->image_url,
            'image_path'   => $user->image_path,
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
            'image'    => ['nullable','image','mimes:jpg,jpeg,png,gif,webp','max:5120'], // 5MB max
            'role'     => ['sometimes','string', Rule::exists('roles','name')->where('guard_name','web')],
            'roles'    => ['sometimes','array'],
            'roles.*'  => ['string', Rule::exists('roles','name')->where('guard_name','web')],
        ]);

        $userData = [
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ];

        // Handle image upload
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $uploadResult = UploadHelper::uploadImageToS3($request->file('image'), 'users');

            if (!$uploadResult['success']) {
                return response()->json([
                    'error' => 'Image upload failed: ' . $uploadResult['error']
                ], 422);
            }

            $userData['image_url'] = $uploadResult['url'];
            $userData['image_path'] = $uploadResult['file_path'];
        }

        $user = User::create($userData);

        // Map `role` -> `roles[]`
        $roles = $data['roles'] ?? (isset($data['role']) ? [$data['role']] : []);
        if (!empty($roles)) {
            $user->syncRoles($roles);
        }

        return response()->json([
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'image_url'   => $user->image_url,
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'password' => ['nullable','string','min:6'],
            'image'    => ['nullable','image','mimes:jpg,jpeg,png,gif,webp','max:5120'],
            'role'     => ['sometimes','string', Rule::exists('roles','name')->where('guard_name','web')],
            'roles'    => ['sometimes','array'],
            'roles.*'  => ['string', Rule::exists('roles','name')->where('guard_name','web')],
            'remove_image' => ['sometimes','boolean'],
        ]);

        $userData = [
            'name'  => $data['name'],
            'email' => $data['email'],
        ];

        // Handle password update
        if (!empty($data['password'])) {
            $userData['password'] = Hash::make($data['password']);
        }

        // Handle image removal
        if ($request->boolean('remove_image')) {
            if ($user->image_path) {
                UploadHelper::deleteImageFromS3($user->image_path);
            }
            $userData['image_url'] = null;
            $userData['image_path'] = null;
        }
        // Handle new image upload
        else if ($request->hasFile('image') && $request->file('image')->isValid()) {
            // Delete old image if exists
            if ($user->image_path) {
                UploadHelper::deleteImageFromS3($user->image_path);
            }

            $uploadResult = UploadHelper::uploadImageToS3($request->file('image'), 'users');

            if (!$uploadResult['success']) {
                return response()->json([
                    'error' => 'Image upload failed: ' . $uploadResult['error']
                ], 422);
            }

            $userData['image_url'] = $uploadResult['url'];
            $userData['image_path'] = $uploadResult['file_path'];
        }

        $user->update($userData);

        if (array_key_exists('roles', $data) || array_key_exists('role', $data)) {
            $roles = $data['roles'] ?? (isset($data['role']) ? [$data['role']] : []);
            $user->syncRoles($roles);
        }

        return [
            'id'        => $user->id,
            'name'      => $user->name,
            'email'     => $user->email,
            'image_url' => $user->image_url,
            'roles'     => $user->getRoleNames(),
        ];
    }

    public function destroy(User $user)
    {
        try {
            // prevent deleting yourself
            if (auth()->id() === $user->id) {
                return response()->json(['message' => 'You cannot delete your own account.'], 403);
            }

            // Delete user image from S3 if exists
            if ($user->image_path) {
                UploadHelper::deleteImageFromS3($user->image_path);
            }

            $user->delete();

            return response()->json(['message' => 'User deleted successfully.'], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to delete user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
