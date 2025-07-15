<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;


class UserController extends Controller // For admin management of users
{
    public function __construct()
    {
        $this->middleware('can:user_view_any')->only('index');
        $this->middleware('can:user_view')->only('show');
        $this->middleware('can:user_create')->only('store');
        $this->middleware('can:user_update')->only('update');
        $this->middleware('can:user_delete')->only('destroy');
        $this->middleware('can:user_assign_roles')->only(['assignRoles', 'update']); // Assuming update might also change roles
    }

    public function index(Request $request)
    {
        $query = User::with('roles:id,name')->orderBy('name');
         if ($request->filled('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%")
                  ->orWhere('username', 'LIKE', "%{$request->search}%")
                  ->orWhere('email', 'LIKE', "%{$request->search}%");
        }
        if($request->filled('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $request->role));
        }
        return UserResource::collection($query->paginate($request->get('per_page', 10)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username|alpha_dash',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role_ids' => 'sometimes|array',
            'role_ids.*' => 'integer|exists:roles,id', // Validate role IDs
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            if (!empty($validated['role_ids'])) {
                $rolesToAssign = Role::whereIn('id', $validated['role_ids'])->pluck('name');
                $user->syncRoles($rolesToAssign);
            }
            DB::commit();
            $user->load('roles:id,name');
            return new UserResource($user);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create user: ' . $e->getMessage()], 500);
        }
    }

    public function show(User $user)
    {
        $user->load('roles:id,name', 'permissions:id,name'); // Load direct permissions too if any
        return new UserResource($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'username' => ['sometimes','required','string','max:255','alpha_dash', Rule::unique('users')->ignore($user->id)],
            'email' => ['sometimes','required','string','email','max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()], // Password optional on update
            'role_ids' => 'sometimes|array',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        DB::beginTransaction();
        try {
            $updateData = [
                'name' => $validated['name'] ?? $user->name, 
                'username' => $validated['username'] ?? $user->username,
                'email' => $validated['email'] ?? $user->email
            ];
            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }
            $user->update($updateData);

            if ($request->has('role_ids')) { // Allows sending empty array to remove all roles
                $rolesToAssign = Role::whereIn('id', $validated['role_ids'] ?? [])->pluck('name');
                $user->syncRoles($rolesToAssign);
            }
            DB::commit();
            $user->load('roles:id,name');
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