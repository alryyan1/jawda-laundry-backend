<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use App\Http\Resources\ExpenseCategoryResource;
use Illuminate\Validation\Rule;

class ExpenseCategoryController extends Controller
{
    public function __construct() {
        // Define permissions in seeder and assign to admin role
        // $this->middleware('can:expense-category:manage'); 
    }

    public function index(Request $request) {
        // If names_only parameter is passed, return just the names
        if ($request->has('names_only')) {
            $categories = ExpenseCategory::orderBy('name')->pluck('name');
            return response()->json($categories);
        }
        
        $query = ExpenseCategory::withCount('expenses')->orderBy('name');
        return ExpenseCategoryResource::collection($query->get());
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name',
            'description' => 'nullable|string|max:1000',
        ]);
        $category = ExpenseCategory::create($validated);
        return new ExpenseCategoryResource($category);
    }

    public function show(ExpenseCategory $expenseCategory) {
        $expenseCategory->loadCount('expenses');
        return new ExpenseCategoryResource($expenseCategory);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory) {
        $validated = $request->validate([
            'name' => ['sometimes','required','string','max:255', Rule::unique('expense_categories')->ignore($expenseCategory->id)],
            'description' => 'sometimes|nullable|string|max:1000',
        ]);
        $expenseCategory->update($validated);
        return new ExpenseCategoryResource($expenseCategory);
    }

    public function destroy(ExpenseCategory $expenseCategory) {
        if ($expenseCategory->expenses()->exists()) {
            return response()->json(['message' => 'Cannot delete category as it is currently in use by expenses.'], 409);
        }
        $expenseCategory->delete();
        return response()->json(['message' => 'Expense category deleted successfully.']);
    }
}