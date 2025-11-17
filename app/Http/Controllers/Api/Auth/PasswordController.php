<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as RulesPassword;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use App\Models\User;

class PasswordController extends Controller
{
    /**
     * Step 1: Request reset link (Forgot Password)
     */
    public function forgot(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required','email:rfc,dns'],
        ]);

        // Always return a generic response (don’t leak if email exists)
        $status = Password::sendResetLink([
            'email' => Str::lower($validated['email']),
        ]);

        return response()->json([
            'message' => __($status === Password::RESET_LINK_SENT
                ? 'If the email exists, a reset link has been sent.'
                : 'If the email exists, a reset link has been sent.')
        ]);
    }

    /**
     * Step 2: Reset password with token (from email)
     */
    public function reset(Request $request)
    {
        $validated = $request->validate([
            'token'    => ['required','string'],
            'email'    => ['required','email:rfc,dns'],
            'password' => [
                'required','confirmed',
                RulesPassword::min(8)->mixedCase()->numbers()->uncompromised()
            ],
        ]);

        $status = Password::reset(
            [
                'email'                 => Str::lower($validated['email']),
                'password'              => $validated['password'],
                'password_confirmation' => $request->input('password_confirmation'),
                'token'                 => $validated['token'],
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Invalidate all existing API tokens (good security hygiene)
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => __('Password has been reset.')]);
        }

        return response()->json([
            'message' => __('Invalid or expired token.')
        ], 422);
    }

    /**
     * Change password (while logged in)
     */
    public function change(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required','string'],
            'password'         => [
                'required','confirmed',
                RulesPassword::min(8)->mixedCase()->numbers()->uncompromised()
            ],
        ]);

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => __('Current password is incorrect.')], 422);
        }

        // Prevent reusing same password
        if (Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => __('New password must be different.')], 422);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        // Optionally invalidate all tokens except the current one.
        // If you want to invalidate all, uncomment the next block.
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        return response()->json(['message' => __('Password changed successfully.')]);
    }
}
