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
            'username' => 'required|string|max:255|unique:users,username|alpha_dash', // Ensure unique and only alpha-numeric, dashes, underscores
            'email' => 'nullable|string|email|max:255|unique:users,email', // Email is now optional
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        try {
            $user = User::create([
                'name' => $validatedData['name'],
                'username' => $validatedData['username'],
                'email' => $validatedData['email'] ?? null, // Save email if provided
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
            $token = $user->createToken('api-token-for-' . $user->username)->plainTextToken;

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
     * Authenticate an existing user with username and password.
     */
    public function login(Request $request)
    {
        $validatedData = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Find the user by their username
        $user = User::where('username', $validatedData['username'])->first();

        // Check if user exists and password is correct
        if (! $user || ! Hash::check($validatedData['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => [trans('auth.failed')], // The error is now associated with the 'username' field
            ]);
        }

        // Revoke old tokens and create a new one
        // $user->tokens()->delete(); // Optional: log out from other devices
        $token = $user->createToken('api-token-for-' . $user->username)->plainTextToken;

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