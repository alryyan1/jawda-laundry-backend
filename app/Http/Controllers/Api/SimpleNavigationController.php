<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NavigationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SimpleNavigationController extends Controller
{
    protected NavigationService $navigationService;

    public function __construct(NavigationService $navigationService)
    {
        $this->navigationService = $navigationService;
    }

    /**
     * Get navigation items for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get user's primary role
        $role = $user->roles->first()?->name ?? 'staff';
        
        // Get navigation items based on role
        $navigationItems = $this->navigationService->getSortedNavigationItems($role);

        return response()->json([
            'data' => $navigationItems,
            'user_role' => $role
        ]);
    }

    /**
     * Get navigation items for a specific role (admin only)
     */
    public function getByRole(Request $request, string $role): JsonResponse
    {
        $user = $request->user();
        
        if (!$user || !$user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $navigationItems = $this->navigationService->getSortedNavigationItems($role);

        return response()->json([
            'data' => $navigationItems,
            'role' => $role
        ]);
    }
}
