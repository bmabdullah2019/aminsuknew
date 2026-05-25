@extends('backEnd.layouts.master')
@section('title', $year ? 'Edit Fiscal Year' : 'New Fiscal Year')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right"><a href="{{ route('admin.accounts.fiscal-year.index') }}" class="btn btn-sm btn-secondary"><i class="mdi mdi-arrow-left"></i> Back</a></div>
                <h4 class="page-title">{{ $year ? 'Edit Fiscal Year' : 'New Fiscal Year' }}</h4>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.accounts.fiscal-year.store') }}">
                @csrf
                @if($year)<input type="hidden" name="FiscalYearId" value="{{ $year->FiscalYearId }}">@endif
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Opening Date <span class="text-danger">*</span></label>
                        <input type="date" name="OpeningDate" class="form-control @error('OpeningDate') is-invalid @enderror" value="{{ old('OpeningDate', $year ? $year->OpeningDate->format('Y-m-d') : '') }}" required>
                        @error('OpeningDate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Closing Date <span class="text-danger">*</span></label>
                        <input type="date" name="ClosingDate" class="form-control @error('ClosingDate') is-invalid @enderror" value="{{ old('ClosingDate', $year ? $year->ClosingDate->format('Y-m-d') : '') }}" required>
                        @error('ClosingDate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Remarks</label>
                        <input type="text" name="Remarks" class="form-control" value="{{ old('Remarks', $year->Remarks ?? '') }}">
                    </div>
                </div>
                <div class="mt-4 text-end">
                    <a href="{{ route('admin.accounts.fiscal-year.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">{{ $year ? 'Update' : 'Create' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
