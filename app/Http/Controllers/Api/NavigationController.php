<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NavigationItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class NavigationController extends Controller
{
    /**
     * Get all navigation items for admin management.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', NavigationItem::class);

        // Get all navigation items
        $allItems = NavigationItem::ordered()->get();
        
        // Build tree structure
        $itemsMap = $allItems->keyBy('id');
        $tree = collect();
        
        foreach ($allItems as $item) {
            if (!$item->parent_id) {
                // This is a top-level item
                $tree->push($this->buildTreeItem($item, $itemsMap));
            }
        }
        
        // Sort tree by sort_order
        $tree = $tree->sortBy('sort_order')->values();

        return response()->json([
            'data' => $tree,
            'message' => 'Navigation items retrieved successfully.'
        ]);
    }
    
    /**
     * Build a tree item with its children.
     */
    private function buildTreeItem($item, $itemsMap)
    {
        // Check if item is null
        if (!$item) {
            return null;
        }
        
        $treeItem = $item->toArray();
        $children = collect();
        
        // Find all children of this item
        foreach ($itemsMap as $childItem) {
            if ($childItem->parent_id === $item->id) {
                $children->push($this->buildTreeItem($childItem, $itemsMap));
            }
        }
        
        // Sort children by sort_order
        $treeItem['children'] = $children->sortBy('sort_order')->values()->toArray();
        
        return $treeItem;
    }

    /**
     * Get navigation items accessible to the authenticated user.
     */
    public function getUserNavigation(): JsonResponse
    {
        $user = Auth::user();
        $navigation = $user->getAccessibleNavigationItems();

        return response()->json([
            'data' => $navigation,
            'message' => 'User navigation retrieved successfully.'
        ]);
    }

    /**
     * Store a new navigation item.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', NavigationItem::class);

        $validated = $request->validate([
            'key' => 'required|string|unique:navigation_items|max:255',
            'title' => 'required|array',
            'title.en' => 'required|string|max:255',
            'title.ar' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'route' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:navigation_items,id',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string'
        ]);

        // Create the navigation item
        $navigationItem = NavigationItem::create($validated);

        // Return the created item in tree format
        $allItems = NavigationItem::ordered()->get();
        $itemsMap = $allItems->keyBy('id');
        $createdItem = $this->buildTreeItem($navigationItem, $itemsMap);

        if (!$createdItem) {
            return response()->json([
                'message' => 'Failed to build tree structure for created item.'
            ], 500);
        }

        return response()->json([
            'data' => $createdItem,
            'message' => 'Navigation item created successfully.'
        ], 201);
    }

    /**
     * Update an existing navigation item.
     */
    public function update(Request $request, NavigationItem $navigationItem): JsonResponse
    {
        $this->authorize('update', $navigationItem);

        $validated = $request->validate([
            'key' => ['sometimes', 'string', 'max:255', Rule::unique('navigation_items')->ignore($navigationItem->id)],
            'title' => 'sometimes|array',
            'title.en' => 'sometimes|string|max:255',
            'title.ar' => 'sometimes|string|max:255',
            'icon' => 'nullable|string|max:255',
            'route' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:navigation_items,id',
            'sort_order' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string'
        ]);

        // Update the model
        $navigationItem->update($validated);
        
        // Refresh the model to get the latest data
        $navigationItem->refresh();

        // Return the updated item in tree format
        $allItems = NavigationItem::ordered()->get();
        $itemsMap = $allItems->keyBy('id');
        $updatedItem = $this->buildTreeItem($navigationItem, $itemsMap);

        if (!$updatedItem) {
            return response()->json([
                'message' => 'Failed to build tree structure for updated item.'
            ], 500);
        }

        return response()->json([
            'data' => $updatedItem,
            'message' => 'Navigation item updated successfully.'
        ]);
    }

    /**
     * Delete a navigation item (only if not default).
     */
    public function destroy(NavigationItem $navigationItem): JsonResponse
    {
        $this->authorize('delete', $navigationItem);

        if ($navigationItem->is_default) {
            return response()->json([
                'message' => 'Cannot delete default navigation items.'
            ], 422);
        }

        $navigationItem->delete();

        return response()->json([
            'message' => 'Navigation item deleted successfully.'
        ]);
    }

    /**
     * Update user navigation permissions.
     */
    public function updateUserPermissions(Request $request, User $user): JsonResponse
    {
        $this->authorize('manageUserNavigation', User::class);

        $validated = $request->validate([
            'navigation_permissions' => 'required|array',
            'navigation_permissions.*.navigation_item_id' => 'required|exists:navigation_items,id',
            'navigation_permissions.*.is_granted' => 'required|boolean'
        ]);

        // Remove existing permissions
        $user->navigationItems()->detach();

        // Add new permissions
        foreach ($validated['navigation_permissions'] as $permission) {
            $user->navigationItems()->attach($permission['navigation_item_id'], [
                'is_granted' => $permission['is_granted'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return response()->json([
            'data' => $user->load('navigationItems'),
            'message' => 'User navigation permissions updated successfully.'
        ]);
    }

    /**
     * Get user navigation permissions.
     */
    public function getUserPermissions(User $user): JsonResponse
    {
        $this->authorize('viewUserNavigation', $user);

        $allNavigationItems = NavigationItem::active()->ordered()->get();
        $userPermissions = $user->navigationItems()->get()->keyBy('id');

        $permissions = $allNavigationItems->map(function ($item) use ($userPermissions) {
            $userPermission = $userPermissions->get($item->id);
            
            return [
                'navigation_item_id' => $item->id,
                'navigation_item' => $item,
                'is_granted' => $userPermission ? (bool) $userPermission->pivot->is_granted : null,
                'has_explicit_permission' => $userPermission !== null,
                'can_access_by_role' => $item->userCanAccess(auth()->user()) // This checks role-based access
            ];
        });

        return response()->json([
            'data' => $permissions,
            'message' => 'User navigation permissions retrieved successfully.'
        ]);
    }

    /**
     * Bulk update navigation items order.
     */
    public function updateOrder(Request $request): JsonResponse
    {
        $this->authorize('update', NavigationItem::class);

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:navigation_items,id',
            'items.*.sort_order' => 'required|integer|min:0'
        ]);

        foreach ($validated['items'] as $item) {
            NavigationItem::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json([
            'message' => 'Navigation items order updated successfully.'
        ]);
    }
} 