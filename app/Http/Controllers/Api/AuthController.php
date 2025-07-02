<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role; // Import the Role model
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use App\Http\Resources\UserResource;

class AuthController extends Controller
{
    /**
     * Register a new user.
     * By default, new users are assigned the 'receptionist' role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()], // Use Laravel's default strong password rules
        ]);

        try {
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
            ]);

            // Assign a default role to the newly registered user.
            // Ensure this role exists from your PermissionSeeder.
            $defaultRole = 'receptionist';
            if (Role::where('name', $defaultRole)->exists()) {
                $user->assignRole($defaultRole);
            } else {
                // Log a warning if the default role doesn't exist, but don't fail the registration.
                Log::warning("Default role '{$defaultRole}' not found for new user registration: {$user->email}");
            }

            // Create a token for the new user
            $token = $user->createToken('api-token-for-' . $user->email)->plainTextToken;

            // Eager load roles and permissions to include them in the response
            $user->load(['roles', 'permissions']);

            return response()->json([
                'message' => 'User registered successfully.',
                'user' => new UserResource($user),
                'token' => $token,
            ], 201);

        } catch (\Exception $e) {
            Log::error("User registration failed: " . $e->getMessage());
            return response()->json(['message' => 'Registration failed. Please try again later.'], 500);
        }
    }

    /**
     * Authenticate an existing user and return a token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')], // Standard "these credentials do not match" message
            ]);
        }

        // Create a new token for the user
        $token = $user->createToken('api-token-for-' . $user->email)->plainTextToken;

        // Eager load roles and permissions to include them in the response
        $user->load(['roles', 'permissions']);

        return response()->json([
            'message' => 'Login successful.',
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * Log the user out by revoking the current token.
     * This method is protected by the 'auth:sanctum' middleware.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Get the currently authenticated user's details.
     * This method is protected by the 'auth:sanctum' middleware.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Resources\UserResource
     */
    public function user(Request $request)
    {
        // Eager load roles and permissions every time user data is fetched
        $user = $request->user();
        $user->load(['roles', 'permissions']);

        return new UserResource($user);
    }
}