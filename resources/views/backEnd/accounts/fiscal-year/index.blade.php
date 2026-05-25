@extends('backEnd.layouts.master')
@section('title','Fiscal Years')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.accounts.fiscal-year.create') }}" class="btn btn-sm btn-primary"><i class="mdi mdi-plus"></i> New Fiscal Year</a>
                </div>
                <h4 class="page-title">Fiscal Years</h4>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark"><tr><th>#</th><th>Opening Date</th><th>Closing Date</th><th>Remarks</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        @forelse($years as $y)
                        <tr>
                            <td>{{ $y->FiscalYearId }}</td>
                            <td>{{ $y->OpeningDate ? $y->OpeningDate->format('d/m/Y') : '' }}</td>
                            <td>{{ $y->ClosingDate ? $y->ClosingDate->format('d/m/Y') : '' }}</td>
                            <td>{{ $y->Remarks }}</td>
                            <td>
                                @if($y->IsClosed)
                                    <span class="badge bg-secondary">Closed</span>
                                @else
                                    <span class="badge bg-success">Open</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if(!$y->IsClosed)
                                    <form action="{{ route('admin.accounts.fiscal-year.close', $y->FiscalYearId) }}" method="POST" class="d-inline" onsubmit="return confirm('WARNING: Are you sure you want to CLOSE this Fiscal Year? This action will carry forward all asset/liability balances and transfer net income to retained earnings. It CANNOT be undone!');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Execute Closing Sequence"><i class="mdi mdi-lock"></i></button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.accounts.fiscal-year.edit', $y->FiscalYearId) }}" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-pencil"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted">No fiscal years found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($years->hasPages())<div class="d-flex justify-content-center">{{ $years->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
