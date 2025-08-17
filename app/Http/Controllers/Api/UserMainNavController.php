<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserMainNav;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserMainNavController extends Controller
{
    /**
     * Display a listing of the user's main navigation items.
     */
    public function index()
    {
        $user = Auth::user();
        $navItems = $user->userMainNavs()->get();
        
        return response()->json([
            'data' => $navItems,
            'message' => 'User main navigation items retrieved successfully'
        ]);
    }

    /**
     * Store a newly created navigation item.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('user_main_navs')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                })
            ],
            'title' => 'required|array',
            'title.en' => 'required|string|max:255',
            'title.ar' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'route' => 'nullable|string|max:255',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|max:255',
        ]);

        $validated['user_id'] = $user->id;
        
        $navItem = UserMainNav::create($validated);
        
        return response()->json([
            'data' => $navItem,
            'message' => 'Navigation item created successfully'
        ], 201);
    }

    /**
     * Display the specified navigation item.
     */
    public function show(UserMainNav $userMainNav)
    {
        $user = Auth::user();
        
        // Ensure user can only access their own navigation items
        if ($userMainNav->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json([
            'data' => $userMainNav,
            'message' => 'Navigation item retrieved successfully'
        ]);
    }

    /**
     * Update the specified navigation item.
     */
    public function update(Request $request, UserMainNav $userMainNav)
    {
        $user = Auth::user();
        
        // Ensure user can only update their own navigation items
        if ($userMainNav->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'key' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('user_main_navs')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                })->ignore($userMainNav->id)
            ],
            'title' => 'sometimes|required|array',
            'title.en' => 'required_with:title|string|max:255',
            'title.ar' => 'required_with:title|string|max:255',
            'icon' => 'nullable|string|max:255',
            'route' => 'nullable|string|max:255',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|max:255',
        ]);
        
        $userMainNav->update($validated);
        
        return response()->json([
            'data' => $userMainNav,
            'message' => 'Navigation item updated successfully'
        ]);
    }

    /**
     * Remove the specified navigation item.
     */
    public function destroy(UserMainNav $userMainNav)
    {
        $user = Auth::user();
        
        // Ensure user can only delete their own navigation items
        if ($userMainNav->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $userMainNav->delete();
        
        return response()->json([
            'message' => 'Navigation item deleted successfully'
        ]);
    }

    /**
     * Reorder navigation items.
     */
    public function reorder(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:user_main_navs,id',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);
        
        foreach ($validated['items'] as $item) {
            UserMainNav::where('id', $item['id'])
                      ->where('user_id', $user->id)
                      ->update(['sort_order' => $item['sort_order']]);
        }
        
        return response()->json([
            'message' => 'Navigation items reordered successfully'
        ]);
    }
}
