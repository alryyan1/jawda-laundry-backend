<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use App\Http\Resources\RoleResource; // Create this
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function __construct()
    {
        // Protect all methods in this controller
        $this->middleware('can:role_view_any')->only('index');
        $this->middleware('can:role_view')->only('show');
        $this->middleware('can:role_create')->only('store');
        $this->middleware('can:role_update')->only('update');
        $this->middleware('can:role_delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = Role::with('permissions:id,name')->orderBy('name'); // Eager load permissions
         if ($request->filled('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }
        return RoleResource::collection($query->paginate($request->get('per_page', 10)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'integer|exists:permissions,id', // Ensure permission IDs are valid
        ]);

        DB::beginTransaction();
        try {
            $role = Role::create(['name' => $validated['name']]);
            if (!empty($validated['permissions'])) {
                $permissions = Permission::whereIn('id', $validated['permissions'])->get();
                $role->syncPermissions($permissions);
            }
            DB::commit();
            $role->load('permissions:id,name');
            return new RoleResource($role);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create role: ' . $e->getMessage()], 500);
        }
    }

    public function show(Role $role)
    {
        $role->load('permissions:id,name');
        return new RoleResource($role);
    }

    public function update(Request $request, Role $role)
    {
        // Prevent editing of core roles like 'admin' if desired
        // if (in_array($role->name, ['admin'])) {
        //     return response()->json(['message' => "Cannot edit the '{$role->name}' role."], 403);
        // }
        $validated = $request->validate([
            'name' => ['sometimes','required','string','max:255', Rule::unique('roles')->ignore($role->id)],
            'permissions' => 'sometimes|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['name'])) {
                $role->name = $validated['name'];
                $role->save();
            }
            if ($request->has('permissions')) { // Allows sending empty array to remove all permissions
                $permissions = Permission::whereIn('id', $validated['permissions'] ?? [])->get();
                $role->syncPermissions($permissions);
            }
            DB::commit();
            $role->load('permissions:id,name');
            return new RoleResource($role);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update role: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Role $role)
    {
        // Prevent deletion of core roles
        // if (in_array($role->name, ['admin'])) {
        //     return response()->json(['message' => "Cannot delete the '{$role->name}' role."], 403);
        // }
        // if ($role->users()->count() > 0) {
        //     return response()->json(['message' => 'Cannot delete role. It is assigned to users.'], 409);
        // }
        DB::beginTransaction();
        try {
            $role->syncPermissions([]); // Remove all permissions before deleting role
            $role->delete();
            DB::commit();
            return response()->json(['message' => 'Role deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete role: ' . $e->getMessage()], 500);
        }
    }
}