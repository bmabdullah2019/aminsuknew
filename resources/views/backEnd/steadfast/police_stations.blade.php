@extends('backEnd.layouts.master')
@section('title', 'Steadfast Police Stations')
@section('css')
<style>
.sf-stations .sf-search { border-radius: 8px; border: 1px solid #dee2e6; padding: 10px 14px; max-width: 400px; }
</style>
@endsection
@section('content')
<div class="container-fluid sf-stations">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.steadfast.dashboard') }}" class="btn btn-outline-secondary btn-sm"><i class="fe-arrow-left"></i> Back</a>
                </div>
                <h4 class="page-title"><i class="fe-map-pin me-2"></i>Police Stations</h4>
            </div>
        </div>
    </div>
    @if(!$configured)
    <div class="alert alert-danger"><i class="fe-alert-triangle me-2"></i>API not configured. <a href="{{ route('admin.courierapi.manage') }}">Configure now</a></div>
    @else
    <div class="card" style="border-radius:12px;">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">All Police Stations</h5>
            <input type="text" id="stationSearch" class="form-control sf-search" placeholder="Search stations...">
        </div>
        <div class="card-body p-0">
            @if(empty($stations))
            <div class="text-center py-5 text-muted"><i class="fe-inbox" style="font-size:2rem;"></i><p class="mt-2">No data.</p></div>
            @else
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="stationsTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>District</th>
                            <th>Upazila</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stations as $i => $s)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><strong>{{ $s['name'] ?? $s['police_station_name'] ?? '—' }}</strong></td>
                            <td>{{ $s['district'] ?? $s['district_name'] ?? '—' }}</td>
                            <td>{{ $s['upazila'] ?? $s['upazila_name'] ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
@section('script')
<script>
$(document).ready(function(){
    $('#stationSearch').on('keyup', function(){
        var val = $(this).val().toLowerCase();
        $('#stationsTable tbody tr').each(function(){
            $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
        });
    });
});
</script>
@endsection
