<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceAction;
use Illuminate\Http\Request;
use App\Http\Resources\ServiceActionResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ServiceActionController extends Controller
{
    /**
     * Display a listing of the resource.
     * Typically not paginated for dropdowns.
     */
    public function index(Request $request)
    {
        $query = ServiceAction::withCount('serviceOfferings')->orderBy('name');

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where('name', 'LIKE', "%{$searchTerm}%");
        }
         // Example: Paginate if requested, otherwise get all
        if ($request->has('paginate') && filter_var($request->paginate, FILTER_VALIDATE_BOOLEAN)) {
             $actions = $query->paginate($request->get('per_page', 15));
        } else {
             $actions = $query->get();
        }

        return ServiceActionResource::collection($actions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:service_actions,name',
            'description' => 'nullable|string|max:1000',
            'base_duration_minutes' => 'nullable|integer|min:0',
        ]);

        try {
            $action = ServiceAction::create($validatedData);
            return new ServiceActionResource($action);
        } catch (\Exception $e) {
            Log::error("Error creating service action: " . $e->getMessage());
            return response()->json(['message' => 'Failed to create service action.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceAction $serviceAction) // Route model binding
    {
        $serviceAction->load('serviceOfferings');
        return new ServiceActionResource($serviceAction);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceAction $serviceAction)
    {
        $validatedData = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('service_actions')->ignore($serviceAction->id),
            ],
            'description' => 'sometimes|nullable|string|max:1000',
            'base_duration_minutes' => 'sometimes|nullable|integer|min:0',
        ]);

        try {
            $serviceAction->update($validatedData);
            return new ServiceActionResource($serviceAction);
        } catch (\Exception $e) {
            Log::error("Error updating service action {$serviceAction->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update service action.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceAction $serviceAction)
    {
        // Check if action is used in any service offerings
        if ($serviceAction->serviceOfferings()->exists()) {
            return response()->json(['message' => 'Cannot delete service action. It is used in existing service offerings. Please remove or reassign those offerings first.'], 409); // Conflict
        }

        try {
            $serviceAction->delete();
            return response()->json(['message' => 'Service action deleted successfully.'], 200);
        } catch (\Exception $e) {
            Log::error("Error deleting service action {$serviceAction->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete service action.'], 500);
        }
    }
}