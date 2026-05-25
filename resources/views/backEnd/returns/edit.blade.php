@extends('backEnd.layouts.master')
@section('title', 'Edit Return')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.returns.index') }}">Returns</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.returns.show', $return) }}">{{ $return->return_number }}</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
                <h4 class="page-title">Edit Return</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.returns.update', $return) }}">
                        @csrf
                        @method('PUT')

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Return Number</label>
                                <input type="text" class="form-control" value="{{ $return->return_number }}" disabled>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Order Invoice</label>
                                <input type="text" class="form-control" value="{{ optional($return->order)->invoice_id ?? 'N/A' }}" disabled>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Customer</label>
                                <input type="text" class="form-control" value="{{ optional($return->customer)->name ?? 'N/A' }}" disabled>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="return_reason_id" class="form-label">Return Reason <span class="text-danger">*</span></label>
                                <select name="return_reason_id" id="return_reason_id" class="form-select @error('return_reason_id') is-invalid @enderror" required>
                                    <option value="">Select reason</option>
                                    @foreach($returnReasons as $reason)
                                    <option value="{{ $reason->id }}" {{ (int) old('return_reason_id', $return->return_reason_id) === (int) $reason->id ? 'selected' : '' }}>
                                        {{ $reason->reason_name }} ({{ ucfirst($reason->reason_category) }})
                                    </option>
                                    @endforeach
                                </select>
                                @error('return_reason_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="restock_flag" class="form-label">Restock Flag</label>
                                <select name="restock_flag" id="restock_flag" class="form-select @error('restock_flag') is-invalid @enderror">
                                    <option value="1" {{ old('restock_flag', (int) $return->restock_flag) == 1 ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ old('restock_flag', (int) $return->restock_flag) == 0 ? 'selected' : '' }}>No</option>
                                </select>
                                @error('restock_flag')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="damage_flag" class="form-label">Damage Flag</label>
                                <select name="damage_flag" id="damage_flag" class="form-select @error('damage_flag') is-invalid @enderror">
                                    <option value="1" {{ old('damage_flag', (int) $return->damage_flag) == 1 ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ old('damage_flag', (int) $return->damage_flag) == 0 ? 'selected' : '' }}>No</option>
                                </select>
                                @error('damage_flag')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea name="notes" id="notes" rows="4" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $return->notes) }}</textarea>
                            @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.returns.show', $return) }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Return</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
