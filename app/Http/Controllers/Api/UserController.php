<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;


class UserController extends Controller // For admin management of users
{
    public function __construct()
    {
        // Authorization middleware removed
    }

    public function index(Request $request)
    {
        $query = User::orderBy('name'); // Removed roles eager load
        if ($request->filled('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%")
                ->orWhere('username', 'LIKE', "%{$request->search}%")
                ->orWhere('email', 'LIKE', "%{$request->search}%");
        }
        if ($request->filled('user_type')) {
            $query->where('user_type', $request->user_type);
        }
        return UserResource::collection($query->paginate($request->get('per_page', 10)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username|alpha_dash',
            'password' => ['required', 'confirmed', Password::defaults()],
            'user_type' => 'required|in:admin,staff',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'user_type' => $validated['user_type'],
            ]);

            // Role assignment removed
            DB::commit();
            // $user->load('roles:id,name'); // Removed
            return new UserResource($user);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create user: ' . $e->getMessage()], 500);
        }
    }

    public function show(User $user)
    {
        // $user->load('roles:id,name', 'permissions:id,name'); // Removed
        return new UserResource($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'username' => ['sometimes', 'required', 'string', 'max:255', 'alpha_dash', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()], // Password optional on update
            'user_type' => 'sometimes|required|in:admin,staff',
        ]);

        DB::beginTransaction();
        try {
            $updateData = [
                'name' => $validated['name'] ?? $user->name,
                'username' => $validated['username'] ?? $user->username,
                'user_type' => $validated['user_type'] ?? $user->user_type,
            ];
            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }
            $user->update($updateData);

            // Role update logic removed

            DB::commit();
            // $user->load('roles:id,name'); // Removed
            return new UserResource($user);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update user: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return response()->json(['message' => 'You cannot delete your own account.'], 403);
        }
        // Add more checks, e.g., cannot delete the last admin user

        try {
            // $user->tokens()->delete(); // If using Sanctum tokens and want to log them out
            // $user->syncRoles([]); // Remove all roles
            $user->delete(); // This should also detach roles/permissions if configured or handled by events
            return response()->json(['message' => 'User deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete user: ' . $e->getMessage()], 500);
        }
    }
}
