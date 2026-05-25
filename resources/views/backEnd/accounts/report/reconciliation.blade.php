@extends('backEnd.layouts.master')
@section('title', 'Accounting Reconciliation')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Accounting Reconciliation</h4>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.accounts.reports.reconciliation') }}" class="row g-2 align-items-end mb-4">
                <div class="col-md-3">
                    <label class="form-label" for="AsOfDate">As of Date</label>
                    <input type="date" name="AsOfDate" id="AsOfDate" value="{{ $asOfDate }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Run</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Reconciliation</th>
                            <th class="text-end">Source Balance</th>
                            <th class="text-end">GL Balance</th>
                            <th class="text-end">Difference</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td class="text-end">{{ number_format((float) $row['source'], 2) }}</td>
                                <td class="text-end">{{ number_format((float) $row['gl'], 2) }}</td>
                                <td class="text-end">{{ number_format((float) $row['difference'], 2) }}</td>
                                <td>
                                    @if($row['status'] === 'ok')
                                        <span class="badge bg-success">Matched</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Mismatch</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No reconciliation data available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
