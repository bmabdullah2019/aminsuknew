<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class ExpenseCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:expense-category-list', ['only' => ['index']]);
        $this->middleware('permission:expense-category-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:expense-category-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:expense-category-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of expense categories
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:1,0,active,inactive,true,false',
        ]);

        $query = ExpenseCategory::query();
        $this->applyCategoryFilters($query, $filters);

        $categories = $query
            ->withCount('expenses')
            ->withSum(['expenses as total_paid_expenses' => function ($expenseQuery) {
                $expenseQuery->where('status', 'paid');
            }], 'total_amount')
            ->ordered()
            ->paginate(25)
            ->appends($request->query());

        $statsBaseQuery = ExpenseCategory::query();
        $this->applyCategoryFilters($statsBaseQuery, $filters);

        $categoryStats = [
            'total_categories' => (clone $statsBaseQuery)->count(),
            'active_categories' => (clone $statsBaseQuery)->where('is_active', true)->count(),
            'inactive_categories' => (clone $statsBaseQuery)->where('is_active', false)->count(),
            'total_expenses' => Expense::query()
                ->whereIn('category_id', (clone $statsBaseQuery)->select('id'))
                ->count(),
        ];

        return view('backEnd.expense.category.index', compact('categories', 'categoryStats'));
    }

    /**
     * Show the form for creating a new category
     */
    public function create()
    {
        $existingCategories = ExpenseCategory::query()
            ->active()
            ->ordered()
            ->take(5)
            ->get(['id', 'code', 'name']);

        return view('backEnd.expense.category.create', compact('existingCategories'));
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('expense_categories', 'code'),
            ],
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        ExpenseCategory::create($validated);

        Toastr::success('Expense category created successfully.');

        return redirect()->route('admin.expense-category.index');
    }

    /**
     * Show the form for editing the category
     */
    public function edit(ExpenseCategory $category)
    {
        $category->loadCount('expenses')
            ->loadSum(['expenses as total_paid_expenses' => function ($expenseQuery) {
                $expenseQuery->where('status', 'paid');
            }], 'total_amount');

        $recentExpenses = $category->expenses()->latest('expense_date')->take(3)->get();

        return view('backEnd.expense.category.edit', compact('category', 'recentExpenses'));
    }

    /**
     * Update the category
     */
    public function update(Request $request, ExpenseCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('expense_categories', 'code')->ignore($category->id),
            ],
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $category->update($validated);

        Toastr::success('Expense category updated successfully.');

        return redirect()->route('admin.expense-category.index');
    }

    /**
     * Remove the category
     */
    public function destroy(ExpenseCategory $category)
    {
        try {
            DB::transaction(function () use ($category) {
                $lockedCategory = ExpenseCategory::query()->whereKey($category->id)->lockForUpdate()->firstOrFail();

                if ($lockedCategory->expenses()->exists()) {
                    throw new \RuntimeException('Cannot delete category with existing expenses.');
                }

                $lockedCategory->delete();
            });
        } catch (\RuntimeException $e) {
            Toastr::error($e->getMessage());

            return redirect()->back();
        } catch (QueryException $e) {
            Toastr::error('Cannot delete category because it is in use.');

            return redirect()->back();
        } catch (Throwable $e) {
            report($e);
            Toastr::error('Failed to delete category. Please try again.');

            return redirect()->back();
        }

        Toastr::success('Expense category deleted successfully.');

        return redirect()->route('admin.expense-category.index');
    }

    protected function applyCategoryFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($innerQuery) use ($search) {
                $innerQuery->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if (array_key_exists('status', $filters) && trim((string) $filters['status']) !== '') {
            $status = strtolower((string) $filters['status']);
            if (in_array($status, ['active', '1', 'true'], true)) {
                $query->where('is_active', true);
            } elseif (in_array($status, ['inactive', '0', 'false'], true)) {
                $query->where('is_active', false);
            }
        }
    }
}
