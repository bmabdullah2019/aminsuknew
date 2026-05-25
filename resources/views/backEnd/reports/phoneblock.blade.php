@extends('backEnd.layouts.master')
@section('title','Phone Block Manage')
@section('css')
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-select-bs5/css/select.bootstrap5.min.css" rel="stylesheet" type="text/css" />
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Phone Block Manage</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <div class="border rounded p-2">
                                <small class="text-muted d-block">Total Records</small>
                                <strong>{{ number_format((int) ($totalBlocked ?? 0)) }}</strong>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="border rounded p-2">
                                <small class="text-muted d-block">Active Blocks</small>
                                <strong>{{ number_format((int) ($activeBlocked ?? 0)) }}</strong>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="border rounded p-2">
                                <small class="text-muted d-block">Blocked Today</small>
                                <strong>{{ number_format((int) ($todayBlocked ?? 0)) }}</strong>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="border rounded p-2">
                                <small class="text-muted d-block">Last Blocked At</small>
                                <strong>{{ $latestBlockedAt ? \Carbon\Carbon::parse($latestBlockedAt)->format('d M Y h:i A') : 'N/A' }}</strong>
                            </div>
                        </div>
                    </div>

                    <form action="{{route('admin.customers.phoneblock.store')}}" method="POST" class="row mb-3">
                        @csrf
                        <div class="col-sm-4">
                            <div class="form-group mb-3">
                                <label for="phone" class="form-label">Phone *</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" id="phone" required>
                                @error('phone')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label for="reason" class="form-label">Reason *</label>
                                <input type="text" class="form-control @error('reason') is-invalid @enderror" name="reason" value="{{ old('reason') }}" id="reason" required>
                                @error('reason')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-sm-2 d-flex align-items-end">
                            <div class="form-group mb-3 w-100">
                                <button type="submit" class="btn btn-success w-100">Block Phone</button>
                            </div>
                        </div>
                    </form>

                    <table id="datatable-buttons" class="table table-striped dt-responsive nowrap w-100">
                        <thead>
                            <tr>
                                <th>SL</th>
                                <th>Phone</th>
                                <th>Normalized</th>
                                <th>Cancel Count</th>
                                <th>Status</th>
                                <th>Source</th>
                                <th>Blocked At</th>
                                <th>Reason</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $value)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$value->phone}}</td>
                                <td>{{$value->normalized_phone}}</td>
                                <td>{{(int) $value->cancel_count}}</td>
                                <td>
                                    @if($value->is_active)
                                        <span class="badge bg-danger">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{$value->blocked_source ?: 'N/A'}}</td>
                                <td>{{$value->blocked_at ? \Carbon\Carbon::parse($value->blocked_at)->format('d M Y h:i A') : 'N/A'}}</td>
                                <td>{{\Illuminate\Support\Str::limit((string) $value->reason, 60)}}</td>
                                <td>
                                    <div class="button-list">
                                        <a class="btn btn-xs btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#phoneEdit{{$value->id}}">
                                            <i class="fe-edit-1"></i>
                                        </a>

                                        <form method="post" action="{{route('admin.customers.phoneblock.toggle')}}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="id" value="{{$value->id}}">
                                            <input type="hidden" name="is_active" value="{{$value->is_active ? 0 : 1}}">
                                            <button type="submit" class="btn btn-xs {{$value->is_active ? 'btn-warning' : 'btn-success'}} waves-effect waves-light">
                                                <i class="mdi {{$value->is_active ? 'mdi-lock-open-variant' : 'mdi-lock'}}"></i>
                                            </button>
                                        </form>

                                        <form method="post" action="{{route('admin.customers.phoneblock.destroy')}}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="id" value="{{$value->id}}">
                                            <button type="submit" class="btn btn-xs btn-danger waves-effect waves-light delete-confirm">
                                                <i class="mdi mdi-close"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons/js/buttons.html5.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons/js/buttons.flash.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons/js/buttons.print.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-keytable/js/dataTables.keyTable.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-select/js/dataTables.select.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/pdfmake/build/pdfmake.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/pdfmake/build/vfs_fonts.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/js/pages/datatables.init.js"></script>

@foreach($data as $value)
<div class="modal fade" id="phoneEdit{{$value->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Blocked Phone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{route('admin.customers.phoneblock.update')}}" method="POST" class="row mb-2">
                    @csrf
                    <input type="hidden" name="id" value="{{$value->id}}">
                    <div class="col-sm-12">
                        <div class="form-group mb-3">
                            <label class="form-label">Phone *</label>
                            <input type="text" class="form-control" name="phone" value="{{$value->phone}}" required>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="form-group mb-3">
                            <label class="form-label">Reason *</label>
                            <input type="text" class="form-control" name="reason" value="{{$value->reason}}" required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Cancel Count</label>
                            <input type="number" min="0" class="form-control" name="cancel_count" value="{{(int) $value->cancel_count}}">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-control">
                                <option value="1" {{$value->is_active ? 'selected' : ''}}>Active</option>
                                <option value="0" {{!$value->is_active ? 'selected' : ''}}>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <button type="submit" class="btn btn-success">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection
