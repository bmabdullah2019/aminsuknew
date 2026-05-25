@extends('backEnd.layouts.master')
@section('title','Finance Dashboard')
@section('content')
<div class="row">
    <!-- Revenue -->
    <div class="col-sm-6 col-md-3 mt-3">
        <div class="card card-custom p-0" style="background-color: #e3f2fd; border-bottom: 4px solid #1976d2;">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase font-weight-bold mb-2">Revenue (30d)</h6>
                        <h4 class="mb-0 text-primary font-weight-bold">
                            ৳{{ number_format($revenue, 2) }}
                        </h4>
                    </div>
                    <div>
                        <i class="fas fa-coins fa-2x text-primary" style="opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Expenses -->
    <div class="col-sm-6 col-md-3 mt-3">
        <div class="card card-custom p-0" style="background-color: #ffebee; border-bottom: 4px solid #d32f2f;">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase font-weight-bold mb-2">Expenses (30d)</h6>
                        <h4 class="mb-0 text-danger font-weight-bold">
                            ৳{{ number_format($expenses, 2) }}
                        </h4>
                    </div>
                    <div>
                        <i class="fas fa-file-invoice-dollar fa-2x text-danger" style="opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Net Profit -->
    <div class="col-sm-6 col-md-3 mt-3">
        <div class="card card-custom p-0" style="background-color: #e8f5e9; border-bottom: 4px solid #388e3c;">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase font-weight-bold mb-2">Net Profit (30d)</h6>
                        <h4 class="mb-0 text-success font-weight-bold">
                            ৳{{ number_format($netProfit, 2) }}
                        </h4>
                    </div>
                    <div>
                        <i class="fas fa-chart-line fa-2x text-success" style="opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cash On Hand -->
    <div class="col-sm-6 col-md-3 mt-3">
        <div class="card card-custom p-0" style="background-color: #fff8e1; border-bottom: 4px solid #ffa000;">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase font-weight-bold mb-2">Total Bank & Cash</h6>
                        <h4 class="mb-0 text-warning font-weight-bold">
                            ৳{{ number_format($cashOnHand, 2) }}
                        </h4>
                    </div>
                    <div>
                        <i class="fas fa-vault fa-2x text-warning" style="opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Chart: Expenses by Category -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow rounded mb-4 h-100">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Expense Breakdown (30 Days)</h6>
            </div>
            <div class="card-body">
                <div class="chart-area" style="height: 300px;">
                    <canvas id="expenseChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions Table -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow rounded mb-4 h-100">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Recent Journal Entries</h6>
                 <a href="{{ route('admin.journal.index') }}" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->date->format('M d, Y') }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $transaction->description }}</div>
                                        <small class="text-muted">ID: {{ $transaction->id }} | {{ $transaction->reference_type }}</small>
                                    </td>
                                    <td>
                                        ৳{{ number_format($transaction->items->sum('debit'), 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">No recent transactions.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Expense Breakdown Chart
    var ctx = document.getElementById("expenseChart");
    if(ctx){
        var labels = [!! implode(',', $expenseBreakdown->map(function($e){ return '"'.($e->category->name ?? 'Unknown').'"'; })->toArray()) !!];
        var dataValues = [!! implode(',', $expenseBreakdown->pluck('total')->toArray()) !!];
        
        // Custom color palette for rich UI
        var bgColors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#6f42c1', '#fd7e14'];
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: dataValues,
                    backgroundColor: bgColors,
                    hoverBackgroundColor: bgColors,
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: {
                maintainAspectRatio: false,
                tooltips: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    caretPadding: 10,
                },
                legend: {
                    display: true,
                    position: 'right'
                },
                cutoutPercentage: 70,
            },
        });
    }
});
</script>
@endpush
