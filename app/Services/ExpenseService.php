<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ExpenseService
{
    /**
     * Get expense summary report
     */
    public function getExpenseSummaryReport(array $filters = []): array
    {
        $filters = $this->sanitizeFilters($filters);
        $baseQuery = $this->buildFilteredExpenseQuery($filters);

        $summaryRaw = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_expenses')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END), 0) as pending_amount")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'approved' THEN total_amount ELSE 0 END), 0) as approved_amount")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END), 0) as paid_amount")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'rejected' THEN total_amount ELSE 0 END), 0) as rejected_amount")
            ->first();

        $recentExpenses = (clone $baseQuery)
            ->with(['category', 'creator'])
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return [
            'total_expenses' => (int) ($summaryRaw->total_expenses ?? 0),
            'total_amount' => (float) ($summaryRaw->total_amount ?? 0),
            'pending_amount' => (float) ($summaryRaw->pending_amount ?? 0),
            'approved_amount' => (float) ($summaryRaw->approved_amount ?? 0),
            'paid_amount' => (float) ($summaryRaw->paid_amount ?? 0),
            'rejected_amount' => (float) ($summaryRaw->rejected_amount ?? 0),
            'expenses_by_category' => $this->getExpensesByCategory($baseQuery),
            'expenses_by_month' => $this->getExpensesByMonth($baseQuery),
            'expenses_by_payment_method' => $this->getExpensesByPaymentMethod($baseQuery),
            'top_expense_categories' => $this->getTopExpenseCategories($baseQuery),
            'recent_expenses' => $recentExpenses,
        ];
    }

    /**
     * Get expenses grouped by category
     */
    private function getExpensesByCategory(Builder $query): array
    {
        return (clone $query)
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.category_id')
            ->selectRaw("COALESCE(expense_categories.name, 'Uncategorized') as category_name")
            ->selectRaw('COUNT(expenses.id) as total_count')
            ->selectRaw('COALESCE(SUM(expenses.total_amount), 0) as total_amount')
            ->selectRaw("COALESCE(SUM(CASE WHEN expenses.status = 'paid' THEN expenses.total_amount ELSE 0 END), 0) as paid_amount")
            ->groupBy('category_name')
            ->orderBy('category_name')
            ->get()
            ->mapWithKeys(function ($row) {
                return [
                    (string) $row->category_name => [
                        'count' => (int) $row->total_count,
                        'total_amount' => (float) $row->total_amount,
                        'paid_amount' => (float) $row->paid_amount,
                    ],
                ];
            })
            ->toArray();
    }

    /**
     * Get expenses grouped by month
     */
    private function getExpensesByMonth(Builder $query): array
    {
        return (clone $query)
            ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') as month_key")
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END), 0) as paid_amount")
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get()
            ->mapWithKeys(function ($row) {
                return [
                    (string) $row->month_key => [
                        'count' => (int) $row->total_count,
                        'total_amount' => (float) $row->total_amount,
                        'paid_amount' => (float) $row->paid_amount,
                    ],
                ];
            })
            ->toArray();
    }

    /**
     * Get expenses grouped by payment method
     */
    private function getExpensesByPaymentMethod(Builder $query): array
    {
        return (clone $query)
            ->selectRaw('payment_method')
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
            ->groupBy('payment_method')
            ->orderBy('payment_method')
            ->get()
            ->mapWithKeys(function ($row) {
                return [
                    (string) $row->payment_method => [
                        'count' => (int) $row->total_count,
                        'total_amount' => (float) $row->total_amount,
                    ],
                ];
            })
            ->toArray();
    }

    /**
     * Get top expense categories
     */
    private function getTopExpenseCategories(Builder $query): array
    {
        $topCategories = (clone $query)
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.category_id')
            ->selectRaw('expenses.category_id')
            ->selectRaw('expense_categories.name as category_name')
            ->selectRaw('COUNT(expenses.id) as total_expenses')
            ->selectRaw('COALESCE(SUM(expenses.total_amount), 0) as total_amount')
            ->groupBy('expenses.category_id', 'expense_categories.name')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();

        $categoriesById = ExpenseCategory::query()
            ->whereIn('id', $topCategories->pluck('category_id')->all())
            ->get()
            ->keyBy('id');

        return $topCategories->map(function ($row) use ($categoriesById) {
            $categoryId = (int) $row->category_id;

            return [
                'category' => $categoriesById->get($categoryId),
                'total_expenses' => (int) $row->total_expenses,
                'total_amount' => (float) $row->total_amount,
            ];
        })->values()->toArray();
    }

    /**
     * Get daily expense summary
     */
    public function getDailyExpenseSummary(Carbon $date): array
    {
        $expenses = Expense::whereDate('expense_date', $date)->with(['category', 'creator'])->get();

        return [
            'date' => $date->format('Y-m-d'),
            'total_expenses' => $expenses->count(),
            'total_amount' => $expenses->sum('total_amount'),
            'pending_count' => $expenses->where('status', 'pending')->count(),
            'approved_count' => $expenses->where('status', 'approved')->count(),
            'paid_count' => $expenses->where('status', 'paid')->count(),
            'rejected_count' => $expenses->where('status', 'rejected')->count(),
            'expenses' => $expenses,
        ];
    }

    /**
     * Get expense trends over time
     */
    public function getExpenseTrends(int $months = 12): array
    {
        $months = max(1, $months);
        $trends = [];
        $currentDate = now();
        $startMonth = $currentDate->copy()->startOfMonth()->subMonths($months - 1);

        $trendRows = Expense::query()
            ->where('status', 'paid')
            ->whereDate('expense_date', '>=', $startMonth->toDateString())
            ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') as month_key")
            ->selectRaw('COUNT(*) as expense_count')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
            ->selectRaw('COALESCE(AVG(total_amount), 0) as average_amount')
            ->groupBy('month_key')
            ->get()
            ->keyBy('month_key');

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = $currentDate->copy()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $row = $trendRows->get($monthKey);

            $trends[] = [
                'month' => $date->format('M Y'),
                'month_key' => $monthKey,
                'expense_count' => (int) ($row->expense_count ?? 0),
                'total_amount' => (float) ($row->total_amount ?? 0),
                'average_amount' => (float) ($row->average_amount ?? 0),
            ];
        }

        return $trends;
    }

    /**
     * Get user activity log
     */
    public function getActivityLog(array $filters = []): array
    {
        $filters = $this->sanitizeFilters($filters);
        $query = ExpenseLog::with(['user', 'expense.category']);

        if (! empty($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (! empty($filters['action'])) {
            $query->byAction($filters['action']);
        }

        if (array_key_exists('days', $filters)) {
            if ($filters['days'] !== 'all') {
                $query->recent((int) $filters['days']);
            }
        } else {
            $query->recent(30); // Default to last 30 days
        }

        $logs = (clone $query)->orderBy('expense_logs.created_at', 'desc')->paginate(50);

        $activitiesByAction = (clone $query)
            ->selectRaw('action, COUNT(*) as total')
            ->groupBy('action')
            ->pluck('total', 'action')
            ->toArray();

        $activitiesByUser = (clone $query)
            ->join('users', 'users.id', '=', 'expense_logs.user_id')
            ->selectRaw('users.name as user_name, COUNT(*) as total')
            ->groupBy('users.name')
            ->pluck('total', 'user_name')
            ->toArray();

        return [
            'logs' => $logs,
            'total_activities' => $logs->total(),
            'activities_by_action' => $activitiesByAction,
            'activities_by_user' => $activitiesByUser,
        ];
    }

    /**
     * Validate expense allocation
     */
    public function validateExpenseAllocation(Expense $expense, array $allocations): array
    {
        $errors = [];
        $totalAllocated = 0.0;
        $warehouseIds = [];

        foreach ($allocations as $index => $allocation) {
            if (! is_array($allocation)) {
                $errors[] = 'Invalid allocation payload at row '.($index + 1).'.';

                continue;
            }

            $amount = $allocation['amount'] ?? null;
            if (! is_numeric($amount)) {
                $errors[] = 'Allocation amount is required for row '.($index + 1).'.';

                continue;
            }

            $amount = (float) $amount;
            $totalAllocated += $amount;

            if ($amount <= 0) {
                $errors[] = 'Allocation amount must be greater than 0 for row '.($index + 1).'.';
            }

            $warehouseId = isset($allocation['warehouse_id']) ? (int) $allocation['warehouse_id'] : 0;
            if ($warehouseId <= 0) {
                $errors[] = 'Warehouse must be selected for allocation row '.($index + 1).'.';

                continue;
            }

            if (in_array($warehouseId, $warehouseIds, true)) {
                $errors[] = 'Each warehouse can only appear once in allocations.';
            }

            $warehouseIds[] = $warehouseId;
        }

        $expenseAmount = (float) $expense->total_amount;
        $totalAllocated = round($totalAllocated, 2);
        $expenseAmount = round($expenseAmount, 2);

        if (abs($totalAllocated - $expenseAmount) > 0.01) {
            $errors[] = 'Total allocated amount (BDT '.number_format($totalAllocated, 2).') must equal expense amount (BDT '.number_format($expenseAmount, 2).').';
        }

        return $errors;
    }

    /**
     * Get expense approval workflow statistics
     */
    public function getApprovalWorkflowStats(): array
    {
        $totalExpenses = Expense::count();
        $pendingExpenses = Expense::pending()->count();
        $approvedExpenses = Expense::approved()->count();
        $paidExpenses = Expense::paid()->count();
        $rejectedExpenses = Expense::where('status', 'rejected')->count();

        $averageApprovalTime = Expense::whereNotNull('approved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, approved_at)) as avg_hours')
            ->first()
            ->avg_hours ?? 0;

        return [
            'total_expenses' => $totalExpenses,
            'pending_expenses' => $pendingExpenses,
            'approved_expenses' => $approvedExpenses,
            'paid_expenses' => $paidExpenses,
            'rejected_expenses' => $rejectedExpenses,
            'approval_rate' => $totalExpenses > 0 ? round(($approvedExpenses / $totalExpenses) * 100, 2) : 0,
            'payment_rate' => $approvedExpenses > 0 ? round(($paidExpenses / $approvedExpenses) * 100, 2) : 0,
            'average_approval_time_hours' => round($averageApprovalTime, 2),
        ];
    }

    private function sanitizeFilters(array $filters): array
    {
        return array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    private function buildFilteredExpenseQuery(array $filters): Builder
    {
        $query = Expense::query();

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->byDateRange($filters['start_date'], $filters['end_date']);
        } elseif (! empty($filters['start_date'])) {
            $query->whereDate('expense_date', '>=', $filters['start_date']);
        } elseif (! empty($filters['end_date'])) {
            $query->whereDate('expense_date', '<=', $filters['end_date']);
        }

        if (! empty($filters['category_id'])) {
            $query->byCategory($filters['category_id']);
        }

        if (! empty($filters['warehouse_id'])) {
            $query->byWarehouse($filters['warehouse_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', (int) $filters['supplier_id']);
        }

        if (! empty($filters['purchase_order_id'])) {
            $query->where('purchase_order_id', (int) $filters['purchase_order_id']);
        }

        if (! empty($filters['grn_id'])) {
            $query->where('grn_id', (int) $filters['grn_id']);
        }

        return $query;
    }
}
