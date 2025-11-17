<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','unique:users,email'],
            'password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']), // bcrypt by default
        ]);

        // optional: revoke others for single session policy
        $user->tokens()->delete();

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,     // or 'access_token' if your FE expects that
            'user'  => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        // Optional single-session policy
        $user->tokens()->delete();

        // If you want abilities, pass ['*'] for super token or a specific array
        $token = $user->createToken('api', ['*'])->plainTextToken;

        // Eager-load roles to avoid N+1 in accessors
        $user->load('roles:id,name');

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'               => $user->id,
                'name'             => $user->name,
                'email'            => $user->email,
                'primary_role'     => $user->primary_role,
                'roles_list'       => $user->roles_list,        // ["admin", ...]
                'permissions_list' => $user->permissions_list,  // ["leads.view", ...]
                'created_at'       => $user->created_at,
            ],
        ]);
    }

    public function me(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user()->load('roles:id,name');

        return response()->json([
            'id'               => $user->id,
            'name'             => $user->name,
            'email'            => $user->email,
            'primary_role'     => $user->primary_role,
            'roles_list'       => $user->roles_list,
            'permissions_list' => $user->permissions_list,
            'created_at'       => $user->created_at,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
