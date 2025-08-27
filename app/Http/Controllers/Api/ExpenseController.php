<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use App\Http\Resources\ExpenseResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ExpenseController extends Controller
{
    /**
     * Apply authorization middleware to the controller methods.
     */
    public function __construct()
    {
        // Authorization middleware removed
    }

    /**
     * Display a paginated listing of the expenses.
     */
    public function index(Request $request)
    {
        $query = Expense::with('user:id,name')->orderBy('id', 'desc'); // Default sort by most recent

        // Filtering Logic
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm){
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'LIKE', "%{$searchTerm}%");
            });
        }
        if ($request->filled('expense_category_id')) {
            $query->where('expense_category_id', $request->expense_category_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }

        $expenses = $query->paginate($request->get('per_page', 15));
        return ExpenseResource::collection($expenses);
    }

    /**
     * Store a newly created expense in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'expense_category_id' => 'required|integer|exists:expense_categories,id',
            'description' => 'nullable|string|max:2000',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date_format:Y-m-d',
            'payment_method' => 'required|string|max:255',
        ]);

        try {
            $expense = Expense::create($validated + ['user_id' => Auth::id()]);
            return new ExpenseResource($expense);
        } catch (\Exception $e) {
            Log::error("Error creating expense: " . $e->getMessage());
            return response()->json(['message' => 'Failed to create expense.'], 500);
        }
    }

    /**
     * Display the specified expense.
     */
    public function show(Expense $expense)
    {
        // Authorization check removed
        $expense->load('user');
        return new ExpenseResource($expense);
    }

    /**
     * Update the specified expense in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'expense_category_id' => 'sometimes|required|integer|exists:expense_categories,id',
            'description' => 'sometimes|nullable|string|max:2000',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'expense_date' => 'sometimes|required|date_format:Y-m-d',
            'payment_method' => 'sometimes|required|string|max:255',
        ]);

        try {
            $expense->update($validated);
            return new ExpenseResource($expense);
        } catch (\Exception $e) {
            Log::error("Error updating expense {$expense->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update expense.'], 500);
        }
    }

    /**
     * Remove the specified expense from storage.
     */
    public function destroy(Expense $expense)
    {
        try {
            $expense->delete();
            return response()->json(['message' => 'Expense deleted successfully.']);
        } catch (\Exception $e) {
            Log::error("Error deleting expense {$expense->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete expense.'], 500);
        }
    }

    /**
     * Get a list of expense categories for filter dropdowns.
     */
    public function getCategories(Request $request)
    {
        if ($request->has('names_only')) {
            // Return just category names for backward compatibility
            $categories = \App\Models\ExpenseCategory::select('name')
                ->orderBy('name')
                ->pluck('name');
            return response()->json($categories);
        }
        
        // Return full category objects with IDs
        $categories = \App\Models\ExpenseCategory::select('id', 'name', 'description')
            ->orderBy('name')
            ->get();
            
        return response()->json($categories);
    }
}